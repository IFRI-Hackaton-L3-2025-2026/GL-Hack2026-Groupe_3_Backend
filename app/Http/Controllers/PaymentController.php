<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payments', description: 'Consultation des paiements')]


class PaymentController extends Controller
{
    #[OA\Get(
        path: '/api/v1/payments',
        summary: 'Lister les paiements de l\'utilisateur connecté',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des paiements de l\'utilisateur')
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

    #[OA\Get(
        path: '/api/v1/payments/{id}',
        summary: 'Afficher le détail d\'un paiement (Utilisateur)',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paiement trouvé'),
            new OA\Response(response: 403, description: 'Accès refusé'),
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

    #[OA\Get(
        path: '/api/v1/admin/payments',
        summary: 'Lister tous les paiements (Admin)',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée de tous les paiements')
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
