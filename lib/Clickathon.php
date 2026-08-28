<?php
declare(strict_types=1);

final class Clickathon
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function blocked(string $ip): bool
    {
        if ($ip === '' || !$this->app->db) {
            return false;
        }
        $since = time() - 3600;
        $n = $this->app->db->val(
            'SELECT COUNT(*) FROM clickathon WHERE ip = ? AND time_epoch > ? AND unlocked IS NULL',
            [$ip, $since]
        );
        return (int) $n > 0;
    }

    public function fail(string $ip, string $username): void
    {
        $_SESSION['clickathon_count'] = (int) ($_SESSION['clickathon_count'] ?? 0) + 1;
        $_SESSION['clickathon_time'] = time();
        $_SESSION['clickathon_usernames'] = (($_SESSION['clickathon_usernames'] ?? '') === '')
            ? $username
            : $_SESSION['clickathon_usernames'] . ', ' . $username;
        if ((int) $_SESSION['clickathon_count'] > 5 && $this->app->db) {
            $this->app->db->run(
                'INSERT INTO clickathon (username_list, ip, time_epoch) VALUES (?,?,?)',
                [(string) $_SESSION['clickathon_usernames'], $ip, time()]
            );
        }
    }

    public function clear(): void
    {
        unset($_SESSION['clickathon_count'], $_SESSION['clickathon_usernames'], $_SESSION['clickathon_time']);
    }

    public function tooManyTries(): bool
    {
        $count = (int) ($_SESSION['clickathon_count'] ?? 0);
        $t = (int) ($_SESSION['clickathon_time'] ?? 0);
        return $count > 5 && $t > (time() - 3600);
    }

    public function recentFails(): array
    {
        if (!$this->app->db) {
            return [];
        }
        return $this->app->db->all(
            'SELECT id, username_list, ip, time_stamp, unlocked FROM clickathon ORDER BY id DESC LIMIT 100'
        );
    }

    public function unlock(int $id): void
    {
        $this->app->db->run('UPDATE clickathon SET unlocked = NOW() WHERE id = ?', [$id]);
    }
}
