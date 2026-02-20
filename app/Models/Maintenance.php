<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'equipment_id', 'user_id', 'type',
        'status', 'start_date', 'end_date',
        'cost', 'description',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function technicien()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}