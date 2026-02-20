<?php

namespace App\Http\Controllers;

use App\Models\Breakdown;
use Illuminate\Http\Request;

class BreakdownController extends Controller
{
    public function index()
    {
        $breakdowns = Breakdown::with(['equipment', 'declaredBy'])->get();
        return response()->json($breakdowns, 200);
    }

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

    public function show($id)
    {
        $breakdown = Breakdown::with(['equipment', 'declaredBy'])->find($id);

        if (!$breakdown) {
            return response()->json(['message' => 'Panne non trouvée'], 404);
        }

        return response()->json($breakdown, 200);
    }

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
