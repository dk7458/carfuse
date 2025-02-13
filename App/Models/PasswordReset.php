<?php

namespace App\Models;

use App\Models\BaseModel; // updated base model

class PasswordReset extends BaseModel
{
    protected $table = 'password_resets';
    
    protected $fillable = ['email', 'token', 'expires_at'];
    
    public $timestamps = true;
}
