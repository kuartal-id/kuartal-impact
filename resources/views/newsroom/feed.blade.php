{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>Kuartal Impact Newsroom</title>
    <link>{{ route('newsroom.index') }}</link>
    <atom:link href="{{ route('newsroom.feed') }}" rel="self" type="application/rss+xml"/>
    <description>News, field notes and announcements from Kuartal Impact.</description>
    <language>en</language>
    @if ($posts->isNotEmpty())<lastBuildDate>{{ $posts->first()->published_at->toRssString() }}</lastBuildDate>@endif
    @foreach ($posts as $post)
    <item>
        <title>{{ $post->title }}</title>
        <link>{{ route('newsroom.show', $post) }}</link>
        <guid isPermaLink="true">{{ route('newsroom.show', $post) }}</guid>
        <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
        <category>{{ $post->categoryLabel() }}</category>
        <description>{{ $post->summary() }}</description>
    </item>
    @endforeach
</channel>
</rss>
