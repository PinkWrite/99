<?php
declare(strict_types=1);

final class Auth
{
    private App $app;
    private ?array $user = null;

    private const RANK = [
        'writer' => 1,
        'observer' => 2,
        'editor' => 3,
        'supervisor' => 4,
        'admin' => 5,
        'superintendent' => 6,
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        if (!empty($_SESSION['user_id']) && $this->app->db) {
            $u = $this->app->user->find((int) $_SESSION['user_id']);
            if ($u && $u['status'] === 'active') {
                $this->user = $u;
            } else {
                $this->clear();
            }
        }
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function id(): int
    {
        return (int) ($this->user['id'] ?? 0);
    }

    public function type(): string
    {
        return (string) ($this->user['type'] ?? '');
    }

    public function is(string $type): bool
    {
        return $this->type() === $type;
    }

    public function atLeast(string $type): bool
    {
        $have = self::RANK[$this->type()] ?? 0;
        $need = self::RANK[$type] ?? 99;
        return $have >= $need;
    }

    public function facilityId(): ?int
    {
        if (!$this->user) {
            return null;
        }
        if ($this->is('superintendent') && !empty($_SESSION['facility_id'])) {
            return (int) $_SESSION['facility_id'];
        }
        $fid = $this->user['facility_id'] ?? null;
        return $fid === null ? null : (int) $fid;
    }

    public function requireUser(): array
    {
        if (!$this->user) {
            if ($this->app->isAjax()) {
                $this->app->json(['ok' => false, 'error' => 'auth'], 401);
            }
            $this->app->redirect('login.php');
        }
        return $this->user;
    }

    public function requireRole(string ...$types): array
    {
        $u = $this->requireUser();
        if ($this->is('superintendent')) {
            return $u;
        }
        foreach ($types as $t) {
            if ($this->is($t) || $this->atLeast($t) && in_array($t, ['supervisor', 'admin', 'editor'], true)) {
                // fallthrough handled below
            }
        }
        foreach ($types as $t) {
            if ($this->is($t)) {
                return $u;
            }
        }
        // Admin may use editor/observer/supervisor tools.
        if ($this->is('admin') || $this->is('supervisor')) {
            foreach ($types as $t) {
                if (in_array($t, ['editor', 'observer', 'supervisor', 'admin', 'writer'], true)) {
                    return $u;
                }
            }
        }
        if ($this->app->isAjax()) {
            $this->app->json(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $this->app->redirect('');
        return $u;
    }

    public function login(string $username, string $pass, string $ip): string
    {
        if ($this->app->clickathon->blocked($ip) || $this->app->clickathon->tooManyTries()) {
            return 'blocked';
        }
        if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $username)) {
            $this->app->clickathon->fail($ip, $username);
            return 'bad';
        }
        $u = $this->app->user->findByUsername($username);
        if (!$u || empty($u['pass']) || !password_verify($pass, $u['pass'])) {
            $this->app->clickathon->fail($ip, $username);
            return 'bad';
        }
        if ($u['status'] !== 'active') {
            return 'inactive';
        }
        $this->app->clickathon->clear();
        session_regenerate_id(true);
        if (!empty($u['totp_enabled'])) {
            $_SESSION['pending_2fa'] = (int) $u['id'];
            return 'totp';
        }
        $this->establish($u);
        return 'ok';
    }

    public function finishTotp(string $code): bool
    {
        $id = (int) ($_SESSION['pending_2fa'] ?? 0);
        if ($id < 1) {
            return false;
        }
        $u = $this->app->user->find($id);
        if (!$u || empty($u['totp_secret'])) {
            return false;
        }
        $this->app->need('totp');
        if (!$this->app->totp->verify($u['totp_secret'], $code)) {
            return false;
        }
        unset($_SESSION['pending_2fa']);
        $this->establish($u);
        return true;
    }

    public function establish(array $u): void
    {
        $_SESSION['user_id'] = (int) $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['type'] = $u['type'];
        $_SESSION['name'] = $u['name'];
        $this->user = $u;
        unset($_SESSION['pending_2fa']);
    }

    public function logout(): void
    {
        $this->clear();
        session_regenerate_id(true);
    }

    private function clear(): void
    {
        $this->user = null;
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['type'], $_SESSION['name'], $_SESSION['pending_2fa'], $_SESSION['facility_id']);
    }

    public function canEmailReset(array $u): bool
    {
        return ($u['type'] ?? '') !== 'superintendent';
    }

    public function defaultNotifyPrefs(string $type): array
    {
        $all = Notify::keysFor($type);
        $inapp = [];
        $email = [];
        foreach ($all as $k) {
            $inapp[$k] = true;
            $email[$k] = false;
        }
        return ['inapp' => $inapp, 'email' => $email];
    }
}
