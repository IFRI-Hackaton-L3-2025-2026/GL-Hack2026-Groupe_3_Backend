<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCategory;
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moteur     = ProductCategory::where('name', 'Pieces moteur')->first();
        $carrosserie = ProductCategory::where('name', 'Carrosserie')->first();
        $accessoires = ProductCategory::where('name', 'Accessoires moto')->first();

        $products = [
            [
                'product_category_id' => $moteur->id,
                'name'                => 'Filtre a huile',
                'description'         => 'Filtre a huile haute performance',
                'price'               => 5000,
                'stock_quantity'      => 50,
                'image'               => 'https://images.unsplash.com/photo-1615906655593-ad0386982a0f?w=400',
                'is_active'           => true,
            ],
            [
                'product_category_id' => $carrosserie->id,
                'name'                => 'Pare-choc avant',
                'description'         => 'Pare-choc en plastique renforce',
                'price'               => 25000,
                'stock_quantity'      => 10,
                'image'               => 'https://tse1.mm.bing.net/th/id/OIP.PE51fFZrNZ5tvHd8LDN-4gHaE8?rs=1&pid=ImgDetMain&o=7&rm=3',
                'is_active'           => true,
            ],
            [
                'product_category_id' => $accessoires->id,
                'name'                => 'Casque integral',
                'description'         => 'Casque integral homologue',
                'price'               => 35000,
                'stock_quantity'      => 20,
                'image'               => 'https://www.cdiscount.com/pdt2/8/7/9/1/700x700/mp60884879/rw/casque-modulable-moto-scooter-casques-integral-mod.jpg',
                'is_active'           => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate($product);
        }
    }
}
