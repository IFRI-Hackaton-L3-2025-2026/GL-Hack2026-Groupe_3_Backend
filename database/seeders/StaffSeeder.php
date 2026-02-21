<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $technicienRole   = Role::where('name', 'technicien')->first();
        $gestionnaireRole = Role::where('name', 'gestionnaire')->first();

        User::updateOrCreate([
            'fullname' => 'Jean Technicien',
            'email'    => 'jean@bmi.bj',
            'password' => Hash::make('password123'),
            'role_id'  => $technicienRole->id,
        ]);

        User::updateOrCreate([
            'fullname' => 'Marie Gestionnaire',
            'email'    => 'marie@bmi.bj',
            'password' => Hash::make('password123'),
            'role_id'  => $gestionnaireRole->id,
        ]);
    }
}