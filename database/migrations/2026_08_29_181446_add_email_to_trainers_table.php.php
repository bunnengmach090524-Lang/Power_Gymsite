<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            // Nullable — trainer មិនចាំបាច់មាន email ភ្លាមៗពេលបង្កើត
            // (ឧ. trainer ជា walk-in ដែលមិនទាន់ត្រូវការ login account)
            $table->string('email')->nullable()->after('name');
        });

        // Email ត្រូវ unique តែក្នុង tenant តែមួយ (មិនមែន global unique ទេ ព្រោះ
        // trainer ២ នាក់ខុស gym អាចប្រើ email ដូចគ្នាបានតាមទ្រឹស្តី)
        Schema::table('trainers', function (Blueprint $table) {
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};