<?php
declare(strict_types=1);

final class Update
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function run(bool $pullGit = true): array
    {
        $log = [];
        $root = $this->app->root;
        if ($pullGit) {
            $log[] = $this->pull($root);
        }
        $log[] = $this->migrate();
        return $log;
    }

    private function pull(string $root): string
    {
        $repo = (string) ($this->app->config['github'] ?? 'https://github.com/PinkWrite/99.git');
        $branch = (string) ($this->app->config['github_branch'] ?? 'master');
        if (is_dir($root . '/.git')) {
            $cmd = 'git -C ' . escapeshellarg($root) . ' fetch origin 2>&1'
                . ' && git -C ' . escapeshellarg($root) . ' reset --hard origin/' . escapeshellarg($branch) . ' 2>&1';
            $out = [];
            $code = 0;
            exec($cmd, $out, $code);
            return 'git: ' . implode("\n", $out) . " (exit {$code})";
        }
        $tmp = sys_get_temp_dir() . '/pw99-update-' . getmypid();
        $cmd = 'git clone --depth 1 --branch ' . escapeshellarg($branch) . ' '
            . escapeshellarg($repo) . ' ' . escapeshellarg($tmp) . ' 2>&1';
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);
        if ($code !== 0) {
            return 'clone failed: ' . implode("\n", $out);
        }
        $this->copyTree($tmp, $root);
        $this->rmDir($tmp);
        return 'cloned master over files (config.php kept)';
    }

    private function copyTree(string $from, string $to): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $rel = substr($item->getPathname(), strlen($from) + 1);
            if ($rel === 'config.php' || str_starts_with($rel, '.git')) {
                continue;
            }
            $dest = $to . '/' . $rel;
            if ($item->isDir()) {
                if (!is_dir($dest)) {
                    mkdir($dest, 0755, true);
                }
            } else {
                copy($item->getPathname(), $dest);
            }
        }
    }

    private function rmDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    public function migrate(): string
    {
        require_once $this->app->root . '/sql/migrate.php';
        return pw99_migrate($this->app);
    }
}
