  </div>

  <!-- Interface seçimi modalı (kart sağ üst ayar) -->
  <div id="ifaceModal" class="modal-backdrop" style="display:none" onclick="if(event.target===this) closeInterfaceModal();">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <div id="ifaceModalTitle" class="kbd">Interface seçimi</div>
        <button class="btn secondary" onclick="closeInterfaceModal()">Kapat</button>
      </div>
      <div class="modal-body">
        <div class="form-row">
          <label class="small">Interface:</label>
          <select id="ifaceSelect" class="input"></select>
        </div>
        <div class="form-row" style="display:flex; gap:10px; align-items:center; justify-content: space-between;">
          <div id="ifaceStatus" class="small"></div>
          <button id="ifaceSaveBtn" class="btn" onclick="saveInterface()">Kaydet</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
