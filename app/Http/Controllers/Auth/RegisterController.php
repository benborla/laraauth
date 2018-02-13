<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Auth\Events\Registered;
use Keygen\Keygen;
use App\Mail\RegistrationVerification;
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
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
        $this->middleware('guest');
    }


    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        // create also an entry for verifying the user
        if($user) {
            $userId       = $user['id'];
            $verifyTable  = new \App\VerifyUserRegistration();
            $token        = Keygen::alphanum(20)->generate();

            $verifyTable->user_id = (int) $userId;
            $verifyTable->token   = $token;

            $verifyTable->save();
        }


    }

    public function verification(Request $request, $id)
    {
        $userId    = (int) $id;
        $userTable = new \App\User();
        $user      = $userTable::find($userId);

        if(!$user->verified) {

            $assumedFirstName  = (int) strpos($user->name, ' ');
            $name              = ucwords(substr($user->name, 0, $assumedFirstName));
            $message           = sprintf(trans('register_verification'), $name);

            $verificationTable = new \App\VerifyUserRegistration();
            $verificationData  = $verificationTable::find($userId)->first();

            if($verificationData) {
                $token = $verificationData->token;
            }

            // notify the user that an email has been sent for the verification code
            set_time_limit(0);
            ini_set('memory_limit', '-1');
            \Mail::to($user->email)->send(new RegistrationVerification($message, $token));

            return view('auth.verification');
        }

        return view('auth.already_verified');;

    }

    public function verifyUser(Request $request, $token)
    {

        // http://www.lara-auth.local/auth/verify/5jPjg48ago18SodXHHfM

        // generate a link

        $verificationTable = new \App\VerifyUserRegistration();
        $verificationData  = $verificationTable::with('user')->where('token', '=', $token)->first();



        return $verificationData;
    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'active' => 0
        ]);
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $userId = (int) $user->id;

        if(!$userId) {
            return false;
        }

        $agent  = new Agent();
        $device = $agent->device();

        $knownDevice = new \App\UserKnownDevice();
        $data        = $knownDevice::where('user_id', '=', $userId)->where('device', '=', $device)->get()->count();

        // check if the device and the device stored in the table
        if(!$data) {
            $knownDevice->user_id = $userId;
            $knownDevice->device = $device;
            $knownDevice->save();
        }
    }

}
