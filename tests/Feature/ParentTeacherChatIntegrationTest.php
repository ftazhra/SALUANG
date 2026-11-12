<?php

namespace Tests\Feature;

use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ParentTeacherChatIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_and_authorized_teacher_exchange_persistent_idempotent_messages_in_child_threads(): void
    {
        [$parent, $teacherA, $teacherB, $studentA, $studentB, $relationA, $relationB] = $this->createTwoChildThreads();

        $teacherThreads = $this->actingAs($parent)
            ->getJson('/orang-tua/chat/teachers')
            ->assertOk()
            ->assertJsonCount(2, 'teachers');
        $this->assertSame($studentA->id, $teacherThreads->json('teachers.0.student_id'));
        $this->assertSame($teacherA->id, $teacherThreads->json('teachers.0.id'));
        $this->assertSame($studentB->id, $teacherThreads->json('teachers.1.student_id'));
        $this->assertSame($teacherB->id, $teacherThreads->json('teachers.1.id'));
        $this->assertStringNotContainsString('nomor_telepon', $teacherThreads->getContent());

        $clientMessageId = 'c0a80123-4567-4abc-8def-0123456789ab';
        $parentMessage = [
            'student_id' => $studentA->id,
            'teacher_id' => $teacherA->id,
            'body' => '<script>alert("text only")</script>',
            'client_message_id' => $clientMessageId,
        ];
        $firstSend = $this->postJson('/orang-tua/chat/messages', $parentMessage)
            ->assertCreated();
        $retrySend = $this->postJson('/orang-tua/chat/messages', $parentMessage)
            ->assertCreated();
        $this->assertSame($firstSend->json('id'), $retrySend->json('id'));
        $this->assertDatabaseCount('parent_teacher_messages', 1);
        $this->assertDatabaseHas('parent_teacher_messages', [
            'id' => $firstSend->json('id'),
            'parent_id' => $parent->id,
            'student_id' => $studentA->id,
            'teacher_id' => $teacherA->id,
            'sender_id' => $parent->id,
            'body' => '<script>alert("text only")</script>',
        ]);

        $this->actingAs($teacherA)
            ->getJson("/guru/parent-contacts/{$relationA->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.is_mine', false)
            ->assertJsonPath('messages.0.body', '<script>alert("text only")</script>');

        $reply = $this->postJson("/guru/parent-contacts/{$relationA->id}/messages", [
            'body' => 'Pesan diterima, terima kasih.',
            'client_message_id' => 'd0a80123-4567-4abc-8def-0123456789ab',
        ])->assertCreated();
        $this->assertDatabaseHas('parent_teacher_messages', [
            'id' => $reply->json('id'),
            'parent_id' => $parent->id,
            'student_id' => $studentA->id,
            'teacher_id' => $teacherA->id,
            'sender_id' => $teacherA->id,
            'body' => 'Pesan diterima, terima kasih.',
        ]);

        $parentHistory = $this->actingAs($parent)
            ->getJson("/orang-tua/chat/messages?student_id={$studentA->id}&teacher_id={$teacherA->id}")
            ->assertOk()
            ->assertJsonCount(2, 'messages');
        $this->assertSame($firstSend->json('id'), $parentHistory->json('messages.0.id'));
        $this->assertSame($reply->json('id'), $parentHistory->json('messages.1.id'));
        $this->assertSame(true, $parentHistory->json('messages.0.is_mine'));
        $this->assertSame(false, $parentHistory->json('messages.1.is_mine'));

        $this->getJson("/orang-tua/chat/messages?student_id={$studentB->id}&teacher_id={$teacherB->id}")
            ->assertOk()
            ->assertJsonCount(0, 'messages');
        $this->actingAs($teacherB)
            ->getJson("/guru/parent-contacts/{$relationB->id}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'messages');
    }

    public function test_chat_rejects_wrong_roles_other_threads_invalid_messages_and_anonymous_users(): void
    {
        [$parent, $teacherA, $teacherB, $studentA, $studentB, $relationA, $relationB] = $this->createTwoChildThreads();
        $otherParent = $this->createUser('orang_tua', 'chat-unrelated-parent');
        $unassignedTeacher = $this->createUser('guru', 'chat-unassigned-teacher');
        $studentC = $this->createStudent('chat-student-c', 'VII C', '3333333333');
        $pendingParent = $this->createUser('orang_tua', 'chat-pending-parent');
        $pendingRelation = $this->link($studentC, $pendingParent, 'pending');

        $this->getJson('/orang-tua/chat/teachers')->assertUnauthorized();
        $this->getJson("/guru/parent-contacts/{$relationA->id}/messages")->assertUnauthorized();

        $this->actingAs($studentA)->getJson('/orang-tua/chat/teachers')->assertForbidden();
        $this->getJson("/guru/parent-contacts/{$relationA->id}/messages")->assertForbidden();

        $this->actingAs($parent)
            ->getJson("/orang-tua/chat/messages?student_id={$studentB->id}&teacher_id={$teacherB->id}")
            ->assertOk();
        $this->actingAs($otherParent)
            ->getJson("/orang-tua/chat/messages?student_id={$studentA->id}&teacher_id={$teacherA->id}")
            ->assertNotFound();
        $this->actingAs($parent)
            ->postJson('/orang-tua/chat/messages', [
                'student_id' => $studentA->id,
                'teacher_id' => $unassignedTeacher->id,
                'body' => 'Tidak berwenang',
                'client_message_id' => 'e0a80123-4567-4abc-8def-0123456789ab',
            ])
            ->assertNotFound();
        $this->actingAs($teacherB)
            ->getJson("/guru/parent-contacts/{$relationA->id}/messages")
            ->assertNotFound();
        $this->actingAs($teacherB)
            ->postJson("/guru/parent-contacts/{$relationA->id}/messages", [
                'body' => 'Tidak berwenang',
                'client_message_id' => 'f0a80123-4567-4abc-8def-0123456789ab',
            ])
            ->assertNotFound();

        $this->actingAs($pendingParent)
            ->getJson('/orang-tua/chat/teachers')
            ->assertOk()
            ->assertJsonCount(0, 'teachers');
        $this->actingAs($teacherA)
            ->getJson("/guru/parent-contacts/{$pendingRelation->id}/messages")
            ->assertNotFound();
        $this->actingAs($pendingParent)
            ->postJson('/orang-tua/chat/messages', [
                'student_id' => $studentC->id,
                'teacher_id' => $teacherA->id,
                'body' => 'Relasi belum disetujui',
                'client_message_id' => 'a0a80123-4567-4abc-8def-0123456789ab',
            ])
            ->assertNotFound();

        $this->actingAs($parent)
            ->postJson('/orang-tua/chat/messages', [
                'student_id' => $studentA->id,
                'teacher_id' => $teacherA->id,
                'body' => '   ',
                'client_message_id' => 'b0a80123-4567-4abc-8def-0123456789ab',
            ])
            ->assertUnprocessable();
        $this->actingAs($parent)
            ->postJson('/orang-tua/chat/messages', [
                'student_id' => $studentA->id,
                'teacher_id' => $teacherA->id,
                'body' => str_repeat('x', 2001),
                'client_message_id' => 'b1a80123-4567-4abc-8def-0123456789ab',
            ])
            ->assertUnprocessable();
        $this->actingAs($teacherA)
            ->postJson("/guru/parent-contacts/{$relationA->id}/messages", [
                'body' => "\n  ",
                'client_message_id' => 'c1a80123-4567-4abc-8def-0123456789ab',
            ])->assertUnprocessable();
        $this->actingAs($parent)
            ->postJson('/orang-tua/chat/messages', [
                'student_id' => $studentA->id,
                'teacher_id' => $teacherA->id,
                'body' => 'Tidak ada ID idempotensi',
            ])->assertUnprocessable();

        $this->assertDatabaseCount('parent_teacher_messages', 0);
        $this->assertTrue(DB::table('teacher_class_assignments')
            ->where('teacher_id', $unassignedTeacher->id)
            ->doesntExist());
    }

    public function test_teacher_threads_expose_activity_based_online_presence(): void
    {
        [$parent, $teacher, , $student, , $relationship] = $this->createTwoChildThreads();
        $presenceCache = Cache::store('file');
        $presenceCache->forget('chat-presence:user:' . $teacher->id);
        $presenceCache->forget('chat-presence:throttle:' . $teacher->id);

        $this->actingAs($teacher)
            ->postJson('/chat/presence/activity')
            ->assertOk()
            ->assertJsonPath('recorded', true);

        $response = $this->actingAs($parent)
            ->getJson('/orang-tua/chat/teachers')
            ->assertOk();

        $teacherThread = collect($response->json('teachers'))->first(fn ($thread) => (int) $thread['id'] === (int) $teacher->id);
        $this->assertNotNull($teacherThread);
        $this->assertTrue((bool) ($teacherThread['is_online'] ?? false));
        $this->assertNotNull($teacherThread['last_seen_at'] ?? null);

        $presenceCache->put(
            'chat-presence:user:' . $teacher->id,
            now()->subMinutes(5)->toIso8601String(),
            now()->addDays(30)
        );

        $staleResponse = $this->actingAs($parent)->getJson('/orang-tua/chat/teachers')->assertOk();
        $staleThread = collect($staleResponse->json('teachers'))->first(fn ($thread) => (int) $thread['id'] === (int) $teacher->id);
        $this->assertFalse((bool) ($staleThread['is_online'] ?? true));
        $this->assertNotNull($staleThread['last_seen_at'] ?? null);

        $presenceCache->forget('chat-presence:user:' . $parent->id);
        $presenceCache->forget('chat-presence:throttle:' . $parent->id);
        $this->actingAs($parent)
            ->getJson("/orang-tua/chat/messages?student_id={$student->id}&teacher_id={$teacher->id}")
            ->assertOk();
        $this->assertNull($presenceCache->get('chat-presence:user:' . $parent->id));

        $this->actingAs($parent)->postJson('/chat/presence/activity')->assertOk();
        $this->actingAs($teacher)
            ->getJson("/guru/parent-contacts/{$relationship->id}/presence")
            ->assertOk()
            ->assertJsonPath('is_online', true);

        $student = $this->createStudent('chat-no-presence-access', 'VII A', '7777777777');
        $this->actingAs($student)->postJson('/chat/presence/activity')->assertForbidden();
    }

    public function test_chat_unread_counts_are_per_thread_and_opening_the_thread_marks_incoming_messages_read(): void
    {
        [$parent, $teacher, , $student, , $relationship] = $this->createTwoChildThreads();
        $message = DB::table('parent_teacher_messages')->insertGetId([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'sender_id' => $parent->id,
            'client_message_id' => 'c2a80123-4567-4abc-8def-0123456789ab',
            'body' => 'Pesan orang tua.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('website_notifications')->insert([
            'recipient_id' => $teacher->id,
            'recipient_role' => 'guru',
            'event_key' => 'chat_message:' . $message,
            'event_type' => 'chat_message',
            'source_type' => 'parent_teacher_message',
            'source_id' => $message,
            'student_id' => $student->id,
            'title' => 'Pesan baru',
            'message' => 'Ada pesan baru.',
            'target_url' => '/dashboard?page=orang-tua&relationship_id=' . $relationship->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 1);
        $this->actingAs($teacher)
            ->getJson("/guru/parent-contacts/{$relationship->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'messages');
        $this->actingAs($teacher)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 0);
        $this->assertNotNull(DB::table('website_notifications')
            ->where('source_id', $message)
            ->value('read_at'));

        $teacherMessage = DB::table('parent_teacher_messages')->insertGetId([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'sender_id' => $teacher->id,
            'client_message_id' => 'd2a80123-4567-4abc-8def-0123456789ab',
            'body' => 'Balasan Guru.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('website_notifications')->insert([
            'recipient_id' => $parent->id,
            'recipient_role' => 'orang_tua',
            'event_key' => 'chat_message:' . $teacherMessage,
            'event_type' => 'chat_message',
            'source_type' => 'parent_teacher_message',
            'source_id' => $teacherMessage,
            'student_id' => $student->id,
            'title' => 'Pesan baru',
            'message' => 'Ada pesan baru.',
            'target_url' => '/dashboard?page=chat&student_id=' . $student->id . '&teacher_id=' . $teacher->id,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($parent)
            ->getJson('/orang-tua/chat/teachers')
            ->assertOk()
            ->assertJsonPath('teachers.0.unread_count', 1);
        $this->actingAs($parent)
            ->getJson("/orang-tua/chat/messages?student_id={$student->id}&teacher_id={$teacher->id}")
            ->assertOk()
            ->assertJsonCount(2, 'messages');
        $this->actingAs($parent)
            ->getJson('/orang-tua/chat/teachers')
            ->assertOk()
            ->assertJsonPath('teachers.0.unread_count', 0);
        $this->assertNotNull(DB::table('website_notifications')
            ->where('source_id', $teacherMessage)
            ->value('read_at'));
    }

    public function test_assignment_or_approval_revocation_immediately_blocks_existing_chat_history(): void
    {
        [$parent, $teacher, , $student, , $relation] = $this->createTwoChildThreads();

        $this->actingAs($parent)->postJson('/orang-tua/chat/messages', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'body' => 'Pesan sebelum pencabutan.',
            'client_message_id' => 'd1a80123-4567-4abc-8def-0123456789ab',
        ])->assertCreated();

        DB::table('teacher_class_assignments')
            ->where('teacher_id', $teacher->id)
            ->where('class_name', $student->kelas)
            ->delete();

        $this->getJson("/orang-tua/chat/messages?student_id={$student->id}&teacher_id={$teacher->id}")
            ->assertNotFound();
        $this->actingAs($teacher)
            ->getJson("/guru/parent-contacts/{$relation->id}/messages")
            ->assertNotFound();

        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => $student->kelas,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_guardians')->where('id', $relation->id)->update(['status' => 'rejected']);

        $this->actingAs($parent)
            ->getJson("/orang-tua/chat/messages?student_id={$student->id}&teacher_id={$teacher->id}")
            ->assertNotFound();
        $this->actingAs($teacher)
            ->getJson("/guru/parent-contacts/{$relation->id}/messages")
            ->assertNotFound();
    }

    public function test_teacher_assigned_to_multiple_classes_can_read_each_authorized_child_thread(): void
    {
        [, $teacherA, , , $studentB, , $relationB] = $this->createTwoChildThreads();
        $this->assign($teacherA, 'VII B');

        $this->actingAs($teacherA)
            ->getJson("/guru/parent-contacts/{$relationB->id}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'messages');
        $this->assertSame('VII B', $studentB->kelas);
    }

    public function test_idempotency_migration_refuses_to_drop_keys_referenced_by_messages(): void
    {
        [$parent, $teacher, , $student] = $this->createTwoChildThreads();
        DB::table('parent_teacher_messages')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'sender_id' => $parent->id,
            'client_message_id' => 'e1a80123-4567-4abc-8def-0123456789ab',
            'body' => 'Pesan tersimpan.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_28_150000_add_client_message_id_to_parent_teacher_messages_table.php');
        try {
            $migration->down();
            $this->fail('Rollback must preserve idempotency keys referenced by saved messages.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('idempotency keys', $exception->getMessage());
        }

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('parent_teacher_messages', 'client_message_id'));
        $this->assertDatabaseCount('parent_teacher_messages', 1);
    }

    private function createTwoChildThreads(): array
    {
        $parent = $this->createUser('orang_tua', 'chat-parent-two-children');
        $teacherA = $this->createUser('guru', 'chat-teacher-a');
        $teacherB = $this->createUser('guru', 'chat-teacher-b');
        $studentA = $this->createStudent('chat-student-a', 'VII A', '1111111111');
        $studentB = $this->createStudent('chat-student-b', 'VII B', '2222222222');
        $relationA = $this->link($studentA, $parent, 'approved');
        $relationB = $this->link($studentB, $parent, 'approved');
        $this->assign($teacherA, 'VII A');
        $this->assign($teacherB, 'VII B');

        return [$parent, $teacherA, $teacherB, $studentA, $studentB, $relationA, $relationB];
    }

    private function assign(User $teacher, string $className): void
    {
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => $className,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function link(User $student, User $parent, string $status): StudentGuardian
    {
        return StudentGuardian::query()->create([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => $status,
        ]);
    }

    private function createStudent(string $username, string $class, string $nisn): User
    {
        return $this->createUser('siswa', $username, [
            'kelas' => $class,
            'nisn' => $nisn,
        ]);
    }

    private function createUser(string $role, string $username, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
        ], $attributes));
    }
}
