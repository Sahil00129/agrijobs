<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recruiter extends Model
{
  use SoftDeletes;
    protected $fillable = [
        'user_id',
        'full_name',
    ];
}
