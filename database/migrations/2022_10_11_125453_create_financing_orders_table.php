<?php

use App\Models\Company;
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
        Schema::create('financing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('reference_number')->nullable();
            $table->bigInteger('national_id');
            $table->string('phone_number', '50')->nullable();
            $table->decimal('amount', 64, 0);
            $table->string('currency', 4)->default(config('money.defaultCurrency'));
            $table->decimal('selling_price', 64, 0);
            $table->unsignedTinyInteger('status');
            $table->string('status_reason')->nullable();
            $table->json('customer_details')->nullable();
            $table->boolean('is_verification_required')->default(true);
            $table->timestamp('client_wakala_accepted_at')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->string('creator_type')->nullable();

            $table->index(['creator_type', 'creator_id']);
            $table->foreign('approver_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financing_orders');
    }
};
