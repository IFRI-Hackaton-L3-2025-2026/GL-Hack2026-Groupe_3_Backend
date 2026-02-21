<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $equipments = [
            // Robots (category_id: 1)
            [
                'equipment_category_id' => 1,
                'name'                  => 'Robot KUKA KR 210 - 01',
                'brand'                 => 'KUKA',
                'serial_number'         => 'KUKA-001',
                'installation_date'     => '2022-03-15',
                'status'                => 'actif',
                'location'              => 'Zone A - Soudure',
                'description'           => 'Bras articulé pour soudure et assemblage',
            ],
            [
                'equipment_category_id' => 1,
                'name'                  => 'Robot KUKA KR 210 - 02',
                'brand'                 => 'KUKA',
                'serial_number'         => 'KUKA-002',
                'installation_date'     => '2022-03-15',
                'status'                => 'en_maintenance',
                'location'              => 'Zone A - Assemblage',
                'description'           => 'Bras articulé pour assemblage pièces',
            ],
            // CNC (category_id: 2)
            [
                'equipment_category_id' => 2,
                'name'                  => 'Tour CNC FANUC 30i - 01',
                'brand'                 => 'FANUC',
                'serial_number'         => 'FANUC-001',
                'installation_date'     => '2021-06-10',
                'status'                => 'actif',
                'location'              => 'Zone B - Usinage',
                'description'           => 'Tour à commande numérique série 30i',
            ],
            [
                'equipment_category_id' => 2,
                'name'                  => 'Tour CNC FANUC 30i - 02',
                'brand'                 => 'FANUC',
                'serial_number'         => 'FANUC-002',
                'installation_date'     => '2021-06-10',
                'status'                => 'en_panne',
                'location'              => 'Zone B - Usinage',
                'description'           => 'Tour à commande numérique série 30i',
            ],
            // Presses (category_id: 3)
            [
                'equipment_category_id' => 3,
                'name'                  => 'Presse Hydraulique SCHULER - 01',
                'brand'                 => 'SCHULER',
                'serial_number'         => 'SCHULER-001',
                'installation_date'     => '2020-11-20',
                'status'                => 'actif',
                'location'              => 'Zone C - Emboutissage',
                'description'           => 'Presse hydraulique pour emboutissage de tôles',
            ],
            // Convoyeurs (category_id: 4)
            [
                'equipment_category_id' => 4,
                'name'                  => 'Convoyeur Siemens SIMOTICS - 01',
                'brand'                 => 'Siemens',
                'serial_number'         => 'SIEMENS-001',
                'installation_date'     => '2023-01-05',
                'status'                => 'actif',
                'location'              => 'Zone D - Transport',
                'description'           => 'Convoyeur automatisé équipé de moteurs Siemens',
            ],
        ];

        foreach ($equipments as $equipment) {
            Equipment::updateOrCreate($equipment);
        }
    }
}