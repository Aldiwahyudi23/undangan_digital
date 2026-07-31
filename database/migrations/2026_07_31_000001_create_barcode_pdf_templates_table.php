<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('paper_width_mm', 8, 2);
            $table->decimal('paper_height_mm', 8, 2);
            $table->decimal('margin_top_mm', 8, 2)->default(0);
            $table->decimal('margin_right_mm', 8, 2)->default(0);
            $table->decimal('margin_bottom_mm', 8, 2)->default(0);
            $table->decimal('margin_left_mm', 8, 2)->default(0);
            $table->decimal('label_width_mm', 8, 2);
            $table->decimal('label_height_mm', 8, 2);
            $table->decimal('gap_x_mm', 8, 2)->default(0);
            $table->decimal('gap_y_mm', 8, 2)->default(0);
            $table->integer('columns')->default(1);
            $table->integer('rows')->default(1);
            $table->decimal('corner_radius_mm', 8, 2)->nullable();
            $table->decimal('border_width_mm', 8, 2)->default(0.3);
            $table->string('border_style')->default('dashed');
            $table->boolean('show_header')->default(false);
            $table->decimal('header_height_mm', 8, 2)->default(14);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_pdf_templates');
    }
};
