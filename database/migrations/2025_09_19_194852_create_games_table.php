<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->integer('round')->nullable();
            $table->string('group_name')->nullable();
            $table->foreignId('member1_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('member2_id')->nullable()->constrained('members')->onDelete('set null');
            $table->integer('score1')->nullable();
            $table->integer('score2')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('members')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
