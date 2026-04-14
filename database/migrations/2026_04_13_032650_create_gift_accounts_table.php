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
       Schema::create('gift_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('couple_id')->constrained()->cascadeOnDelete();

            $table->string('bank_name'); // BCA, Mandiri, Dana, OVO, dll
            $table->string('account_number');
            $table->string('account_name'); // Nama pemilik rekening

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_accounts');
    }
};
