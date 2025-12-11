<?php
require_once __DIR__ . '/db.php';

class DeviceRepository {
  public function listByCategory(string $category, int $page=1, int $perPage=12): array {
    $offset = max(0, ($page-1) * $perPage);
    $sql = "SELECT * FROM devices
            WHERE category = :cat AND show_on_dashboard = 1
            ORDER BY name ASC
            LIMIT :lim OFFSET :off";
    $st = db()->prepare($sql);
    $st->bindValue(':cat', $category);
    $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $st->bindValue(':off', $offset, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
  }

  public function countByCategory(string $category): int {
    $st = db()->prepare("SELECT COUNT(*) FROM devices WHERE category=:cat AND show_on_dashboard=1");
    $st->execute([':cat'=>$category]);
    return (int)$st->fetchColumn();
  }

  public function get(int $id): ?array {
    $st = db()->prepare("SELECT * FROM devices WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public function insert(array $d): int {
    $sql = "INSERT INTO devices (name, ip, username, password, category, brand, model, interface, show_on_dashboard)
            VALUES (:name,:ip,:username,:password,:category,:brand,:model,:interface,:show_on_dashboard)";
    $st = db()->prepare($sql);
    $st->execute($d);
    return (int)db()->lastInsertId();
  }

  public function update(int $id, array $d): void {
    $d['id'] = $id;
    $sql = "UPDATE devices SET
              name=:name, ip=:ip, username=:username, password=:password,
              category=:category, brand=:brand, model=:model,
              interface=:interface, show_on_dashboard=:show_on_dashboard
            WHERE id=:id";
    $st = db()->prepare($sql);
    $st->execute($d);
  }

  public function updateInterface(int $id, string $iface): void {
    $st = db()->prepare("UPDATE devices SET interface=:ifc WHERE id=:id");
    $st->execute([':ifc'=>$iface, ':id'=>$id]);
  }

  public function delete(int $id): void {
    $st = db()->prepare("DELETE FROM devices WHERE id=:id");
    $st->execute([':id'=>$id]);
  }
}
