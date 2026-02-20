<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breakdown extends Model
{
    protected $fillable = [
        'equipment_id', 'user_id', 'description',
        'priority', 'status', 'reported_at', 'resolved_at',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function declaredBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}