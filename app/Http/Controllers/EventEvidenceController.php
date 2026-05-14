<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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

        $storedPath = optional($event->evidence)->{$field};

        if (!$storedPath) {
            Log::warning('Evidence path is empty.', [
                'event_id' => $eventId,
                'field' => $field,
                'is_video' => $isVideo,
            ]);

            abort(404, 'La evidencia no está disponible para este evento.');
        }

        $realPath = $this->resolveEvidencePath($storedPath, $eventId, $field, $isVideo);

        if (!$realPath) {
            abort(404, 'La evidencia no está disponible para este evento.');
        }

        $mimeType = $isVideo ? 'video/mp4' : (File::mimeType($realPath) ?: 'application/octet-stream');

        ActivityLogger::log(
            'download_evidence',
            'evidence',
            'Descarga de evidencia',
            'epp_event',
            $event->event_id,
            [
                'evidence_type' => $field,
                'is_video' => $isVideo,
            ],
        );

        return response()->file($realPath, [
            'Content-Type' => $mimeType,
        ]);
    }

    protected function resolveEvidencePath(string $storedPath, string $eventId, string $field, bool $isVideo): ?string
    {
        $allowedBasePath = $this->allowedEvidenceBasePath();

        if (!$allowedBasePath) {
            Log::error('Evidence base path is not configured or does not exist.', [
                'event_id' => $eventId,
                'field' => $field,
                'is_video' => $isVideo,
            ]);

            return null;
        }

        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($storedPath));
        $candidatePath = $this->candidateEvidencePath($normalizedPath);

        if (!$candidatePath) {
            Log::error('Project base path is not configured for relative evidence path.', [
                'event_id' => $eventId,
                'field' => $field,
                'is_video' => $isVideo,
                'stored_path' => $storedPath,
            ]);

            return null;
        }

        $realPath = realpath($candidatePath);

        if (!$realPath || !File::isFile($realPath)) {
            Log::warning('Evidence file does not exist.', [
                'event_id' => $eventId,
                'field' => $field,
                'is_video' => $isVideo,
                'stored_path' => $storedPath,
            ]);

            return null;
        }

        if (!$this->pathIsInsideBase($realPath, $allowedBasePath)) {
            Log::warning('Evidence path is outside the allowed base directory.', [
                'event_id' => $eventId,
                'field' => $field,
                'is_video' => $isVideo,
                'stored_path' => $storedPath,
            ]);

            return null;
        }

        return $realPath;
    }

    protected function candidateEvidencePath(string $normalizedPath): ?string
    {
        if ($this->isAbsolutePath($normalizedPath)) {
            return $normalizedPath;
        }

        $projectBasePath = config('epp.project_base_path');

        if (!$projectBasePath) {
            return null;
        }

        return rtrim($projectBasePath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . ltrim($normalizedPath, DIRECTORY_SEPARATOR);
    }

    protected function allowedEvidenceBasePath(): ?string
    {
        $evidenceBasePath = config('epp.evidence_base_path');
        $projectBasePath = config('epp.project_base_path');
        $basePath = $evidenceBasePath;

        if (!$basePath && $projectBasePath) {
            $basePath = rtrim($projectBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'evidence';
        }

        return $basePath ? realpath($basePath) ?: null : null;
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }

    protected function pathIsInsideBase(string $path, string $basePath): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $basePath = strtolower($basePath);
        }

        return $path === $basePath || str_starts_with($path, $basePath . DIRECTORY_SEPARATOR);
    }
}
