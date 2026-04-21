<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_interactions', function (Blueprint $table) {
            $table->id('ai_id');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects', 'project_id')->onDelete('set null');
            $table->foreignId('task_id')->nullable()->constrained('tasks', 'task_id')->onDelete('set null');
            $table->string('request_type');
            $table->text('input_data');
            $table->text('output_data');
            $table->timestamp('request_date')->useCurrent();
            $table->timestamps();

            $table->index('user_id');
            $table->index('request_date');
            $table->index(['project_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_interactions');
    }
};
