<?php

/**
 * Rate Limiter Configuration
 *
 * This configuration file defines custom rate limits for API routes in the application.
 * Each key represents the **route name**, and the corresponding value defines the maximum allowed attempts
 */

return [
    // Authentication Routes
    'api.v1.sign-up' => 6,
    'api.v1.lender.sign-up' => 6,
    'api.v1.supplier.sign-up' => 6,
    'api.v1.login' => 60,
    'api.v1.supplier.login' => 60,
    'api.v1.verify.otp' => 5,
    'api.v1.verify.email' => 6,

    // Visitor Enquiry Routes
    'api.v1.visitor.enquiry' => 6,
    'api.v1.visitor.enquiry.reply' => 6,
];
