<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_COLUMNS = [
        'whatsapp_phone_verified_at',
        'whatsapp_notifications_consented_at',
        'whatsapp_notifications_revoked_at',
    ];

    public function up(): void
    {
        if (!filter_var(env('SALUANG_CONFIRM_WHATSAPP_SCHEMA_CLEANUP', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException(
                'Set SALUANG_CONFIRM_WHATSAPP_SCHEMA_CLEANUP=true only after a database backup and explicit approval.'
            );
        }

        foreach (['whatsapp_phone_verifications', 'whatsapp_notification_outbox'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException(
                    "WhatsApp data exists in {$table}; export or retain it before cleanup."
                );
            }
        }

        $columns = array_values(array_filter(
            self::USER_COLUMNS,
            fn (string $column): bool => Schema::hasColumn('users', $column)
        ));
        if ($columns !== [] && DB::table('users')->where(function ($query) use ($columns) {
            foreach ($columns as $column) {
                $query->orWhereNotNull($column);
            }
        })->exists()) {
            throw new RuntimeException(
                'WhatsApp verification or consent data exists in users; export or retain it before cleanup.'
            );
        }

        foreach (['jobs', 'failed_jobs'] as $table) {
            if (
                Schema::hasTable($table)
                && Schema::hasColumn($table, 'payload')
                && DB::table($table)
                    ->where('payload', 'like', '%SendParentWhatsAppNotification%')
                    ->exists()
            ) {
                throw new RuntimeException(
                    "A WhatsApp notification job exists in {$table}; process or retain it before cleanup."
                );
            }
        }

        Schema::dropIfExists('whatsapp_notification_outbox');
        Schema::dropIfExists('whatsapp_phone_verifications');

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'whatsapp_phone_verified_at')) {
                    $table->timestamp('whatsapp_phone_verified_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'whatsapp_notifications_consented_at')) {
                    $table->timestamp('whatsapp_notifications_consented_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'whatsapp_notifications_revoked_at')) {
                    $table->timestamp('whatsapp_notifications_revoked_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('whatsapp_phone_verifications')) {
            Schema::create('whatsapp_phone_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
                $table->char('phone_hash', 64);
                $table->string('code_hash');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->index(['guardian_id', 'created_at'], 'wpv_guardian_created_idx');
            });
        }

        if (!Schema::hasTable('whatsapp_notification_outbox')) {
            Schema::create('whatsapp_notification_outbox', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->string('event_type', 40);
                $table->string('source_type', 40);
                $table->unsignedBigInteger('source_id');
                $table->string('deduplication_key', 191)->unique();
                $table->string('status', 20)->default('pending');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->string('provider_message_id')->nullable();
                $table->string('last_error_code', 64)->nullable();
                $table->timestamp('processing_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at'], 'wno_status_created_idx');
                $table->index(['recipient_id', 'status'], 'wno_recipient_status_idx');
                $table->index(['student_id', 'event_type', 'source_id'], 'wno_source_idx');
            });
        }
    }
};
