<?php

return [
    // Default rows per page for paginated transaction reports.
    'default_per_page' => 50,

    // Maximum date-range span (in days) allowed for a full-range export,
    // to keep memory bounded. ~1 year.
    'max_export_days' => 366,
];
