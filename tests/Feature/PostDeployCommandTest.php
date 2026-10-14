<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PostDeployCommandTest extends TestCase
{
    private string $dir;

    /** @var list<string> */
    public static array $ran = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/post-deploy-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->dir.'/git/refs/heads');
        self::$ran = [];

        Artisan::command('test:step-ok', fn () => PostDeployCommandTest::$ran[] = 'ok');
        Artisan::command('test:step-fail', function () {
            PostDeployCommandTest::$ran[] = 'fail';

            return 1;
        });
        Artisan::command('test:step-throw', function () {
            throw new \RuntimeException('database is locked');
        });

        config([
            'deploy.build_file' => $this->dir.'/deploy-build.json',
            'deploy.git_dir' => $this->dir.'/git',
            'deploy.marker' => $this->dir.'/storage/deployed_sha',
            'deploy.lock' => $this->dir.'/storage/post-deploy.lock',
            'deploy.retry_after_seconds' => 300,
            'deploy.env_file' => $this->dir.'/.env',
            'deploy.env_mode' => 0600,
            // Never run the real optimize in tests: it would write bootstrap/cache into the repo.
            'deploy.steps' => [['migrate', ['--force' => true]], ['test:step-ok', []]],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function build(string $sha, string $run = '1'): void
    {
        File::put($this->dir.'/deploy-build.json', json_encode(['source_sha' => $sha, 'run_id' => $run]));
    }

    public function test_runs_the_steps_once_for_a_new_release_and_writes_the_marker(): void
    {
        $this->build(str_repeat('a', 40));

        $this->artisan('app:post-deploy')->assertSuccessful();

        $this->assertSame(['ok'], self::$ran);
        $this->assertSame(str_repeat('a', 40).'-1', trim(File::get($this->dir.'/storage/deployed_sha')));
    }

    public function test_is_quiet_and_does_nothing_when_the_release_is_already_done(): void
    {
        $this->build(str_repeat('a', 40));
        $this->artisan('app:post-deploy')->assertSuccessful();
        self::$ran = [];

        $this->artisan('app:post-deploy')->doesntExpectOutputToContain('post-deploy')->assertSuccessful();

        $this->assertSame([], self::$ran);
    }

    public function test_a_new_build_triggers_the_steps_again(): void
    {
        $this->build(str_repeat('a', 40), '1');
        $this->artisan('app:post-deploy')->assertSuccessful();

        $this->build(str_repeat('b', 40), '2');
        $this->artisan('app:post-deploy')->assertSuccessful();

        $this->assertSame(['ok', 'ok'], self::$ran);
        $this->assertSame(str_repeat('b', 40).'-2', trim(File::get($this->dir.'/storage/deployed_sha')));
    }

    public function test_falls_back_to_a_detached_git_head(): void
    {
        File::put($this->dir.'/git/HEAD', str_repeat('c', 40)."\n");

        $this->artisan('app:post-deploy')->assertSuccessful();

        $this->assertSame(str_repeat('c', 40), trim(File::get($this->dir.'/storage/deployed_sha')));
    }

    public function test_reads_a_symbolic_ref_from_loose_refs_and_packed_refs(): void
    {
        File::put($this->dir.'/git/HEAD', "ref: refs/heads/deploy\n");
        File::put($this->dir.'/git/packed-refs', "# pack-refs with: peeled fully-peeled sorted\n".str_repeat('d', 40)." refs/heads/deploy\n");

        $this->artisan('app:post-deploy')->assertSuccessful();
        $this->assertSame(str_repeat('d', 40), trim(File::get($this->dir.'/storage/deployed_sha')));

        File::put($this->dir.'/git/refs/heads/deploy', str_repeat('e', 40)."\n");

        $this->artisan('app:post-deploy')->assertSuccessful();
        $this->assertSame(str_repeat('e', 40), trim(File::get($this->dir.'/storage/deployed_sha')));
    }

    public function test_a_failing_step_is_logged_leaves_no_marker_and_is_retried_later(): void
    {
        Log::spy();
        config(['deploy.steps' => [['test:step-fail', []], ['test:step-ok', []]]]);
        $this->build(str_repeat('f', 40));

        $this->artisan('app:post-deploy')->assertFailed();

        $this->assertSame(['fail'], self::$ran, 'Later steps must not run after a failure.');
        $this->assertFileDoesNotExist($this->dir.'/storage/deployed_sha');
        Log::shouldHaveReceived('error')->withArgs(fn ($m, $ctx = []) => str_contains($m, 'step failed') && $ctx['step'] === 'test:step-fail')->once();

        // Within the back-off window: not retried.
        $this->artisan('app:post-deploy')->assertFailed();
        $this->assertSame(['fail'], self::$ran);

        // After the window: retried, and this time it works.
        File::put($this->dir.'/storage/deployed_sha.failed', str_repeat('f', 40).'-1 '.(time() - 301));
        config(['deploy.steps' => [['test:step-ok', []]]]);
        $this->artisan('app:post-deploy')->assertSuccessful();

        $this->assertSame(['fail', 'ok'], self::$ran);
        $this->assertFileDoesNotExist($this->dir.'/storage/deployed_sha.failed');
    }

    public function test_an_exception_in_a_step_is_logged_not_thrown(): void
    {
        Log::spy();
        config(['deploy.steps' => [['test:step-throw', []]]]);
        $this->build(str_repeat('1', 40));

        $this->artisan('app:post-deploy')->assertFailed();

        Log::shouldHaveReceived('error')->withArgs(fn ($m, $ctx = []) => str_contains($ctx['reason'] ?? '', 'database is locked'))->once();
        $this->assertFileDoesNotExist($this->dir.'/storage/deployed_sha');
    }

    public function test_does_nothing_while_another_run_holds_the_lock(): void
    {
        $this->build(str_repeat('2', 40));
        File::ensureDirectoryExists($this->dir.'/storage');
        $held = fopen($this->dir.'/storage/post-deploy.lock', 'c');
        flock($held, LOCK_EX);

        try {
            $this->artisan('app:post-deploy')->assertSuccessful();
        } finally {
            flock($held, LOCK_UN);
            fclose($held);
        }

        $this->assertSame([], self::$ran);
        $this->assertFileDoesNotExist($this->dir.'/storage/deployed_sha');
    }

    public function test_fails_loudly_when_the_release_cannot_be_determined(): void
    {
        Log::spy();

        $this->artisan('app:post-deploy')->assertFailed();

        Log::shouldHaveReceived('error')->once();
        $this->assertSame([], self::$ran);
    }

    public function test_default_steps_are_migrate_then_optimize_clear_then_optimize(): void
    {
        $steps = (require base_path('config/deploy.php'))['steps'];

        $this->assertSame(
            [['migrate', ['--force' => true]], ['optimize:clear', []], ['optimize', []]],
            $steps,
        );
    }

    private function mode(string $file): int
    {
        clearstatcache(true, $file);

        return fileperms($file) & 0777;
    }

    public function test_resets_env_to_0600_on_every_run_even_when_the_release_is_already_done(): void
    {
        Log::spy();
        $this->build(str_repeat('a', 40));
        File::put($this->dir.'/.env', "APP_KEY=x\n");
        chmod($this->dir.'/.env', 0600);
        $this->artisan('app:post-deploy')->assertSuccessful();
        self::$ran = [];

        // Hostinger's publish resets it; the release is unchanged.
        chmod($this->dir.'/.env', 0644);
        $this->artisan('app:post-deploy')->doesntExpectOutputToContain('post-deploy')->assertSuccessful();

        $this->assertSame(0600, $this->mode($this->dir.'/.env'));
        $this->assertSame([], self::$ran, 'Fixing .env must not re-run the release steps.');
        $this->assertSame("APP_KEY=x\n", File::get($this->dir.'/.env'));
        Log::shouldHaveReceived('info')->withArgs(fn ($m, $ctx = []) => str_contains($m, '.env permissions reset') && $ctx === ['from' => '644', 'to' => '600'])->once();
    }

    public function test_leaves_a_correct_env_alone_quietly(): void
    {
        Log::spy();
        $this->build(str_repeat('a', 40));
        File::put($this->dir.'/.env', "APP_KEY=x\n");
        chmod($this->dir.'/.env', 0600);
        File::ensureDirectoryExists($this->dir.'/storage');
        File::put($this->dir.'/storage/deployed_sha', str_repeat('a', 40).'-1');

        $this->artisan('app:post-deploy')->doesntExpectOutputToContain('post-deploy')->assertSuccessful();

        $this->assertSame(0600, $this->mode($this->dir.'/.env'));
        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_a_missing_env_file_is_ignored(): void
    {
        $this->build(str_repeat('a', 40));

        $this->artisan('app:post-deploy')->assertSuccessful();

        $this->assertFileDoesNotExist($this->dir.'/.env');
    }
}
