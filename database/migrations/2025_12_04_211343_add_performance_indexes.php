<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add performance indexes to optimize common query patterns.
     */
    public function up(): void
    {
        // Shifts table - Most critical for BPA module
        Schema::table('shifts', function (Blueprint $table) {
            // Primary query pattern: worked shifts by date range
            $table->index(['is_archived', 'is_unavailable', 'starts_at'], 'shifts_worked_date_idx');

            // Query pattern: assistant's shifts
            $table->index(['assistant_id', 'starts_at'], 'shifts_assistant_date_idx');

            // Individual column indexes for specific queries
            $table->index('starts_at', 'shifts_starts_at_idx');
            $table->index('is_unavailable', 'shifts_is_unavailable_idx');
            $table->index('deleted_at', 'shifts_deleted_at_idx');
        });

        // Wishlist tables
        Schema::table('wishlist_items', function (Blueprint $table) {
            // For standalone items ordering
            $table->index(['group_id', 'sort_order'], 'wishlist_items_group_sort_idx');

            // For items within groups
            $table->index(['group_id', 'created_at'], 'wishlist_items_group_created_idx');
        });

        Schema::table('wishlist_groups', function (Blueprint $table) {
            $table->index('sort_order', 'wishlist_groups_sort_idx');
        });

        // Equipment table
        Schema::table('equipment', function (Blueprint $table) {
            // Composite index for sorting by type then name
            $table->index(['type', 'name'], 'equipment_type_name_idx');

            // For deleted_at filtering (soft deletes)
            $table->index('deleted_at', 'equipment_deleted_at_idx');
        });

        // Categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->index('deleted_at', 'categories_deleted_at_idx');
        });

        // Prescriptions table
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->index('valid_to', 'prescriptions_valid_to_idx');
            $table->index('deleted_at', 'prescriptions_deleted_at_idx');
        });

        // Assistants table
        Schema::table('assistants', function (Blueprint $table) {
            $table->index('deleted_at', 'assistants_deleted_at_idx');
            $table->index('employee_number', 'assistants_employee_number_idx');
        });

        // Settings table - unique index on key for fast lookups
        Schema::table('settings', function (Blueprint $table) {
            $table->unique('key', 'settings_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_worked_date_idx');
            $table->dropIndex('shifts_assistant_date_idx');
            $table->dropIndex('shifts_starts_at_idx');
            $table->dropIndex('shifts_is_unavailable_idx');
            $table->dropIndex('shifts_deleted_at_idx');
        });

        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropIndex('wishlist_items_group_sort_idx');
            $table->dropIndex('wishlist_items_group_created_idx');
        });

        Schema::table('wishlist_groups', function (Blueprint $table) {
            $table->dropIndex('wishlist_groups_sort_idx');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex('equipment_type_name_idx');
            $table->dropIndex('equipment_deleted_at_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_deleted_at_idx');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropIndex('prescriptions_valid_to_idx');
            $table->dropIndex('prescriptions_deleted_at_idx');
        });

        Schema::table('assistants', function (Blueprint $table) {
            $table->dropIndex('assistants_deleted_at_idx');
            $table->dropIndex('assistants_employee_number_idx');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
        });
    }
};
