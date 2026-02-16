<?php
// database/migrations/xxxx_create_conversation_contexts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversation_contexts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('context_data')->nullable(); // Store NLP context
            $table->integer('conversation_turns')->default(0);
            $table->timestamps();
        });

        Schema::create('nlp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('user_message');
            $table->string('intent')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('entities')->nullable();
            $table->string('action')->nullable();
            $table->boolean('needs_clarification')->default(false);
            $table->text('clarification_question')->nullable();
            $table->json('nlp_response')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nlp_logs');
        Schema::dropIfExists('conversation_contexts');
    }
};