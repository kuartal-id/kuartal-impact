<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Serves media-library files from the private disk: public ones to anyone, any to admins. */
class MediaController extends Controller
{
    public function show(Media $media): StreamedResponse
    {
        abort_unless($media->isPublic(), 404);

        return $this->send($media, 'public, max-age=86400');
    }

    public function admin(Media $media): StreamedResponse
    {
        return $this->send($media, 'private, no-store');
    }

    private function send(Media $media, string $cache): StreamedResponse
    {
        $disk = Storage::disk('documents');
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, $media->slugName(), [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => $cache,
        ], 'inline');
    }
}
