<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A media-library file (image or PDF). Stored on the private `documents`
 * disk under media/, never in public_html. Public files are served by
 * App\Http\Controllers\MediaController; private ones only to admins.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid', 'path', 'original_name', 'mime_type', 'kind', 'size', 'sha256', 'visibility',
        'title', 'alt_text', 'width', 'height', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    public function slugName(): string
    {
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($this->original_name, PATHINFO_FILENAME)) ?: 'file';

        return $base.($ext ? '.'.$ext : '');
    }

    /** Public URL (only meaningful for public media). */
    public function url(): string
    {
        return route('media.show', ['media' => $this->uuid, 'name' => $this->slugName()], false);
    }

    public function absoluteUrl(): string
    {
        return route('media.show', ['media' => $this->uuid, 'name' => $this->slugName()]);
    }

    public function humanSize(): string
    {
        $kb = $this->size / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format(max($kb, 1), 0).' KB';
    }
}
