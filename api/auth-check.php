<?php
// api/auth-check.php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';

require_login();
header('Content-Type: application/json; charset=utf-8');
