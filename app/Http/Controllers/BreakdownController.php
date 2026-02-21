<?php

namespace App\Http\Controllers;

use App\Models\Breakdown;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pannes', description: 'Gestion du cycle de vie des pannes (Signalement, Suivi, Résolution)')]
class BreakdownController extends Controller
{
    /**
     * Liste toutes les pannes enregistrées
     * * Récupère l'historique complet des pannes avec les détails de l'équipement et l'auteur du signalement.
     */
    #[OA\Get(
        path: '/api/v1/breakdowns',
        summary: 'Lister toutes les pannes',
        description: 'Retourne une liste exhaustive des pannes incluant les relations avec les équipements et les utilisateurs.',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste des pannes récupérée avec succès',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'description', type: 'string', example: 'Moteur en surchauffe'),
                            new OA\Property(property: 'status', type: 'string', example: 'en_cours'),
                            new OA\Property(property: 'equipment', type: 'object', properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Générateur A1')
                            ]),
                            new OA\Property(property: 'declared_by', type: 'object', properties: [
                                new OA\Property(property: 'fullname', type: 'string', example: 'Jean Technicien')
                            ])
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Session expirée ou jeton manquant')
        ]
    )]
    public function index()
    {
        $breakdowns = Breakdown::with(['equipment', 'declaredBy'])->get();
        return response()->json($breakdowns, 200);
    }

    /**
     * Signaler une nouvelle panne
     * * Permet d'enregistrer un incident technique. 
     * Le statut par défaut est "ouverte". La priorité "critique" déclenche généralement une alerte immédiate.
     */
    #[OA\Post(
        path: '/api/v1/breakdowns',
        summary: 'Signaler une nouvelle panne',
        description: 'Crée un ticket de panne. Note : Seuls les techniciens et admins ont les droits complets sur cette action.',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['equipment_id', 'user_id', 'description', 'priority', 'reported_at'],
                properties: [
                    new OA\Property(property: 'equipment_id', type: 'integer', example: 1, description: 'ID de l\'équipement en panne'),
                    new OA\Property(property: 'user_id', type: 'integer', example: 2, description: 'ID de l\'utilisateur déclarant (technicien ou client)'),
                    new OA\Property(property: 'description', type: 'string', example: 'Vibrations anormales au démarrage'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['faible', 'moyenne', 'critique'], example: 'moyenne'),
                    new OA\Property(property: 'status', type: 'string', enum: ['ouverte', 'en_cours', 'resolue'], example: 'ouverte'),
                    new OA\Property(property: 'reported_at', type: 'string', format: 'date-time', example: '2026-02-19 10:00:00'),
                    new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', nullable: true, example: null)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ticket de panne créé avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé — Droits insuffisants'),
            new OA\Response(response: 422, description: 'Données invalides ou équipement inexistant')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'user_id'      => 'required|exists:users,id',
            'description'  => 'required|string',
            'priority'     => 'required|in:faible,moyenne,critique',
            'status'       => 'nullable|in:ouverte,en_cours,resolue',
            'reported_at'  => 'required|date',
            'resolved_at'  => 'nullable|date|after:reported_at',
        ]);
        $breakdown = Breakdown::create($request->all());
        return response()->json([
            'message'   => 'Panne signalée avec succès',
            'breakdown' => $breakdown->load(['equipment', 'declaredBy'])
        ], 201);
    }

    /**
     * Détails d'un incident spécifique
     */
    #[OA\Get(
        path: '/api/v1/breakdowns/{id}',
        summary: 'Consulter une panne spécifique',
        description: 'Retourne toutes les informations d\'une panne via son identifiant unique.',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        parameters: [
            new OA\Parameter(
                name: 'id', 
                in: 'path', 
                description: 'ID unique de la panne',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails de la panne récupérés'),
            new OA\Response(response: 404, description: 'Panne non trouvée dans la base de données')
        ]
    )]
    public function show($id)
    {
        $breakdown = Breakdown::with(['equipment', 'declaredBy'])->find($id);
        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }
        return response()->json($breakdown, 200);
    }

    /**
     * Mise à jour de la panne (Statut et Résolution)
     * * Utilisé principalement pour changer l'état (ex: passer de "ouverte" à "en_cours") ou clore le ticket.
     */
    #[OA\Put(
        path: '/api/v1/breakdowns/{id}',
        summary: 'Mettre à jour une panne',
        description: 'Permet de modifier les informations ou de marquer la panne comme résolue.',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['ouverte', 'en_cours', 'resolue'], example: 'resolue'),
                    new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: '2026-02-20 15:00:00', description: 'Date de fin d\'intervention')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Panne mise à jour avec succès'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Panne introuvable')
        ]
    )]
    public function update(Request $request, $id)
    {
        $breakdown = Breakdown::find($id);
        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }
        $request->validate([
            'equipment_id' => 'nullable|exists:equipments,id',
            'user_id'      => 'nullable|exists:users,id',
            'description'  => 'nullable|string',
            'priority'     => 'nullable|in:faible,moyenne,critique',
            'status'       => 'nullable|in:ouverte,en_cours,resolue',
            'reported_at'  => 'nullable|date',
            'resolved_at'  => 'nullable|date|after:reported_at',
        ]);
        $breakdown->update($request->all());
        return response()->json([
            'message'   => 'Panne modifiée avec succès',
            'breakdown' => $breakdown->load(['equipment', 'declaredBy'])
        ], 200);
    }

    /**
     * Suppression d'un ticket
     * * Action irréversible. Généralement limitée aux administrateurs.
     */
    #[OA\Delete(
        path: '/api/v1/breakdowns/{id}',
        summary: 'Supprimer un ticket de panne',
        description: 'Supprime définitivement une panne du système.',
        security: [['sanctum' => []]],
        tags: ['Pannes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Panne supprimée avec succès'),
            new OA\Response(response: 403, description: 'Droits administrateur requis'),
            new OA\Response(response: 404, description: 'Le ticket n\'existe plus')
        ]
    )]
    public function destroy($id)
    {
        $breakdown = Breakdown::find($id);
        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }
        $breakdown->delete();
        return response()->json(['message' => 'Panne supprimée avec succès'], 200);
    }
}