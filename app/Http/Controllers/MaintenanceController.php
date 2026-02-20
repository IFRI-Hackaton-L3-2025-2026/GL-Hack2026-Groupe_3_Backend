<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $maintenances = Maintenance::with(['equipment', 'technicien'])->get();
        return response()->json($maintenances, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'user_id'      => 'required|exists:users,id',
            'type'         => 'required|in:preventive,corrective',
            'status'       => 'nullable|in:planifiee,en_cours,terminee,annulee',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after:start_date',
            'cost'         => 'nullable|numeric',
            'description'  => 'nullable|string',
        ]);

        $maintenance = Maintenance::create($request->all());

        return response()->json([
            'message'     => 'Maintenance créée avec succès',
            'maintenance' => $maintenance->load(['equipment', 'technicien'])
        ], 201);
    }

    public function show($id)
    {
        $maintenance = Maintenance::with(['equipment', 'technicien'])->find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Maintenance non trouvée'], 404);
        }

        return response()->json($maintenance, 200);
    }

    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Maintenance non trouvée'], 404);
        }

        $request->validate([
            'equipment_id' => 'nullable|exists:equipments,id',
            'user_id'      => 'nullable|exists:users,id',
            'type'         => 'nullable|in:preventive,corrective',
            'status'       => 'nullable|in:planifiee,en_cours,terminee,annulee',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after:start_date',
            'cost'         => 'nullable|numeric',
            'description'  => 'nullable|string',
        ]);

        $maintenance->update($request->all());

        return response()->json([
            'message'     => 'Maintenance modifiée avec succès',
            'maintenance' => $maintenance->load(['equipment', 'technicien'])
        ], 200);
    }

    public function destroy($id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json(['message' => 'Maintenance non trouvée'], 404);
        }

        $maintenance->delete();

        return response()->json(['message' => 'Maintenance supprimée avec succès'], 200);
    }
}