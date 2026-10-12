<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Knowledge base ----
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kb_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body');
            $table->string('visibility')->default('internal'); // public|internal
            $table->string('status')->default('draft'); // draft|review|published|archived
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedInteger('helpful_votes')->default(0);
            $table->unsignedInteger('not_helpful_votes')->default(0);
            $table->string('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'visibility']);
        });

        Schema::create('kb_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->longText('body');
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['kb_article_id', 'version']);
        });

        Schema::create('kb_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('helpful');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index('kb_article_id');
        });

        // ---- Service catalogue ----
        Schema::create('service_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('form_schema')->nullable(); // [{key,label,type,required,options,depends_on}]
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sla_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('requires_approval')->default(false);
            $table->string('approver_role')->default('manager');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('number')->nullable();
            $table->foreignId('service_catalog_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft'); // draft|pending_approval|approved|rejected|in_progress|fulfilled|cancelled
            $table->json('form_data')->nullable();
            $table->json('approvals')->nullable(); // [{by,decision,at,note}]
            $table->text('completion_evidence')->nullable();
            $table->boolean('customer_confirmed')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('service_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open|in_progress|done|skipped
            $table->text('evidence')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('service_request_id');
        });

        // ---- Problem / Change / Major incident ----
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open|investigating|known_error|resolved|closed
            $table->text('root_cause')->nullable();
            $table->text('workaround')->nullable();
            $table->boolean('known_error')->default(false);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('problem_ticket', function (Blueprint $table) {
            $table->foreignId('problem_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->primary(['problem_id', 'ticket_id']);
        });

        Schema::create('changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('normal'); // standard|normal|emergency
            $table->string('status')->default('draft'); // draft|pending_approval|approved|rejected|scheduled|implementing|implemented|failed|closed
            $table->text('impact')->nullable();
            $table->string('risk')->default('medium'); // low|medium|high
            $table->text('implementation_plan')->nullable();
            $table->text('test_plan')->nullable();
            $table->text('rollback_plan')->nullable();
            $table->dateTime('window_start')->nullable();
            $table->dateTime('window_end')->nullable();
            $table->json('approvals')->nullable();
            $table->text('outcome')->nullable();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('change_ticket', function (Blueprint $table) {
            $table->foreignId('change_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->primary(['change_id', 'ticket_id']);
        });

        Schema::create('major_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('commander_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open|mitigating|resolved|closed
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('major_incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('notify_audience')->default(false);
            $table->timestamps();
        });

        Schema::create('major_incident_ticket', function (Blueprint $table) {
            $table->foreignId('major_incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->primary(['major_incident_id', 'ticket_id']);
        });

        // ---- CMDB relationships ----
        Schema::create('ci_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('from_type'); // asset|customer|location|user|service
            $table->unsignedBigInteger('from_id');
            $table->string('to_type');
            $table->unsignedBigInteger('to_id');
            $table->string('relation'); // depends_on|hosted_on|used_by|located_at|manages|member_of
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'from_type', 'from_id', 'to_type', 'to_id', 'relation'], 'ci_rel_unique');
            $table->index(['tenant_id', 'from_type', 'from_id']);
            $table->index(['tenant_id', 'to_type', 'to_id']);
        });

        // ---- Automation engine ----
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger'); // ticket.created|ticket.updated|priority.changed|sla.warning|sla.breach|ticket.reassigned|customer.replied|warranty.expiring|invoice.overdue|approval.decided
            $table->json('conditions')->nullable(); // [{field,operator,value}]
            $table->json('actions')->nullable(); // [{type,params}]
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_runs_per_hour')->default(60);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('run_count')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('subject_id')->nullable(); // ticket/request/invoice id
            $table->string('status')->default('ok'); // ok|skipped|failed
            $table->json('detail')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamps();
            $table->unique(['automation_rule_id', 'dedupe_key'], 'auto_run_dedupe');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('ci_relationships');
        Schema::dropIfExists('major_incident_ticket');
        Schema::dropIfExists('major_incident_updates');
        Schema::dropIfExists('major_incidents');
        Schema::dropIfExists('change_ticket');
        Schema::dropIfExists('changes');
        Schema::dropIfExists('problem_ticket');
        Schema::dropIfExists('problems');
        Schema::dropIfExists('service_tasks');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_catalog_items');
        Schema::dropIfExists('kb_feedback');
        Schema::dropIfExists('kb_article_versions');
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
    }
};
