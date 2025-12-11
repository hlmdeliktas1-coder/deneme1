<?php
interface DriverInterface {
  public function supports(string $brand): bool;
  public function getTraffic(string $ip, string $user, string $pass, string $interface): array;
  public function getCpu(string $ip, string $user, string $pass): array;
  public function getVersion(string $ip, string $user, string $pass): array;
  public function getInterfaces(string $ip, string $user, string $pass): array;
}
