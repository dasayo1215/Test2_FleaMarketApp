<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradeMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('trade_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_room_id')->constrained('trade_rooms')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users');
            $table->text('message');
            $table->timestamps();

            // 取引ルームID＋作成日時のインデックス
            $table->index(['trade_room_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('trade_messages');
    }
}
