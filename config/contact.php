<?php

return [
    'recipients' => array_values(array_unique(array_filter(array_map(
        'trim',
        explode(',', (string) env('CONTACT_NOTIFICATION_RECIPIENTS', 'aut0run@gmail.com,ali.zahedi.7953@gmail.com')),
    ), fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false))),
];
