<?php
require_once __DIR__ . '/../auth-check.php';
require_once __DIR__ . '/../src/DeviceService.php';

$svc = new DeviceService();
$id = (int)($_GET['id'] ?? 0);
echo json_encode($svc->cpu($id));
