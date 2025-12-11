<?php
$id = (int)$d['id'];
?>
<div class="device-card" onclick="window.location='/public/device/detail.php?id=<?= $id ?>'">
  <div class="card-top">
    <div class="title">
      <div class="name"><?= htmlspecialchars($d['name']) ?></div>
      <div class="sub"><?= htmlspecialchars($d['brand'].' / '.$d['model']) ?></div>
    </div>
    <button class="gear" onclick="event.stopPropagation(); openInterfaceModal(<?= $id ?>);">⚙</button>
  </div>

  <div class="meta">
    IP: <span class="kbd"><?= htmlspecialchars($d['ip']) ?></span><br>
    IF: <span class="kbd" id="if_<?= $id ?>"><?= htmlspecialchars($d['interface']) ?></span>
  </div>

  <div class="stats">
    <div>DL: <span id="down_<?= $id ?>">--</span> Mbps</div>
    <div>UL: <span id="up_<?= $id ?>">--</span> Mbps</div>
    <div>CPU: <span id="cpu_<?= $id ?>">--</span> %</div>
    <div>Ver: <span id="ver_<?= $id ?>">--</span></div>
  </div>
</div>

<script>
  (function(){ loadCardMetrics(<?= $id ?>); })();
</script>
