<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    protected $table = 'password_reset_tokens';
    protected $guarded = ['id'];
    public $timestamps = false;
    public $incrementing = false;
    protected $hidden = [];
    protected $primaryKey = 'email';
    protected $fillable = ['token', 'email', 'created_at'];
}
