<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // API: WHERE is_active = 1 ORDER BY sort_order, id DESC  → covered by this composite.
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order', 'id'], 'idx_cat_active_sort');
        });

        // Videos filtered by category and ordered by id (no filesort).
        Schema::table('videos', function (Blueprint $table) {
            $table->index(['category_id', 'id'], 'idx_vid_cat_id');
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_cat_active_sort');
        });
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex('idx_vid_cat_id');
        });
    }
}
