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
        Schema::create('transactions', function (Blueprint $table) {
            // ULID primary key for scalability & ordering
            $table->ulid('id')->primary();

            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');

            // Core monetary fields (DECIMAL for precision)
            $table->decimal('amount', 18, 4);
            $table->decimal('commission_fee', 18, 4);

            $table->timestamps();

            // Indexes for high-volume lookups
            $table->index(['sender_id', 'created_at']);
            $table->index(['receiver_id', 'created_at']);
            $table->index('created_at');

            // Referential integrity
            $table->foreign('sender_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('receiver_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
