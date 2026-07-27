<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_checkins', function (Blueprint $table) {
            $table->id();
             $table->foreignId('invitation_id')->constrained()->onDelete('cascade');
            $table->foreignId('invitation_guest_id')->constrained('invitation_guests')->cascadeOnDelete();
            $table->foreignId('invitation_barcode_id')->nullable()->constrained('invitation_barcodes')->nullOnDelete();
            $table->timestamp('checkin_at');
            $table->timestamp('checkout_at')->nullable();
            $table->integer('attended_people')->default(1);
            $table->enum('arrival_with', ['sendiri', 'suami', 'istri', 'anak', 'orang_tua', 'saudara', 'teman'])->default('sendiri');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_checkins');
    }
};
