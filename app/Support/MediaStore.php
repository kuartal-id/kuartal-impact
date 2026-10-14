<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Validation rules + storage for media-library uploads. */
class MediaStore
{
    /** Laravel rules for an uploaded media file (images or PDFs). */
    public static function rules(bool $required = true, bool $imagesOnly = false): array
    {
        $c = config('kuartalimpact.media');
        $exts = $imagesOnly ? $c['image_extensions'] : array_merge($c['image_extensions'], $c['pdf_extensions']);
        $max = $imagesOnly ? $c['max_image_kb'] : max($c['max_image_kb'], $c['max_pdf_kb']);

        return [$required ? 'required' : 'nullable', 'file',
            'extensions:'.implode(',', $exts), 'mimes:'.implode(',', $exts), 'max:'.$max];
    }

    /**
     * Second-stage checks (per-kind size limit, real image decode), then store.
     *
     * @throws ValidationException
     */
    public static function store(UploadedFile $file, string $visibility, ?int $userId, ?string $title = null, ?string $alt = null, string $field = 'file'): Media
    {
        $c = config('kuartalimpact.media');
        $ext = strtolower($file->getClientOriginalExtension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $mime = (string) $file->getMimeType();
        $isPdf = in_array($ext, $c['pdf_extensions'], true);
        $kb = (int) ceil($file->getSize() / 1024);

        $width = $height = null;
        if ($isPdf) {
            $magic = (string) @file_get_contents($file->getRealPath(), false, null, 0, 5);
            if ($mime !== 'application/pdf' || $magic !== '%PDF-' || $kb > $c['max_pdf_kb']) {
                throw ValidationException::withMessages([$field => $kb > $c['max_pdf_kb']
                    ? 'PDFs may be at most '.intdiv($c['max_pdf_kb'], 1024).' MB.' : 'That file is not a valid PDF.']);
            }
        } else {
            $info = @getimagesize($file->getRealPath());
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (! $info || ! in_array($info['mime'] ?? '', $allowedMimes, true) || ! in_array($mime, $allowedMimes, true)) {
                throw ValidationException::withMessages([$field => 'That file is not a valid JPG, PNG, WebP or GIF image.']);
            }
            if ($kb > $c['max_image_kb']) {
                throw ValidationException::withMessages([$field => 'Images may be at most '.intdiv($c['max_image_kb'], 1024).' MB.']);
            }
            [$width, $height] = $info;
            if ($width * $height > $c['max_image_pixels']) {
                throw ValidationException::withMessages([$field => 'That image is too large (pixel dimensions).']);
            }
        }

        $uuid = (string) Str::uuid();
        $path = $file->storeAs('media/'.now()->format('Y/m'), $uuid.'.'.$ext, 'documents');

        return Media::create([
            'uuid' => $uuid,
            'path' => $path,
            'original_name' => Str::limit(preg_replace('/[^\w.\- ()]+/u', '_', $file->getClientOriginalName()), 180, ''),
            'mime_type' => $isPdf ? 'application/pdf' : $info['mime'],
            'kind' => $isPdf ? 'pdf' : 'image',
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'visibility' => $visibility === 'private' ? 'private' : 'public',
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'alt_text' => $alt,
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $userId,
        ]);
    }

    public static function delete(Media $media): void
    {
        Storage::disk('documents')->delete($media->path);
        $media->delete();
    }
}
