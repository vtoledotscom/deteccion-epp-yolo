<?php

return [
    'roles' => [
        'admin' => [
            'view_dashboard',
            'view_reports',
            'view_events',
            'view_event_detail',
            'export_pdf',
            'export_csv',
            'manage_users',
            'view_user_activity_logs',
            'view_open_events',
            'resolve_open_events',
            'review_detection_events',
        ],

        'supervisor' => [
            'view_dashboard',
            'view_reports',
            'view_events',
            'view_event_detail',
            'export_pdf',
            'export_csv',
            'view_user_activity_logs',
            'view_open_events',
            'resolve_open_events',
            'review_detection_events',
        ],

        'operator' => [
            'view_dashboard',
            'view_events',
            'view_event_detail',
            'view_open_events',
            'resolve_open_events',
        ],

        'viewer' => [
            'view_dashboard',
            'view_events',
            'view_event_detail',
        ],
    ],
];
