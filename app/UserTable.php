<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTable extends Model
{
    // table
    protected $table = 'users';

    public function verifyUser()
    {
        return $this->hasOne('App\VerifyUserRegistration', 'user_id', 'id');
    }

}
