<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\Breakdown;

class DashboardController extends Controller
{
    public function index()
    {
        //  Equipements
        $totalEquipments     = Equipment::count();
        $actifs              = Equipment::where('status', 'actif')->count();
        $enPanne             = Equipment::where('status', 'en_panne')->count();
        $enMaintenance       = Equipment::where('status', 'en_maintenance')->count();
        $horsService         = Equipment::where('status', 'hors_service')->count();

        // Maintenances
        $totalMaintenances   = Maintenance::count();
        $maintenancesPlanifiees  = Maintenance::where('status', 'planifiee')->count();
        $maintenancesEnCours     = Maintenance::where('status', 'en_cours')->count();
        $maintenancesTerminees   = Maintenance::where('status', 'terminee')->count();

        //  Pannes 
        $totalBreakdowns     = Breakdown::count();
        $pannesOuvertes      = Breakdown::where('status', 'ouverte')->count();
        $pannesEnCours       = Breakdown::where('status', 'en_cours')->count();
        $pannesResolues      = Breakdown::where('status', 'resolue')->count();

        //  Pannes récentes (5 dernières)
        $pannesRecentes = Breakdown::with(['equipment', 'declaredBy'])
            ->orderBy('reported_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($breakdown) {
                return [
                    'id'          => $breakdown->id,
                    'equipment'   => $breakdown->equipment->name,
                    'priority'    => $breakdown->priority,
                    'status'      => $breakdown->status,
                    'reported_at' => $breakdown->reported_at,
                    'declared_by' => $breakdown->declaredBy->fullname,
                ];
            });

        //  Maintenances à venir (5 prochaines)
        $maintenancesAVenir = Maintenance::with(['equipment', 'technicien'])
            ->where('status', 'planifiee')
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get()
            ->map(function ($maintenance) {
                return [
                    'id'         => $maintenance->id,
                    'equipment'  => $maintenance->equipment->name,
                    'type'       => $maintenance->type,
                    'start_date' => $maintenance->start_date,
                    'technicien' => $maintenance->technicien->fullname,
                ];
            });

        return response()->json([
            'equipements' => [
                'total'          => $totalEquipments,
                'actifs'         => $actifs,
                'en_panne'       => $enPanne,
                'en_maintenance' => $enMaintenance,
                'hors_service'   => $horsService,
            ],
            'maintenances' => [
                'total'     => $totalMaintenances,
                'planifiees' => $maintenancesPlanifiees,
                'en_cours'  => $maintenancesEnCours,
                'terminees' => $maintenancesTerminees,
            ],
            'pannes' => [
                'total'    => $totalBreakdowns,
                'ouvertes' => $pannesOuvertes,
                'en_cours' => $pannesEnCours,
                'resolues' => $pannesResolues,
            ],
            'pannes_recentes'      => $pannesRecentes,
            'maintenances_a_venir' => $maintenancesAVenir,
        ], 200);
    }
}