<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->onDelete('cascade');
            $table->foreignId('invitation_guest_id')->constrained()->onDelete('cascade');

            // 💬 Isi chat
            $table->text('message');

            // 🧩 Type chat
            $table->enum('type', ['text', 'system'])->default('text');

            // 🗑 Soft delete versi custom
            $table->boolean('is_deleted')->default(false);

            $table->timestamps();

            // ⚡ Index (biar query cepat)
            $table->index('invitation_id');
            $table->index('invitation_guest_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chats');
    }
};
