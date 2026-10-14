<?php

// Hostinger Git deploy: settings for `php artisan app:post-deploy` (see docs/DEPLOY.md).
// Read through config() so tests can point them at temporary files.
return [

    // Written by .github/workflows/build-deploy-branch.yml on the `deploy` branch:
    // {"source_sha": "<main commit>", "run_id": "...", "built_at": "..."}.
    'build_file' => base_path('bootstrap/deploy-build.json'),

    // Fallback when there is no build file: the checkout's own git metadata.
    'git_dir' => base_path('.git'),

    // Last release the post-deploy steps completed for -- one marker (and lock) PER
    // app folder: storage is shared by both hosts' public_html, bootstrap/cache is not.
    // Last release the post-deploy steps completed for. Outside the git tree on purpose
    // (storage/ is gitignored, so Hostinger keeps it between deploys).
    'marker' => storage_path('app/deployed_sha-'.substr(md5(base_path()), 0, 12)),

    // Hostinger's Git publish resets .env to 0644 on every deploy. Re-applied on every run
    // (cheap: one stat; chmod only when the mode differs). null disables it.
    'env_file' => app()->environmentFilePath(),
    'env_mode' => 0600,

    // Prevents two cron runs from migrating at the same time.
    'lock' => storage_path('app/post-deploy-'.substr(md5(base_path()), 0, 12).'.lock'),

    // After a failure, wait this long before trying the same release again.
    'retry_after_seconds' => 300,

    // Artisan commands run, in order, once per new release.
    'steps' => [
        ['migrate', ['--force' => true]],
        ['optimize:clear', []],
        ['optimize', []],
    ],
];
