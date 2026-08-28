<?php
declare(strict_types=1);

final class Totp
{
    public function secret(): string
    {
        $bytes = random_bytes(20);
        return $this->b32($bytes);
    }

    public function uri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $account);
        return 'otpauth://totp/' . $label
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $t = (int) floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals($this->at($secret, $t + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public function at(string $secret, int $counter): string
    {
        $key = $this->unb32($secret);
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $off = ord($hash[19]) & 0x0f;
        $trunc = unpack('N', substr($hash, $off, 4))[1] & 0x7fffffff;
        return str_pad((string) ($trunc % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function b32(string $data): string
    {
        $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $out .= $alpha[bindec($chunk)];
        }
        return $out;
    }

    private function unb32(string $b32): string
    {
        $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
        $bits = '';
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $bits .= str_pad(decbin(strpos($alpha, $b32[$i])), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }
}
