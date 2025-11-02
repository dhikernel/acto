<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        DB::statement('
            CREATE TABLE layers (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(100) NULL,
                geometry geometry(Geometry,4326),
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                deleted_at TIMESTAMP NULL
            )
        ');

        DB::statement('CREATE INDEX layers_geometry_gist ON layers USING GIST (geometry)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS layers');
    }
};
