<?php
declare(strict_types=1);

final class Csrf
{
    public function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return (string) $_SESSION['_csrf'];
    }

    public function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public function check(?string $posted = null): bool
    {
        $posted = $posted ?? (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '');
        $have = (string) ($_SESSION['_csrf'] ?? '');
        return $have !== '' && hash_equals($have, $posted);
    }

    public function require(): void
    {
        if (!$this->check()) {
            http_response_code(400);
            echo 'Bad request.';
            exit;
        }
    }
}
