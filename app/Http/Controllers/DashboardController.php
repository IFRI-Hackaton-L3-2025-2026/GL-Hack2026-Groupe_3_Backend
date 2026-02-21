<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\Breakdown;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard', description: 'Statistiques consolidées et indicateurs clés de performance (KPI)')]
class DashboardController extends Controller
{
    /**
     * Vue d'ensemble du système
     * * Fournit une vue 360° de l'état du parc : 
     * 1. Compteurs d'état des équipements (Actifs, en panne, etc.).
     * 2. État d'avancement des maintenances.
     * 3. Volume et statut des pannes signalées.
     * 4. Listes flash (Top 5) pour les actions urgentes ou à venir.
     */
    #[OA\Get(
        path: '/api/v1/dashboard',
        summary: 'Récupérer les statistiques du tableau de bord',
        description: 'Retourne un agrégat complet de données pour le dashboard : compteurs globaux et listes simplifiées des 5 derniers incidents/maintenances.',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Statistiques globales retournées avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'equipements', type: 'object', properties: [
                            new OA\Property(property: 'total', type: 'integer', example: 50),
                            new OA\Property(property: 'actifs', type: 'integer', example: 42),
                            new OA\Property(property: 'en_panne', type: 'integer', example: 5),
                            new OA\Property(property: 'en_maintenance', type: 'integer', example: 2),
                            new OA\Property(property: 'hors_service', type: 'integer', example: 1)
                        ]),
                        new OA\Property(property: 'maintenances', type: 'object', properties: [
                            new OA\Property(property: 'total', type: 'integer', example: 120),
                            new OA\Property(property: 'planifiees', type: 'integer', example: 10),
                            new OA\Property(property: 'en_cours', type: 'integer', example: 3),
                            new OA\Property(property: 'terminees', type: 'integer', example: 107)
                        ]),
                        new OA\Property(property: 'pannes', type: 'object', properties: [
                            new OA\Property(property: 'total', type: 'integer', example: 15),
                            new OA\Property(property: 'ouvertes', type: 'integer', example: 4),
                            new OA\Property(property: 'en_cours', type: 'integer', example: 2),
                            new OA\Property(property: 'resolues', type: 'integer', example: 9)
                        ]),
                        new OA\Property(property: 'pannes_recentes', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'equipment', type: 'string', example: 'Climatiseur Central'),
                                new OA\Property(property: 'priority', type: 'string', example: 'critique'),
                                new OA\Property(property: 'status', type: 'string', example: 'ouverte'),
                                new OA\Property(property: 'reported_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'declared_by', type: 'string', example: 'Jean Dupont')
                            ]
                        )),
                        new OA\Property(property: 'maintenances_a_venir', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'equipment', type: 'string', example: 'Groupe Électrogène'),
                                new OA\Property(property: 'type', type: 'string', example: 'préventive'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'technicien', type: 'string', example: 'Marius Tech')
                            ]
                        ))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Accès non autorisé : Token invalide ou absent')
        ]
    )]
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
                    'equipment'   => $breakdown->equipment->name ?? 'N/A',
                    'priority'    => $breakdown->priority,
                    'status'      => $breakdown->status,
                    'reported_at' => $breakdown->reported_at,
                    'declared_by' => $breakdown->declaredBy->fullname ?? 'Système',
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
                    'equipment'  => $maintenance->equipment->name ?? 'N/A',
                    'type'       => $maintenance->type,
                    'start_date' => $maintenance->start_date,
                    'technicien' => $maintenance->technicien->fullname ?? 'Non assigné',
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
                'total'      => $totalMaintenances,
                'planifiees' => $maintenancesPlanifiees,
                'en_cours'   => $maintenancesEnCours,
                'terminees'  => $maintenancesTerminees,
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