<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Validation\ValidationException;
use Keygen\Keygen;
use App\Mail\LoginVerificationMail;
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


    /**
     * Overrides the logic of the Laravel's built-in login process
     * this process, matches the device that the user is using for logging-in
     * if the device is not yet recorded on user's known devices, then it will send
     * an email for verification
     * @param Request $request
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|void
     */
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

            $user             = $this->guard()->user();
            $userId           = (int) $user->id;
            $verificationCode = $request->input('verificationCode');


            // check if the device used for logging-in is currently stored in user's known device
            $isKnownDevice = $knownDevice::where('user_id', '=', $userId)->where('device', '=', $device)->get()->count();

            if(!$isKnownDevice) {

                // check if user provided verification code
                if($verificationCode) {
                    // check if the verification code matches to the stored user's login verification code
                    $isMatch = $loginVerification::where('user_id', '=', $userId)->where('device', '=', $device)->where('security_code', '=', $verificationCode)->first();

                    if($isMatch) {
                        // store the new device
                        $this->storeNewUserDevice($userId, $device);
                        // delete record
                        $loginVerification::find($isMatch['id'])->delete();
                        // login user

                        return $this->sendLoginResponse($request);
                    }
                    else {
                        $this->blockLogin($request);
                        return $this->sendInvalidCodeAuth($request);
                    }
                }
                else {
                    $this->blockLogin($request);

                    // send an email to the user
                    $this->sendEmailToUser($request, $userId, $device);

                    // then let user know that a verification code is need to complete the login
                    return $this->sendTwoFactoryAuthRequired($request);
                }

            }
            else {

                // if it is a known device
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
                $this->username()           => [trans('auth.two_way_auth')],
                $this->validationRequired() => true
            ]);
    }

    /**
     * Return if the provided code is invalid
     * @param Request $request
     * @return $this
     */
    protected function sendInvalidCodeAuth(Request $request)
    {
        return redirect('login')
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->verificationCode()   => [trans('auth.invalid_verification_code')],
                $this->validationRequired() => true
            ]);
    }

    /**
     * Sends an email the user then stores the validation code in the login
     * verification table
     * @param Request $request
     * @param $userId
     * @param $device
     */
    protected function sendEmailToUser(Request $request, $userId, $device)
    {
        $loginVerification = new \App\LoginVerification();

        $email = $request->input($this->username());
        $code  = strtoupper(Keygen::alphanum(6)->generate());

        // check first if code has been generated already
        $verificationData = $loginVerification::where('user_id', '=', $userId)
            ->where('device', '=', $device)->first();
        $isExists = false;

        if($verificationData) {
            $code = $verificationData['security_code'];

            $isExists = true;

        }

        if(false === $isExists) {

            // store data to login verification code
            $loginVerification->user_id       = $userId;
            $loginVerification->device        = $device;
            $loginVerification->security_code = $code;

            $loginVerification->save();
        }

        // notify the user that an email has been sent for the verification code
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        \Mail::to($email)->send(new LoginVerificationMail(trans('auth.two_way_auth_message'), $code));



    }

    /**
     * Stores the new device of the user
     * @param $userId
     * @param $device
     */
    protected function storeNewUserDevice($userId, $device)
    {
        $knownDevice          = new \App\UserKnownDevice();
        $knownDevice->user_id = $userId;
        $knownDevice->device  = $device;

        $knownDevice->save();
    }

    /**
     * Invalidates the login, reset the login session
     * @param Request $request
     */
    protected function blockLogin(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
    }

    /**
     * Get the login verificationCode to be used by the controller.
     * @return string
     */
    protected function verificationCode()
    {
        return 'verificationCode';
    }

    /**
     * Flag if new device is detected
     * @return string
     */
    protected function validationRequired()
    {
        return 'validationRequired';
    }



}
