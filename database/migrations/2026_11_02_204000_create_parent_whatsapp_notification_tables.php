<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        if (
            Schema::hasTable('whatsapp_notification_outbox')
            && DB::table('whatsapp_notification_outbox')->exists()
        ) {
            throw new RuntimeException(
                'WhatsApp delivery history exists; retain or export it before removing this migration.'
            );
        }

        if (
            Schema::hasTable('whatsapp_phone_verifications')
            && DB::table('whatsapp_phone_verifications')->exists()
        ) {
            throw new RuntimeException(
                'WhatsApp verification history exists; retain or export it before removing this migration.'
            );
        }

        $userWhatsAppColumns = array_filter([
            Schema::hasColumn('users', 'whatsapp_phone_verified_at')
                ? 'whatsapp_phone_verified_at'
                : null,
            Schema::hasColumn('users', 'whatsapp_notifications_consented_at')
                ? 'whatsapp_notifications_consented_at'
                : null,
            Schema::hasColumn('users', 'whatsapp_notifications_revoked_at')
                ? 'whatsapp_notifications_revoked_at'
                : null,
        ]);
        $hasUserWhatsAppData = false;
        if ($userWhatsAppColumns !== []) {
            $hasUserWhatsAppData = DB::table('users')
                ->where(function ($query) use ($userWhatsAppColumns) {
                    foreach ($userWhatsAppColumns as $column) {
                        $query->orWhereNotNull($column);
                    }
                })
                ->exists();
        }
        if ($hasUserWhatsAppData) {
            throw new RuntimeException(
                'WhatsApp verification or consent data exists; retain it before removing this migration.'
            );
        }

        Schema::dropIfExists('whatsapp_notification_outbox');
        Schema::dropIfExists('whatsapp_phone_verifications');

        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'whatsapp_phone_verified_at')
                    ? 'whatsapp_phone_verified_at'
                    : null,
                Schema::hasColumn('users', 'whatsapp_notifications_consented_at')
                    ? 'whatsapp_notifications_consented_at'
                    : null,
                Schema::hasColumn('users', 'whatsapp_notifications_revoked_at')
                    ? 'whatsapp_notifications_revoked_at'
                    : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
