<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EppEvent extends Model
{
    protected $table = 'epp_events';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $appends = ['display_id'];

    protected $casts = [
        'sequence_id' => 'integer',
        'event_observed_at' => 'datetime',
        'event_confirmed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'violation_codes_json' => 'array',
        'person_box_json' => 'array',
        'head_box_json' => 'array',
        'torso_box_json' => 'array',
        'helmet_box_json' => 'array',
        'vest_box_json' => 'array',
        'confirmed_status_snapshot_json' => 'array',
    ];

    public function evidence()
    {
        return $this->hasOne(EppEventEvidence::class, 'event_id', 'event_id');
    }

    /**
     * Obtiene el ID con formato correlativo para mostrar
     */
    public function getDisplayIdAttribute(): string
    {
        return 'EVT-' . str_pad((string)$this->sequence_id, 6, '0', STR_PAD_LEFT);
    }
}