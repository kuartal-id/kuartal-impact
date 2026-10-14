<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Same reverse-proxy trust as the rest of the Kuartal apps -- needed
        // for correct URL generation behind Hostinger's proxy/Codespaces.
        $middleware->trustProxies(at: '*');

        // No local login page: signing in always goes through Kuartal ID.
        $middleware->redirectGuestsTo(fn () => route('login.kuartal-id'));

        // Kuartal ID entitlement gates (kuartal-login CatalogSeeder):
        // kuartalimpact.admin -> /admin.
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Hostinger Git deploy: hPanel pulls the `deploy` branch straight into
// ~/domains/kuartalimpact.org/public_html. Runtime state must survive
// every pull, so when ~/kuartalimpact-data/.env exists (looked
// up from the app dir upwards) it is the .env, and ~/kuartalimpact-data/storage
// is the storage path. Without it (local dev, CI) nothing changes.
$dir = dirname(__DIR__);
for ($i = 0; $i < 4; $i++) {
    $dir = dirname($dir);
    if (is_file($dir.'/kuartalimpact-data/.env')) {
        $app->useEnvironmentPath($dir.'/kuartalimpact-data');
        if (is_dir($dir.'/kuartalimpact-data/storage')) {
            $app->useStoragePath($dir.'/kuartalimpact-data/storage');
        }
        break;
    }
}

return $app;
