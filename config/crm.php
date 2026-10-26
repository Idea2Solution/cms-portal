<?php

return [
    // Customers module (menu, list, "Convert to customer", auto-convert on Won).
    // Off: every company is treated as a lead and listed under Leads.
    'customers' => env('CRM_CUSTOMERS', true),
];
