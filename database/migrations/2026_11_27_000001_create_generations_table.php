<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained();
            $table->string('provider_id')->nullable()->unique();
            $table->text('prompt');
            $table->string('size');
            $table->string('quality');
            $table->jsonb('reference_images')->default('[]');
            $table->string('status');
            $table->bigInteger('estimated_cost');
            $table->bigInteger('cost')->nullable();
            $table->bigInteger('provider_cost')->nullable();
            $table->jsonb('usage')->nullable();
            $table->jsonb('rates')->nullable();
            $table->jsonb('error')->nullable();
            $table->jsonb('events')->default('[]');
            $table->text('image_url')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
