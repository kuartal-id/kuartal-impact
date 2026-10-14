<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NewsroomController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $tag = $request->query('tag');
        $q = trim((string) $request->query('q', ''));
        $year = $request->query('year');

        $posts = Post::live()->with('cover')
            ->when(is_string($category) && $category !== '', fn ($w) => $w->where('category', $category))
            ->when(is_string($tag) && $tag !== '', fn ($w) => $w->whereJsonContains('tags', $tag))
            ->when(is_string($year) && preg_match('/^\d{4}$/', $year), fn ($w) => $w->whereYear('published_at', (int) $year))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%'.$q.'%')
                ->orWhere('excerpt', 'like', '%'.$q.'%')->orWhere('body', 'like', '%'.$q.'%')))
            ->orderByDesc('published_at')->paginate(12)->withQueryString();

        $all = Post::live()->get(['category', 'tags', 'published_at']);

        return view('newsroom.index', [
            'posts' => $posts,
            'categories' => $all->pluck('category')->unique()->sort()->values(),
            'tags' => $all->pluck('tags')->flatten()->filter()->countBy()->sortDesc()->keys()->take(20),
            'years' => $all->map(fn ($p) => $p->published_at->year)->unique()->sortDesc()->values(),
            'filters' => compact('category', 'tag', 'q', 'year'),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isLive(), 404);
        $post->load(['cover', 'author']);

        $related = Post::live()->with('cover')->whereKeyNot($post->id)
            ->where('category', $post->category)->orderByDesc('published_at')->limit(3)->get();

        return view('newsroom.show', ['post' => $post, 'related' => $related]);
    }

    public function feed(): Response
    {
        $posts = Post::live()->orderByDesc('published_at')->limit(30)->get();

        return response()->view('newsroom.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
