<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubCategoryIdToVideosTable extends Migration
{
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('sub_category_id')
                  ->nullable()
                  ->after('category_id')
                  ->constrained('sub_categories')
                  ->nullOnDelete();
            $table->index(['sub_category_id', 'sort_order', 'id'], 'idx_vid_subcat_sort');
        });
    }

    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['sub_category_id']);
            $table->dropIndex('idx_vid_subcat_sort');
            $table->dropColumn('sub_category_id');
        });
    }
}
