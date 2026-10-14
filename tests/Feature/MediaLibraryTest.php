<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Support\KuartalId\SessionTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'https://kuartalimpact.test';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        config(['services.kuartal_id.entitlements_refresh_minutes' => 600]);
    }

    private function admin(): static
    {
        return $this->actingAs(User::factory()->create(['entitlements' => ['kuartalimpact.admin']]))
            ->withSession([SessionTokens::CHECKED_AT_KEY => time()]);
    }

    private function pdf(string $name = 'deck.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    }

    public function test_upload_public_image_and_private_pdf(): void
    {
        $this->admin()->post(self::MAIN.'/admin/media', [
            'files' => [UploadedFile::fake()->image('Photo One.png', 800, 600)], 'visibility' => 'public', 'alt_text' => 'Team',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->admin()->post(self::MAIN.'/admin/media', ['files' => [$this->pdf()], 'visibility' => 'private'])->assertSessionHasNoErrors();

        $img = Media::where('kind', 'image')->firstOrFail();
        $pdf = Media::where('kind', 'pdf')->firstOrFail();
        $this->assertSame([800, 600], [$img->width, $img->height]);
        $this->assertSame('Team', $img->alt_text);
        Storage::disk('documents')->assertExists($img->path);
        $this->assertStringStartsWith('media/', $img->path);

        $this->get(self::MAIN.$img->url())->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Type', 'image/png');
        $this->get(self::MAIN.$pdf->url())->assertNotFound();
        $this->admin()->get(self::MAIN."/admin/media/{$pdf->id}/file")->assertOk();
        $this->admin()->get(self::MAIN.'/admin/media')->assertOk()->assertSee('Copy public URL');
    }

    public function test_rejects_unsafe_or_oversized_files(): void
    {
        $cases = [
            UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->createWithContent('x.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('fake.pdf', 'not a pdf at all'),
            UploadedFile::fake()->createWithContent('x.html', '<html><script></script></html>'),
        ];
        foreach ($cases as $file) {
            $this->admin()->post(self::MAIN.'/admin/media', ['files' => [$file], 'visibility' => 'public'])->assertSessionHasErrors();
        }

        config(['kuartalimpact.media.max_image_kb' => 1]);
        $this->admin()->post(self::MAIN.'/admin/media', ['files' => [UploadedFile::fake()->image('big.jpg', 400, 400)->size(50)], 'visibility' => 'public'])->assertSessionHasErrors();

        $this->assertSame(0, Media::count());
    }

    public function test_guest_cannot_fetch_private_file(): void
    {
        $this->admin()->post(self::MAIN.'/admin/media', ['files' => [$this->pdf()], 'visibility' => 'private']);
        $pdf = Media::firstOrFail();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get(self::MAIN."/admin/media/{$pdf->id}/file")->assertRedirect();
    }

    public function test_non_admin_cannot_upload(): void
    {
        $this->actingAs(User::factory()->create(['entitlements' => []]))
            ->withSession([SessionTokens::CHECKED_AT_KEY => time()])
            ->post(self::MAIN.'/admin/media', ['files' => [UploadedFile::fake()->image('a.png')], 'visibility' => 'public'])->assertForbidden();
        $this->assertSame(0, Media::count());
    }

    public function test_update_visibility_guard_and_delete(): void
    {
        $this->admin()->post(self::MAIN.'/admin/media', ['files' => [UploadedFile::fake()->image('a.png')], 'visibility' => 'public']);
        $m = Media::firstOrFail();
        Post::create(['title' => 'p', 'slug' => 'p', 'category' => 'announcement', 'status' => 'draft', 'cover_media_id' => $m->id]);

        $this->admin()->put(self::MAIN."/admin/media/{$m->id}", ['visibility' => 'private', 'title' => 't'])->assertSessionHasErrors('visibility');
        $this->assertTrue($m->fresh()->isPublic());

        $this->admin()->delete(self::MAIN."/admin/media/{$m->id}")->assertRedirect();
        Storage::disk('documents')->assertMissing($m->path);
        $this->assertNull(Post::first()->cover_media_id);
    }
}
