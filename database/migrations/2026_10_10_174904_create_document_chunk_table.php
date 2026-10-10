<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('page_start');
            $table->integer('page_end');
            $table->vector('embedding', dimensions: 1024);
            $table->string('embedding_model');
            $table->timestamps();

            $table->index('organization_id');
            $table->index('client_id');
            $table->unique(['document_id', 'chunk_index']);
        });

        DB::statement(
            'CREATE INDEX document_chunks_embedding_hnsw
             ON document_chunks USING hnsw (embedding vector_cosine_ops)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
