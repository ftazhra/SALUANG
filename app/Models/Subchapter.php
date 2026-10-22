<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subchapter extends Model
{
    protected $fillable = [
        'chapter_id',
        'nomor_subbab',
        'judul',
        'konten',
        'content_blocks_enabled',
        'is_published',
    ];

    protected $casts = [
        'content_blocks_enabled' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(SubchapterMedia::class)
            ->orderByRaw("CASE WHEN media_type = 'image' THEN 0 ELSE 1 END")
            ->orderBy('id');
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(SubchapterContentBlock::class)
            ->orderBy('position');
    }

    public function materialBlocks(): array
    {
        if ($this->content_blocks_enabled) {
            return $this->contentBlocks
                ->map(fn (SubchapterContentBlock $block) =>
                    $block->asMaterialBlock()
                )
                ->all();
        }

        $blocks = [];

        if (is_string($this->konten) && trim($this->konten) !== '') {
            $blocks[] = [
                'id' => null,
                'type' => 'text',
                'position' => 1,
                'payload' => ['content' => $this->konten],
                'media_id' => null,
                'media' => null,
            ];
        }

        foreach ($this->media as $media) {
            $blocks[] = [
                'id' => null,
                'type' => $media->media_type,
                'position' => count($blocks) + 1,
                'payload' => [],
                'media_id' => $media->id,
                'media' => $media,
            ];
        }

        return $blocks;
    }
}
