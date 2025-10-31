<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->foreignId('ratee_id')->constrained('users')->index();
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            // purchase_id と ratee_id の組み合わせを一意に
            $table->unique(['purchase_id', 'ratee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
