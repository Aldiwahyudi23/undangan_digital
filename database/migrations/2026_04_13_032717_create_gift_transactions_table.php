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
    Schema::create('gift_transactions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
        $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();

        $table->string('name'); // nama pengirim (bisa manual)
        $table->integer('amount');
        
        $table->string('payment_method')->nullable(); // qris, bank, dll
        $table->string('transaction_id')->nullable(); // dari gateway
        
        $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_transactions');
    }
};
