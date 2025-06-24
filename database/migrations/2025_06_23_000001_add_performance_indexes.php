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
        // Add indexes for SaleInvoice table (most critical)
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->index(['isHold', 'created_at'], 'idx_sale_invoice_hold_created');
            $table->index(['date', 'isHold'], 'idx_sale_invoice_date_hold');
            $table->index(['customerId', 'isHold'], 'idx_sale_invoice_customer_hold');
            $table->index(['userId', 'isHold'], 'idx_sale_invoice_user_hold');
            $table->index(['orderStatus', 'isHold'], 'idx_sale_invoice_status_hold');
            $table->index(['status', 'isHold'], 'idx_sale_invoice_status_hold_2');
        });

        // Add indexes for Transaction table (heavy queries)
        Schema::table('transaction', function (Blueprint $table) {
            $table->index(['relatedId', 'type'], 'idx_transaction_related_type');
            $table->index(['type', 'debitId', 'creditId'], 'idx_transaction_type_debit_credit');
            $table->index(['relatedId', 'type', 'debitId'], 'idx_transaction_related_type_debit');
            $table->index(['relatedId', 'type', 'creditId'], 'idx_transaction_related_type_credit');
        });

        // Add indexes for Product table
        Schema::table('product', function (Blueprint $table) {
            $table->index(['status', 'id'], 'idx_product_status_id');
            $table->index(['productSubCategoryId', 'status'], 'idx_product_subcategory_status');
            $table->index(['productBrandId', 'status'], 'idx_product_brand_status');
            $table->index(['name', 'status'], 'idx_product_name_status');
            $table->index(['sku', 'status'], 'idx_product_sku_status');
        });

        // Add indexes for Customer table
        Schema::table('customer', function (Blueprint $table) {
            $table->index(['status', 'id'], 'idx_customer_status_id');
            $table->index(['username', 'status'], 'idx_customer_username_status');
            $table->index(['email', 'status'], 'idx_customer_email_status');
        });

        // Add indexes for RolePermission table (authentication)
        Schema::table('rolePermission', function (Blueprint $table) {
            $table->index(['roleId', 'permissionId'], 'idx_role_permission_role_perm');
        });

        // Add indexes for Users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['username', 'status'], 'idx_users_username_status');
            $table->index(['roleId', 'status'], 'idx_users_role_status');
        });

        // Add indexes for PurchaseInvoice table
        Schema::table('purchaseInvoice', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'idx_purchase_invoice_created_id');
            $table->index(['supplierId'], 'idx_purchase_invoice_supplier');
        });

        // Add indexes for Supplier table
        Schema::table('supplier', function (Blueprint $table) {
            $table->index(['status', 'id'], 'idx_supplier_status_id');
            $table->index(['name', 'status'], 'idx_supplier_name_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop SaleInvoice indexes
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->dropIndex('idx_sale_invoice_hold_created');
            $table->dropIndex('idx_sale_invoice_date_hold');
            $table->dropIndex('idx_sale_invoice_customer_hold');
            $table->dropIndex('idx_sale_invoice_user_hold');
            $table->dropIndex('idx_sale_invoice_status_hold');
            $table->dropIndex('idx_sale_invoice_status_hold_2');
        });

        // Drop Transaction indexes
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropIndex('idx_transaction_related_type');
            $table->dropIndex('idx_transaction_type_debit_credit');
            $table->dropIndex('idx_transaction_related_type_debit');
            $table->dropIndex('idx_transaction_related_type_credit');
        });

        // Drop Product indexes
        Schema::table('product', function (Blueprint $table) {
            $table->dropIndex('idx_product_status_id');
            $table->dropIndex('idx_product_subcategory_status');
            $table->dropIndex('idx_product_brand_status');
            $table->dropIndex('idx_product_name_status');
            $table->dropIndex('idx_product_sku_status');
        });

        // Drop Customer indexes
        Schema::table('customer', function (Blueprint $table) {
            $table->dropIndex('idx_customer_status_id');
            $table->dropIndex('idx_customer_username_status');
            $table->dropIndex('idx_customer_email_status');
        });

        // Drop RolePermission indexes
        Schema::table('rolePermission', function (Blueprint $table) {
            $table->dropIndex('idx_role_permission_role_perm');
        });

        // Drop Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_username_status');
            $table->dropIndex('idx_users_role_status');
        });

        // Drop PurchaseInvoice indexes
        Schema::table('purchaseInvoice', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_invoice_created_id');
            $table->dropIndex('idx_purchase_invoice_supplier');
        });

        // Drop Supplier indexes
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropIndex('idx_supplier_status_id');
            $table->dropIndex('idx_supplier_name_status');
        });
    }
};
