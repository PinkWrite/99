<?php
declare(strict_types=1);

final class Notify
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public static function catalog(): array
    {
        return [
            'new_writ' => 'New writ',
            'edited_writ' => 'Edited writ',
            'scored_writ' => 'Scored writ',
            'redraft_writ' => 'Redraft requested',
            'new_memo' => 'New memo',
            'new_assignment' => 'New assignment',
            'new_test' => 'New test',
            'new_writer' => 'New writer',
            'new_observer' => 'New observer',
            'new_block' => 'New block',
            'new_admin' => 'New administrator',
            'new_facility' => 'New facility',
            'password_change' => 'Password change',
        ];
    }

    public static function keysFor(string $type): array
    {
        $writer = ['new_writ', 'edited_writ', 'scored_writ', 'redraft_writ', 'new_memo', 'new_assignment', 'new_test'];
        $observer = $writer;
        $editor = array_merge($writer, ['new_writer', 'new_observer', 'new_block', 'password_change']);
        $admin = array_merge($editor, ['new_admin']);
        $super = array_merge($admin, ['new_facility']);
        switch ($type) {
            case 'writer':
                return $writer;
            case 'observer':
                return $observer;
            case 'editor':
            case 'supervisor':
                return $editor;
            case 'admin':
                return $admin;
            case 'superintendent':
                return $super;
            default:
                return $writer;
        }
    }

    public function send(int $userId, string $type, string $title, string $link = '', string $body = ''): void
    {
        $u = $this->app->user->find($userId);
        if (!$u) {
            return;
        }
        $prefs = $this->app->user->prefs($u);
        $inapp = !empty($prefs['inapp'][$type]);
        $email = !empty($prefs['email'][$type]);
        if ($inapp) {
            $this->app->db->run(
                'INSERT INTO notifications (user_id, type, title, body, link) VALUES (?,?,?,?,?)',
                [$userId, $type, $title, $body !== '' ? $body : null, $link !== '' ? $link : null]
            );
        }
        if ($email && $this->app->mail && $this->app->mail->enabled()) {
            $msg = $title . "\n";
            if ($body !== '') {
                $msg .= $body . "\n";
            }
            if ($link !== '') {
                $msg .= $this->app->url($link) . "\n";
            }
            $this->app->mail->send($u['email'], '[' . $this->app->title() . '] ' . $title, $msg);
        }
    }

    public function list(int $userId): array
    {
        return $this->app->db->all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 100',
            [$userId]
        );
    }

    public function count(int $userId): int
    {
        return (int) $this->app->db->val(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ?',
            [$userId]
        );
    }

    public function ack(int $id, int $userId): void
    {
        $this->app->db->run('DELETE FROM notifications WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function toEditorOf(int $writerId, string $type, string $title, string $link = ''): void
    {
        $w = $this->app->user->find($writerId);
        if ($w && !empty($w['editor_id'])) {
            $this->send((int) $w['editor_id'], $type, $title, $link);
        }
    }

    public function toObserversOf(int $writerId, string $type, string $title, string $link = ''): void
    {
        $obs = $this->app->db->all(
            'SELECT id, observing_json FROM users WHERE type = \'observer\' AND status = \'active\''
        );
        foreach ($obs as $o) {
            $ids = json_arr($o['observing_json']);
            foreach ($ids as $id) {
                if ((int) $id === $writerId) {
                    $this->send((int) $o['id'], $type, $title, $link);
                    break;
                }
            }
        }
    }
}
