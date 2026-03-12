<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make eligible_quantity non-negative by using an UNSIGNED integer.
        DB::statement('ALTER TABLE `local_market_eligible_quantities` MODIFY `eligible_quantity` INT UNSIGNED NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert eligible_quantity back to a signed INT.
        DB::statement('ALTER TABLE `local_market_eligible_quantities` MODIFY `eligible_quantity` INT NOT NULL');
    }
};
