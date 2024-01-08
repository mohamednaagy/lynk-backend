<?php

use Cknow\Money\Money;
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

        DB::table('trader_orders')
            ->select(['id', 'data'])
            ->chunkById(50, function ($traderOrders) {
                foreach ($traderOrders as $traderOrder) {
                    if (is_null($traderOrder->data)) {
                        continue;
                    }

                    $data = json_decode($traderOrder->data, true);
                    DB::table('trader_orders')
                        ->where('id', $traderOrder->id)
                        ->update([
                            'data' => $this->fixJsonColumnsIfMoneyObjectExists($data),
                        ]);
                }
            });

        DB::table('notifications')
            ->select(['id', 'data'])
            ->chunkById(50, function ($notifications) {
                foreach ($notifications as $notification) {
                    if (is_null($notification->data)) {
                        continue;
                    }

                    $data = json_decode($notification->data, true);
                    DB::table('notifications')
                        ->where('id', $notification->id)
                        ->update([
                            'data' => $this->fixJsonColumnsIfMoneyObjectExists($data),
                        ]);
                }
            });

        DB::connection('wallet')
            ->table('transactions')
            ->update([
                'currency' => config('app.currency'),
                'amount' => DB::raw('CONCAT(`amount`, "00")'),
            ]);

        DB::connection('wallet')
            ->table('transactions')
            ->select(['id', 'meta'])
            ->chunkById(50, function ($transactions) {
                foreach ($transactions as $transaction) {
                    if (is_null($transaction->meta)) {
                        continue;
                    }

                    $data = json_decode($transaction->meta, true);
                    DB::connection('wallet')
                        ->table('transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'meta' => $this->fixJsonColumnsIfMoneyObjectExists($data),
                        ]);
                }
            });

        DB::connection('wallet')
            ->table('transfers')
            ->update([
                'currency' => config('app.currency'),
                'amount' => DB::raw('CONCAT(`amount`, "00")'),
            ]);

        DB::connection('wallet')
            ->table('wallets')
            ->update([
                'currency' => config('app.currency'),
            ]);
    }

    public function down(): void
    {
        //
    }

    private function fixJsonColumnsIfMoneyObjectExists(array|string &$data): ?array
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            if ($this->isMoneyObject($value)) {
                if ($value['amount'] !== '0') {
                    $data[$key]['amount'] .= '00';
                }
                $data[$key]['currency'] = config('app.currency');

                $data[$key]['formatted'] = Money::parse($data[$key]['amount'], $data[$key]['currency'])->formatByIntl();

                continue;
            }

            $this->fixJsonColumnsIfMoneyObjectExists($value);
        }

        return $data;
    }

    private function isMoneyObject(array $data): bool
    {
        return array_key_exists('amount', $data) && array_key_exists('currency', $data) && array_key_exists('formatted', $data);
    }
};
