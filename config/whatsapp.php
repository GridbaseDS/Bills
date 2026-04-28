<?php
/**
 * Gridbase Digital Solutions - WhatsApp Configuration
 * 
 * Uses the Meta WhatsApp Cloud API.
 * 
 * Setup steps:
 * 1. Create a Meta Business account at business.facebook.com
 * 2. Create an app at developers.facebook.com
 * 3. Add WhatsApp product to your app
 * 4. Get your Phone Number ID and generate a permanent access token
 * 5. Create and submit message templates for approval
 */

return [
    'enabled'       => false,
    'api_version'   => 'v21.0',
    'api_base_url'  => 'https://graph.facebook.com',
    'access_token'  => '',
    'phone_id'      => '',
    'business_id'   => '',
    
    // Pre-defined template names (must be approved by WhatsApp)
    'templates' => [
        'invoice_sent'    => 'gridbase_invoice_notification',
        'invoice_reminder'=> 'gridbase_payment_reminder',
        'quote_sent'      => 'gridbase_quote_notification',
    ],
    
    // Default country code for phone numbers
    'default_country_code' => '1',
];
