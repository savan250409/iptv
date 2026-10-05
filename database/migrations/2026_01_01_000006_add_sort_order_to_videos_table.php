<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortOrderToVideosTable extends Migration
{
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            // 0 = unordered (falls back to newest-first); set to 1..N when indexed per category.
            $table->unsignedInteger('sort_order')->default(0)->after('video_file');
            $table->index(['category_id', 'sort_order', 'id'], 'idx_vid_cat_sort');
        });
    }

    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex('idx_vid_cat_sort');
            $table->dropColumn('sort_order');
        });
    }
}
