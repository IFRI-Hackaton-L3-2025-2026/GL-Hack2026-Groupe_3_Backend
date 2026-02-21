<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Breakdown;

class BreakdownSeeder extends Seeder
{
    public function run(): void
    {
        $breakdowns = [
            [
                'equipment_id' => 2,
                'user_id'      => 2,
                'description'  => 'Vibrations anormales détectées sur le bras articulé',
                'priority'     => 'critique',
                'status'       => 'en_cours',
                'reported_at'  => '2026-02-18 07:30:00',
                'resolved_at'  => null,
            ],
            [
                'equipment_id' => 4,
                'user_id'      => 2,
                'description'  => 'Erreur de lecture outil sur axe Z',
                'priority'     => 'moyenne',
                'status'       => 'ouverte',
                'reported_at'  => '2026-02-19 10:15:00',
                'resolved_at'  => null,
            ],
            [
                'equipment_id' => 1,
                'user_id'      => 2,
                'description'  => 'Surchauffe moteur principale détectée',
                'priority'     => 'faible',
                'status'       => 'resolue',
                'reported_at'  => '2026-01-05 14:00:00',
                'resolved_at'  => '2026-01-05 16:30:00',
            ],
        ];

        foreach ($breakdowns as $breakdown) {
            Breakdown::updateOrCreate($breakdown);
        }
    }
}