<?php
declare(strict_types=1);

final class FacilityRepo
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function find(int $id): ?array
    {
        return $this->app->db->one('SELECT * FROM facilities WHERE id = ?', [$id]);
    }

    public function all(): array
    {
        return $this->app->db->all('SELECT * FROM facilities ORDER BY name');
    }

    public function create(string $name, string $code = ''): int
    {
        $this->app->db->run(
            'INSERT INTO facilities (name, code, status) VALUES (?,?,\'open\')',
            [$name, $code !== '' ? $code : null]
        );
        return (int) $this->app->db->lastId();
    }

    public function setStatus(int $id, string $status): void
    {
        $this->app->db->run('UPDATE facilities SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function rename(int $id, string $name, string $code): void
    {
        $this->app->db->run('UPDATE facilities SET name = ?, code = ? WHERE id = ?', [$name, $code, $id]);
    }
}
