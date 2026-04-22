<?php

return [
    'project_base_path' => env('EPP_PROJECT_BASE_PATH'),
    'evidence_base_path' => env('EPP_EVIDENCE_BASE_PATH'),
    'cameras_json_path' => env('EPP_CAMERAS_JSON_PATH'),
    'scenarios_json_path' => env('EPP_SCENARIOS_JSON_PATH'),
    'pagination' => [
        'events' => 15,
        'open_events' => 15,
    ],
];