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
        Schema::create('contents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('public_id')->unique();

            // Provider idempotent
            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('provider_item_id', 128);
            $table->unique(['provider_id', 'provider_item_id'], 'contents_provider_item_unique');


            $table->string('title', 512);
            $table->string('type', 16); // 'video' | 'article'
            $table->timestampTz('published_at')->index();

            $table->json('tags')->nullable();
            $table->json('metrics')->nullable();

            // Score
            $table->decimal('base_score',       10, 3)->default(0);
            $table->decimal('freshness_score',  10, 3)->default(0);
            $table->decimal('engagement_score', 10, 3)->default(0);
            $table->decimal('final_score',      12, 4)->default(0);

            // sync
            $table->string('content_hash', 64)->nullable()->after('id')->index();
            $table->timestampTz('synced_at')->nullable()->after('content_hash');

            $table->timestamps();

            $table->softDeletesTz();

            $table->index('final_score');
        });

        if (DB::getDriverName() === 'pgsql') {

            DB::statement("
                ALTER TABLE contents
                ADD CONSTRAINT chk_contents_type
                CHECK (type IN ('video','article'))
            ");


            DB::statement("ALTER TABLE contents ALTER COLUMN tags    TYPE jsonb USING tags::jsonb");
            DB::statement("ALTER TABLE contents ALTER COLUMN metrics TYPE jsonb USING metrics::jsonb");
            DB::statement("ALTER TABLE contents ALTER COLUMN tags    SET DEFAULT '[]'::jsonb");
            DB::statement("ALTER TABLE contents ALTER COLUMN metrics SET DEFAULT '{}'::jsonb");
            DB::statement("ALTER TABLE contents ALTER COLUMN tags    SET NOT NULL");
            DB::statement("ALTER TABLE contents ALTER COLUMN metrics SET NOT NULL");


            DB::statement("CREATE INDEX contents_final_score_desc_idx    ON contents (final_score DESC)");
            DB::statement("CREATE INDEX contents_published_at_desc_idx   ON contents (published_at DESC)");
            DB::statement("CREATE INDEX contents_pub_final_desc_idx      ON contents (published_at DESC, final_score DESC)");

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
