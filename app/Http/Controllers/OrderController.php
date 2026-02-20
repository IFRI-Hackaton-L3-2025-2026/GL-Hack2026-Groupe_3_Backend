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

#[OA\Tag(name: 'Orders', description: 'Gestion des commandes')]


class OrderController extends Controller
{
    const DELIVERY_FEE = 2000;

    #[OA\Get(
        path: '/api/v1/orders',
        summary: 'Lister les commandes de l\'utilisateur connecté',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des commandes')
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

    #[OA\Get(
        path: '/api/v1/admin/orders',
        summary: 'Lister toutes les commandes (Admin)',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Liste de toutes les commandes')
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

    #[OA\Get(
        path: '/api/v1/orders/{id}',
        summary: 'Afficher le détail d\'une commande',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Commande trouvée'),
            new OA\Response(response: 404, description: 'Commande non trouvée')
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

    #[OA\Post(
        path: '/api/v1/orders/checkout',
        summary: 'Passer une commande (simulation paiement)',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['delivery_type', 'payment_method'],
                properties: [
                    new OA\Property(property: 'delivery_type', type: 'string', enum: ['pickup', 'delivery']),
                    new OA\Property(property: 'delivery_address', type: 'string'),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'card', 'mobile_money'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Commande créée avec succès'),
            new OA\Response(response: 400, description: 'Panier vide ou stock insuffisant'),
            new OA\Response(response: 422, description: 'Erreur de validation')
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
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // 1. Vérifier que le panier existe et n'est pas vide
        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 400);
        }

        // 2. Revérifier le stock de chaque produit
        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour le produit : ' . $item->product->name . '. Stock disponible : ' . $item->product->stock_quantity,
                ], 400);
            }

            if (!$item->product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le produit ' . $item->product->name . ' n\'est plus disponible',
                ], 400);
            }
        }

        // 3. Calculer les montants
        $subtotal     = $cart->items->sum(fn($item) => $item->quantity * $item->product->price);
        $deliveryFee  = $request->delivery_type === 'delivery' ? self::DELIVERY_FEE : 0;
        $totalAmount  = $subtotal + $deliveryFee;

        // 4. Déterminer l'adresse de livraison
        $deliveryAddress = null;
        if ($request->delivery_type === 'delivery') {
            $deliveryAddress = $request->delivery_address ?? $user->address;
        }

        // 5. Déterminer le statut initial selon le mode de réception
        $initialStatus = 'en_attente';

        // 6. Tout dans une transaction DB pour éviter les incohérences
        try {
            DB::beginTransaction();

            // Simulation paiement réussi
            // Créer la commande
            $order = Order::create([
                'user_id'          => $user->id,
                'order_date'       => now(),
                'subtotal_amount'  => $subtotal,
                'delivery_fee'     => $deliveryFee,
                'total_amount'     => $totalAmount,
                'delivery_type'    => $request->delivery_type,
                'delivery_address' => $deliveryAddress,
                'status'           => $initialStatus,
            ]);

            // Créer les order_items + décrémenter le stock
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->product->price,
                    'total_price' => $item->quantity * $item->product->price,
                ]);

                // Décrémenter le stock
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            // Créer le paiement (simulé comme réussi)
            Payment::create([
                'order_id'              => $order->id,
                'amount'                => $totalAmount,
                'method'                => $request->payment_method,
                'status'                => 'successful',
                'transaction_reference' => 'SIM-' . strtoupper(uniqid()),
                'paid_at'               => now(),
            ]);

            // Vider le panier
            $cart->items()->delete();

            DB::commit();

            $order->load('items.product', 'payment');

            return response()->json([
                'success' => true,
                'message' => 'Commande passée avec succès',
                'data'    => $order,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/orders/{id}/cancel',
        summary: 'Annuler une commande (Utilisateur)',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Commande annulée'),
            new OA\Response(response: 400, description: 'Annulation impossible'),
            new OA\Response(response: 404, description: 'Commande non trouvée')
        ]
    )]


    public function cancel(Request $request, $id)
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

        if (!in_array($order->status, ['en_attente', 'confirmée'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Réajuster le stock
            foreach ($order->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
            }

            // Mettre à jour le statut de la commande
            $order->update(['status' => 'annulée']);

            // Simuler le remboursement
            if ($order->payment) {
                $order->payment->update(['status' => 'refunded']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès. Remboursement en cours.',
                'data'    => $order,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/orders/{id}/status',
        summary: 'Modifier le statut d\'une commande (Admin)',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['en_attente', 'confirmée', 'en_preparation', 'expédiée', 'prete_au_retrait', 'livrée', 'récupérée', 'annulée'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Statut mis à jour'),
            new OA\Response(response: 404, description: 'Commande non trouvée')
        ]
    )]

    
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:en_attente,confirmée,en_preparation,expédiée,prete_au_retrait,livrée,récupérée,annulée',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data'    => $order,
        ], 200);
    }
}
