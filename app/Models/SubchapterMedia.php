<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubchapterMedia extends Model
{
    protected $table = 'subchapter_media';

    protected $appends = [
        'url',
        'embed_url',
    ];

    protected $fillable = [
        'subchapter_id',
        'media_type',
        'source',
        'storage_path',
        'library_path',
        'youtube_video_id',
        'title',
        'caption',
    ];

    public function subchapter(): BelongsTo
    {
        return $this->belongsTo(Subchapter::class);
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->source === 'youtube') {
            return $this->embed_url;
        }

        if ($this->source === 'library' && $this->library_path) {
            $path = ltrim($this->library_path, '/');
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (
                str_starts_with($path, 'images/')
                && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
                && is_file(public_path($path))
            ) {
                return asset($path);
            }

            return null;
        }

        if (
            $this->storage_path
            && Storage::disk('public')->exists($this->storage_path)
        ) {
            return Storage::disk('public')->url($this->storage_path);
        }

        return null;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if (
            $this->source !== 'youtube'
            || !is_string($this->youtube_video_id)
            || preg_match('/^[A-Za-z0-9_-]{11}$/', $this->youtube_video_id) !== 1
        ) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'
            . $this->youtube_video_id;
    }
}
