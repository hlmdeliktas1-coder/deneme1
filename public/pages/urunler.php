<?php
// public/import_models.php
// CSV -> brands/models import tool
// Usage: place this file in public/ ve tarayıcıdan açın (login gerekli)

require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

require_login(); // giriş gerekli

// helpers
function ensure_tables(PDO $db) {
    // create categories, brands, models if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        category_id INT NULL,
        UNIQUE KEY uq_brand (name, category_id),
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS models (
        id INT AUTO_INCREMENT PRIMARY KEY,
        brand_id INT NOT NULL,
        model_name VARCHAR(300) NOT NULL,
        UNIQUE KEY uq_model (brand_id, model_name),
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // try to add brand_id / model_id columns to devices if devices exists and columns missing
    $res = $db->query("SHOW TABLES LIKE 'devices'")->fetchColumn();
    if ($res) {
        $hasBrand = (bool)$db->query("SHOW COLUMNS FROM devices LIKE 'brand_id'")->fetch();
        if (!$hasBrand) {
            $db->exec("ALTER TABLE devices ADD COLUMN brand_id INT NULL");
        }
        $hasModel = (bool)$db->query("SHOW COLUMNS FROM devices LIKE 'model_id'")->fetch();
        if (!$hasModel) {
            $db->exec("ALTER TABLE devices ADD COLUMN model_id INT NULL");
        }
    }
}

function csv_preview($path, $maxRows = 20, $delimiter = ',') {
    $fh = fopen($path, 'r');
    if (!$fh) return ['error'=>'cannot_open'];
    $rows = [];
    $i = 0;
    while (($data = fgetcsv($fh, 0, $delimiter)) !== false && $i < $maxRows) {
        $rows[] = $data;
        $i++;
    }
    fclose($fh);
    return $rows;
}

function insert_category(PDO $db, string $name) : int {
    $name = trim($name);
    if ($name === '') return 0;
    $st = $db->prepare("SELECT id FROM categories WHERE name=:n LIMIT 1");
    $st->execute([':n'=>$name]);
    if ($r = $st->fetch(PDO::FETCH_ASSOC)) return (int)$r['id'];
    $ins = $db->prepare("INSERT INTO categories (name) VALUES (:n)");
    $ins->execute([':n'=>$name]);
    return (int)$db->lastInsertId();
}

function insert_brand(PDO $db, string $name, ?int $category_id = null) : int {
    $name = trim($name);
    $st = $db->prepare("SELECT id FROM brands WHERE name=:n AND (category_id = :cid OR (category_id IS NULL AND :cid IS NULL)) LIMIT 1");
    $st->execute([':n'=>$name, ':cid'=>$category_id]);
    if ($r = $st->fetch(PDO::FETCH_ASSOC)) return (int)$r['id'];
    $ins = $db->prepare("INSERT INTO brands (name, category_id) VALUES (:n, :cid)");
    $ins->execute([':n'=>$name, ':cid'=>$category_id]);
    return (int)$db->lastInsertId();
}

function insert_model(PDO $db, int $brand_id, string $model) : int {
    $model = trim($model);
    $st = $db->prepare("SELECT id FROM models WHERE brand_id=:bid AND model_name=:m LIMIT 1");
    $st->execute([':bid'=>$brand_id, ':m'=>$model]);
    if ($r = $st->fetch(PDO::FETCH_ASSOC)) return (int)$r['id'];
    $ins = $db->prepare("INSERT INTO models (brand_id, model_name) VALUES (:bid, :m)");
    $ins->execute([':bid'=>$brand_id, ':m'=>$model]);
    return (int)$db->lastInsertId();
}

// handle upload and process
$db = db();
ensure_tables($db);

$step = $_POST['step'] ?? 'form';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'upload') {
        // handle file upload and preview
        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $messages[] = ['type'=>'error','text'=>'CSV dosyası yüklenemedi.'];
        } else {
            $tmp = $_FILES['csv_file']['tmp_name'];
            $orig = basename($_FILES['csv_file']['name']);
            $dstDir = __DIR__ . '/../data/uploads';
            if (!is_dir($dstDir)) mkdir($dstDir, 0777, true);
            $newPath = $dstDir . '/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/','_', $orig);
            if (!move_uploaded_file($tmp, $newPath)) {
                $messages[] = ['type'=>'error','text'=>'Dosya taşınamadı.'];
            } else {
                // csv preview and store path in session
                $delim = $_POST['delimiter'] ?? ',';
                $preview = csv_preview($newPath, 30, $delim);
                session_start();
                $_SESSION['import_csv_path'] = $newPath;
                $_SESSION['import_csv_delim'] = $delim;
                $_SESSION['import_csv_original_name'] = $orig;
                // redirect to mapping step show preview
                header('Location: import_models.php?stage=map');
                exit;
            }
        }
    }

    if ($step === 'import') {
        // actual import step
        session_start();
        $path = $_SESSION['import_csv_path'] ?? null;
        $delim = $_SESSION['import_csv_delim'] ?? ',';
        $hasHeader = isset($_POST['has_header']) && $_POST['has_header'] === '1';
        $col_category = $_POST['col_category'] ?? '';
        $col_brand = $_POST['col_brand'] ?? '';
        $col_model = $_POST['col_model'] ?? '';

        if (!$path || !file_exists($path)) {
            $messages[] = ['type'=>'error','text'=>'Yüklü CSV bulunamadı. Lütfen tekrar yükleyin.'];
        } else {
            // read CSV and import
            $fh = fopen($path, 'r');
            if (!$fh) $messages[] = ['type'=>'error','text'=>'CSV açılamıyor.'];
            else {
                // read header if present
                $headers = [];
                if ($hasHeader) {
                    $headers = fgetcsv($fh, 0, $delim);
                }
                $rowNo = 0;
                $insertedBrands = 0;
                $insertedModels = 0;
                $skipped = 0;

                $db->beginTransaction();
                try {
                    while (($data = fgetcsv($fh, 0, $delim)) !== false) {
                        $rowNo++;
                        // get fields by index or header name
                        $getValue = function($key) use ($data, $headers) {
                            if ($key === '') return '';
                            if (is_numeric($key)) {
                                $i = (int)$key;
                                return $data[$i] ?? '';
                            } else {
                                // header name
                                $idx = array_search($key, $headers);
                                return $idx !== false ? ($data[$idx] ?? '') : '';
                            }
                        };

                        $catVal = trim($getValue($col_category));
                        $brandVal = trim($getValue($col_brand));
                        $modelVal = trim($getValue($col_model));

                        if ($brandVal === '' || $modelVal === '') {
                            $skipped++;
                            continue;
                        }

                        // category handling
                        $catId = null;
                        if ($catVal !== '') {
                            $catId = insert_category($db, $catVal);
                        }

                        // insert brand and model
                        $brandId = insert_brand($db, $brandVal, $catId);
                        if ($brandId) {
                            $insertedBrands += 0; // brand may already exist; count models separately
                            $modelId = insert_model($db, $brandId, $modelVal);
                            if ($modelId) $insertedModels++;
                        }
                    }
                    $db->commit();
                    $messages[] = ['type'=>'success','text'=>"Import tamamlandı. Satır: $rowNo, Ekl. model: $insertedModels, Atlanan: $skipped"];
                } catch (Exception $e) {
                    $db->rollBack();
                    $messages[] = ['type'=>'error','text'=>'İçe aktarım hatası: ' . $e->getMessage()];
                }
                fclose($fh);
            }
        }
    }
}

