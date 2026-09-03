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

    public function setStatus(int $id, string $status): void
    {
        $st = $status === 'closed' ? 'closed' : 'open';
        $this->app->db->run('UPDATE blocks SET status = ? WHERE id = ?', [$st, $id]);
    }

    public function delete(int $id): void
    {
        if ($this->app->db->columnExists('writs', 'block_id')) {
            $this->app->db->run('UPDATE writs SET block_id = 0 WHERE block_id = ?', [$id]);
        }
        if ($this->app->db->tableExists('tests') && $this->app->db->columnExists('tests', 'block_id')) {
            $this->app->db->run('UPDATE tests SET block_id = 0 WHERE block_id = ?', [$id]);
        }
        if ($this->app->db->tableExists('notes') && $this->app->db->columnExists('notes', 'editor_set_block')) {
            $this->app->db->run('UPDATE notes SET editor_set_block = 0 WHERE editor_set_block = ?', [$id]);
        }
        if ($this->app->db->columnExists('users', 'blocks_json')) {
            foreach ($this->app->db->all('SELECT id, blocks_json FROM users') as $u) {
                $have = array_map('intval', json_arr($u['blocks_json'] ?? '[]'));
                $next = array_values(array_filter($have, static fn ($b) => (int) $b !== $id));
                if ($next !== $have) {
                    $this->app->db->run('UPDATE users SET blocks_json = ? WHERE id = ?', [json_enc($next), (int) $u['id']]);
                }
            }
        }
        $this->app->db->run('DELETE FROM blocks WHERE id = ?', [$id]);
    }
}
