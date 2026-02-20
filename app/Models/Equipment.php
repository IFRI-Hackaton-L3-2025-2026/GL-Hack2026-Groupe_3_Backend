<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
     // Force Laravel à utiliser le nom de table avec un "s"
    protected $table = 'equipments';

    protected $fillable = [
        'equipment_category_id', 'name', 'brand',
        'serial_number', 'installation_date',
        'status', 'location', 'picture', 'description',
    ];

    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function breakdowns()
    {
        return $this->hasMany(Breakdown::class);
    }
}