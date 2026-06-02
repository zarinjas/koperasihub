<?php

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cooperative::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Member::class)->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('caruman_semasa', 15, 2)->default(0);
            $table->decimal('caruman_keseluruhan', 15, 2)->default(0);
            $table->decimal('dividen', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cooperative_id', 'member_id', 'year'], 'uq_member_contributions_coop_member_year');
            $table->index(['cooperative_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_contributions');
    }
};