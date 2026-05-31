<?php

return [
    'container' => env('MAIL_CONTAINER', 'mailserver'),
    'domain' => env('MAIL_DOMAIN', 'example.com'),
    'hostname' => env('MAIL_HOSTNAME', 'mail.example.com'),
    'collector_interval' => (int) env('COLLECTOR_INTERVAL_SECONDS', 60),
    'log_tail_limit' => (int) env('LOG_TAIL_LIMIT', 50000),
    'queue_alert_threshold' => (int) env('QUEUE_ALERT_THRESHOLD', 100),
    'bounce_rate_alert_threshold' => (float) env('BOUNCE_RATE_ALERT_THRESHOLD', 10),
    'deferred_rate_alert_threshold' => (float) env('DEFERRED_RATE_ALERT_THRESHOLD', 20),
];
