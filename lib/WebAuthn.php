<?php
declare(strict_types=1);

final class WebAuthn
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function rpId(): string
    {
        $host = (string) ($this->app->config['host'] ?? '');
        $host = preg_replace('#/.*$#', '', $host) ?? $host;
        return $host;
    }

    public function challenge(): string
    {
        $c = random_bytes(32);
        $_SESSION['wa_challenge'] = base64_encode($c);
        return $this->b64u($c);
    }

    public function optionsCreate(array $user): array
    {
        return [
            'challenge' => $this->challenge(),
            'rp' => ['name' => $this->app->title(), 'id' => $this->rpId()],
            'user' => [
                'id' => $this->b64u(pack('N', (int) $user['id'])),
                'name' => $user['username'],
                'displayName' => $user['name'],
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'authenticatorSelection' => [
                'residentKey' => 'preferred',
                'userVerification' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
    }

    public function optionsGet(?int $userId = null): array
    {
        $allow = [];
        if ($userId) {
            foreach ($this->list($userId) as $row) {
                $allow[] = [
                    'type' => 'public-key',
                    'id' => $this->b64u($row['credential_id']),
                ];
            }
        }
        $opt = [
            'challenge' => $this->challenge(),
            'rpId' => $this->rpId(),
            'timeout' => 60000,
            'userVerification' => 'preferred',
        ];
        if ($allow) {
            $opt['allowCredentials'] = $allow;
        }
        return $opt;
    }

    public function register(int $userId, string $credIdB64, string $spkiB64, string $name = ''): void
    {
        $id = $this->b64d($credIdB64);
        $spki = $this->b64d($spkiB64);
        $pem = $this->spkiToPem($spki);
        $this->app->db->run(
            'INSERT INTO passkeys (user_id, credential_id, public_key, name) VALUES (?,?,?,?)',
            [$userId, $id, $pem, $name !== '' ? $name : 'Passkey']
        );
    }

    public function assert(string $credIdB64, string $clientDataB64, string $authDataB64, string $sigB64): ?array
    {
        $id = $this->b64d($credIdB64);
        $row = $this->app->db->one('SELECT * FROM passkeys WHERE credential_id = ?', [$id]);
        if (!$row) {
            return null;
        }
        $client = $this->b64d($clientDataB64);
        $auth = $this->b64d($authDataB64);
        $sig = $this->b64d($sigB64);
        $data = json_decode($client, true);
        if (!is_array($data) || ($data['type'] ?? '') !== 'webauthn.get') {
            return null;
        }
        $want = (string) ($_SESSION['wa_challenge'] ?? '');
        $got = strtr((string) ($data['challenge'] ?? ''), '-_', '+/');
        $want = strtr($want, '-_', '+/');
        if ($want === '' || !hash_equals(rtrim($want, '='), rtrim($got, '='))) {
            return null;
        }
        $signed = $auth . hash('sha256', $client, true);
        $pem = $row['public_key'];
        if (strlen($sig) === 64) {
            $sig = $this->ecdsaDer($sig);
        }
        $ok = openssl_verify($signed, $sig, $pem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return null;
        }
        unset($_SESSION['wa_challenge']);
        $this->app->db->run(
            'UPDATE passkeys SET sign_count = sign_count + 1 WHERE id = ?',
            [$row['id']]
        );
        return $this->app->user->find((int) $row['user_id']);
    }

    public function list(int $userId): array
    {
        return $this->app->db->all('SELECT * FROM passkeys WHERE user_id = ? ORDER BY id', [$userId]);
    }

    public function rename(int $id, int $userId, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'Passkey';
        }
        if (function_exists('mb_substr')) {
            $name = mb_substr($name, 0, 80);
        } else {
            $name = substr($name, 0, 80);
        }
        $this->app->db->run(
            'UPDATE passkeys SET name = ? WHERE id = ? AND user_id = ?',
            [$name, $id, $userId]
        );
    }

    public function delete(int $id, int $userId): void
    {
        $this->app->db->run('DELETE FROM passkeys WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    private function spkiToPem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function ecdsaDer(string $raw): string
    {
        $h = intdiv(strlen($raw), 2);
        return $this->derSeq($this->derInt(substr($raw, 0, $h)) . $this->derInt(substr($raw, $h)));
    }

    private function derInt(string $n): string
    {
        $n = ltrim($n, "\x00");
        if ($n === '' || (ord($n[0]) & 0x80)) {
            $n = "\x00" . $n;
        }
        return "\x02" . chr(strlen($n)) . $n;
    }

    private function derSeq(string $inner): string
    {
        $len = strlen($inner);
        if ($len < 128) {
            return "\x30" . chr($len) . $inner;
        }
        return "\x30\x81" . chr($len) . $inner;
    }

    public function b64u(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public function b64d(string $b64): string
    {
        $b64 = strtr($b64, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        return (string) base64_decode($b64, true);
    }
}
