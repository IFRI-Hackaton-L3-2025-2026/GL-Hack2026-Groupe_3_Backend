<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Paiements', description: 'Consultation et historique des transactions financières')]
class PaymentController extends Controller
{
    /**
     * Historique des paiements de l'utilisateur
     * * Récupère tous les paiements liés aux commandes de l'utilisateur connecté.
     */
    #[OA\Get(
        path: '/api/v1/payments',
        summary: 'Lister mes paiements',
        description: 'Retourne l\'historique des transactions avec les détails de la commande associée.',
        security: [['sanctum' => []]],
        tags: ['Paiements'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste des paiements récupérée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function userPayments(Request $request)
    {
        $payments = Payment::with('order')
            ->whereHas('order', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $payments,
        ], 200);
    }

    /**
     * Détail d'un paiement spécifique
     * * Affiche les informations précises d'une transaction (référence, méthode, statut).
     */
    #[OA\Get(
        path: '/api/v1/payments/{id}',
        summary: 'Détail d\'un paiement',
        description: 'Affiche les détails d\'une transaction si elle appartient à l\'utilisateur.',
        security: [['sanctum' => []]],
        tags: ['Paiements'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID du paiement', schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paiement trouvé'),
            new OA\Response(response: 403, description: 'Accès refusé - Ce paiement ne vous appartient pas'),
            new OA\Response(response: 404, description: 'Paiement non trouvé')
        ]
    )]
    public function show(Request $request, $id)
    {
        $payment = Payment::with('order')->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé',
            ], 404);
        }

        // Vérifier que ce paiement appartient bien à l'utilisateur connecté
        if ($payment->order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $payment,
        ], 200);
    }

    /**
     * Administration : Liste globale des transactions
     * * Accès réservé aux administrateurs. Inclus la pagination et les infos utilisateurs.
     */
    #[OA\Get(
        path: '/api/v1/admin/payments',
        summary: 'Lister tous les paiements (Admin)',
        description: 'Vue d\'ensemble de toutes les transactions financières du système.',
        security: [['sanctum' => []]],
        tags: ['Paiements'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste paginée récupérée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'total', type: 'integer', example: 150)
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Accès interdit - Droits admin requis')
        ]
    )]
    public function index()
    {
        $payments = Payment::with('order.user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success'      => true,
            'data'         => $payments->items(),
            'current_page' => $payments->currentPage(),
            'last_page'    => $payments->lastPage(),
            'per_page'     => $payments->perPage(),
            'total'        => $payments->total(),
        ], 200);
    }
}