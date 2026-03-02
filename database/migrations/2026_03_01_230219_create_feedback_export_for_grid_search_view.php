<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateFeedbackExportForGridSearchView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE VIEW feedback_export_for_grid_search AS
            SELECT 
                nf.id,
                nf.user_id,
                nf.session_id,
                nf.user_query_id,
                uq.query_text,
                uq.entities AS entities_json,
                nf.recipe_id,
                r.nama AS recipe_name,
                nf.rank_shown,
                nf.rating,
                nf.feedback_type,
                nf.query_hash,
                nf.matched_score,
                nf.created_at
            FROM nlp_feedback nf
            LEFT JOIN user_queries uq ON nf.user_query_id = uq.id
            LEFT JOIN recipes r ON nf.recipe_id = r.id
            ORDER BY nf.created_at DESC
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS feedback_export_for_grid_search");
    }
}