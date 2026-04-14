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
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('label')->nullable();
            $table->text('url_frame')->nullable();
            $table->text('address');
            $table->foreignId('image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // link_rute_mobil, motor, jalan_kaki, lat, lon
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
