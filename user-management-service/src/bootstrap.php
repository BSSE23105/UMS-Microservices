<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {

    // Keep a shared cookie name across services (must be before session_start)
    session_name('UMSSESSID');

    // Keep your cookie settings (must be before session_start)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // only if HTTPS
    ]);

    // Override hardcoded php.ini values using ECS env vars
    $redisHost = getenv('REDIS_HOST') ?: '';
    $redisPort = getenv('REDIS_PORT') ?: '6379';

    if ($redisHost !== '') {
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', "tcp://{$redisHost}:{$redisPort}");
    }

    session_start();
}
