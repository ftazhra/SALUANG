<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruMaterialPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_can_save_a_single_prompt(): void
    {
        $guru = $this->createGuru();

        $response = $this->actingAs($guru)->postJson(
            '/guru/chapters',
            $this->chapterPayload('Pernah melihat ikan saluang?')
        );

        $response->assertCreated();
        $this->assertDatabaseHas('chapters', [
            'user_id' => $guru->id,
            'pertanyaan_pemantik' => 'Pernah melihat ikan saluang?',
        ]);
    }

    public function test_guru_cannot_save_multiple_new_prompts(): void
    {
        $guru = $this->createGuru();

        $response = $this->actingAs($guru)->postJson(
            '/guru/chapters',
            $this->chapterPayload(
                "Pernah melihat ikan saluang?\nApa makanannya?"
            )
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('pertanyaan_pemantik');
    }

    public function test_unchanged_legacy_multiple_prompts_remain_editable(): void
    {
        $guru = $this->createGuru();
        $legacyPrompt = "Pernah melihat ikan saluang?\nApa makanannya?";

        $chapter = Chapter::create(array_merge(
            $this->chapterPayload($legacyPrompt),
            ['user_id' => $guru->id]
        ));

        $response = $this->actingAs($guru)->putJson(
            '/guru/chapters/' . $chapter->id,
            $this->chapterPayload($legacyPrompt)
        );

        $response->assertOk();
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'pertanyaan_pemantik' => $legacyPrompt,
        ]);
    }

    public function test_guru_cannot_replace_a_legacy_prompt_with_multiple_prompts(): void
    {
        $guru = $this->createGuru();
        $legacyPrompt = "Pertanyaan lama?\nPertanyaan lama kedua?";

        $chapter = Chapter::create(array_merge(
            $this->chapterPayload($legacyPrompt),
            ['user_id' => $guru->id]
        ));

        $response = $this->actingAs($guru)->putJson(
            '/guru/chapters/' . $chapter->id,
            $this->chapterPayload(
                "Pertanyaan baru?\nPertanyaan baru kedua?"
            )
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('pertanyaan_pemantik');

        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'pertanyaan_pemantik' => $legacyPrompt,
        ]);
    }

    public function test_edit_without_introduction_keeps_existing_introduction(): void
    {
        $guru = $this->createGuru();
        $chapter = Chapter::create(array_merge(
            $this->chapterPayload('Pertanyaan pemantik lama?'),
            [
                'user_id' => $guru->id,
                'pengantar' => 'Pengantar lama tetap dipertahankan.',
            ]
        ));
        $payload = $this->chapterPayload('Pertanyaan pemantik baru?');
        unset($payload['pengantar']);
        $payload['deskripsi'] = 'Deskripsi telah diperbarui.';

        $response = $this->actingAs($guru)->putJson(
            '/guru/chapters/' . $chapter->id,
            $payload
        );

        $response->assertOk();
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'deskripsi' => 'Deskripsi telah diperbarui.',
            'pengantar' => 'Pengantar lama tetap dipertahankan.',
        ]);
    }

    private function createGuru(): User
    {
        return User::create([
            'name' => 'Guru SALUANG',
            'username' => 'guru-saluang',
            'password' => 'secret123',
            'role' => 'guru',
            'email' => null,
        ]);
    }

    private function chapterPayload(string $prompt): array
    {
        return [
            'nomor_bab' => 1,
            'judul' => 'Interaksi Makhluk Hidup',
            'deskripsi' => 'Materi kelas VII.',
            'pengantar' => 'Pengantar Bab.',
            'tujuan_pembelajaran' => [
                'Mengidentifikasi makhluk hidup.',
            ],
            'pertanyaan_pemantik' => $prompt,
            'is_published' => false,
        ];
    }
}
