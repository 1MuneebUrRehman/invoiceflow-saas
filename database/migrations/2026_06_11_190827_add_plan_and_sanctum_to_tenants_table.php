<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('plan')->default('free')->after('default_currency');
            $table->string('stripe_id')->nullable()->index()->after('plan');
            $table->string('pm_type', 25)->nullable()->after('stripe_id');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four');
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('billable');
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['billable_id', 'billable_type']);
        });

        Schema::create('subscription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_id')->unique();
            $table->string('stripe_product')->nullable();
            $table->string('stripe_price');
            $table->integer('quantity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['plan', 'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at']);
        });

        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
    }
};
