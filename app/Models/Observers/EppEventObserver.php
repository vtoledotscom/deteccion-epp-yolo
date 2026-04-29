<?php

namespace App\Models\Observers;

use App\Models\EppEvent;

class EppEventObserver
{
    /**
     * Handle the EppEvent "creating" event.
     * Asigna automáticamente el siguiente sequence_id correlativo.
     */
    public function creating(EppEvent $event): void
    {
        // Obtiene el próximo número correlativo de forma thread-safe
        $lastSequence = EppEvent::query()
            ->lockForUpdate()
            ->max('sequence_id') ?? 0;
        
        $event->sequence_id = $lastSequence + 1;
    }
}
