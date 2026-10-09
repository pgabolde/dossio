<?php

use App\Enums\DocumentStatus;
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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->index()->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('path');
            $table->string('mime_type');
            $table->integer('size');
            $table->string('status')->default(DocumentStatus::Pending->value);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
