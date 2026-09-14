<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'email', 'password'];
    protected $hidden = ['password'];
    public $timestamps = true;

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
