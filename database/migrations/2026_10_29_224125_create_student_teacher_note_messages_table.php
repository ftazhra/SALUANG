
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_teacher_note_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->string('audience', 30)->default('orang_tua');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['student_id', 'audience', 'published_at'], 'stnm_student_audience_date_idx');
        });

        // Salin catatan lama yang sudah dikirim agar riwayatnya tidak hilang.
        DB::table('student_teacher_notes')
            ->whereNotNull('published_at')
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($notes) {
                foreach ($notes as $note) {
                    DB::table('student_teacher_note_messages')->insert([
                        'student_id' => $note->student_id,
                        'teacher_id' => $note->teacher_id,
                        'note' => $note->note,
                        'audience' => $note->audience ?? 'orang_tua',
                        'published_at' => $note->published_at,
                        'created_at' => $note->created_at,
                        'updated_at' => $note->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_teacher_note_messages');
    }
};
