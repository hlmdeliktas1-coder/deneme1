<?php
require_once __DIR__ . '/DriverInterface.php';
require_once __DIR__ . '/RouterOSApi.php';

class MikrotikDriver implements DriverInterface {

  public function supports(string $brand): bool {
    $b = strtolower(trim($brand));
    return in_array($b, ['mikrotik','routeros','router-os','router os'], true);
  }

  public function getTraffic(string $ip, string $user, string $pass, string $interface): array {
    $api = new RouterOSApi();
    if (!$api->connect($ip,$user,$pass)) return ['ok'=>false,'error'=>'connect_failed'];

    $res = $api->comm('/interface/monitor-traffic', ['interface'=>$interface, 'once'=>'']);
    $api->disconnect();

    $rx = (float)($res[0]['rx-bits-per-second'] ?? 0);
    $tx = (float)($res[0]['tx-bits-per-second'] ?? 0);

    return ['ok'=>true, 'rx_mbps'=>round($rx/1_000_000, 2), 'tx_mbps'=>round($tx/1_000_000, 2)];
  }

  public function getCpu(string $ip, string $user, string $pass): array {
    $api = new RouterOSApi();
    if (!$api->connect($ip,$user,$pass)) return ['ok'=>false,'error'=>'connect_failed'];

    $res = $api->comm('/system/resource/print');
    $api->disconnect();

    $cpu = isset($res[0]['cpu-load']) ? (int)$res[0]['cpu-load'] : null;
    return ['ok'=>true, 'cpu_percent'=>$cpu];
  }

  public function getVersion(string $ip, string $user, string $pass): array {
    $api = new RouterOSApi();
    if (!$api->connect($ip,$user,$pass)) return ['ok'=>false,'error'=>'connect_failed'];

    $cur = $api->comm('/system/package/print');
    $upd = $api->comm('/system/package/update/print');
    $api->disconnect();

    $current = $cur[0]['version'] ?? null;
    $available = $upd[0]['latest-version'] ?? null; // bazı RouterOS sürümlerinde alan adı farklı olabilir

    return ['ok'=>true, 'current'=>$current, 'available'=>$available];
  }

  public function getInterfaces(string $ip, string $user, string $pass): array {
    $api = new RouterOSApi();
    if (!$api->connect($ip,$user,$pass)) return ['ok'=>false,'error'=>'connect_failed'];

    $res = $api->comm('/interface/print');
    $api->disconnect();

    $ifs = [];
    foreach ($res as $r) if (!empty($r['name'])) $ifs[] = $r['name'];
    return ['ok'=>true, 'interfaces'=>$ifs];
  }
}
