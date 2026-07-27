<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_guests', function (Blueprint $table) {
            $table->enum('invitation_type', ['digital', 'physical'])->default('digital')->after('location_tag');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_guests', function (Blueprint $table) {
            $table->dropColumn('invitation_type');
        });
    }
};
