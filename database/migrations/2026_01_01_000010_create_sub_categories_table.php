<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name', 190)->index();
            $table->string('image', 255)->nullable();   // stored file name only
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['category_id', 'is_active', 'sort_order', 'id'], 'idx_subcat_cat_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_categories');
    }
}
