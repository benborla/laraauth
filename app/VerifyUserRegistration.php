<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VerifyUserRegistration extends Model
{
    // table
    protected $table = 'verify_user_registration';

    public function user()
    {
        return $this->belongsTo('App\UserTable', 'user_id', 'id');
    }
}
