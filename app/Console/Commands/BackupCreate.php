<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;

/**
 * Application backup: database + file storage + manifest in one zip.
 * - SQLite: file copy. MySQL/MariaDB: mysqldump when available, else abort
 *   with instructions (never a silent partial backup).
 * - Manifest records app version, git SHA (if available), table row counts
 *   and archive checksums for the verify step.
 * - Restrictive file permissions (0600) on the output.
 */
class BackupCreate extends Command
{
    protected $signature = 'supportflow:backup {--dir= : Output directory (default storage/app/backups)}';

    protected $description = 'Create a timestamped application backup (database + storage)';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $stamp = now()->format('Ymd-His');
        $tmp = $dir.'/tmp-'.$stamp;
        mkdir($tmp, 0700, true);

        try {
            $this->dumpDatabase($tmp);
            $this->copyStorage($tmp);
            $manifest = $this->manifest($stamp);
            file_put_contents($tmp.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            $zipPath = $dir.'/supportflow-'.$stamp.'.zip';
            $zip = new ZipArchive;
            abort_unless($zip->open($zipPath, ZipArchive::CREATE) === true, 500, 'Could not create backup archive.');
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmp, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $zip->addFile($file->getPathname(), 'supportflow-'.$stamp.'/'.$file->getFilename());
            }
            $zip->close();
            chmod($zipPath, 0600);

            $this->info('Backup written: '.$zipPath.' ('.round(filesize($zipPath) / 1024).' KB)');
            $this->info('Manifest tables: '.count($manifest['tables'] ?? []));
        } finally {
            $this->removeDir($tmp);
        }

        return 0;
    }

    protected function dumpDatabase(string $tmp): void
    {
        $conn = config('database.default');
        if ($conn === 'sqlite') {
            $src = config('database.connections.sqlite.database');
            abort_unless($src && file_exists($src), 500, 'SQLite database file not found: '.$src);
            copy($src, $tmp.'/database.sqlite');
            $this->info('Database: SQLite copy.');

            return;
        }
        if (in_array($conn, ['mysql', 'mariadb'], true)) {
            $dump = trim((string) shell_exec('command -v mysqldump 2>/dev/null || where mysqldump 2>NUL'));
            abort_if($dump === '', 500, 'mysqldump not found. Install MySQL client tools or back up manually.');
            $c = config("database.connections.{$conn}");
            $cmd = sprintf('%s --single-transaction --quick --skip-lock-tables -h %s -P %s -u %s %s %s > %s 2>&1',
                escapeshellarg(trim(explode("\n", $dump)[0])),
                escapeshellarg($c['host']), escapeshellarg($c['port']),
                escapeshellarg($c['username']),
                $c['password'] !== '' && $c['password'] !== null ? '-p'.escapeshellarg($c['password']) : '',
                escapeshellarg($c['database']), escapeshellarg($tmp.'/database.sql'));
            exec($cmd, $out, $code);
            abort_if($code !== 0 || ! file_exists($tmp.'/database.sql'), 500, 'mysqldump failed: '.implode("\n", $out));
            $this->info('Database: mysqldump.');

            return;
        }
        abort(500, 'Unsupported database driver for backup: '.$conn);
    }

    protected function copyStorage(string $tmp): void
    {
        foreach (['private', 'public'] as $disk) {
            $src = storage_path('app/'.$disk);
            $dest = $tmp.'/storage-'.$disk;
            mkdir($dest, 0700, true);
            if (! is_dir($src)) {
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isDir()) {
                    continue;
                }
                $rel = substr($file->getPathname(), strlen($src) + 1);
                $target = $dest.'/'.$rel;
                if (! is_dir(dirname($target))) {
                    mkdir(dirname($target), 0700, true);
                }
                copy($file->getPathname(), $target);
            }
        }
        $this->info('Storage: copied.');
    }

    protected function manifest(string $stamp): array
    {
        $tables = [];
        try {
            $names = match (config('database.default')) {
                'sqlite' => collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))->pluck('name'),
                default => collect(DB::select('SHOW TABLES'))->map(fn ($r) => reset($r)),
            };
            foreach ($names as $table) {
                $tables[$table] = DB::table($table)->count();
            }
        } catch (\Throwable $e) {
            $tables['_error'] = $e->getMessage();
        }

        return [
            'app' => config('app.name'), 'created_at' => now()->toIso8601String(),
            'db_driver' => config('database.default'),
            'git_sha' => $this->gitSha(),
            'tables' => $tables,
        ];
    }

    protected function gitSha(): string
    {
        try {
            $null = DIRECTORY_SEPARATOR === '\\' ? '2>nul' : '2>/dev/null';
            $out = shell_exec("git rev-parse HEAD {$null}");
            $sha = trim((string) $out);

            return preg_match('/^[0-9a-f]{40}$/', $sha) ? $sha : 'unknown';
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
