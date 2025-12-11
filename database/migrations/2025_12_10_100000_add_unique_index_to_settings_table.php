<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Before adding the unique constraint, we must remove duplicates.
        // This keeps the latest setting and removes older duplicates.
        $duplicates = DB::table('settings')
            ->select('group', 'name', DB::raw('COUNT(id) as count'))
            ->groupBy('group', 'name')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $latestId = DB::table('settings')
                ->where('group', $duplicate->group)
                ->where('name', $duplicate->name)
                ->latest('id')
                ->first()
                ->id;

            DB::table('settings')
                ->where('group', $duplicate->group)
                ->where('name', $duplicate->name)
                ->where('id', '<>', $latestId)
                ->delete();
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['group', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['group', 'name']);
        });
    }
};
