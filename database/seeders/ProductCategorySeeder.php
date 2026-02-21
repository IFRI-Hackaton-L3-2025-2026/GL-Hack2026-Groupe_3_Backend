<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Pieces moteur',
                'description' => 'Toutes les pieces liees au moteur',
            ],
            [
                'name'        => 'Carrosserie',
                'description' => 'Pieces de carrosserie et de structure',
            ],
            [
                'name'        => 'Accessoires moto',
                'description' => 'Accessoires et equipements pour motos',
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate($category);
        }
    }
}
