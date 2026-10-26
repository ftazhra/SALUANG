<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SubchapterContentBlock extends Model
{
    protected $fillable = [
        'subchapter_id',
        'type',
        'position',
        'payload',
        'media_id',
    ];
    protected $casts = [
        'position' => 'integer',
        'payload' => 'array',
    ];
    public function subchapter(): BelongsTo
    {
        return $this->belongsTo(Subchapter::class);
    }
    public function media(): BelongsTo
    {
        return $this->belongsTo(SubchapterMedia::class);
    }
    public function asMaterialBlock(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'position' => $this->position,
            'payload' => $this->payload ?? [],
            'media_id' => $this->media_id,
            'media' => $this->media,
        ];
    }
}
