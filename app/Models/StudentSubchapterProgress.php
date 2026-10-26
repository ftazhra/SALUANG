<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentSubchapterProgress extends Model
{
    protected $table = 'student_subchapter_progress';
    protected $fillable = [
        'user_id',
        'subchapter_id',
        'status',
        'opened_at',
        'completed_at',
    ];
    protected $casts = [
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    public function subchapter(): BelongsTo
    {
        return $this->belongsTo(Subchapter::class);
    }
}
