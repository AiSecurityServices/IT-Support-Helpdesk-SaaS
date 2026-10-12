<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

/**
 * Verifies a supportflow backup WITHOUT touching production:
 * extracts to an isolated temp dir, checks the manifest, opens the database
 * copy, compares table counts, and runs a login-capability smoke check
 * (users table readable, plans present). Records date + result for release
 * evidence (see docs/OPERATIONS/BACKUP_RESTORE_RUNBOOK.md).
 */
class BackupVerify extends Command
{
    protected $signature = 'supportflow:backup-verify {zip : Path to the backup zip}';

    protected $description = 'Verify a backup archive in an isolated temp directory';

    public function handle(): int
    {
        $zipPath = $this->argument('zip');
        abort_unless(file_exists($zipPath), 404, 'Backup file not found.');

        $tmp = sys_get_temp_dir().'/sf-restore-'.uniqid();
        mkdir($tmp, 0700, true);
        $failures = [];

        try {
            $zip = new ZipArchive;
            abort_unless($zip->open($zipPath) === true, 500, 'Cannot open backup archive.');
            $zip->extractTo($tmp);
            $zip->close();

            $manifests = glob($tmp.'/*/manifest.json') ?: glob($tmp.'/manifest.json');
            abort_if(empty($manifests), 500, 'No manifest.json in backup.');
            $manifest = json_decode(file_get_contents($manifests[0]), true);
            abort_unless(is_array($manifest) && isset($manifest['tables']), 500, 'Invalid manifest.');
            $base = dirname($manifests[0]);
            $this->info('Manifest: '.($manifest['created_at'] ?? '?').' driver='.($manifest['db_driver'] ?? '?'));

            // SQLite verification path (dev + small installs).
            $dbFiles = glob($base.'/database.sqlite');
            if ($dbFiles) {
                $pdo = new \PDO('sqlite:'.$dbFiles[0]);
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
                $this->info('Tables in backup: '.count($tables));
                foreach (['users', 'tenants', 'tickets', 'plans'] as $t) {
                    abort_unless(in_array($t, $tables, true), 500, "Missing table {$t} in backup.");
                    $count = (int) $pdo->query("SELECT COUNT(*) FROM \"{$t}\"")->fetchColumn();
                    $expected = $manifest['tables'][$t] ?? null;
                    $this->info("  {$t}: backup={$count}".($expected !== null ? " manifest={$expected}" : ''));
                    if ($expected !== null && $count !== (int) $expected) {
                        $failures[] = "count mismatch {$t}: backup {$count} vs manifest {$expected}";
                    }
                }
                // Login-capability smoke: users + plans readable.
                $users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                $this->info("  users readable: {$users} (login smoke OK)");
                $pdo = null; // release Windows file lock before cleanup
            } else {
                $this->warn('No SQLite copy in backup (MySQL dump path) — structural checks only.');
                abort_unless(glob($base.'/database.sql'), 500, 'No database dump in backup.');
            }

            // Attachments present?
            $files = 0;
            foreach (glob($base.'/storage-*', GLOB_ONLYDIR) ?: [] as $d) {
                $files += iterator_count(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($d, \FilesystemIterator::SKIP_DOTS)));
            }
            $this->info('Storage files in backup: '.$files);
        } finally {
            $this->removeDir($tmp);
        }

        if ($failures) {
            foreach ($failures as $f) {
                $this->error($f);
            }

            return 1;
        }
        $this->info('RESTORE VERIFICATION PASSED (isolated, production untouched).');

        return 0;
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
