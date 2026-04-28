<?php
/**
 * Gridbase Digital Solutions - Email Configuration
 * 
 * Configure your SMTP settings here. For cPanel, you can use
 * the server's own mail service or an external provider like
 * SendGrid, Mailgun, or Postmark for better deliverability.
 */

return [
    'host'       => 'mail.gridbase.com.do',
    'port'       => 587,
    'username'   => 'bills@gridbase.com.do',
    'password'   => '',
    'encryption' => 'tls',  // 'tls' or 'ssl'
    'from_name'  => 'Gridbase Digital Solutions',
    'from_email' => 'bills@gridbase.com.do',
    'reply_to'   => 'info@gridbase.com.do',
    
    // Debug level: 0 = off, 1 = client, 2 = client+server
    'debug'      => 0,
];
