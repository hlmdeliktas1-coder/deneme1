<?php
require_once __DIR__ . '/../src/auth.php';
ensure_session();
session_destroy();
header('Location: /public/login.php');
exit;
