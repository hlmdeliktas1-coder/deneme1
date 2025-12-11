<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/DeviceRepository.php';

require_login();
$repo = new DeviceRepository();

$id = (int)($_GET['id'] ?? 0);
$dev = $repo->get($id);
if (!$dev) { http_response_code(404); exit('Cihaz bulunamadı'); }

include __DIR__ . '/../_layout/header.php';
?>
<h2><?= htmlspecialchars($dev['name']) ?> — detay</h2>

<div class="toolbar">
  <a class="btn secondary" href="/public/pages/<?= htmlspecialchars($dev['category']) ?>.php">← Geri</a>
  <a class="btn" href="/public/devices/edit.php?id=<?= $id ?>">Düzenle</a>
  <a class="btn" href="/public/devices/delete.php?id=<?= $id ?>" onclick="return confirm('Silinsin mi?');">Sil</a>
</div>

<div class="grid" style="grid-template-columns: 1fr; max-width: 920px;">
  <div class="device-card" style="cursor: default;">
    <div class="title">
      <div class="name"><?= htmlspecialchars($dev['name']) ?></div>
      <div class="sub"><?= htmlspecialchars($dev['brand'].' / '.$dev['model']) ?></div>
    </div>

    <div class="meta" style="margin-top:10px;">
      IP: <span class="kbd"><?= htmlspecialchars($dev['ip']) ?></span> ·
      IF: <span class="kbd" id="detail_if"><?= htmlspecialchars($dev['interface']) ?></span>
      <button class="btn" style="margin-left:10px;" onclick="openInterfaceModal(<?= $id ?>)">Interface ayarla</button>
    </div>

    <div class="stats" style="margin-top:12px;">
      <div>CPU: <span id="detail_cpu">--</span> %</div>
      <div>Versiyon: <span id="detail_ver">--</span></div>
      <div>Güncel sürüm: <span id="detail_update">--</span></div>
    </div>

    <div style="margin-top:12px;">
      <canvas id="trafficChart" height="140"></canvas>
    </div>
  </div>
</div>

<script>
const deviceId = <?= (int)$id ?>;

const ctx = document.getElementById('trafficChart').getContext('2d');
const trafficChart = new Chart(ctx, {
  type: 'line',
  data: { labels: [], datasets: [
    { label: 'RX (Mbps)', data: [], borderWidth: 2, tension: 0.25 },
    { label: 'TX (Mbps)', data: [], borderWidth: 2, tension: 0.25 }
  ]},
  options: {
    animation: false,
    responsive: true,
    scales: { x: { display: true }, y: { beginAtZero: true } },
    plugins: { legend: { position: 'top' } }
  }
});

function tick(){
  apiGet(`/api/metrics/traffic.php?id=${deviceId}`).then(d=>{
    if(d.ok){
      const t = new Date();
      trafficChart.data.labels.push(t.toLocaleTimeString());
      trafficChart.data.datasets[0].data.push(d.rx_mbps);
      trafficChart.data.datasets[1].data.push(d.tx_mbps);
      if (trafficChart.data.labels.length > 20) {
        trafficChart.data.labels.shift();
        trafficChart.data.datasets[0].data.shift();
        trafficChart.data.datasets[1].data.shift();
      }
      trafficChart.update();
    }
  });

  apiGet(`/api/metrics/cpu.php?id=${deviceId}`).then(d=>{
    if(d.ok) document.getElementById('detail_cpu').textContent = (d.cpu_percent ?? '--');
  });

  apiGet(`/api/metrics/version.php?id=${deviceId}`).then(d=>{
    if(d.ok){
      document.getElementById('detail_ver').textContent = d.current ?? '--';
      document.getElementById('detail_update').textContent = d.available ?? '—';
    }
  });
}

setInterval(tick, 3000);
tick();
</script>

<?php include __DIR__ . '/../_layout/footer.php'; ?>
