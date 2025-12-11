<?php
require_once __DIR__ . '/DriverInterface.php';

class PlaceholderDriver implements DriverInterface {
  public function supports(string $brand): bool { return true; }

  public function getTraffic(string $ip, string $u, string $p, string $if): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
  public function getCpu(string $ip, string $u, string $p): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
  public function getVersion(string $ip, string $u, string $p): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
  public function getInterfaces(string $ip, string $u, string $p): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
}
