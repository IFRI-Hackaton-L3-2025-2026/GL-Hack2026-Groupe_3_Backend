<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EquipmentCategory;

class EquipmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Robot', 'CNC', 'Presse', 'Convoyeur'];

        foreach ($categories as $category) {
            EquipmentCategory::create(['name' => $category]);
        }
    }
}