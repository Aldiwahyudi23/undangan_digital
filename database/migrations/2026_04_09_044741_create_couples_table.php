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
        Schema::create('couples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->enum('gender', ['male', 'female']);
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->integer('birth_order')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couples');
    }
};
