<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/DeviceRepository.php';
require_once __DIR__ . '/../../src/constants.php';

require_login();
$repo = new DeviceRepository();

$id = (int)($_GET['id'] ?? 0);
$dev = $repo->get($id);
if (!$dev) { http_response_code(404); exit('Cihaz bulunamadı'); }

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
  $repo->update($id, $d);
  header('Location: /public/pages/' . $d['category'] . '.php');
  exit;
}

include __DIR__ . '/../_layout/header.php';
?>
<h2>Cihaz Düzenle (<?= htmlspecialchars($dev['name']) ?>)</h2>

<form method="post" class="form-row" style="max-width: 700px;">
  <label>Ad</label><input class="input" name="name" value="<?= htmlspecialchars($dev['name']) ?>" required>
  <label>IP</label><input class="input" name="ip" value="<?= htmlspecialchars($dev['ip']) ?>" required>
  <label>Kullanıcı</label><input class="input" name="username" value="<?= htmlspecialchars($dev['username']) ?>" required>
  <label>Şifre</label><input class="input" name="password" value="<?= htmlspecialchars($dev['password']) ?>" required>
  <label>Kategori</label>
  <select class="input" name="category">
    <?php foreach (CATEGORIES as $c): ?>
      <option value="<?= $c ?>" <?= $c===$dev['category']?'selected':'' ?>><?= $c ?></option>
    <?php endforeach; ?>
  </select>
  <label>Marka</label><input class="input" name="brand" value="<?= htmlspecialchars($dev['brand']) ?>" required>
  <label>Model</label><input class="input" name="model" value="<?= htmlspecialchars($dev['model']) ?>" required>
  <label>Interface</label><input class="input" name="interface" value="<?= htmlspecialchars($dev['interface']) ?>">
  <label><input type="checkbox" name="show_on_dashboard" <?= $dev['show_on_dashboard']?'checked':'' ?>> Listelerde göster</label>

  <button class="btn" type="submit">Kaydet</button>
</form>

<?php include __DIR__ . '/../_layout/footer.php'; ?>
