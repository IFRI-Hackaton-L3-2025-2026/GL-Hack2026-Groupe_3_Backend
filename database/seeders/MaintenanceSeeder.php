<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Maintenance;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $maintenances = [
            [
                'equipment_id' => 1,
                'user_id'      => 2, // Jean Technicien
                'type'         => 'preventive',
                'status'       => 'terminee',
                'start_date'   => '2026-01-10 08:00:00',
                'end_date'     => '2026-01-10 12:00:00',
                'cost'         => 75000,
                'description'  => 'Vérification générale et lubrification robot KUKA 01',
            ],
            [
                'equipment_id' => 2,
                'user_id'      => 2,
                'type'         => 'corrective',
                'status'       => 'en_cours',
                'start_date'   => '2026-02-18 09:00:00',
                'end_date'     => null,
                'cost'         => 120000,
                'description'  => 'Remplacement capteur de vibration robot KUKA 02',
            ],
            [
                'equipment_id' => 3,
                'user_id'      => 2,
                'type'         => 'preventive',
                'status'       => 'planifiee',
                'start_date'   => '2026-02-25 08:00:00',
                'end_date'     => '2026-02-25 17:00:00',
                'cost'         => 50000,
                'description'  => 'Maintenance préventive trimestrielle CNC FANUC 01',
            ],
        ];

        foreach ($maintenances as $maintenance) {
            Maintenance::create($maintenance);
        }
    }
}