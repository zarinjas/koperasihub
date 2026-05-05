<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('card_public_token')->nullable()->unique()->after('profile_photo_path');
            $table->timestamp('card_token_generated_at')->nullable()->after('card_public_token');
        });

        DB::table('members')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (object $member): void {
                do {
                    $token = Str::random(48);
                } while (DB::table('members')->where('card_public_token', $token)->exists());

                DB::table('members')
                    ->where('id', $member->id)
                    ->update([
                        'card_public_token' => $token,
                        'card_token_generated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['card_public_token']);
            $table->dropColumn(['card_public_token', 'card_token_generated_at']);
        });
    }
};
