<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('news_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('thumbnail')->nullable(); // ảnh đại diện
            $table->longText('content');
            $table->string('author')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
