<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finishes a Hostinger Git deploy: runs migrations and rebuilds Laravel's caches once
 * per new release. Hostinger only copies files (and runs composer), so this runs from
 * an hPanel cron job every minute:
 *
 *   cd ~/domains/kuartalimpact.org/public_html && /opt/alt/php84/usr/bin/php artisan app:post-deploy
 *
 * - Release id: bootstrap/deploy-build.json (written by the build workflow), otherwise
 *   .git/HEAD read directly (exec() is disabled on Hostinger, so no `git` calls).
 * - Silent and quick when the release is already done (the common case).
 * - flock() on a lock file: overlapping cron runs never migrate twice.
 * - The marker is written only after every step succeeded. A failure is logged, and the
 *   same release is retried after `deploy.retry_after_seconds`.
 * - Every run also puts .env back to 0600 (Hostinger's publish resets it to 0644).
 */
class PostDeploy extends Command
{
    protected $signature = 'app:post-deploy {--force : Run the steps even if this release is already marked done}';

    protected $description = 'Run migrations and rebuild caches once per new deploy (Hostinger Git deploy cron)';

    public function handle(): int
    {
        $this->secureEnvFile();

        $release = $this->currentRelease();

        if ($release === null) {
            Log::error('post-deploy: cannot determine the current release (no build file, no readable .git/HEAD).');
            $this->error('Cannot determine the current release.');

            return self::FAILURE;
        }

        $marker = (string) config('deploy.marker');

        if (! $this->option('force') && $this->readMarker($marker) === $release) {
            return self::SUCCESS;
        }

        $lockPath = (string) config('deploy.lock');
        $this->ensureDirectory($lockPath);
        $lock = @fopen($lockPath, 'c');

        if ($lock === false) {
            Log::error('post-deploy: cannot open the lock file.', ['lock' => $lockPath]);

            return self::FAILURE;
        }

        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                return self::SUCCESS; // Another run is busy with it.
            }

            // Re-check under the lock: a run that just finished may have done this release.
            if (! $this->option('force') && $this->readMarker($marker) === $release) {
                return self::SUCCESS;
            }

            if (! $this->option('force') && $this->recentlyFailed($marker, $release)) {
                return self::FAILURE;
            }

            return $this->runSteps($release, $marker);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function runSteps(string $release, string $marker): int
    {
        $short = substr($release, 0, 12);
        $this->line("post-deploy: release {$short}");

        foreach ((array) config('deploy.steps', []) as [$command, $arguments]) {
            try {
                $code = $this->call($command, $arguments);
            } catch (Throwable $e) {
                return $this->recordFailure($marker, $release, $command, get_class($e).': '.$e->getMessage());
            }

            if ($code !== self::SUCCESS) {
                return $this->recordFailure($marker, $release, $command, "exit code {$code}");
            }
        }

        $this->ensureDirectory($marker);
        file_put_contents($marker, $release.PHP_EOL, LOCK_EX);
        @unlink($marker.'.failed');

        Log::info('post-deploy: release done.', ['release' => $short]);
        $this->info("post-deploy: release {$short} done.");

        return self::SUCCESS;
    }

    private function recordFailure(string $marker, string $release, string $command, string $reason): int
    {
        $this->ensureDirectory($marker);
        file_put_contents($marker.'.failed', $release.' '.time().PHP_EOL, LOCK_EX);

        Log::error('post-deploy: step failed; will retry.', [
            'release' => substr($release, 0, 12),
            'step' => $command,
            'reason' => mb_substr($reason, 0, 500),
        ]);
        $this->error("post-deploy: `{$command}` failed ({$reason}).");

        return self::FAILURE;
    }

    private function recentlyFailed(string $marker, string $release): bool
    {
        $failed = @file_get_contents($marker.'.failed');

        if (! is_string($failed)) {
            return false;
        }

        [$sha, $at] = array_pad(explode(' ', trim($failed), 2), 2, '0');

        return $sha === $release
            && (time() - (int) $at) < (int) config('deploy.retry_after_seconds', 300);
    }

    /**
     * The release being served: the main commit recorded by the build workflow, otherwise
     * the checked-out commit from .git.
     */
    public function currentRelease(): ?string
    {
        $buildFile = (string) config('deploy.build_file');

        if (is_file($buildFile)) {
            $data = json_decode((string) file_get_contents($buildFile), true);

            if (is_array($data) && is_string($data['source_sha'] ?? null) && $data['source_sha'] !== '') {
                // Include the run id so a rebuild of the same commit also counts as a release.
                return $data['source_sha'].(isset($data['run_id']) ? '-'.$data['run_id'] : '');
            }
        }

        return $this->gitHead((string) config('deploy.git_dir'));
    }

    private function gitHead(string $gitDir): ?string
    {
        $head = @file_get_contents($gitDir.'/HEAD');

        if (! is_string($head)) {
            return null;
        }

        $head = trim($head);

        if (preg_match('/^[0-9a-f]{40}([0-9a-f]{24})?$/', $head)) {
            return $head; // Detached HEAD (how Hostinger checks out).
        }

        if (! preg_match('#^ref: (refs/[^\s]+)$#', $head, $m)) {
            return null;
        }

        $ref = $m[1];
        $loose = @file_get_contents($gitDir.'/'.$ref);

        if (is_string($loose) && preg_match('/^[0-9a-f]{40,64}$/', trim($loose))) {
            return trim($loose);
        }

        $packed = @file($gitDir.'/packed-refs', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach (is_array($packed) ? $packed : [] as $line) {
            if (preg_match('/^([0-9a-f]{40,64}) (\S+)$/', $line, $p) && $p[2] === $ref) {
                return $p[1];
            }
        }

        return null;
    }

    /**
     * Hostinger's Git publish resets .env to 0644 on each deploy. Put it back to 0600 on
     * every run: one stat when it's already right (the quiet, common case).
     */
    private function secureEnvFile(): void
    {
        $file = config('deploy.env_file');
        $mode = (int) config('deploy.env_mode', 0600);

        if (! is_string($file) || $file === '') {
            return;
        }

        clearstatcache(true, $file);
        $perms = @fileperms($file);

        if ($perms === false || ($perms & 0777) === $mode) {
            return;
        }

        if (@chmod($file, $mode)) {
            Log::info('post-deploy: .env permissions reset.', ['from' => sprintf('%o', $perms & 0777), 'to' => sprintf('%o', $mode)]);
        } else {
            Log::warning('post-deploy: could not chmod .env.', ['mode' => sprintf('%o', $perms & 0777)]);
        }
    }

    private function readMarker(string $marker): ?string
    {
        $value = @file_get_contents($marker);

        return is_string($value) ? trim($value) : null;
    }

    private function ensureDirectory(string $file): void
    {
        $dir = dirname($file);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}
