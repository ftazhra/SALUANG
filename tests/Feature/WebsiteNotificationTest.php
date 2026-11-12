<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WebsiteNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class WebsiteNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_quiz_and_evaluation_notifications_are_idempotent_scoped_and_readable(): void
    {
        $teacher = $this->createUser('guru', 'notification-teacher');
        $otherTeacher = $this->createUser('guru', 'notification-other-teacher');
        $student = $this->createUser('siswa', 'notification-student', 'VII A');
        $parent = $this->createUser('orang_tua', 'notification-parent');
        $pendingParent = $this->createUser('orang_tua', 'notification-pending-parent');
        $otherParent = $this->createUser('orang_tua', 'notification-other-parent');

        $chapterId = DB::table('chapters')->insertGetId([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subchapterId = DB::table('subchapters')->insertGetId([
            'chapter_id' => $chapterId,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Kuis',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->link($student, $parent, 'approved');
        $this->link($student, $pendingParent, 'pending');

        $quizResultId = DB::table('student_assessment_results')->insertGetId([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $chapterId,
            'subchapter_id' => $subchapterId,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $evaluationSnapshot = [];
        foreach (range(1, 15) as $number) {
            $questionId = DB::table('evaluation_questions')->insertGetId([
                'user_id' => $teacher->id,
                'nomor_soal' => $number,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "Evaluasi {$number}",
                'konten_soal' => json_encode([
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                ]),
                'kunci_jawaban' => json_encode(['jawaban' => 'A']),
                'pembahasan' => null,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $evaluationSnapshot[] = [
                'id' => $questionId,
                'user_id' => $teacher->id,
                'nomor_soal' => $number,
            ];
        }
        $evaluationId = DB::table('evaluation_attempts')->insertGetId([
            'user_id' => $student->id,
            'teacher_id' => $teacher->id,
            'chapter_id' => $chapterId,
            'evaluation_bank_id' => null,
            'question_snapshot' => json_encode($evaluationSnapshot),
            'answers' => json_encode(new \stdClass()),
            'ends_at' => now(),
            'submitted_at' => now(),
            'score' => 80,
            'correct_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notifications = app(WebsiteNotificationService::class);
        $notifications->completedAssessment((int) $student->id, 'quiz', $quizResultId);
        $notifications->completedAssessment((int) $student->id, 'quiz', $quizResultId);
        $notifications->completedAssessment((int) $student->id, 'evaluation', $evaluationId);
        $notifications->completedAssessment((int) $student->id, 'evaluation', $evaluationId);

        DB::commit();

        $this->assertNotificationIndexes();
        foreach ([
            ['quiz_result', $quizResultId],
            ['evaluation_result', $evaluationId],
        ] as [$eventType, $sourceId]) {
            $eventNotifications = DB::table('website_notifications')
                ->where('event_type', $eventType)
                ->where('source_id', $sourceId);

            $this->assertSame(3, (clone $eventNotifications)->count());
            $this->assertSame(1, (clone $eventNotifications)
                ->where('recipient_id', $student->id)
                ->count());
            $this->assertSame(1, (clone $eventNotifications)
                ->where('recipient_id', $teacher->id)
                ->count());
            $this->assertSame(1, (clone $eventNotifications)
                ->where('recipient_id', $parent->id)
                ->count());
            $this->assertSame(0, (clone $eventNotifications)
                ->whereIn('recipient_id', [$otherTeacher->id, $pendingParent->id, $otherParent->id])
                ->count());
        }

        $studentQuizNotification = DB::table('website_notifications')
            ->where('recipient_id', $student->id)
            ->where('event_type', 'quiz_result')
            ->first();
        $teacherQuizNotification = DB::table('website_notifications')
            ->where('recipient_id', $teacher->id)
            ->where('event_type', 'quiz_result')
            ->first();
        $parentQuizNotification = DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('event_type', 'quiz_result')
            ->first();
        $studentEvaluationNotification = DB::table('website_notifications')
            ->where('recipient_id', $student->id)
            ->where('event_type', 'evaluation_result')
            ->first();
        $parentEvaluationNotification = DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('event_type', 'evaluation_result')
            ->first();

        $this->assertFalse($notifications->latestFor($student)->first()['is_read']);
        $this->actingAs($otherParent)
            ->get("/notifications/{$studentQuizNotification->id}/open")
            ->assertNotFound();

        DB::table('student_guardians')
            ->where('student_id', $student->id)
            ->where('guardian_id', $parent->id)
            ->update(['status' => 'pending']);
        $this->actingAs($parent)
            ->get("/notifications/{$parentQuizNotification->id}/open")
            ->assertNotFound();
        $this->assertNull(DB::table('website_notifications')
            ->where('id', $parentQuizNotification->id)
            ->value('read_at'));
        DB::table('student_guardians')
            ->where('student_id', $student->id)
            ->where('guardian_id', $parent->id)
            ->update(['status' => 'approved']);

        $this->actingAs($student)
            ->get("/notifications/{$studentQuizNotification->id}/open")
            ->assertRedirect("/dashboard?section=quiz&subchapter_id={$subchapterId}");
        $this->actingAs($teacher)
            ->get("/notifications/{$teacherQuizNotification->id}/open")
            ->assertRedirect("/dashboard?page=nilai&student_id={$student->id}");
        $this->actingAs($parent)
            ->get("/notifications/{$parentQuizNotification->id}/open")
            ->assertRedirect("/dashboard?page=hasil&child_id={$student->id}");
        $this->actingAs($student)
            ->get("/notifications/{$studentEvaluationNotification->id}/open")
            ->assertRedirect("/dashboard?section=evaluasi&teacher_id={$teacher->id}");
        $this->actingAs($parent)
            ->get("/notifications/{$parentEvaluationNotification->id}/open")
            ->assertRedirect("/dashboard?page=hasil&child_id={$student->id}&teacher_id={$teacher->id}");

        foreach ([$studentQuizNotification, $teacherQuizNotification, $parentQuizNotification] as $notification) {
            $this->assertNotNull(DB::table('website_notifications')
                ->where('id', $notification->id)
                ->value('read_at'));
        }
        $this->assertTrue($notifications->latestFor($student)
            ->firstWhere('event_type', 'quiz_result')['is_read']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_published_content_and_chat_notifications_use_class_and_thread_relations(): void
    {
        $teacher = $this->createUser('guru', 'notification-content-teacher');
        $student = $this->createUser('siswa', 'notification-content-student', 'VII A');
        $otherStudent = $this->createUser('siswa', 'notification-content-other', 'VII B');
        $parent = $this->createUser('orang_tua', 'notification-chat-parent');
        $pendingParent = $this->createUser('orang_tua', 'notification-chat-pending');

        $chapterId = DB::table('chapters')->insertGetId([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapterId,
            'user_id' => $teacher->id,
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('teacher_class_assignments', [
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
        ]);
        $this->assertSame('VII A', $student->fresh()->kelas);
        $this->link($student, $parent, 'approved');
        $approvedRelationId = DB::table('student_guardians')
            ->where('student_id', $student->id)
            ->where('guardian_id', $parent->id)
            ->where('status', 'approved')
            ->value('id');
        $this->link($student, $pendingParent, 'pending');
        $this->assertSame(1, DB::table('teacher_class_assignments')
            ->where('teacher_id', $teacher->id)
            ->pluck('class_name')
            ->unique()
            ->count());
        $this->assertSame(1, User::query()
            ->where('role', 'siswa')
            ->whereIn('kelas', ['VII A'])
            ->whereKey($student->id)
            ->count());
        $notifications = app(WebsiteNotificationService::class);
        $parentMessageId = DB::table('parent_teacher_messages')->insertGetId([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'sender_id' => $parent->id,
            'client_message_id' => 'c0a80123-4567-4abc-8def-0123456789ab',
            'body' => 'Pesan Orang Tua',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $teacherMessageId = DB::table('parent_teacher_messages')->insertGetId([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'sender_id' => $teacher->id,
            'client_message_id' => 'd0a80123-4567-4abc-8def-0123456789ab',
            'body' => 'Balasan Guru',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notifications->publishedContent((int) $teacher->id, 'chapter', $chapterId);
        $notifications->publishedContent((int) $teacher->id, 'chapter', $chapterId);
        $notifications->chatMessageCreated($parentMessageId);
        $notifications->chatMessageCreated($parentMessageId);
        $notifications->chatMessageCreated($teacherMessageId);

        DB::commit();

        $notifications->publishedContent((int) $teacher->id, 'chapter', $chapterId);
        $notifications->chatMessageCreated($parentMessageId);
        $notifications->chatMessageCreated($teacherMessageId);

        $materialNotification = DB::table('website_notifications')
            ->where('recipient_id', $student->id)
            ->where('event_type', 'material_published')
            ->first();
        $teacherChatNotification = DB::table('website_notifications')
            ->where('recipient_id', $teacher->id)
            ->where('event_type', 'chat_message')
            ->first();
        $parentChatNotification = DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('event_type', 'chat_message')
            ->first();

        $this->assertNotNull($teacherChatNotification, 'Parent message notification was not stored.');
        $this->assertNotNull($parentChatNotification, 'Teacher message notification was not stored.');
        $this->assertNotNull($materialNotification, 'Published chapter notification was not stored.');
        $this->assertSame('siswa', $materialNotification->recipient_role);
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $student->id)
            ->where('event_type', 'material_published')
            ->count());
        $this->assertSame(0, DB::table('website_notifications')
            ->where('recipient_id', $otherStudent->id)
            ->where('event_type', 'material_published')
            ->count());
        $this->assertSame(0, DB::table('website_notifications')
            ->where('recipient_id', $pendingParent->id)
            ->count());
        $this->assertSame($parentMessageId, (int) $teacherChatNotification->source_id);
        $this->assertSame($teacherMessageId, (int) $parentChatNotification->source_id);
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $teacher->id)
            ->where('event_type', 'chat_message')
            ->count());
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('event_type', 'chat_message')
            ->count());
        $this->assertSame(0, DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('source_id', $parentMessageId)
            ->count());
        $this->assertSame(0, DB::table('website_notifications')
            ->where('recipient_id', $teacher->id)
            ->where('source_id', $teacherMessageId)
            ->count());

        $this->actingAs($student)
            ->get("/notifications/{$materialNotification->id}/open")
            ->assertRedirect("/dashboard?section=material&chapter_id={$chapterId}");
        $this->actingAs($teacher)
            ->get("/notifications/{$teacherChatNotification->id}/open")
            ->assertRedirect('/dashboard?page=orang-tua&relationship_id=' . $approvedRelationId);
        $this->actingAs($parent)
            ->get("/notifications/{$parentChatNotification->id}/open")
            ->assertRedirect('/dashboard?page=chat&student_id=' . $student->id . '&teacher_id=' . $teacher->id);

        $this->actingAs($parent)
            ->get("/notifications/{$teacherChatNotification->id}/open")
            ->assertNotFound();
        $this->actingAs($teacher)
            ->get("/notifications/{$parentChatNotification->id}/open")
            ->assertNotFound();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_chat_notifications_follow_quiz_owner_and_remain_persistent_and_idempotent(): void
    {
        $teacher = $this->createUser('guru', 'notification-chat-quiz-owner');
        $otherTeacher = $this->createUser('guru', 'notification-chat-other-owner');
        $student = $this->createUser('siswa', 'notification-chat-quiz-student', 'VII B');
        $parent = $this->createUser('orang_tua', 'notification-chat-quiz-parent');
        $otherParent = $this->createUser('orang_tua', 'notification-chat-quiz-other-parent');
        $this->link($student, $parent, 'approved');

        $chapterId = DB::table('chapters')->insertGetId([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_assessment_results')->insert([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $chapterId,
            'total_questions' => 5,
            'correct_answers' => 4,
            'score' => 80,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $relationshipId = (int) DB::table('student_guardians')
            ->where('student_id', $student->id)
            ->where('guardian_id', $parent->id)
            ->value('id');

        DB::commit();

        $clientMessageId = 'c2a80123-4567-4abc-8def-0123456789ab';
        $message = [
            'body' => 'Pesan dari Guru pemilik Kuis.',
            'client_message_id' => $clientMessageId,
        ];
        $sent = $this->actingAs($teacher)
            ->postJson("/guru/parent-contacts/{$relationshipId}/messages", $message)
            ->assertCreated();
        $this->postJson("/guru/parent-contacts/{$relationshipId}/messages", $message)
            ->assertCreated()
            ->assertJsonPath('id', $sent->json('id'));

        $parentNotification = DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('event_type', 'chat_message')
            ->first();
        $this->assertNotNull($parentNotification);
        $this->assertSame('orang_tua', $parentNotification->recipient_role);
        $this->assertSame((int) $sent->json('id'), (int) $parentNotification->source_id);
        $this->assertSame('Pesan baru dari ' . $teacher->name, $parentNotification->title);
        $this->assertSame(
            '/dashboard?page=chat&student_id=' . $student->id . '&teacher_id=' . $teacher->id,
            $parentNotification->target_url
        );
        $this->assertSame(1, app(WebsiteNotificationService::class)->unreadCountFor($parent));
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('source_id', $sent->json('id'))
            ->count());
        $this->assertSame(0, DB::table('website_notifications')
            ->whereIn('recipient_id', [$otherTeacher->id, $otherParent->id])
            ->where('source_id', $sent->json('id'))
            ->count());

        $this->actingAs($parent)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-notification-count')
            ->assertSee('>1</span>', false)
            ->assertSee($teacher->name);
        $this->get("/notifications/{$parentNotification->id}/open")
            ->assertRedirect(
                '/dashboard?page=chat&student_id=' . $student->id . '&teacher_id=' . $teacher->id
            );
        $this->assertSame(0, app(WebsiteNotificationService::class)->unreadCountFor($parent));

        $parentReply = $this->postJson('/orang-tua/chat/messages', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'body' => 'Balasan dari Orang Tua.',
            'client_message_id' => 'd2a80123-4567-4abc-8def-0123456789ab',
        ])->assertCreated();
        $teacherNotification = DB::table('website_notifications')
            ->where('recipient_id', $teacher->id)
            ->where('event_type', 'chat_message')
            ->first();
        $this->assertNotNull($teacherNotification);
        $this->assertSame((int) $parentReply->json('id'), (int) $teacherNotification->source_id);
        $this->assertSame('Pesan baru dari ' . $parent->name, $teacherNotification->title);
        $this->assertSame(
            '/dashboard?page=orang-tua&relationship_id=' . $relationshipId,
            $teacherNotification->target_url
        );
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $teacher->id)
            ->where('event_type', 'chat_message')
            ->count());
        $this->actingAs($teacher)
            ->get("/notifications/{$teacherNotification->id}/open")
            ->assertRedirect('/dashboard?page=orang-tua&relationship_id=' . $relationshipId);

        $this->actingAs($parent)
            ->getJson('/orang-tua/chat/messages?student_id=' . $student->id . '&teacher_id=' . $teacher->id)
            ->assertOk()
            ->assertJsonCount(2, 'messages');
        $this->assertNotNull(DB::table('website_notifications')
            ->where('id', $parentNotification->id)
            ->value('read_at'));
    }

    public function test_notifications_can_only_be_deleted_by_their_recipient_without_deleting_source_data(): void
    {
        $student = $this->createUser('siswa', 'notification-delete-student');
        $teacher = $this->createUser('guru', 'notification-delete-teacher');
        $parent = $this->createUser('orang_tua', 'notification-delete-parent');
        $otherUser = $this->createUser('siswa', 'notification-delete-other');

        $resultId = DB::table('student_assessment_results')->insertGetId([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => null,
            'subchapter_id' => null,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notifications = [];
        foreach ([$student, $teacher, $parent] as $recipient) {
            $notifications[$recipient->role] = DB::table('website_notifications')->insertGetId([
                'recipient_id' => $recipient->id,
                'recipient_role' => $recipient->role,
                'event_key' => 'delete-test-' . $recipient->role,
                'event_type' => 'quiz_result',
                'source_type' => 'student_assessment_results',
                'source_id' => $resultId,
                'student_id' => $student->id,
                'title' => 'Hasil Kuis',
                'message' => 'Hasil tersimpan.',
                'target_url' => '/dashboard',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $studentNotificationId = $notifications['siswa'];
        $this->actingAs($otherUser)
            ->deleteJson(route('notifications.destroy', $studentNotificationId))
            ->assertNotFound();
        $this->assertDatabaseHas('website_notifications', ['id' => $studentNotificationId]);

        foreach ([$student, $teacher, $parent] as $recipient) {
            $notificationId = $notifications[$recipient->role];

            $this->actingAs($recipient)
                ->deleteJson(route('notifications.destroy', $notificationId))
                ->assertOk()
                ->assertJson(['deleted' => true]);

            $this->assertDatabaseMissing('website_notifications', ['id' => $notificationId]);
            $this->assertFalse(
                app(WebsiteNotificationService::class)->latestFor($recipient)
                    ->contains('id', $notificationId)
            );
        }

        $this->assertDatabaseHas('student_assessment_results', [
            'id' => $resultId,
            'user_id' => $student->id,
            'score' => 100,
        ]);
    }

    private function assertNotificationIndexes(): void
    {
        $indexes = collect(Schema::getIndexes('website_notifications'));
        $unique = $indexes->firstWhere('name', 'website_notifications_recipient_event_unique');
        $this->assertNotNull($unique);
        $this->assertTrue($unique['unique']);
        $this->assertSame(['recipient_id', 'event_key'], $unique['columns']);
        $this->assertNotNull($indexes->firstWhere('name', 'website_notifications_inbox_index'));
        $this->assertNotNull($indexes->firstWhere('name', 'website_notifications_source_index'));

        $foreignKeys = collect(Schema::getForeignKeys('website_notifications'));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['recipient_id']
                && $foreignKey['foreign_table'] === 'users'
        ));
        $this->assertTrue(Schema::hasColumn('website_notifications', 'read_at'));
    }

    private function link(User $student, User $parent, string $status): void
    {
        DB::table('student_guardians')->insert([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(string $role, string $username, ?string $className = null): User
    {
        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'kelas' => $className,
            'email' => null,
        ]);
    }
}
