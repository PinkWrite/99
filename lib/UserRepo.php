<?php
declare(strict_types=1);

final class UserRepo
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function find(int $id): ?array
    {
        return $this->app->db->one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->app->db->one('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->app->db->one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function create(array $row): int
    {
        $prefs = $row['notify_prefs'] ?? json_enc($this->app->auth
            ? $this->app->auth->defaultNotifyPrefs($row['type'])
            : ['inapp' => [], 'email' => []]);
        if (is_array($prefs)) {
            $prefs = json_enc($prefs);
        }
        $this->app->db->run(
            'INSERT INTO users (type, facility_id, username, email, name, project, level, groups_json, blocks_json, observing_json, editor_id, status, pass, notify_prefs)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $row['type'],
                $row['facility_id'] ?? null,
                $row['username'],
                $row['email'],
                $row['name'],
                $row['project'] ?? null,
                $row['level'] ?? 0,
                $row['groups_json'] ?? '[]',
                $row['blocks_json'] ?? '[]',
                $row['observing_json'] ?? '[]',
                $row['editor_id'] ?? null,
                $row['status'] ?? 'active',
                $row['pass'],
                $prefs,
            ]
        );
        return (int) $this->app->db->lastId();
    }

    public function setPassword(int $id, string $plain): void
    {
        $this->app->db->run('UPDATE users SET pass = ? WHERE id = ?', [
            password_hash($plain, PASSWORD_DEFAULT),
            $id,
        ]);
    }

    public function savePrefs(int $id, array $prefs): void
    {
        $this->app->db->run('UPDATE users SET notify_prefs = ? WHERE id = ?', [json_enc($prefs), $id]);
    }

    public function setTotp(int $id, ?string $secret, bool $enabled): void
    {
        $this->app->db->run('UPDATE users SET totp_secret = ?, totp_enabled = ? WHERE id = ?', [
            $secret,
            $enabled ? 1 : 0,
            $id,
        ]);
    }

    public function listByFacility(?int $facilityId, ?string $type = null): array
    {
        if ($facilityId) {
            if ($type) {
                return $this->app->db->all(
                    'SELECT * FROM users WHERE facility_id = ? AND type = ? ORDER BY name',
                    [$facilityId, $type]
                );
            }
            return $this->app->db->all(
                'SELECT * FROM users WHERE facility_id = ? ORDER BY type, name',
                [$facilityId]
            );
        }
        if ($type) {
            return $this->app->db->all('SELECT * FROM users WHERE type = ? ORDER BY name', [$type]);
        }
        return $this->app->db->all('SELECT * FROM users ORDER BY type, name');
    }

    public function writersForEditor(int $editorId): array
    {
        return $this->app->db->all(
            'SELECT * FROM users WHERE editor_id = ? AND type = \'writer\' AND status = \'active\' ORDER BY name',
            [$editorId]
        );
    }

    public function blocksOf(array $user): array
    {
        $ids = json_arr($user['blocks_json'] ?? '[]');
        $out = [];
        foreach ($ids as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                $out[] = (int) $id;
            }
        }
        return $out;
    }

    public function setBlocks(int $id, array $blockIds): void
    {
        $this->app->db->run('UPDATE users SET blocks_json = ? WHERE id = ?', [json_enc(array_values($blockIds)), $id]);
    }

    public function setObserving(int $id, array $writerIds): void
    {
        $this->app->db->run('UPDATE users SET observing_json = ? WHERE id = ?', [json_enc(array_values($writerIds)), $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->app->db->run('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function setFacility(int $id, ?int $facilityId): void
    {
        $this->app->db->run('UPDATE users SET facility_id = ? WHERE id = ?', [$facilityId, $id]);
    }

    public function createReset(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->app->db->run(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 1 HOUR))',
            [$userId, hash('sha256', $token)]
        );
        return $token;
    }

    public function consumeReset(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $row = $this->app->db->one(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
            [$hash]
        );
        if (!$row) {
            return null;
        }
        $this->app->db->run('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$row['id']]);
        return $this->find((int) $row['user_id']);
    }

    public function prefs(array $user): array
    {
        $p = json_arr($user['notify_prefs'] ?? []);
        if (!isset($p['inapp'])) {
            $p = $this->app->auth->defaultNotifyPrefs($user['type']);
        }
        return $p;
    }
}
