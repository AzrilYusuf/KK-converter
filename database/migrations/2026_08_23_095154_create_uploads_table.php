<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique();
            $table->string('original_filename');
            $table->string('file_path');
            $table->enum('file_type', ['pdf', 'jpg', 'png']);
            $table->enum('status', ['uploaded', 'in_progress', 'completed'])->default('uploaded');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
