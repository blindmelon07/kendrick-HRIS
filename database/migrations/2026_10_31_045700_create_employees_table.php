<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('civil_status', 20)->nullable();
            $table->string('mobile_number', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->string('level', 30);
            $table->string('job_title');
            $table->string('employee_status', 20)->default('active');
            $table->string('employment_status', 20);
            $table->date('date_hired');
            $table->date('date_separated')->nullable();

            $table->string('pay_type', 20);
            $table->decimal('rate', 12, 2);
            $table->string('payment_method', 10)->default('cash');
            $table->string('bank_account_number', 40)->nullable();

            $table->string('sss_number', 30)->nullable();
            $table->string('philhealth_number', 30)->nullable();
            $table->string('pagibig_number', 30)->nullable();
            $table->string('tin', 30)->nullable();
            $table->boolean('has_statutory_deductions')->default(false);
            $table->decimal('sss_contribution', 10, 2)->default(0);
            $table->decimal('philhealth_contribution', 10, 2)->default(0);
            $table->decimal('pagibig_contribution', 10, 2)->default(0);

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();
            $table->string('emergency_contact_number', 30)->nullable();
            $table->timestamps();

            $table->index(['employee_status', 'last_name']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_employee_id')->nullable()->after('email')->constrained('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_employee_id');
        });

        Schema::dropIfExists('employees');
    }
};
