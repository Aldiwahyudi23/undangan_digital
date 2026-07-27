<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_barcodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
             $table->foreignId('invitation_id')->constrained()->onDelete('cascade');
            $table->string('barcode_code')->unique();
            $table->string('barcode_token')->unique();
            $table->foreignId('invitation_guest_id')->nullable()->constrained('invitation_guests')->nullOnDelete();
            $table->foreignId('barcode_pdf_batch_id')->nullable()->constrained('barcode_pdf_batches')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_barcodes');
    }
};
