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
        ],

        'supervisor' => [
            'view_dashboard',
            'view_reports',
            'view_events',
            'view_event_detail',
            'export_pdf',
            'export_csv',
        ],

        'operator' => [
            'view_dashboard',
            'view_events',
            'view_event_detail',
        ],

        'viewer' => [
            'view_dashboard',
            'view_events',
            'view_event_detail',
        ],
    ],
];