// UI: show upload form, mapping preview, or result
$stage = $_GET['stage'] ?? 'form';
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <title>CSV Import — Models</title>
  <link rel="stylesheet" href="/public/assets/css/app.css">
  <style>
    .wrap { max-width: 980px; margin: 24px auto; }
    table.preview { width:100%; border-collapse: collapse; }
    table.preview th, table.preview td { border:1px solid rgba(255,255,255,0.06); padding:6px 8px; text-align:left; font-size:13px; }
    .msg { padding:8px; margin:8px 0; border-radius:8px; }
    .msg.error { background:#381111; color:#ffdede; }
    .msg.success { background:#0b3a11; color:#bff0c4; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../_layout/header.php'; ?>
  <div class="wrap">
    <h2>CSV İçe Aktar — Marka / Model</h2>

    <?php foreach ($messages as $m): ?>
      <div class="msg <?= htmlspecialchars($m['type']) ?>"><?= htmlspecialchars($m['text']) ?></div>
    <?php endforeach; ?>

    <?php if ($stage === 'form'): ?>
      <div class="device-card" style="cursor:default;">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="step" value="upload">
          <label class="small">CSV Dosyası (UTF-8, virgül veya noktalı virgül ayracı):</label>
          <input class="input" type="file" name="csv_file" accept=".csv,text/csv" required>
          <div class="form-row">
            <label class="small">Ayracı seç (delimiter):</label>
            <select class="input" name="delimiter">
              <option value=",">Virgül (,)</option>
              <option value=";">Noktalı virgül (;)</option>
              <option value="\t">Tab (TSV)</option>
            </select>
          </div>
          <div class="form-row">
            <button class="btn" type="submit">Yükle ve Önizle</button>
          </div>
        </form>
        <div class="small">CSV sütunları: örn. <code>category,brand,model</code> veya <code>brand,model</code></div>
      </div>

    <?php elseif ($stage === 'map'): 
        // show preview and mapping UI
        session_start();
        $path = $_SESSION['import_csv_path'] ?? null;
        $delim = $_SESSION['import_csv_delim'] ?? ',';
        if (!$path || !file_exists($path)):
          echo "<div class='msg error'>Yüklü CSV bulunamadı. Lütfen tekrar yükleyin.</div>";
        else:
          $preview = csv_preview($path, 20, $delim);
          if (!$preview) { echo "<div class='msg error'>CSV okunamadı veya boş.</div>"; }
          else {
            // detect headers?
            $hasHeaderGuess = true;
            // simple heuristic: if first row contains non-numeric values and not all numeric, treat as header
            $first = $preview[0];
            $allNumeric = true; foreach($first as $c){ if (!is_numeric($c)) { $allNumeric = false; break; } }
            if ($allNumeric) $hasHeaderGuess = false;
            // build column list
            $colCount = max(array_map('count', $preview));
            $cols = [];
            for ($i=0;$i<$colCount;$i++){
                $cols[$i] = "Sütun #$i";
            }
            if ($hasHeaderGuess) {
                // override labels with header row values
                $hdr = $preview[0];
                for ($i=0;$i<count($hdr);$i++){
                    if (trim($hdr[$i]) !== '') $cols[$i] = $hdr[$i] . " (Sütun $i)";
                }
            }
            ?>
            <form method="post">
              <input type="hidden" name="step" value="import">
              <div class="form-row">
                <label class="small">CSV İlk satır başlık mı?</label>
                <select class="input" name="has_header">
                  <option value="1" <?= $hasHeaderGuess? 'selected':'' ?>>Evet</option>
                  <option value="0" <?= !$hasHeaderGuess? 'selected':'' ?>>Hayır</option>
                </select>
              </div>

              <div class="form-row">
                <label class="small">Kategori sütunu (varsa):</label>
                <select class="input" name="col_category">
                  <option value="">(yok)</option>
                  <?php foreach($cols as $k=>$label): ?>
                    <option value="<?= $k ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-row">
                <label class="small">Marka sütunu:</label>
                <select class="input" name="col_brand" required>
                  <?php foreach($cols as $k=>$label): ?>
                    <option value="<?= $k ?>" <?= $k===0? 'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-row">
                <label class="small">Model sütunu:</label>
                <select class="input" name="col_model" required>
                  <?php foreach($cols as $k=>$label): ?>
                    <option value="<?= $k ?>" <?= $k===1? 'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-row">
                <button class="btn" type="submit">İçe Aktar (Gerçek)</button>
                <a class="btn secondary" href="import_models.php">Başka CSV yükle</a>
              </div>

              <h4>Önizleme (ilk 20 satır)</h4>
              <table class="preview">
                <?php foreach($preview as $ridx=>$row): ?>
                  <tr>
                    <?php for($c=0;$c<$colCount;$c++): ?>
                      <td><?= htmlspecialchars($row[$c] ?? '') ?></td>
                    <?php endfor; ?>
                  </tr>
                <?php endforeach; ?>
              </table>
            </form>
          <?php
          }
        endif;
      endif; ?>

  </div>
  <?php include __DIR__ . '/../_layout/footer.php'; ?>
</body>
</html>
