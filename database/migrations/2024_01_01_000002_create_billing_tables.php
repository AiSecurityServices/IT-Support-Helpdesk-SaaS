<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_annual', 10, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->unsignedInteger('technician_seats')->default(3);
            $table->unsignedInteger('asset_limit')->default(250);
            $table->unsignedInteger('ticket_limit_monthly')->default(500);
            $table->unsignedInteger('storage_mb')->default(1024);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('trial_eligible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('trial'); // trial|active|past_due|grace|cancelled|expired
            $table->string('interval')->default('monthly'); // monthly|annual
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('provider')->default('manual'); // manual|stripe
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // created|renewed|payment_failed|upgraded|downgraded|cancelled|trial_started...
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('provider_event_id')->nullable()->unique();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
