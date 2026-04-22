<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventEvidenceController extends Controller
{
    public function annotated(string $eventId)
    {
        return $this->serveEvidence($eventId, 'image_annotated_path');
    }

    public function full(string $eventId)
    {
        return $this->serveEvidence($eventId, 'image_full_path');
    }

    public function crop(string $eventId)
    {
        return $this->serveEvidence($eventId, 'image_crop_path');
    }

    public function video(string $eventId)
    {
        return $this->serveEvidence($eventId, 'video_path', true);
    }

    protected function serveEvidence(string $eventId, string $field, bool $isVideo = false)
    {
        $event = EppEvent::query()
            ->with('evidence')
            ->where('event_id', $eventId)
            ->firstOrFail();

        $relativePath = optional($event->evidence)->{$field};

        if (!$relativePath) {
            abort(404, 'Evidencia no disponible');
        }

        $relativePath = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        $basePath = config('epp.project_base_path');
        $absolutePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        if (!File::exists($absolutePath)) {
            dd([
                'event_id' => $eventId,
                'field' => $field,
                'relative_path' => $relativePath,
                'base_path' => $basePath,
                'absolute_path' => $absolutePath,
                'file_exists' => File::exists($absolutePath),
            ]);
        }

        $mimeType = File::mimeType($absolutePath) ?: ($isVideo ? 'video/mp4' : 'application/octet-stream');

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
        ]);
    }
}