<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobSeeker extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'experience_years',
        'current_location',
        'about'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
