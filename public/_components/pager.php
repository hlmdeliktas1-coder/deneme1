<?php
$pages = max(1, (int)ceil($total / $perPage));
?>
<div class="toolbar">
  <?php if ($page > 1): ?>
    <a class="btn secondary" href="?page=<?= $page-1 ?>">← Önceki</a>
  <?php endif; ?>
  <div class="small">Sayfa <?= $page ?> / <?= $pages ?></div>
  <?php if ($page < $pages): ?>
    <a class="btn secondary" href="?page=<?= $page+1 ?>">Sonraki →</a>
  <?php endif; ?>
</div>
