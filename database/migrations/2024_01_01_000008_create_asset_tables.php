<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('asset_tag')->nullable();
            $table->string('name');
            $table->string('type')->default('computer'); // computer|laptop|server|printer|network|mobile|software|other
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('computer_name')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('os_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->date('warranty_expires_at')->nullable();
            $table->string('status')->default('in_stock');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'asset_tag']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
        });

        Schema::create('asset_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // created|updated|assigned|transferred|maintenance|status_changed|note
            $table->text('details')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'created_at']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });

        Schema::create('remote_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider')->default('other'); // teamviewer|anydesk|rdp|teams|zoom|other
            $table->string('join_url')->nullable();
            $table->text('purpose')->nullable();
            $table->text('customer_instructions')->nullable();
            $table->boolean('consent_confirmed')->default(false);
            $table->string('status')->default('scheduled'); // scheduled|active|completed|expired|cancelled
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('minutes')->default(0);
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('status')->default('logged'); // running|logged|approved|invoiced
            $table->foreignId('invoice_id')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'status']);
            $table->index(['tenant_id', 'technician_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('remote_sessions');
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
        });
        Schema::dropIfExists('asset_histories');
        Schema::dropIfExists('assets');
    }
};
