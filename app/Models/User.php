<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'mobile',
        'username',
        'password',
        'role_id',
        'email_verified',
        'is_active',
        'basic_details',
        'role_verification',
        'profile_details',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Recruiter relation
    public function recruiter()
    {
        return $this->hasOne(Recruiter::class);
    }

    // Job Seeker relation
    public function jobSeeker()
    {
        return $this->hasOne(JobSeeker::class);
    }
}
