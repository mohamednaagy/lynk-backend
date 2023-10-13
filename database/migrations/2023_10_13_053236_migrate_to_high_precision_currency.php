<?php

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('edaat_invoices')
            ->update([
                'currency' => config('app.currency'),
                'amount' => DB::raw('CONCAT(`amount`, "00")'),
            ]);

        DB::table('financing_orders')
            ->update([
                'currency' => config('app.currency'),
                'amount' => DB::raw('CONCAT(`amount`, "00")'),
                'selling_price' => DB::raw('CONCAT(`selling_price`, "00")'),
            ]);

        DB::table('wallet_notifications')
            ->update([
                'value' => DB::raw('CONCAT(`value`, "00")'),
            ]);

        DB::table('tiered_pricing')
            ->update([
                'order_value_start' => DB::raw('CONCAT(`order_value_start`, "00")'),
                'order_value_end' => DB::raw('CONCAT(`order_value_end`, "00")'),
                'order_cost_without_vat' => DB::raw('CONCAT(`order_cost_without_vat`, "00")'),
                'proration_amount' => DB::raw('CONCAT(`order_value_end`, "00")'),
            ]);

        $settingInstance = app(GetSettingsClassInstance::class)->handle(Area::Lender);
        $settingInstance->default_order_cost .= '00';
        $settingInstance->save();

        $settingInstance = app(GetSettingsClassInstance::class)->handle(Area::Trader);
        $settingInstance->default_order_cost .= '00';
        $settingInstance->save();

        DB::connection('wallet')
            ->table('transactions')
            ->update([
                'currency' => config('app.currency'),
                'amount' => DB::raw('CONCAT(`amount`, "00")'),
            ]);
        DB::connection('wallet')
            ->table('transfers')
            ->update([
                'currency' => config('app.currency'),
                'amount' => DB::raw('CONCAT(`amount`, "00")'),
            ]);
    }

    public function down(): void
    {
        //
    }

    private function fixJsonColumns()
    {

    }
};
