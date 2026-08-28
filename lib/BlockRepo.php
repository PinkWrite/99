<?php
declare(strict_types=1);

final class BlockRepo
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function find(int $id): ?array
    {
        return $this->app->db->one('SELECT * FROM blocks WHERE id = ?', [$id]);
    }

    public function forEditor(int $editorId, bool $openOnly = true): array
    {
        $sql = 'SELECT * FROM blocks WHERE editor_id = ?';
        $params = [$editorId];
        if ($openOnly) {
            $sql .= ' AND status = \'open\'';
        }
        $sql .= ' ORDER BY name';
        return $this->app->db->all($sql, $params);
    }

    public function forFacility(?int $facilityId, bool $openOnly = false): array
    {
        $sql = 'SELECT * FROM blocks WHERE 1=1';
        $params = [];
        if ($facilityId) {
            $sql .= ' AND facility_id = ?';
            $params[] = $facilityId;
        }
        if ($openOnly) {
            $sql .= ' AND status = \'open\'';
        }
        $sql .= ' ORDER BY name';
        return $this->app->db->all($sql, $params);
    }

    public function create(array $row): int
    {
        $this->app->db->run(
            'INSERT INTO blocks (facility_id, editor_id, name, code, status) VALUES (?,?,?,?,?)',
            [
                $row['facility_id'] ?? null,
                $row['editor_id'],
                $row['name'],
                $row['code'] ?? null,
                $row['status'] ?? 'open',
            ]
        );
        return (int) $this->app->db->lastId();
    }

    public function save(int $id, array $row): void
    {
        $this->app->db->run(
            'UPDATE blocks SET name = ?, code = ?, status = ? WHERE id = ?',
            [$row['name'], $row['code'] ?? null, $row['status'] ?? 'open', $id]
        );
    }

    public function named(array $block): string
    {
        $n = $block['name'] ?? '';
        $c = $block['code'] ?? '';
        return $c !== '' && $c !== null ? $n . ' (' . $c . ')' : $n;
    }
}
