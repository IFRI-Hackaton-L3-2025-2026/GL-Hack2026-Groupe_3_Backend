<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,  // On le met en premier, AdminSeeder en dépend
            AdminSeeder::class,
        ]);
    }
}