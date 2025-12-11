<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/DeviceRepository.php';

require_login();
$category = 'ap';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$repo = new DeviceRepository();
$total = $repo->countByCategory($category);
$devices = $repo->listByCategory($category, $page, $perPage);

include __DIR__ . '/../_layout/header.php';
?>
<h2>AP</h2>
<div class="toolbar">
  <a class="btn" href="/public/devices/add.php?category=ap">Cihaz Ekle</a>
</div>

<div class="grid">
  <?php foreach ($devices as $d): ?>
    <?php include __DIR__ . '/../_components/device_card.php'; ?>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../_components/pager.php'; ?>
<?php include __DIR__ . '/../_layout/footer.php'; ?>
