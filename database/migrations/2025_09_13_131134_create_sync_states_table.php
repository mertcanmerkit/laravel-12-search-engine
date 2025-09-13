<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_states', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->string('provider_key', 191)->nullable()->index();

            $table->json('cursor')->nullable();
            $table->timestampTz('since')->nullable();

            $table->unsignedInteger('last_page')->nullable()->default(0);

            $table->timestampTz('last_success_at')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'provider_key']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sync_states')) {
            Schema::drop('sync_states');
        }
    }
};
