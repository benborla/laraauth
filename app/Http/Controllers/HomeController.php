<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId      = (int) Auth::guard()->user()->id;
        $usersDevice = \App\UserKnownDevice::where('user_id', '=', $userId)->get();

        return view('home', ['devices' => $usersDevice]);


    }
}
