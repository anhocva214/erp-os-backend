<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        define('endpoints', [
            "paymentPurchaseInvoice",
            "paymentSaleInvoice",
            "returnSaleInvoice",
            "purchaseInvoice",
            "returnPurchaseInvoice",
            "rolePermission",
            "saleInvoice",
            "transaction",
            "permission",
            "dashboard",
            "customer",
            "supplier",
            "product",
            "user",
            "role",
            "designation",
            "productCategory",
            "account",
            "setting",
            "productSubCategory",
            "productBrand",
            "email",
            "adjust",
            "warehouse",
            "stock",
            "attribute",
            "color",
            "meta",
            "transfer",
            "vat",
            "reorderQuantity",
            "purchaseReorderInvoice",
            "pageSize",
            "quote",
            "emailConfig",
            "shift",
            "award",
            "awardHistory",
            "department",
            "designationHistory",
            "education",
            "salaryHistory",
            "employmentStatus",
            "announcement",
            "discount",
            "currency",
            "productReports",
            "productAttribute",
            "productAttributeValue",
            "productProductAttributeValue",
            "paymentMethod",
            "manualPayment",
            'termsAndCondition',
            'smsConfig',
            'uom',
            'sms',
            'wightUnit',
            'dimensionUnit',
            'manufacturer',

        ]);

        define('PERMISSIONSTYPES', [
            'create',
            'readAll',
            "readSingle",
            'update',
            'delete',
        ]);
        foreach (endpoints as $endpoint) {
            foreach (PERMISSIONSTYPES as $permissionType) {
                $permission = new Permission();
                $permission->name = $permissionType . "-" . $endpoint;
                $permission->save();
            }
        }
    }
}
