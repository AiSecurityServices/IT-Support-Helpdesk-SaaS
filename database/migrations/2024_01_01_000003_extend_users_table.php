<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('technician')->after('tenant_id');
            $table->boolean('is_super_admin')->default(false)->after('role');
            $table->string('phone')->nullable()->after('email');
            $table->string('job_title')->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('job_title');
            $table->string('timezone')->default('Europe/London')->after('avatar_path');
            $table->foreignId('customer_id')->nullable()->after('timezone')->constrained('customers')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('customer_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('totp_secret')->nullable()->after('last_login_at');
            $table->timestamp('totp_enabled_at')->nullable()->after('totp_secret');
            $table->index(['tenant_id', 'role']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['role', 'is_super_admin', 'phone', 'job_title', 'avatar_path', 'timezone', 'is_active', 'last_login_at', 'totp_secret', 'totp_enabled_at']);
        });
    }
};
