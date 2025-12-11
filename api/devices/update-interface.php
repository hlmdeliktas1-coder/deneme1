<?php
require_once __DIR__ . '/../auth-check.php';
require_once __DIR__ . '/../src/DeviceRepository.php';

$id = (int)($_POST['id'] ?? 0);
$iface = trim($_POST['interface'] ?? '');

if (!$id || $iface === '') {
  echo json_encode(['ok'=>false,'error'=>'invalid_request']); exit;
}

$repo = new DeviceRepository();
$dev = $repo->get($id);
if (!$dev) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

$repo->updateInterface($id, $iface);
echo json_encode(['ok'=>true]);
