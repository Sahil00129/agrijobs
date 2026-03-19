<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
     {
      DB::table('roles')->insertOrIgnore([
    ['name' => 'admin'],
    ['name' => 'recruiter'],
    ['name' => 'job_seeker'],
    ['name' => 'institute_admin'],
]);
    }
}
