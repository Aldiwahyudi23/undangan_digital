<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doorprize_winners', function (Blueprint $table) {
            $table->id();
             $table->foreignId('invitation_id')->constrained()->onDelete('cascade');
            $table->foreignId('invitation_guest_id')->constrained('invitation_guests')->cascadeOnDelete();
            $table->string('prize');
            $table->string('session')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doorprize_winners');
    }
};
