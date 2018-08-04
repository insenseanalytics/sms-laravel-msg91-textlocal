<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_reports', function (Blueprint $table) {
            $table->increments('sms_report_id');
            $table->string('recipient', 15);
            $table->text('content');
            $table->boolean('unicode');
            $table->boolean('transactional');
            $table->datetime('schedule_time')->nullable();
            $table->enum('status', ['pending', 'delivered', 'failed', 'rejected'])->default('pending');
            $table->datetime('delivery_timestamp')->nullable();
            $table->datetime('request_timestamp');
            $table->string('batch_id')->nullable();
            $table->string('custom_id');
            $table->string('message_id')->nullable();
            $table->string('error_message')->nullable();
            $table->unique(['recipient', 'custom_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_reports');
    }
}
