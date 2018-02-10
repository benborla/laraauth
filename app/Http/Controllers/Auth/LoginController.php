<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Validation\ValidationException;
use Keygen\Keygen;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;



    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }


    public function login(Request $request)
    {
        $loginVerification = new \App\LoginVerification();
        $agent             = new Agent();
        $device            = $agent->device();
        $knownDevice       = new \App\UserKnownDevice();

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }


        if ($this->attemptLogin($request)) {

            $user           = $this->guard()->user();
            $userId         = (int) $user->id;
            $validationCode = (int) $request->input('verificationCode');



            $checkLogin2fa = $loginVerification::where('user_id', '=', $userId)
                ->where('security_code', '=', $validationCode)->where('device', $device)->first();

            if($checkLogin2fa) {

                // save the new device
                $knownDevice->user_id = $userId;
                $knownDevice->device  = $device;
                $knownDevice->save();


                // delete record
                $loginVerification::find($checkLogin2fa['id'])->delete();


                // verified
                return $this->sendLoginResponse($request);
            }
            else {

                // get the info of the known device (if recorded)
                $data = $knownDevice::where('user_id', '=', $userId)->where('device', '=', $device)->get()->count();

                // if the device used for logging-in does not exists yet, then we ask for a verification
                if(!$data)  {

                    // destroy session
                    $this->guard()->logout();
                    $request->session()->invalidate();

                    $loginVerificationData = $loginVerification::where('user_id', '=', $userId)
                        ->where('device', '=', $device)->get()->count();

                    // if no verification code stored yet
                    $code = Keygen::numeric(6)->generate();

                    if(!$loginVerificationData) {

                        // save new entry for user 2FA
                        $loginVerification->user_id = $userId;
                        $loginVerification->device = $device;
                        $loginVerification->security_code = $code;

                        $loginVerification->save();

                        // notify the user that an email has been sent for the verification code

                        \Mail::send('auth.mail.verify', [
                            'title' => trans('new_device_login'),
                            'content' => trans('two_way_auth_message'),
                            'code'    => $code
                        ], function ($message)
                        {

                            $message->from('no-reply@laraauth.com', config('app.name'));

                            $message->to('benborla@icloud.com');

                            $message->subject(trans('auth.new_device_login'));

                        });

                    }


                    return $this->sendTwoFactoryAuthRequired($request);

                }
                else
                    return $this->sendLoginResponse($request);
            }
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);


    }



    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws ValidationException
     */
    protected function sendTwoFactoryAuthRequired(Request $request)
    {
        return redirect('login')
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->username() => [trans('auth.two_way_auth')],
                'validationRequired' => true
            ]);
    }


}
