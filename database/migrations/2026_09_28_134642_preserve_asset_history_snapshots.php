<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preserve actor and asset labels so audit records survive later deletion.
     */
    public function up(): void
    {
        Schema::table('asset_histories', function (Blueprint $table) {
            $table->string('asset_code')->nullable()->after('asset_id');
            $table->string('asset_name')->nullable()->after('asset_code');
            $table->string('user_name')->nullable()->after('user_id');
        });

        DB::table('asset_histories')
            ->orderBy('id')
            ->chunkById(500, function (Collection $histories): void {
                $assets = DB::table('assets')
                    ->whereIn('id', $histories->pluck('asset_id')->filter()->unique())
                    ->get(['id', 'asset_code', 'name'])
                    ->keyBy('id');
                $users = DB::table('users')
                    ->whereIn('id', $histories->pluck('user_id')->filter()->unique())
                    ->pluck('name', 'id');

                foreach ($histories as $history) {
                    $asset = $assets->get($history->asset_id);

                    DB::table('asset_histories')->where('id', $history->id)->update([
                        'asset_code' => $asset?->asset_code,
                        'asset_name' => $asset?->name,
                        'user_name' => $history->user_id ? $users->get($history->user_id) : null,
                    ]);
                }
            });

        Schema::table('asset_histories', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->unsignedBigInteger('asset_id')->nullable()->change();
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });
    }

    /**
     * Restore the original cascading relation.
     */
    public function down(): void
    {
        Schema::table('asset_histories', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
        });

        DB::table('asset_histories')->whereNull('asset_id')->delete();

        Schema::table('asset_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('asset_id')->nullable(false)->change();
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->dropColumn(['asset_code', 'asset_name', 'user_name']);
        });
    }
};
