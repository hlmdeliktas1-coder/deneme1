<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';

ensure_session();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = $_POST['password'] ?? '';

  $st = db()->prepare("SELECT id, password_hash FROM users WHERE username=:u LIMIT 1");
  $st->execute([':u'=>$u]);
  $row = $st->fetch();

  if ($row && password_verify($p, $row['password_hash'])) {
    $_SESSION['user_id'] = $row['id'];
    header('Location: /public/pages/router.php');
    exit;
  }
  $error = 'Kullanıcı adı veya şifre hatalı.';
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Giriş</title>
  <link rel="stylesheet" href="/public/assets/css/app.css" />
</head>
<body>
  <div class="container" style="max-width: 520px; margin: 80px auto;">
    <div class="device-card" style="cursor: default;">
      <div class="title">
        <div class="name">Giriş</div>
        <div class="sub">Panel erişimi için</div>
      </div>
      <form method="post" class="form-row" style="margin-top: 12px;">
        <input class="input" name="username" placeholder="kullanıcı" required />
        <input class="input" name="password" type="password" placeholder="şifre" required />
        <button class="btn" type="submit">Giriş</button>
        <?php if($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      </form>
    </div>
  </div>
</body>
</html>
