<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
protected $fillable = [
    'user_id',
    'device_uuid',
    'app_id',
    'device_name',
    'platform',
    'last_login_at'
];

}

