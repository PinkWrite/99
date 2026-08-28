<?php
/**
 * PinkWrite 99 — SysAdmin config.
 * Copy to pw99-config.php and edit. Never commit the live file.
 * The in-app Administrator does not edit this. Mail, host, and DB are SysAdmin work.
 */
return [
    'configured' => true,

    // Walk-in recovery: SysAdmin sets true, creates a Superintendent, sets false again.
    'allow_create_super' => false,

    // Everything after https:// — no scheme. Examples:
    //   write.pink
    //   99.example.org
    //   www.example.org
    //   example.org/99
    // www. is the SysAdmin's choice. The app always uses https://
    'host' => 'write.pink/99',

    'site_title' => 'PinkWrite 99',

    'db' => [
        'host' => '127.0.0.1', // TCP. Do not use localhost (that is a Unix socket).
        'port' => 3306,
        'name' => 'pw99db',
        'user' => 'pw99db',
        'pass' => 'change-me',
        'charset' => 'utf8mb4',
    ],

    // Mail is SysAdmin-only. SMTP password lives here on purpose.
    'mail' => [
        'transport' => 'mail', // mail | smtp | off
        'from' => 'noreply@write.pink',
        'from_name' => 'PinkWrite 99',
        'smtp_host' => '127.0.0.1',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls', // tls | ssl | none
    ],

    'github' => 'https://github.com/PinkWrite/99.git',
    'github_branch' => 'master',
];
