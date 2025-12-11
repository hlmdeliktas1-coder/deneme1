<?php
/**
 * src/db.php
 * Returns a PDO instance using getConfig()
 */

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $cfg = getConfig();
    $dsn  = $cfg['db']['dsn'] ?? '';
    $user = $cfg['db']['user'] ?? '';
    $pass = $cfg['db']['pass'] ?? '';

    if (!$dsn) {
        throw new \RuntimeException("Database DSN is not configured in src/config.php");
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}
