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
        Schema::table('tournaments', function (Blueprint $table) {
            
            if (Schema::hasColumn('tournaments', 'draw_for_serve')) {
                $table->dropColumn('draw_for_serve');
            }
            if (Schema::hasColumn('tournaments', 'elimination_rounds')) {
                $table->dropColumn('elimination_rounds');
            }

            
            $table->decimal('tournament_price', 8, 2)->nullable();
            $table->enum('rubber_type', ['Liso', 'Pupo', 'Todos'])->nullable();
            $table->integer('groups_number')->nullable();
            $table->integer('rounds')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tournaments', function (Blueprint $table) {
            
            $table->boolean('draw_for_serve')->default(false);
            $table->integer('elimination_rounds')->default(1);

            
            $table->dropColumn(['tournament_price', 'rubber_type', 'groups_number', 'rounds']);
        });
    }
};
