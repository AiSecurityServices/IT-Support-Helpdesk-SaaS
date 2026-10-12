<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Token expiry for Sanctum personal access tokens (Sanctum 3.x already
        // ships expires_at; only add it on older schemas).
        if (! Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('last_used_at');
            });
        }

        // Service accounts (token-only, no password login).
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_service_account')->default(false)->after('is_super_admin');
            $table->string('locale', 12)->nullable()->after('timezone');
        });

        // Authentication event history.
        Schema::create('auth_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event'); // login_success|login_failed|mfa_success|mfa_failed|mfa_recovery_used|password_reset_requested|password_reset_completed|logout|token_used|impersonation_started|impersonation_ended
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'event']);
        });

        // MFA recovery codes (hashed, single-use).
        Schema::create('recovery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        // Audited, time-limited impersonation grants (super-admin only).
        Schema::create('impersonation_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['target_user_id', 'expires_at']);
        });

        // Mail delivery events (bounces, complaints, failures).
        Schema::create('mail_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->string('kind'); // bounced|complained|failed|delivered
            $table->string('provider')->default('smtp');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['recipient', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_events');
        Schema::dropIfExists('impersonation_grants');
        Schema::dropIfExists('recovery_codes');
        Schema::dropIfExists('auth_events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_service_account', 'locale']);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Column belongs to Sanctum's own schema on v3; keep it.
        });
    }
};
