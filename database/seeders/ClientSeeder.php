<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientRole = Role::where('name', 'client')->first();

        $clients = [
            [
                'fullname' => 'Kouassi Amed',
                'email'    => 'kouassi@test.com',
                'password' => Hash::make('password123'),
                'phone'    => '97111111',
                'address'  => 'Cotonou, Akpakpa',
                'role_id'  => $clientRole->id,
            ],
            [
                'fullname' => 'Fatima Bello',
                'email'    => 'fatima@test.com',
                'password' => Hash::make('password123'),
                'phone'    => '97222222',
                'address'  => 'Cotonou, Cadjehoun',
                'role_id'  => $clientRole->id,
            ],
            [
                'fullname' => 'Moise Dossou',
                'email'    => 'moise@test.com',
                'password' => Hash::make('password123'),
                'phone'    => '97333333',
                'address'  => 'Cotonou, Fidjrosse',
                'role_id'  => $clientRole->id,
            ],
        ];

        foreach ($clients as $client) {
            User::updateOrCreate($client);
        }
    }
}
