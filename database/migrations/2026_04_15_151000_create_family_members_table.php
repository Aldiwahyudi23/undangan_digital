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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
             // relasi ke undangan
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();

            // basic info
            $table->string('name');
            $table->string('role')->nullable(); // contoh: Ayah, Ibu, Kakak, dll

            // 🔥 grouping (penting banget)
            $table->enum('group', [
                'bride_family',      // keluarga wanita
                'groom_family',      // keluarga pria
                'bride_invite',      // turut mengundang wanita
                'groom_invite'       // turut mengundang pria
            ]);

            // 🔥 penanda utama
            $table->boolean('is_core')->default(true); 
            // true = keluarga inti
            // false = pasangan / bawaan / turut

            // 🔥 opsional tambahan (biar fleksibel)
            $table->string('relation_label')->nullable(); 
            // contoh bebas: "Istri dari Kakak", "Anak ke 2", dll

            // 🔥 urutan tampil
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
