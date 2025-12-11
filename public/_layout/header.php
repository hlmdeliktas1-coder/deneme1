<?php
/**
 * public/_layout/header.php
 * Use this header at top of all protected pages.
 */

// header.php is in public/_layout/
// src is in project_root/src/
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';

ensure_session();
$cfg = getConfig();
$appName = $cfg['app']['name'] ?? 'Network Panel';
$basePath = $cfg['app']['base_path'] ?? '/';
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($appName) ?></title>
  <link rel="stylesheet" href="<?= rtrim($basePath, '/') ?>/public/assets/css/app.css" />
  <script defer src="<?= rtrim($basePath, '/') ?>/public/assets/js/app.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="topbar">
    <div class="brand"><?= htmlspecialchars($appName) ?></div>
    <div class="nav">
      <a href="<?= rtrim($basePath, '/') ?>/public/pages/router.php">Router</a>
      <a href="<?= rtrim($basePath, '/') ?>/public/pages/switch.php">Switch</a>
      <a href="<?= rtrim($basePath, '/') ?>/public/pages/ap.php">AP</a>
      <a href="<?= rtrim($basePath, '/') ?>/public/pages/ptp.php">PTP</a>
      <a href="<?= rtrim($basePath, '/') ?>/public/logout.php">Çıkış</a>
    </div>
  </div>

  <div class="container">
