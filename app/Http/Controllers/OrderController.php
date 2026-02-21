<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Cart;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Commandes', description: 'Gestion du tunnel d\'achat, du suivi et de l\'administration des commandes')]
class OrderController extends Controller
{
    const DELIVERY_FEE = 2000;

    /**
     * Historique des commandes utilisateur
     * * Retourne toutes les commandes passées par l'utilisateur connecté avec le détail des produits.
     */
    #[OA\Get(
        path: '/api/v1/orders',
        summary: 'Lister mes commandes',
        description: 'Récupère l\'historique personnel des commandes avec les produits et l\'état du paiement.',
        security: [['sanctum' => []]],
        tags: ['Commandes'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Liste récupérée',
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
    public function index(Request $request)
    {
        $orders = Order::with('items.product', 'payment')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ], 200);
    }

    /**
     * Administration : Liste globale
     * * Accès réservé aux administrateurs. Pagination incluse.
     */
    #[OA\Get(
        path: '/api/v1/admin/orders',
        summary: 'Toutes les commandes (Admin)',
        description: 'Vue d\'ensemble de toutes les commandes du système pour la gestion logistique.',
        security: [['sanctum' => []]],
        tags: ['Commandes'],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée des commandes'),
            new OA\Response(response: 403, description: 'Accès interdit')
        ]
    )]
    public function adminIndex()
    {
        $orders = Order::with('items.product', 'payment', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success'      => true,
            'data'         => $orders->items(),
            'current_page' => $orders->currentPage(),
            'last_page'    => $orders->lastPage(),
            'per_page'     => $orders->perPage(),
            'total'        => $orders->total(),
        ], 200);
    }

    /**
     * Détail d'une commande
     */
    #[OA\Get(
        path: '/api/v1/orders/{id}',
        summary: 'Détail d\'une commande',
        security: [['sanctum' => []]],
        tags: ['Commandes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails trouvés'),
            new OA\Response(response: 404, description: 'Commande introuvable')
        ]
    )]
    public function show(Request $request, $id)
    {
        $order = Order::with('items.product', 'payment')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $order,
        ], 200);
    }

    /**
     * Processus de Checkout
     * * Transforme le panier en commande, décrémente les stocks et simule le paiement.
     */
    #[OA\Post(
        path: '/api/v1/orders/checkout',
        summary: 'Finaliser la commande',
        description: 'Valide le panier, vérifie les stocks, calcule les frais de port et enregistre la transaction.',
        security: [['sanctum' => []]],
        tags: ['Commandes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['delivery_type', 'payment_method'],
                properties: [
                    new OA\Property(property: 'delivery_type', type: 'string', enum: ['pickup', 'delivery'], example: 'delivery'),
                    new OA\Property(property: 'delivery_address', type: 'string', example: '123 Rue de la République, Paris'),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card', 'mobile_money'], example: 'card')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Commande créée avec succès'),
            new OA\Response(response: 400, description: 'Panier vide ou rupture de stock'),
            new OA\Response(response: 422, description: 'Erreur de validation des champs')
        ]
    )]
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_type'    => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:delivery_type,delivery|string|nullable',
            'payment_method'   => 'required|in:cash,card,mobile_money',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Votre panier est vide'], 400);
        }

        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock_quantity) {
                return response()->json(['success' => false, 'message' => 'Stock insuffisant pour : ' . $item->product->name], 400);
            }
        }

        $subtotal     = $cart->items->sum(fn($item) => $item->quantity * $item->product->price);
        $deliveryFee  = $request->delivery_type === 'delivery' ? self::DELIVERY_FEE : 0;
        $totalAmount  = $subtotal + $deliveryFee;

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id'          => $user->id,
                'order_date'       => now(),
                'subtotal_amount'  => $subtotal,
                'delivery_fee'     => $deliveryFee,
                'total_amount'     => $totalAmount,
                'delivery_type'    => $request->delivery_type,
                'delivery_address' => $request->delivery_type === 'delivery' ? ($request->delivery_address ?? $user->address) : null,
                'status'           => 'en_attente',
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->product->price,
                    'total_price' => $item->quantity * $item->product->price,
                ]);
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            Payment::create([
                'order_id'              => $order->id,
                'amount'                => $totalAmount,
                'method'                => $request->payment_method,
                'status'                => 'successful',
                'transaction_reference' => 'SIM-' . strtoupper(uniqid()),
                'paid_at'               => now(),
            ]);

            $cart->items()->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande passée avec succès',
                'data'    => $order->load('items.product', 'payment'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Annulation de commande
     * * Remet les produits en stock et marque le paiement comme remboursé.
     */
    #[OA\Put(
        path: '/api/v1/orders/{id}/cancel',
        summary: 'Annuler une commande',
        description: 'Possible uniquement si la commande est encore "en attente" ou "confirmée".',
        security: [['sanctum' => []]],
        tags: ['Commandes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Commande annulée et stock réajusté'),
            new OA\Response(response: 400, description: 'Annulation impossible à ce stade')
        ]
    )]
    public function cancel(Request $request, $id)
    {
        $order = Order::with('items.product', 'payment')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Commande non trouvée'], 404);
        }

        if (!in_array($order->status, ['en_attente', 'confirmée'])) {
            return response()->json(['success' => false, 'message' => 'Annulation impossible'], 400);
        }

        try {
            DB::beginTransaction();
            foreach ($order->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
            $order->update(['status' => 'annulée']);
            if ($order->payment) $order->payment->update(['status' => 'refunded']);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Commande annulée', 'data' => $order], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mise à jour du statut logistique (Admin)
     */
    #[OA\Put(
        path: '/api/v1/admin/orders/{id}/status',
        summary: 'Changer le statut (Admin)',
        description: 'Permet de faire progresser la commande (ex: en préparation -> expédiée).',
        security: [['sanctum' => []]],
        tags: ['Commandes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['en_attente', 'confirmée', 'en_preparation', 'expédiée', 'prete_au_retrait', 'livrée', 'récupérée', 'annulée'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Statut mis à jour'),
            new OA\Response(response: 422, description: 'Statut invalide')
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:en_attente,confirmée,en_preparation,expédiée,prete_au_retrait,livrée,récupérée,annulée',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);
        if (!$order) return response()->json(['success' => false, 'message' => 'Commande non trouvée'], 404);

        $order->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour', 'data' => $order], 200);
    }
}