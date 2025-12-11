function qs(sel, root=document){ return root.querySelector(sel); }

function apiGet(url){
  return fetch(url, {credentials:'include'}).then(r => r.json());
}

function loadCardMetrics(id){
  apiGet(`/api/metrics/traffic.php?id=${id}`).then(d=>{
    if(d.ok){
      qs(`#down_${id}`)?.textContent = d.rx_mbps;
      qs(`#up_${id}`)?.textContent   = d.tx_mbps;
    }
  });
  apiGet(`/api/metrics/cpu.php?id=${id}`).then(d=>{
    if(d.ok) qs(`#cpu_${id}`)?.textContent = d.cpu_percent ?? '-';
  });
  apiGet(`/api/metrics/version.php?id=${id}`).then(d=>{
    if(d.ok) qs(`#ver_${id}`)?.textContent = d.current ?? '-';
  });
}

function openInterfaceModal(id){
  const mb = qs('#ifaceModal');
  qs('#ifaceModalTitle').textContent = `Interface seç (Cihaz #${id})`;
  qs('#ifaceSaveBtn').dataset.id = id;
  qs('#ifaceStatus').textContent = 'Yükleniyor...';

  mb.style.display = 'flex';

  apiGet(`/api/devices/interfaces.php?id=${id}`).then(d=>{
    const sel = qs('#ifaceSelect');
    sel.innerHTML = '';
    if(!d.ok){ qs('#ifaceStatus').textContent = (d.error || 'Hata'); return; }
    d.interfaces.forEach(n=>{
      const opt = document.createElement('option');
      opt.value = n; opt.textContent = n;
      sel.appendChild(opt);
    });
    qs('#ifaceStatus').textContent = `Toplam: ${d.interfaces.length}`;
  });
}

function closeInterfaceModal(){
  qs('#ifaceModal').style.display = 'none';
}

function saveInterface(){
  const id = qs('#ifaceSaveBtn').dataset.id;
  const iface = qs('#ifaceSelect').value;
  fetch('/api/devices/update-interface.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    credentials:'include',
    body: `id=${encodeURIComponent(id)}&interface=${encodeURIComponent(iface)}`
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.ok){
      qs(`#if_${id}`)?.textContent = iface;
      qs('#ifaceStatus').textContent = 'Kaydedildi';
    } else {
      qs('#ifaceStatus').textContent = d.error || 'Hata';
    }
  });
}
