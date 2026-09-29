<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['central', 'program', 'department', 'other'])->default('other');
            $table->string('borrow_public_token', 64)->unique()->nullable();
            $table->string('borrow_pin_hash')->nullable();
            $table->unsignedInteger('borrow_pin_version')->default(1);
            $table->boolean('borrowing_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['head', 'staff', 'member']);
            $table->boolean('can_manage_inventory')->default(false);
            $table->boolean('can_manage_borrowing')->default(false);
            $table->timestamps();
            $table->unique(['unit_id', 'user_id']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('specification')->nullable();
            $table->boolean('borrowable')->default(true);
            $table->boolean('require_return_photo')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
        });

        Schema::create('unit_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedInteger('available_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->unsignedInteger('borrowed_qty')->default(0);
            $table->unsignedInteger('damaged_qty')->default(0);
            $table->unsignedInteger('lost_qty')->default(0);
            $table->timestamps();
            $table->unique(['unit_id', 'item_id']);
        });

        Schema::create('incoming_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('central_unit_id')->constrained('units');
            $table->date('received_at');
            $table->string('supplier');
            $table->string('invoice_number')->nullable();
            $table->string('invoice_object_key')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('incoming_good_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_good_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->string('photo_object_key')->nullable();
            $table->timestamps();
        });

        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('source_unit_id')->constrained('units');
            $table->foreignId('target_unit_id')->constrained('units');
            $table->enum('status', ['draft', 'awaiting_signed_document', 'completed', 'cancelled'])->default('draft');
            $table->string('generated_document_object_key')->nullable();
            $table->string('signed_document_object_key')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['distribution_id', 'item_id']);
        });

        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('unit_id')->constrained();
            $table->unsignedBigInteger('juara_student_id');
            $table->string('student_name');
            $table->string('student_nis', 50);
            $table->string('student_class', 100);
            $table->string('phone', 30);
            $table->text('purpose');
            $table->timestamp('expected_return_at');
            $table->enum('status', [
                'pending_approval',
                'approved',
                'rejected',
                'borrowed',
                'return_pending',
                'partially_returned',
                'completed',
                'cancelled'
            ])->default('pending_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('borrowed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'status']);
            $table->index('expected_return_at');
        });

        Schema::create('borrowing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->unsignedInteger('requested_qty');
            $table->unsignedInteger('approved_qty')->nullable();
            $table->unsignedInteger('handed_over_qty')->default(0);
            $table->unsignedInteger('returned_qty')->default(0);
            $table->unsignedInteger('damaged_qty')->default(0);
            $table->unsignedInteger('lost_qty')->default(0);
            $table->timestamps();
            $table->unique(['borrowing_id', 'item_id']);
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('borrowing_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('photo_object_key');
            $table->text('borrower_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('return_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('borrowing_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('borrower_condition', ['good', 'damaged', 'lost'])->default('good');
            $table->enum('verified_condition', ['good', 'damaged', 'lost'])->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->enum('bucket', ['available', 'reserved', 'borrowed', 'damaged', 'lost']);
            $table->integer('delta');
            $table->string('reason', 50);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'item_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('borrowing_items');
        Schema::dropIfExists('borrowings');
        Schema::dropIfExists('distribution_items');
        Schema::dropIfExists('distributions');
        Schema::dropIfExists('incoming_good_items');
        Schema::dropIfExists('incoming_goods');
        Schema::dropIfExists('unit_stocks');
        Schema::dropIfExists('items');
        Schema::dropIfExists('unit_memberships');
        Schema::dropIfExists('units');
        Schema::dropIfExists('users');
    }
};
