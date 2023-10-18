<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('wallet_id')->index();
            $table->tinyInteger('type');
            $table->decimal('value', 64, 0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_notifications');
    }
};
