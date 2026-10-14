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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('name');
            $table->enum('type', ['passenger', 'cargo']);
            $table->enum('ownership', ['owned', 'rented']);
            $table->string('rental_company')->nullable();
            $table->foreignId('location_id')->constrained();
            $table->unsignedInteger('odometer')->default(0);
            $table->enum('status', ['available', 'maintenance'])->default('available');
            $table->date('last_service_at')->nullable();
            $table->date('next_service_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
