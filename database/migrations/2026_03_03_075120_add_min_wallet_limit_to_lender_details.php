<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->decimal('min_wallet_limit', 64, 2)->nullable()->after('company_cr');
        });
    }

    public function down(): void
    {
        Schema::table('lender_details', function (Blueprint $table) {
            $table->dropColumn('min_wallet_limit');
        });
    }
};
