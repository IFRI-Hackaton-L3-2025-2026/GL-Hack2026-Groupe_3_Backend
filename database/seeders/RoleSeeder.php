<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
 public function run(): void
{
    $roles = ['admin', 'technicien', 'gestionnaire', 'client'];

    foreach ($roles as $role) {
        // On vérifie si le nom existe déjà dans la table
        if (!DB::table('roles')->where('name', $role)->exists()) {
            DB::table('roles')->insert(['name' => $role]);
        }
    }
}
}