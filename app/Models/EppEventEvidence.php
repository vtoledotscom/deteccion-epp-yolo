<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EppEventEvidence extends Model
{
    protected $table = 'epp_event_evidence';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(EppEvent::class, 'event_id', 'event_id');
    }
}