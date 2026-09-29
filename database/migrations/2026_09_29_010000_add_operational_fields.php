<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('system_role', ['admin', 'sarpras', 'unit'])
                ->default('unit')
                ->after('password')
                ->index();
        });

        Schema::table('unit_stocks', function (Blueprint $table) {
            $table->enum('acquisition_source', ['central_distribution', 'unit_existing', 'unit_purchase', 'other'])
                ->default('other')
                ->after('item_id');
            $table->string('photo_object_key')->nullable()->after('lost_qty');
            $table->text('notes')->nullable()->after('photo_object_key');
            $table->foreignId('created_by')->nullable()->after('notes')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('public_id');
            $table->string('recipient_name')->nullable()->after('target_unit_id');
            $table->string('recipient_title')->nullable()->after('recipient_name');
            $table->text('notes')->nullable()->after('signed_document_object_key');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('rejected_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejection_reason', 'rejected_at']);
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn(['document_number', 'recipient_name', 'recipient_title', 'notes']);
        });

        Schema::table('unit_stocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['acquisition_source', 'photo_object_key', 'notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('system_role');
        });
    }
};
