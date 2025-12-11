<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/DeviceRepository.php';

require_login();
$repo = new DeviceRepository();

$id = (int)($_GET['id'] ?? 0);
$dev = $repo->get($id);
if (!$dev) { http_response_code(404); exit('Cihaz bulunamadı'); }

$repo->delete($id);
header('Location: /public/pages/' . $dev['category'] . '.php');
exit;
