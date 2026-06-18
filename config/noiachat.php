<?php

return [
    'health' => [
        'disk_warning_percent' => (float) env('NOIACHAT_HEALTH_DISK_WARNING_PERCENT', 15),
        'disk_critical_percent' => (float) env('NOIACHAT_HEALTH_DISK_CRITICAL_PERCENT', 5),
        'max_pending_jobs' => (int) env('NOIACHAT_HEALTH_MAX_PENDING_JOBS', 50),
        'max_webhook_age_hours' => (int) env('NOIACHAT_HEALTH_MAX_WEBHOOK_AGE_HOURS', 24),
        'max_backup_age_hours' => (int) env('NOIACHAT_HEALTH_MAX_BACKUP_AGE_HOURS', 24),
        'max_recent_log_errors' => (int) env('NOIACHAT_HEALTH_MAX_RECENT_LOG_ERRORS', 0),
        'token_expiry_warning_days' => (int) env('NOIACHAT_HEALTH_TOKEN_EXPIRY_WARNING_DAYS', 14),
    ],

    'two_factor' => [
        'admin_roles' => ['admin', 'super_admin', 'company_admin', 'branch_manager'],
        'code_ttl_minutes' => (int) env('NOIACHAT_2FA_CODE_TTL_MINUTES', 10),
        'max_attempts' => (int) env('NOIACHAT_2FA_MAX_ATTEMPTS', 5),
        'expose_code_in_non_production' => (bool) env('NOIACHAT_2FA_EXPOSE_CODE_NON_PROD', true),
    ],

];
