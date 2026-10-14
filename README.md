# kuartalimpact.org

Kuartal Impact: Laravel 13 site for the Kuartal arm focused on social, environmental and climate causes.

```
composer install && npm ci && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate && npm run build && php artisan serve
```

Read `AGENTS.md` before changing anything. Deploys: `docs/DEPLOY.md`. Work log: `docs/HANDOFF.md`.
