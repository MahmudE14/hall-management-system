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
        Schema::create('block_tutor_profile', function (Blueprint $table) {
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->unique(['block_id', 'tutor_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('block_tutor_profile');
    }
};
