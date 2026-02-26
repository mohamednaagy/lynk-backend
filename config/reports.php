<?php

return [
    'orders_export_limit' => (int) env('ORDERS_EXPORT_LIMIT', 300000),
    'transactions_export_limit' => (int) env('TRANSACTIONS_EXPORT_LIMIT', 300000),
];
