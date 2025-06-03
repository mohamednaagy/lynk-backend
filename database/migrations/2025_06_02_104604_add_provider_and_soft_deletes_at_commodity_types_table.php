<?php

use App\Enums\CommodityTypeProvider;
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
        Schema::table('commodity_types', function (Blueprint $table) {
            $table->string('provider')->default(CommodityTypeProvider::Lynk)->after('unique_name')->comment('Provider of the commodity type, e.g., lynk, bursam');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('commodity_types', function (Blueprint $table) {
            $table->dropColumn('provider');
            $table->dropColumn('deleted_at');
        });
    }
};
