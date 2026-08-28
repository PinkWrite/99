<?php
declare(strict_types=1);

final class View
{
    private App $app;
    private bool $started = false;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function start(string $pageTitle, string $active = ''): void
    {
        $this->started = true;
        $title = $pageTitle . ' :: ' . $this->app->title();
        $u = $this->app->auth->user();
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml"><head>';
        echo '<link rel="shortcut icon" type="image/png" href="favicon.png"/>';
        echo '<meta name="robots" content="noindex">';
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
        echo '<link rel="stylesheet" href="css/styles.css" type="text/css" />';
        echo '<meta http-equiv="Cache-Control" content="no-cache" />';
        echo '<title>' . h($title) . '</title></head><body><div id="wrap">';
        if ($u) {
            $this->topNav($u, $active);
        }
        echo '<div id="header" class="header"></div><div class="page"><div class="content">';
        if ($u && $active !== 'login') {
            $this->dashNav($u, $active, $pageTitle);
        }
    }

    public function end(): void
    {
        echo '<p><br clear="all" /></p></div>';
        echo '<div class="footer"><p class="dk sans">';
        echo '<a class="dk" href="Terms.htm">Terms & Conditions</a> | ';
        echo '<a class="dk" href="Privacy.htm">Privacy</a> | ';
        echo '<a class="dk" href="https://github.com/PinkWrite/99">OpenSource project from GitHub</a> | ';
        echo '<a class="dk" href="https://pinkwrite.com">pinkwrite.com</a> &nbsp; - &nbsp; &copy; PinkWrite, ';
        echo '<a class="dk" href="https://www.gnu.org/licenses/gpl-3.0.en.html">GPLv3</a></p></div>';
        echo '</div></div></body></html>';
    }

    private function topNav(array $u, string $active): void
    {
        $is = fn ($k) => $active === $k ? 'activedash' : '';
        echo '<div id="top_menu_nav"><div id="topnav"><ul class="topnav">';
        echo '<li><h1><a class="dklink" href="' . h($this->app->url('')) . '">PinkWrite 99</a></h1></li>';
        echo '<li class="user">' . button('Logout', 'Exit from this login session', 'logout.php', 'navButton user') . '</li>';
        $type = $u['type'];
        if (in_array($type, ['superintendent', 'admin', 'supervisor'], true)) {
            if ($type === 'superintendent') {
                echo '<li class="user">' . button('Super', 'Facilities', 'super.php', 'navButton user ' . $is('super')) . '</li>';
            }
            echo '<li class="user">' . button('Admin Dash', 'Admin', 'admin.php', 'navButton user ' . $is('admin')) . '</li>';
            echo '<li class="user">' . button('Editor Dash', 'Editor', 'editor.php', 'navButton user ' . $is('editor')) . '</li>';
            echo '<li class="user">' . button('Observer Dash', 'Observer', 'observer.php', 'navButton user ' . $is('observer')) . '</li>';
        } elseif ($type === 'editor') {
            echo '<li class="user">' . button('Editor Dash', 'Editor', 'editor.php', 'navButton user ' . $is('editor')) . '</li>';
            echo '<li class="user">' . button('Observer Dash', 'Observer', 'observer.php', 'navButton user ' . $is('observer')) . '</li>';
        } elseif ($type === 'observer') {
            echo '<li class="user">' . button('Observer Dash', 'Observer', 'observer.php', 'navButton user ' . $is('observer')) . '</li>';
        }
        echo '<li class="user">' . button('My Dash', 'Home', $this->app->url(''), 'navButton user ' . $is('dash')) . '</li>';
        echo '</ul></div></div>';
    }

    private function dashNav(array $u, string $active, string $greeting): void
    {
        $is = fn ($k) => $active === $k ? 'active' : '';
        $n = 0;
        try {
            $n = $this->app->notify->count((int) $u['id']);
        } catch (Throwable $e) {
            $n = 0;
        }
        $noteLabel = $n > 0 ? "Notifications ({$n})" : 'Notifications';
        $type = $u['type'];
        echo '<div class="dash_menu_nav"><div class="dashnav"><ul class="dashnav">';
        echo '<li class="lt sans">' . h($greeting) . '</li>';
        echo '<li class="user">' . button('Locker', 'Open your locker', $this->lockerFor($type), 'navDarkButton user ' . $is('locker')) . '</li>';
        echo '<li class="user">' . button($noteLabel, 'Notifications', 'notifications.php', 'navDarkButton user ' . $is('notify')) . '</li>';
        if ($type === 'writer' || $this->app->auth->atLeast('editor')) {
            echo '<li class="user">' . button('Binder', 'Memos', $this->binderFor($type), 'navDarkButton user ' . $is('binder')) . '</li>';
        }
        if ($type === 'writer') {
            echo '<li class="user">' . button('Notes', 'Notes', 'notes.php', 'navDarkButton user ' . $is('notes')) . '</li>';
            echo '<li class="user">' . button('Writs', 'Writs', 'writs.php', 'navDarkButton user ' . $is('writs')) . '</li>';
        }
        if ($this->app->auth->atLeast('editor')) {
            echo '<li class="user">' . button('Roll', 'Writers', 'enrollment.php', 'navDarkButton user ' . $is('roll')) . '</li>';
            echo '<li class="user">' . button('Blocks', 'Blocks', 'blocks.php', 'navDarkButton user ' . $is('blocks')) . '</li>';
            echo '<li class="user">' . button('Writs', 'Writs', 'writs-editor.php', 'navDarkButton user ' . $is('ewrits')) . '</li>';
            echo '<li class="user">' . button('Tests', 'Tests', 'tests.php', 'navDarkButton user ' . $is('tests')) . '</li>';
        }
        if ($type === 'observer') {
            echo '<li class="user">' . button('Writs', 'Writs', 'writs-observer.php', 'navDarkButton user ' . $is('owrits')) . '</li>';
        }
        echo '<li class="user lt sans">' . h($u['name']) . ' (' . h($type) . ')</li>';
        echo '</ul></div></div>';
    }

    private function lockerFor(string $type): string
    {
        return match ($type) {
            'superintendent' => 'locker-super.php',
            'admin', 'supervisor' => 'locker-admin.php',
            'editor' => 'locker-editor.php',
            'observer' => 'locker-observer.php',
            default => 'locker.php',
        };
    }

    private function binderFor(string $type): string
    {
        return match ($type) {
            'editor', 'supervisor', 'admin', 'superintendent' => 'binder-editor.php',
            'observer' => 'binder-observer.php',
            default => 'binder.php',
        };
    }

    public function flash(?string $html): void
    {
        if ($html) {
            echo $html;
        }
    }

    public function notice(string $msg, bool $ok = true): string
    {
        $c = $ok ? 'noticegreen' : 'noticered';
        return '<p class="sans ' . $c . '">' . h($msg) . '</p>';
    }
}
