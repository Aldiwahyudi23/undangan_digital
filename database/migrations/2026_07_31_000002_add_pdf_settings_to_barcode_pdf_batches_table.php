<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barcode_pdf_batches', function (Blueprint $table) {
            $table->json('pdf_settings')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('barcode_pdf_batches', function (Blueprint $table) {
            $table->dropColumn('pdf_settings');
        });
    }
};
