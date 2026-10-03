<?php
date_default_timezone_set('Asia/Colombo');
/* Runtime configuration. Keep secrets out of Git by using includes/config.local.php. */
$local = file_exists(__DIR__.'/config.local.php') ? require __DIR__.'/config.local.php' : [];
$cfg = static function(string $key, string $env, $default='') use ($local) {
    if (array_key_exists($key, $local)) return $local[$key];
    $value = getenv($env);
    return ($value !== false && $value !== '') ? $value : $default;
};
define('APP_NAME', 'WildTrail Lanka');
define('APP_URL', rtrim((string)$cfg('app_url','WILDTRAIL_APP_URL','http://localhost/wildtrail'), '/'));
define('GOOGLE_CLIENT_ID', (string)$cfg('google_client_id','GOOGLE_CLIENT_ID',''));
define('GOOGLE_CLIENT_SECRET', (string)$cfg('google_client_secret','GOOGLE_CLIENT_SECRET',''));
define('PAYHERE_MODE', (string)$cfg('payhere_mode','PAYHERE_MODE','local_demo')); // sandbox | live | local_demo
define('PAYHERE_MERCHANT_ID', (string)$cfg('payhere_merchant_id','PAYHERE_MERCHANT_ID',''));
define('PAYHERE_MERCHANT_SECRET', (string)$cfg('payhere_merchant_secret','PAYHERE_MERCHANT_SECRET',''));
define('PAYHERE_CURRENCY', 'LKR');
// Customer cancellation policy: eligible paid cancellations receive 50% of the amount paid.
define('CANCELLATION_REFUND_RATE', 0.50);
define('CANCELLATION_REFUND_PERCENT', 50);
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
