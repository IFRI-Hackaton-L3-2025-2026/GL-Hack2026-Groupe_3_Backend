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
            StaffSeeder::class,  
            EquipmentCategorySeeder::class,
            EquipmentSeeder::class,
            MaintenanceSeeder::class,
            BreakdownSeeder::class,
        ]);
    }
}