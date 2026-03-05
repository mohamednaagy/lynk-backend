<?php

return [
    'subject' => 'Remaining Balance Alert',
    'greeting' => 'Hello :company_name Team,',
    'body' => "
This is to inform you that your wallet remaining balance on the LYNK platform has reached or fallen below the configured limit.\n
Current Remaining Balance: :balance.\n
Configured Threshold: :remaining_balance_limit.",
    'footer' => 'To avoid order processing interruptions, please recharge your wallet at your earliest convenience.',
    'regards' => 'Regards',
];
