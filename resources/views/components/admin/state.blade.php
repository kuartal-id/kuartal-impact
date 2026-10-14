@props(['post'])
@php $s = $post->state(); @endphp
<span class="k-pill shrink-0 whitespace-nowrap {{ ['published' => 'bg-green-100 text-green-800', 'scheduled' => 'bg-amber-100 text-amber-800', 'draft' => 'bg-gray-200 text-gray-700'][$s] }}">{{ ucfirst($s) }}{{ $s !== 'draft' && $post->published_at ? ' · '.$post->published_at->format('j M Y H:i') : '' }}</span>
