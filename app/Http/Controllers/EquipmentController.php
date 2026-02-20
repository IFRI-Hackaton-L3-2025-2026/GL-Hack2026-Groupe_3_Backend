<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    // Liste tous les équipements
    public function index()
    {
        $equipments = Equipment::with('category')->get();

        return response()->json($equipments, 200);
    }

    // Créer un équipement
    public function store(Request $request)
    {
        $request->validate([
            'equipment_category_id' => 'required|exists:equipment_categories,id',
            'name'                  => 'required|string|max:255',
            'brand'                 => 'nullable|string|max:255',
            'serial_number'         => 'required|string|unique:equipments,serial_number',
            'installation_date'     => 'nullable|date',
            'status'                => 'nullable|in:actif,en_panne,en_maintenance,hors_service',
            'location'              => 'nullable|string|max:255',
            'picture'               => 'nullable|string',
            'description'           => 'nullable|string',
        ]);

        $equipment = Equipment::create($request->all());

        return response()->json([
            'message'   => 'Équipement créé avec succès',
            'equipment' => $equipment->load('category')
        ], 201);
    }

    // Détail d'un équipement
    public function show($id)
    {
        $equipment = Equipment::with(['category', 'maintenances', 'breakdowns'])->find($id);

        if (!$equipment) {
            return response()->json([
                'message' => 'Équipement non trouvé'
            ], 404);
        }

        return response()->json($equipment, 200);
    }

    // Modifier un équipement
    public function update(Request $request, $id)
    {
        $equipment = Equipment::find($id);

        if (!$equipment) {
            return response()->json([
                'message' => 'Équipement non trouvé'
            ], 404);
        }

        $request->validate([
            'equipment_category_id' => 'nullable|exists:equipment_categories,id',
            'name'                  => 'nullable|string|max:255',
            'brand'                 => 'nullable|string|max:255',
            'serial_number'         => 'nullable|string|unique:equipments,serial_number,' . $id,
            'installation_date'     => 'nullable|date',
            'status'                => 'nullable|in:actif,en_panne,en_maintenance,hors_service',
            'location'              => 'nullable|string|max:255',
            'picture'               => 'nullable|string',
            'description'           => 'nullable|string',
        ]);

        $equipment->update($request->all());

        return response()->json([
            'message'   => 'Équipement modifié avec succès',
            'equipment' => $equipment->load('category')
        ], 200);
    }

    // Supprimer un équipement
    public function destroy($id)
    {
        $equipment = Equipment::find($id);

        if (!$equipment) {
            return response()->json([
                'message' => 'Équipement non trouvé'
            ], 404);
        }

        $equipment->delete();

        return response()->json([
            'message' => 'Équipement supprimé avec succès'
        ], 200);
    }
}
