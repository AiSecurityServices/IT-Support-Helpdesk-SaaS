<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('number')->nullable(); // TKT-000123, unique per tenant
            $table->string('subject');
            $table->text('description');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subcategory')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable(); // FK added after assets table
            $table->string('source')->default('staff');
            $table->string('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->foreignId('sla_policy_id')->nullable();
            $table->unsignedInteger('escalation_level')->default(0);
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedTinyInteger('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visibility')->default('public'); // public|internal
            $table->text('body');
            $table->timestamps();
            $table->index(['ticket_id', 'visibility']);
            $table->index(['tenant_id', 'ticket_id']);
        });

        Schema::create('ticket_watchers', function (Blueprint $table) {
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['ticket_id', 'user_id']);
        });

        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('visibility')->default('public'); // public|internal
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
            $table->index(['tenant_id', 'attachable_type']);
        });

        Schema::create('saved_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('applies_to')->default('ticket');
            $table->string('label');
            $table->string('key');
            $table->string('type')->default('text'); // text|number|date|select|boolean
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'applies_to', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
        Schema::dropIfExists('saved_replies');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('ticket_histories');
        Schema::dropIfExists('ticket_watchers');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('categories');
    }
};
