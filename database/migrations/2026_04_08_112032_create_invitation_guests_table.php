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
        Schema::create('invitation_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuid('uuid')->unique();
            $table->string('token')->nullable();

            $table->string('name');
            $table->string('share_whatsapp');
            $table->text('note')->nullable();
            
            $table->string('group_name')->nullable();
            $table->string('location_tag')->nullable();
            
            // 📊 tracking
            $table->boolean('is_opened')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->integer('max_device')->default(1);
            $table->json('device_ids')->nullable();
            
            // 🔐 Security tracking
            $table->string('last_ip')->nullable();
            $table->text('last_user_agent')->nullable();
            
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_streaming')->default(false);
            $table->enum('role', ['host', 'guest'])->default('guest');
            $table->json('permissions')->nullable(); // untuk kebutuhan custom di masa depan   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_guests');
    }
};
