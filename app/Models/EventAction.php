<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAction extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'action',
        'note',
        'notified_person',
        'notification_method',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
        ];
    }

    public function event()
    {
        return $this->belongsTo(EppEvent::class, 'event_id', 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
