<?php

// Only friendly field names; every message text still comes from Laravel's own validation file.
return [
    'attributes' => [
        'company_id' => 'lead / customer',
        'inquiry_id' => 'inquiry',
        'proposal_id' => 'proposal',
        'invoice_id' => 'invoice',
        'agent' => 'sales agent',
        'priority' => 'temperature',
        'budget_from' => 'budget from',
        'budget_to' => 'budget to',
        'start_date' => 'expected start date',
        'next_fu' => 'follow-up date',
        'due' => 'due date',
        'valid_until' => 'valid until',
        'tax_pct' => 'tax %',
        'items.*.qty' => 'quantity',
        'items.*.price' => 'unit price',
        'items.*.service' => 'service',
        'items.*.desc' => 'description',
        'current_password' => 'current password',
        'next_due' => 'next follow-up',
        'next_purpose' => 'purpose of next follow-up',
    ],
];
