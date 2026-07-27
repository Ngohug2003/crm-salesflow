<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Lead Scoring Rules & Weights Configuration
    |--------------------------------------------------------------------------
    |
    | Defines point rules to calculate Lead quality score automatically.
    |
    */

    'profile_completeness' => [
        'has_email' => 10,
        'has_phone' => 10,
        'has_company' => 10,
        'has_job_title' => 5,
        'has_address_or_city' => 5,
        'has_estimated_value' => 10,
    ],

    'status_points' => [
        'qualified' => 20,
        'in_progress' => 15,
        'new' => 10,
        'unqualified' => 0,
        'lost' => 0,
    ],

    'priority_points' => [
        'high' => 15,
        'medium' => 10,
        'low' => 5,
    ],

    'engagement' => [
        'has_owner' => 10,
        'recent_activity_days' => 7,
        'recent_activity_bonus' => 15,
    ],

    'stale_penalties' => [
        'stale_14_days' => -15,
        'stale_30_days' => -30,
    ],

    'thresholds' => [
        'hot' => 70,
        'warm' => 40,
    ],
];
