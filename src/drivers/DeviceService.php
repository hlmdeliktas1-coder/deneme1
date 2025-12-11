<?php
require_once __DIR__ . '/DeviceRepository.php';
require_once __DIR__ . '/Drivers/MikrotikDriver.php';
require_once __DIR__ . '/Drivers/DummyDriver.php';

class DeviceService {
  private DeviceRepository $repo;
  private array $drivers;

  public function __construct() {
    $this->repo = new DeviceRepository();
    $this->drivers = [ new MikrotikDriver(), new DummyDriver() ];
  }

  private function driverFor(array $device): DriverInterface {
    $brand = $device['brand'] ?? '';
    foreach ($this->drivers as $d) {
      if ($d->supports($brand)) return $d;
    }
    return new DummyDriver();
  }

  public function device(int $id): ?array {
    return $this->repo->get($id);
  }

  public function traffic(int $id): array {
    $d = $this->device($id);
    if (!$d) return ['ok'=>false,'error'=>'not_found'];
    $drv = $this->driverFor($d);
    return $drv->getTraffic($d['ip'], $d['username'], $d['password'], $d['interface']);
  }

  public function cpu(int $id): array {
    $d = $this->device($id);
    if (!$d) return ['ok'=>false,'error'=>'not_found'];
    $drv = $this->driverFor($d);
    return $drv->getCpu($d['ip'], $d['username'], $d['password']);
  }

  public function version(int $id): array {
    $d = $this->device($id);
    if (!$d) return ['ok'=>false,'error'=>'not_found'];
    $drv = $this->driverFor($d);
    return $drv->getVersion($d['ip'], $d['username'], $d['password']);
  }

  public function interfaces(int $id): array {
    $d = $this->device($id);
    if (!$d) return ['ok'=>false,'error'=>'not_found'];
    $drv = $this->driverFor($d);
    return $drv->getInterfaces($d['ip'], $d['username'], $d['password']);
  }
}
