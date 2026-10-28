<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_teacher_messages', function (Blueprint $table) {
            $table->uuid('client_message_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('parent_teacher_messages')
            && DB::table('parent_teacher_messages')->whereNotNull('client_message_id')->exists()
        ) {
            throw new RuntimeException(
                'Cannot drop chat idempotency keys while messages reference them.'
            );
        }

        Schema::table('parent_teacher_messages', function (Blueprint $table) {
            $table->dropUnique('parent_teacher_messages_client_message_id_unique');
            $table->dropColumn('client_message_id');
        });
    }
};