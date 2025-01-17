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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->ulid('nano_id')->unique();
            $table->foreignId('admin_id')->constrained(table: 'users', column: 'id');
            $table->string('name');
            $table->string('image')->nullable();
            $table->mediumText('description')->nullable();
            $table->string('status')->default('upcoming'); // ['upcoming', 'in-progress', 'completed']
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
