<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_role', 20);
            $table->string('event_key', 191);
            $table->string('event_type', 40);
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('title', 120);
            $table->string('message', 500);
            $table->string('target_url', 512);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['recipient_id', 'event_key'], 'website_notifications_recipient_event_unique');
            $table->index(
                ['recipient_id', 'recipient_role', 'read_at', 'created_at'],
                'website_notifications_inbox_index'
            );
            $table->index(['source_type', 'source_id'], 'website_notifications_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_notifications');
    }
};
