<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortOrderToCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active')->index();
        });

        // Seed existing rows so their current order (newest first) is preserved.
        $i = 1;
        foreach (\App\Models\Category::orderByDesc('id')->get() as $cat) {
            $cat->sort_order = $i++;
            $cat->save();
        }
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
}
