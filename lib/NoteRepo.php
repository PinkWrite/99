<?php
declare(strict_types=1);

final class NoteRepo
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function find(int $id): ?array
    {
        return $this->app->db->one('SELECT * FROM notes WHERE id = ?', [$id]);
    }

    public function create(array $row): int
    {
        $this->app->db->run(
            'INSERT INTO notes (type, status, writer_id, editor_id, editor_set_writer_id, editor_set_block, body, pinned, seen_writer, seen_observer)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $row['type'] ?? 'note',
                $row['status'] ?? 'live',
                $row['writer_id'] ?? null,
                $row['editor_id'] ?? null,
                $row['editor_set_writer_id'] ?? 0,
                $row['editor_set_block'] ?? 0,
                $row['body'] ?? '',
                !empty($row['pinned']) ? 1 : 0,
                $row['seen_writer'] ?? 'new',
                $row['seen_observer'] ?? 'new',
            ]
        );
        return (int) $this->app->db->lastId();
    }

    public function saveBody(int $id, string $body, array $extra = []): void
    {
        $sql = 'UPDATE notes SET body = ?';
        $params = [$body];
        if (isset($extra['editor_set_writer_id'])) {
            $sql .= ', editor_set_writer_id = ?';
            $params[] = $extra['editor_set_writer_id'];
        }
        if (isset($extra['editor_set_block'])) {
            $sql .= ', editor_set_block = ?';
            $params[] = $extra['editor_set_block'];
        }
        if (isset($extra['type'])) {
            $sql .= ', type = ?';
            $params[] = $extra['type'];
        }
        if (isset($extra['status'])) {
            $sql .= ', status = ?';
            $params[] = $extra['status'];
        }
        if (array_key_exists('pinned', $extra)) {
            $sql .= ', pinned = ?';
            $params[] = $extra['pinned'] ? 1 : 0;
        }
        $sql .= ', seen_writer = \'new\' WHERE id = ?';
        $params[] = $id;
        $this->app->db->run($sql, $params);
    }

    public function markRead(int $id): void
    {
        $this->app->db->run('UPDATE notes SET seen_writer = \'read\' WHERE id = ?', [$id]);
    }

    public function forWriter(int $writerId, string $type = 'note'): array
    {
        return $this->app->db->all(
            'SELECT * FROM notes WHERE writer_id = ? AND type = ? AND status = \'live\' ORDER BY pinned DESC, id DESC',
            [$writerId, $type]
        );
    }

    public function memosForWriter(int $writerId): array
    {
        return $this->app->db->all(
            'SELECT * FROM notes WHERE type = \'memo\' AND status = \'live\'
             AND (editor_set_writer_id = ? OR writer_id = ?)
             ORDER BY pinned DESC, id DESC',
            [$writerId, $writerId]
        );
    }

    public function memosForEditor(int $editorId): array
    {
        return $this->app->db->all(
            'SELECT * FROM notes WHERE editor_id = ? AND type IN (\'memo\',\'task\') AND status = \'live\' ORDER BY id DESC',
            [$editorId]
        );
    }

    public function copyNoteToMemo(int $noteId, int $editorId): int
    {
        $n = $this->find($noteId);
        if (!$n) {
            throw new RuntimeException('Note not found');
        }
        return $this->create([
            'type' => 'memo',
            'status' => 'live',
            'editor_id' => $editorId,
            'editor_set_writer_id' => $n['writer_id'] ?? 0,
            'body' => $n['body'],
        ]);
    }
}
