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

    public function start(string $pageTitle, string $active = '', string $dash = 'auto'): void
    {
        $this->started = true;
        $title = $pageTitle . ' :: ' . $this->app->title();
        $u = $this->app->auth->user();
        $dash = $this->resolveDash($u, $active, $dash);
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml"><head>';
        echo '<link rel="shortcut icon" type="image/png" href="favicon.png"/>';
        echo '<meta name="robots" content="noindex">';
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
        $cssV = @filemtime(__DIR__ . '/../css/styles.css') ?: time();
        $jsV = @filemtime(__DIR__ . '/../js/pw99.js') ?: time();
        echo '<link rel="stylesheet" href="css/styles.css?v=' . (int) $cssV . '" type="text/css" />';
        echo '<script src="js/pw99.js?v=' . (int) $jsV . '"></script>';
        echo '<meta http-equiv="Cache-Control" content="no-cache" />';
        echo '<meta http-equiv="Pragma" content="no-cache" />';
        echo '<meta http-equiv="Expires" content="0" />';
        echo '<title>' . h($title) . '</title></head><body><div id="wrap">';
        if ($u) {
            $this->topNav($u, $active, $dash);
        }
        echo '<div id="header" class="header"></div><div class="page"><div class="content">';
        if ($u && $active !== 'login') {
            $this->dashNav($u, $active, $pageTitle, $dash);
        }
        if (!empty($_SESSION['act_message'])) {
            echo $_SESSION['act_message'];
            unset($_SESSION['act_message']);
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

    public function setFlash(string $msg, bool $ok = true): void
    {
        $_SESSION['act_message'] = $this->notice($msg, $ok);
    }

    private function resolveDash(?array $u, string $active, string $dash): string
    {
        if (in_array($dash, ['writer', 'editor', 'observer'], true)) {
            return $dash;
        }
        $type = (string) ($u['type'] ?? '');
        if (in_array($active, ['observer', 'owrits'], true) || $type === 'observer') {
            return 'observer';
        }
        if (in_array($active, ['editor', 'ewrits', 'roll', 'tests', 'enrollment', 'assign'], true)) {
            return 'editor';
        }
        return 'writer';
    }

    private function topNav(array $u, string $active, string $dash): void
    {
        $is = fn ($k) => $active === $k ? 'activedash' : '';
        $editorTop = $dash === 'editor' ? 'activedash' : $is('editor');
        $observerTop = $dash === 'observer' ? 'activedash' : $is('observer');
        $myTop = $dash === 'writer' && in_array($active, ['dash', 'writs', 'notes', 'blocks', 'binder', 'locker'], true)
            ? 'activedash'
            : $is('dash');
        if ($active === 'dash') {
            $myTop = 'activedash';
        }
        echo '<div id="top_menu_nav"><div id="topnav"><ul class="topnav">';
        echo '<li><h1><a class="dklink" href="' . h($this->app->url('')) . '">PinkWrite 99</a></h1></li>';
        echo '<li class="user">' . button('Logout', 'Exit from this login session', 'logout.php', 'navButton user') . '</li>';
        $type = $u['type'];
        if (in_array($type, ['superintendent', 'admin', 'supervisor'], true)) {
            if ($type === 'superintendent') {
                echo '<li class="user">' . button('Super Dash', 'Facilities', 'super.php', 'navButton user ' . $is('super')) . '</li>';
            }
            echo '<li class="user">' . button('Admin Dash', 'Admin', 'admin.php', 'navButton user ' . $is('admin')) . '</li>';
            echo '<li class="user">' . button('Editor Dash', 'Editor', 'editor.php', 'navButton user ' . $editorTop) . '</li>';
            echo '<li class="user">' . button('Observer Dash', 'Observer', 'observer.php', 'navButton user ' . $observerTop) . '</li>';
        } elseif ($type === 'editor') {
            echo '<li class="user">' . button('Editor Dash', 'Editor', 'editor.php', 'navButton user ' . $editorTop) . '</li>';
            echo '<li class="user">' . button('Observer Dash', 'Observer', 'observer.php', 'navButton user ' . $observerTop) . '</li>';
        } elseif ($type === 'observer') {
            echo '<li class="user">' . button('Observer Dash', 'Observer', 'observer.php', 'navButton user ' . $observerTop) . '</li>';
        }
        echo '<li class="user">' . button('My Dash', 'Home', $this->app->url(''), 'navButton user ' . $myTop) . '</li>';
        echo '</ul></div></div>';
    }

    private function dashNav(array $u, string $active, string $greeting, string $dash): void
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
        $dash = $this->resolveDash($u, $active, $dash);
        $locker = $dash === 'editor'
            ? 'locker-editor.php'
            : ($dash === 'observer' ? 'locker-observer.php' : $this->lockerFor($type));
        echo '<div class="dash_menu_nav"><div class="dashnav"><ul class="dashnav">';
        echo '<li class="lt sans">' . h($greeting) . '</li>';
        echo '<li class="user">' . button('Locker', 'Open your locker', $locker, 'navDarkButton user ' . $is('locker')) . '</li>';
        echo '<li class="user">' . button($noteLabel, 'Notifications', 'notifications.php', 'navDarkButton user ' . $is('notify')) . '</li>';
        if (in_array($active, ['super', 'admin'], true)) {
            // Super/Admin keep Locker and Notifications; their own tools live on the page.
        } elseif ($dash === 'observer') {
            echo '<li class="user">' . button('Binder', 'List memos', 'binder-observer.php', 'navDarkButton user ' . $is('binder')) . '</li>';
            echo '<li class="user">' . button('Observees', 'View observed writers', 'observer.php', 'navDarkButton user ' . $is('observer')) . '</li>';
            echo '<li class="user">' . button('Writs', 'Writs', 'writs-observer.php', 'navDarkButton user ' . $is('owrits')) . '</li>';
        } elseif ($dash === 'editor') {
            echo '<li class="user">' . button('Binder', 'List memos', 'binder-editor.php', 'navDarkButton user ' . $is('binder')) . '</li>';
            echo '<li class="user">' . button('Roll', 'List writers', 'enrollment.php', 'navDarkButton user ' . $is('roll')) . '</li>';
            echo '<li class="user">' . button('Blocks', 'List blocks', 'blocks-editor.php', 'navDarkButton user ' . $is('blocks')) . '</li>';
            echo '<li class="user">' . button('Writs', 'List writs', 'writs-editor.php', 'navDarkButton user ' . $is('ewrits')) . '</li>';
            echo '<li class="user">' . button('New assignment', 'From a memo', 'assignment.php', 'navDarkButton user ' . $is('assign')) . '</li>';
            echo '<li class="user">' . button('New test', 'Compose a test', 'test.php', 'navDarkButton user ' . $is('tests')) . '</li>';
        } else {
            echo '<li class="user">' . button('Binder', 'View memos & tasks', 'binder.php', 'navDarkButton user ' . $is('binder')) . '</li>';
            echo '<li class="user">' . button('Notes', 'View notes', 'notes.php', 'navDarkButton user ' . $is('notes')) . '</li>';
            echo '<li class="user">' . button('Blocks', 'View blocks', 'blocks.php', 'navDarkButton user ' . $is('blocks')) . '</li>';
            echo '<li class="user">' . button('Writs', 'View writs', 'writs.php', 'navDarkButton user ' . $is('writs')) . '</li>';
        }
        $label = $dash === 'editor' ? 'Editor' : ($dash === 'observer' ? 'Observer' : (in_array($active, ['super', 'admin'], true) ? ($active === 'super' ? 'Super' : 'Admin') : 'Dash'));
        echo '<li class="user lt sans">' . h($u['name']) . ' (' . h($label) . ')</li>';
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
