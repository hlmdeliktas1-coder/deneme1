<?php
// Basit ve bağımsız RouterOS API istemcisi (SSL/TCP) — Mikrotik için yeterli.
class RouterOSApi {
  private $socket;
  private $debug = false;

  public function connect(string $host, string $user, string $pass, int $port = 8728, int $timeout = 5): bool {
    $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$this->socket) return false;

    stream_set_timeout($this->socket, $timeout);

    $this->write('/login');
    $this->write('=name='.$user);
    $this->write('=password='.$pass);

    $res = $this->read();
    return is_array($res);
  }

  public function disconnect(): void {
    if ($this->socket) {
      fclose($this->socket);
      $this->socket = null;
    }
  }

  public function comm(string $command, array $params = []): array {
    $this->write($command);
    foreach ($params as $k => $v) {
      $this->write('='.$k.'='.$v);
    }
    return $this->read();
  }

  private function write(string $sentence): void {
    $len = strlen($sentence);
    $this->writeLength($len);
    fwrite($this->socket, $sentence, $len);
  }

  private function writeLength(int $len): void {
    if ($len < 0x80) {
      fwrite($this->socket, chr($len));
    } elseif ($len < 0x4000) {
      $len |= 0x8000;
      fwrite($this->socket, chr(($len >> 8) & 0xFF));
      fwrite($this->socket, chr($len & 0xFF));
    } elseif ($len < 0x200000) {
      $len |= 0xC00000;
      fwrite($this->socket, chr(($len >> 16) & 0xFF));
      fwrite($this->socket, chr(($len >> 8) & 0xFF));
      fwrite($this->socket, chr($len & 0xFF));
    } else {
      $len |= 0xE0000000;
      fwrite($this->socket, chr(($len >> 24) & 0xFF));
      fwrite($this->socket, chr(($len >> 16) & 0xFF));
      fwrite($this->socket, chr(($len >> 8) & 0xFF));
      fwrite($this->socket, chr($len & 0xFF));
    }
  }

  private function read(): array {
    $replies = [];
    $reply = [];

    while (true) {
      $word = $this->readWord();
      if ($word === '') break;

      if ($word === '!done') {
        if (!empty($reply)) { $replies[] = $reply; $reply = []; }
        break;
      }
      if ($word === '!re') continue;
      if (strpos($word, '=') === 0) {
        $eq = strpos($word, '=', 1);
        if ($eq !== false) {
          $k = substr($word, 1, $eq-1);
          $v = substr($word, $eq+1);
          $reply[$k] = $v;
        }
      }
    }
    return $replies;
  }

  private function readWord(): string {
    $len = $this->readLength();
    if ($len === 0) return '';
    return fread($this->socket, $len) ?: '';
  }

  private function readLength(): int {
    $byte = ord(fread($this->socket, 1));
    if ($byte < 0x80) return $byte;
    if (($byte & 0xC0) === 0x80) {
      $byte2 = ord(fread($this->socket, 1));
      return (($byte & ~0xC0) << 8) + $byte2;
    }
    if (($byte & 0xE0) === 0xC0) {
      $b2 = ord(fread($this->socket, 1));
      $b3 = ord(fread($this->socket, 1));
      return (($byte & ~0xE0) << 16) + ($b2 << 8) + $b3;
    }
    $b2 = ord(fread($this->socket, 1));
    $b3 = ord(fread($this->socket, 1));
    $b4 = ord(fread($this->socket, 1));
    return (($byte & ~0xF0) << 24) + ($b2 << 16) + ($b3 << 8) + $b4;
  }
}
