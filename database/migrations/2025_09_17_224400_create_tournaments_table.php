<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id(); 
            $table->string('tournament_code')->unique();
            $table->string('name');
            $table->string('country');
            $table->string('province');
            $table->string('city');
            $table->string('club_name');
            $table->string('address');
            $table->date('date');
            $table->time('time');
            $table->date('registration_deadline');
            $table->string('modality'); 
            $table->string('match_type'); 
            $table->string('elimination_type'); 
            $table->integer('participants_number');
            $table->string('seeding_type'); 
            $table->boolean('ranking_all');
            $table->string('ranking_from')->nullable();
            $table->string('ranking_to')->nullable();
            $table->boolean('age_all');
            $table->integer('age_from')->nullable();
            $table->integer('age_to')->nullable();
            $table->string('gender'); 
            $table->boolean('affects_ranking');
            $table->boolean('draw_for_serve');
            $table->boolean('system_invitation');
            $table->string('resend_invitation_schedule')->nullable();
            $table->string('main_image_path')->nullable();
            $table->string('prize1')->nullable();
            $table->string('prize2')->nullable();
            $table->string('prize3')->nullable();
            $table->string('prize4')->nullable();
            $table->string('prize5')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('ball_info');
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
