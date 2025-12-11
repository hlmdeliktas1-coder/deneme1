<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/DeviceRepository.php';
require_once __DIR__ . '/../../src/constants.php';

require_login();

$repo = new DeviceRepository();
$category = in_array($_GET['category'] ?? '', CATEGORIES, true) ? $_GET['category'] : 'router';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $d = [
    'name' => trim($_POST['name'] ?? ''),
    'ip' => trim($_POST['ip'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'password' => trim($_POST['password'] ?? ''),
    'category' => $_POST['category'] ?? 'router',
    'brand' => trim($_POST['brand'] ?? ''),
    'model' => trim($_POST['model'] ?? ''),
    'interface' => trim($_POST['interface'] ?? 'ether1'),
    'show_on_dashboard' => isset($_POST['show_on_dashboard']) ? 1 : 0
  ];
  $repo->insert($d);
  header('Location: /public/pages/' . $d['category'] . '.php');
  exit;
}

include __DIR__ . '/../_layout/header.php';
?>
<h2>Cihaz Ekle (<?= htmlspecialchars($category) ?>)</h2>

<form method="post" class="form-row" style="max-width: 700px;">
  <label>Ad</label><input class="input" name="name" required>
  <label>IP</label><input class="input" name="ip" required>
  <label>Kullanıcı</label><input class="input" name="username" required>
  <label>Şifre</label><input class="input" name="password" required>
  <label>Kategori</label>
  <select class="input" name="category">
    <?php foreach (CATEGORIES as $c): ?>
      <option value="<?= $c ?>" <?= $c===$category?'selected':'' ?>><?= $c ?></option>
    <?php endforeach; ?>
  </select>
  <label>Marka</label><input class="input" name="brand" placeholder="mikrotik / huawei / ..." required>
  <label>Model</label><input class="input" name="model" required>
  <label>Interface (varsayılan)</label><input class="input" name="interface" value="ether1">
  <label><input type="checkbox" name="show_on_dashboard" checked> Listelerde göster</label>

  <button class="btn" type="submit">Kaydet</button>
  <div class="small">Not: Kart üzerindeki ⚙ butonuyla interface listesini cihazdan çekip güncelleyebilirsin (MikroTik destekli).</div>
</form>

<?php include __DIR__ . '/../_layout/footer.php'; ?>
