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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_id')->unique();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('student_profile_id');
            $table->index('category');
            $table->index('status');
            $table->index('assignee_id');
            $table->index('created_at');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
