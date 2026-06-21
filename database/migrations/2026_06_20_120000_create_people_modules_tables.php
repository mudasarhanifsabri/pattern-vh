<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $this->profileColumns($table);
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_mobile')->nullable();
            $table->string('nationality')->nullable();
            $this->auditColumns($table);
        });

        Schema::create('agents', function (Blueprint $table): void {
            $table->id();
            $this->profileColumns($table);
            $table->string('agency_name')->nullable();
            $table->string('rera_no')->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $this->auditColumns($table);
        });

        Schema::create('operations_team_members', function (Blueprint $table): void {
            $table->id();
            $this->profileColumns($table);
            $table->string('team_role')->default('operations');
            $table->string('specialty')->nullable();
            $table->string('service_area')->nullable();
            $table->string('availability_status')->default('available');
            $table->boolean('auto_assign_checkout_cleaning')->default(false);
            $table->boolean('auto_assign_checkout_inspection')->default(false);
            $table->boolean('auto_assign_stay_tasks')->default(false);
            $this->auditColumns($table);
        });

        Schema::create('person_notes', function (Blueprint $table): void {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_notes');
        Schema::dropIfExists('operations_team_members');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('tenants');
    }

    private function profileColumns(Blueprint $table): void
    {
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->string('full_name');
        $table->string('mobile_no');
        $table->boolean('mobile_has_whatsapp')->default(true);
        $table->string('email')->nullable();
        $table->string('identity_type')->default('emirates_id');
        $table->string('identity_no')->nullable();
        $table->date('identity_expiry_date')->nullable();
        $table->date('date_of_birth')->nullable();
        $table->string('document_disk')->nullable();
        $table->string('document_path')->nullable();
        $table->string('document_original_name')->nullable();
        $table->boolean('is_blacklisted')->default(false);
        $table->text('blacklist_reason')->nullable();
        $table->string('bank_name')->nullable();
        $table->string('bank_account_name')->nullable();
        $table->string('bank_account_no')->nullable();
        $table->string('iban')->nullable();
        $table->string('swift_code')->nullable();
        $table->timestamp('portal_invitation_sent_at')->nullable();
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();
    }
};
