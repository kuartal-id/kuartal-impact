<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\HtmlSanitizer;
use App\Support\KuartalId\SessionTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsroomTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'https://kuartalimpact.test';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        config(['services.kuartal_id.entitlements_refresh_minutes' => 600]);
    }

    private function admin(): User
    {
        return User::factory()->create(['entitlements' => ['kuartalimpact.admin']]);
    }

    private function asUser(User $user): static
    {
        return $this->actingAs($user)->withSession([SessionTokens::CHECKED_AT_KEY => time()]);
    }

    private function makePost(array $attrs = []): Post
    {
        return Post::create($attrs + [
            'title' => 'Hello', 'slug' => 'hello-'.uniqid(), 'category' => 'announcement', 'status' => 'published',
            'published_at' => now()->subHour(), 'body' => '<p>Body text</p>', 'tags' => ['capital'],
        ]);
    }

    public function test_index_lists_only_live_posts(): void
    {
        $this->makePost(['title' => 'Live one']);
        $this->makePost(['title' => 'Draft one', 'status' => 'draft']);
        $this->makePost(['title' => 'Future one', 'status' => 'scheduled', 'published_at' => now()->addDay()]);
        $this->makePost(['title' => 'Due scheduled', 'status' => 'scheduled', 'published_at' => now()->subMinute()]);

        $this->get(self::MAIN.'/newsroom')->assertOk()
            ->assertSee('Live one')->assertSee('Due scheduled')
            ->assertDontSee('Draft one')->assertDontSee('Future one');
    }

    public function test_index_empty_state(): void
    {
        $this->get(self::MAIN.'/newsroom')->assertOk()->assertSee('No news posts yet');
    }

    public function test_filters_by_category_tag_year_and_search(): void
    {
        $this->makePost(['title' => 'Capital deal', 'category' => 'capital', 'tags' => ['spv'], 'published_at' => now()->subYear()]);
        $this->makePost(['title' => 'Media launch', 'category' => 'media', 'tags' => ['rukre']]);

        $this->get(self::MAIN.'/newsroom?category=capital')->assertSee('Capital deal')->assertDontSee('Media launch');
        $this->get(self::MAIN.'/newsroom?tag=rukre')->assertSee('Media launch')->assertDontSee('Capital deal');
        $this->get(self::MAIN.'/newsroom?year='.now()->subYear()->year)->assertSee('Capital deal')->assertDontSee('Media launch');
        $this->get(self::MAIN.'/newsroom?q=launch')->assertSee('Media launch')->assertDontSee('Capital deal');
        $this->get(self::MAIN.'/newsroom?category=media&q=nothing-matches')->assertSee('No posts match those filters');
    }

    public function test_post_page_and_404_for_unpublished(): void
    {
        $live = $this->makePost(['title' => 'Open post', 'meta_description' => 'SEO desc here']);
        $draft = $this->makePost(['status' => 'draft']);
        $future = $this->makePost(['status' => 'scheduled', 'published_at' => now()->addDay()]);

        $this->get(self::MAIN.'/newsroom/'.$live->slug)->assertOk()->assertSee('Open post')
            ->assertSee('SEO desc here', false)->assertSee('NewsArticle', false);
        $this->get(self::MAIN.'/newsroom/'.$draft->slug)->assertNotFound();
        $this->get(self::MAIN.'/newsroom/'.$future->slug)->assertNotFound();
    }

    public function test_rss_feed(): void
    {
        $this->makePost(['title' => 'Feed & item']);
        $this->makePost(['title' => 'Hidden draft', 'status' => 'draft']);

        $res = $this->get(self::MAIN.'/newsroom/feed')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $xml = simplexml_load_string($res->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('Feed & item', (string) $xml->channel->item[0]->title);
        $this->assertCount(1, $xml->channel->item);
    }

    public function test_admin_only(): void
    {
        $this->get(self::MAIN.'/admin/posts')->assertRedirect();
        $this->asUser(User::factory()->create(['entitlements' => ['investor.portal']]))->get(self::MAIN.'/admin/posts')->assertForbidden();
        $this->asUser(User::factory()->create(['entitlements' => ['investor.portal']]))
            ->post(self::MAIN.'/admin/posts', ['title' => 'x', 'category' => 'announcement', 'status' => 'published'])->assertForbidden();
        $this->assertSame(0, Post::count());
    }

    public function test_admin_crud_with_slug_sanitising_and_cover_upload(): void
    {
        $admin = $this->admin();
        $this->asUser($admin)->get(self::MAIN.'/admin/posts/new')->assertOk();

        $this->asUser($admin)->post(self::MAIN.'/admin/posts', [
            'title' => 'Kuartal Cloud opens AI Lab',
            'excerpt' => 'Short',
            'body' => '<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:alert(1)">bad</a> <a href="https://kuartal.id">ok</a></p><img src="x" onerror="y">',
            'category' => 'research', 'tags' => 'AI, cloud, ai', 'status' => 'published',
            'meta_title' => 'Meta', 'cover_upload' => UploadedFile::fake()->image('cover.jpg', 1200, 630),
        ])->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertSame('kuartal-cloud-opens-ai-lab', $post->slug);
        $this->assertSame(['ai', 'cloud'], $post->tags);
        $this->assertTrue($post->isLive());
        $this->assertStringNotContainsString('script', $post->body);
        $this->assertStringNotContainsString('onclick', $post->body);
        $this->assertStringNotContainsString('javascript:', $post->body);
        $this->assertStringNotContainsString('onerror', $post->body);
        $this->assertStringContainsString('href="https://kuartal.id"', $post->body);
        $this->assertNotNull($post->cover);
        $this->assertTrue($post->cover->isPublic());
        $this->get(self::MAIN.$post->cover->url())->assertOk();

        // Same title again → unique slug.
        $this->asUser($admin)->post(self::MAIN.'/admin/posts', ['title' => 'Kuartal Cloud opens AI Lab', 'category' => 'research', 'status' => 'draft'])->assertRedirect();
        $this->assertTrue(Post::where('slug', 'kuartal-cloud-opens-ai-lab-2')->exists());

        $this->asUser($admin)->put(self::MAIN."/admin/posts/{$post->id}", [
            'title' => 'Renamed', 'slug' => 'renamed', 'category' => 'research', 'status' => 'draft',
        ])->assertRedirect();
        $this->assertSame('draft', $post->fresh()->status);
        $this->get(self::MAIN.'/newsroom/renamed')->assertNotFound();

        $this->asUser($admin)->get(self::MAIN.'/admin/posts')->assertOk()->assertSee('Renamed');
        $this->asUser($admin)->delete(self::MAIN."/admin/posts/{$post->id}")->assertRedirect();
        $this->assertNull($post->fresh());
    }

    public function test_scheduling_rules(): void
    {
        $admin = $this->admin();
        $this->asUser($admin)->post(self::MAIN.'/admin/posts', ['title' => 'Later', 'category' => 'announcement', 'status' => 'scheduled', 'published_at' => now()->subDay()->format('Y-m-d H:i')])
            ->assertSessionHasErrors('published_at');
        $this->asUser($admin)->post(self::MAIN.'/admin/posts', ['title' => 'Later', 'category' => 'announcement', 'status' => 'scheduled', 'published_at' => now()->addDay()->format('Y-m-d H:i')])
            ->assertSessionHasNoErrors();
        $post = Post::firstOrFail();
        $this->assertSame('scheduled', $post->state());
        $this->get(self::MAIN.'/newsroom/'.$post->slug)->assertNotFound();
        $this->travel(2)->days();
        $this->get(self::MAIN.'/newsroom/'.$post->slug)->assertOk();
    }

    public function test_slug_validation(): void
    {
        $this->asUser($this->admin())->post(self::MAIN.'/admin/posts', ['title' => 'x', 'slug' => 'Bad Slug!', 'category' => 'announcement', 'status' => 'draft'])
            ->assertSessionHasErrors('slug');
    }

    public function test_sanitizer_unit_cases(): void
    {
        $this->assertSame('', HtmlSanitizer::clean('<script>x</script>'));
        $this->assertSame('<p>a<b>b</b></p>', HtmlSanitizer::clean('<p style="color:red">a<b>b</b></p>'));
        $this->assertStringNotContainsString('href', HtmlSanitizer::clean('<a href="//evil.example">x</a>'));
        $this->assertStringNotContainsString('href', HtmlSanitizer::clean('<a href="/\\evil.example">x</a>'));
        $this->assertStringContainsString('<h2>T</h2>', HtmlSanitizer::clean('<h2>T</h2><iframe src="https://x"></iframe>'));
        $this->assertStringContainsString('src="/media/abc/x.jpg"', HtmlSanitizer::clean('<img src="/media/abc/x.jpg" alt="a">'));
        $this->assertStringNotContainsString('<img', HtmlSanitizer::clean('<img src="data:image/png;base64,AAA">'));
        $this->assertSame('<p>Ünïcode ✓</p>', HtmlSanitizer::clean('<p>Ünïcode ✓</p>'));
    }
}
