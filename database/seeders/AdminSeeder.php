<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        User::updateOrCreate([
            'fullname' => 'Super Admin BMI',
            'email'    => 'admin@bmi.bj',
            'password' => Hash::make('password_secret'),
            'phone'    => null,
            'address'  => null,
            'role_id'  => $adminRole->id,
        ]);
    }
}