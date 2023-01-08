<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name',191)->nullable();
            $table->string('email',191)->nullable();
            $table->string('phone',60)->nullable();
            $table->double('amount')->default(0);
            $table->text('address')->nullable();
            $table->string('status',20)->default('Pending');
            $table->string('transaction_id',191);
            $table->string('currency',20)->nullable()->default('BDT');
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
        Schema::dropIfExists('orders');
    }
};
