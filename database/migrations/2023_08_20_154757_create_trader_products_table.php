<?php

use App\Enums\BursamProductCode;
use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use App\Models\TraderProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Traits\Localizable;

return new class extends Migration
{
    use Localizable;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trader_products', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->unsignedTinyInteger('order');
            $table->string('code');
            $table->string('provider');
            $table->string('status');

            $table->timestamps();
        });

        $order = 1;
        foreach (BursamProductCode::getProductCodes(app()->environment()) as $value) {
            TraderProduct::create([
                'name' => [
                    'en' => $this->withLocale('en', fn () => BursamProductCode::getDescription($value)),
                    'ar' => $this->withLocale('ar', fn () => BursamProductCode::getDescription($value)),
                ],
                'order' => $order,
                'provider' => Trader::Bursam,
                'code' => $value,
                'status' => TraderProductStatus::Enabled,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trader_products');
    }
};
