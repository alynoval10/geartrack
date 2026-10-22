<?php

return [
    'time' => env('OPERATIONAL_REMINDER_TIME', '07:00'),
    'maintenance_days' => (int) env('OPERATIONAL_REMINDER_MAINTENANCE_DAYS', 7),
    'warranty_days' => (int) env('OPERATIONAL_REMINDER_WARRANTY_DAYS', 30),
];
