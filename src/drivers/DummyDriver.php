<?php
require_once __DIR__ . '/DriverInterface.php';

class DummyDriver implements DriverInterface {
  public function supports(string $brand): bool { return true; }

  public function getTraffic(string $ip, string $user, string $pass, string $interface): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
  public function getCpu(string $ip, string $user, string $pass): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
  public function getVersion(string $ip, string $user, string $pass): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
  public function getInterfaces(string $ip, string $user, string $pass): array {
    return ['ok'=>false,'error'=>'driver_not_implemented'];
  }
}
