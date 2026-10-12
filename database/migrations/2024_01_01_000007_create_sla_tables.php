<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // JSON: {"urgent":{"first_response":30,"resolution":240},...} minutes
            $table->json('targets');
            $table->json('working_hours')->nullable(); // {"days":[1..5],"start":"09:00","end":"17:30","timezone":...}
            $table->boolean('pause_on_pending_customer')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date');
            $table->boolean('recurring')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'date']);
        });

        Schema::create('sla_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // started|paused|resumed|warned|breached|met|policy_changed|escalated
            $table->string('target')->nullable(); // first_response|resolution
            $table->dateTime('deadline_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'type']);
        });

        // Cross-table FKs that required both sides to exist.
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('sla_policy_id')->references('id')->on('sla_policies')->nullOnDelete();
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('sla_policy_id')->references('id')->on('sla_policies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
        });
        Schema::dropIfExists('sla_events');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('sla_policies');
    }
};
