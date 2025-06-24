<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('user', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        $this->routes(function () {
            
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
            Route::middleware('user')
                ->prefix('user')
                ->group(base_path('routes/userRoutes.php'));
            Route::middleware('permission')
                ->prefix('permission')
                ->group(base_path('routes/permissionRoutes.php'));
            Route::middleware('role')
                ->prefix('role')
                ->group(base_path('routes/roleRoutes.php'));
            Route::middleware('setting')
                ->prefix('setting')
                ->group(base_path('routes/appSettingRoutes.php'));
            Route::middleware('account')
                ->prefix('account')
                ->group(base_path('routes/accountRoutes.php'));
            Route::middleware('transaction')
                ->prefix('transaction')
                ->group(base_path('routes/transactionRoutes.php'));
            Route::middleware('role-permission')
                ->prefix('role-permission')
                ->group(base_path('routes/rolePermissionRoutes.php'));
            Route::middleware('designation')
                ->prefix('designation')
                ->group(base_path('routes/designationRoutes.php'));
            Route::middleware('files')
                ->prefix('files')
                ->group(base_path('routes/filesRoutes.php'));
            Route::middleware('email-config')
                ->prefix('email-config')
                ->group(base_path('routes/emailConfigRoutes.php'));
            Route::middleware('email')
                ->prefix('email')
                ->group(base_path('routes/emailRoutes.php'));
            Route::middleware('dashboard')
                ->prefix('dashboard')
                ->group(base_path('routes/dashboardRoutes.php'));
            Route::middleware('product-category')
                ->prefix('product-category')
                ->group(base_path('routes/productCategoryRoutes.php'));
            Route::middleware('product-sub-category')
                ->prefix('product-sub-category')
                ->group(base_path('routes/productSubCategoryRoutes.php'));
            Route::middleware('product-vat')
                ->prefix('product-vat')
                ->group(base_path('routes/productVatRoutes.php'));
            Route::middleware('customer')
                ->prefix('customer')
                ->group(base_path('routes/customerRoutes.php'));
            Route::middleware('product-brand')
                ->prefix('product-brand')
                ->group(base_path('routes/productBrandRoutes.php'));
            Route::middleware('product')
                ->prefix('product')
                ->group(base_path('routes/productRoutes.php'));
            Route::middleware('product-color')
                ->prefix('product-color')
                ->group(base_path('routes/colorsRoutes.php'));
            Route::middleware('adjust-inventory')
                ->prefix('adjust-inventory')
                ->group(base_path('routes/adjustInventoryRoutes.php'));
            Route::middleware('supplier')
                ->prefix('supplier')
                ->group(base_path('routes/supplierRoutes.php'));
            Route::middleware('purchase-invoice')
                ->prefix('purchase-invoice')
                ->group(base_path('routes/purchaseInvoiceRoutes.php'));
            Route::middleware('payment-purchase-invoice')
                ->prefix('payment-purchase-invoice')
                ->group(base_path('routes/paymentPurchaseInvoiceRoutes.php'));
            Route::middleware('return-purchase-invoice')
                ->prefix('return-purchase-invoice')
                ->group(base_path('routes/returnPurchaseInvoiceRoutes.php'));
            Route::middleware('sale-invoice')
                ->prefix('sale-invoice')
                ->group(base_path('routes/saleInvoiceRoutes.php'));
            Route::middleware('payment-sale-invoice')
                ->prefix('payment-sale-invoice')
                ->group(base_path('routes/paymentSaleInvoiceRoutes.php'));
            Route::middleware('return-sale-invoice')
                ->prefix('return-sale-invoice')
                ->group(base_path('routes/returnSaleInvoiceRoutes.php'));
            Route::middleware('product-image')
                ->prefix('product-image')
                ->group(base_path('routes/productImageRoutes.php'));
            Route::middleware('report')
                ->prefix('report')
                ->group(base_path('routes/reportRoutes.php'));
            Route::middleware('reorder-quantity')
                ->prefix('reorder-quantity')
                ->group(base_path('routes/reorderQuantityRoutes.php'));
            Route::middleware('purchase-reorder-invoice')
                ->prefix('purchase-reorder-invoice')
                ->group(base_path('routes/purchaseReorderInvoiceRoutes.php'));
            Route::middleware('page-size')
                ->prefix('page-size')
                ->group(base_path('routes/pageSizeRoutes.php'));
            Route::middleware('quote')
                ->prefix('quote')
                ->group(base_path('routes/quoteRoutes.php'));
            Route::middleware('email-invoice')
                ->prefix('email-invoice')
                ->group(base_path('routes/sendEmailRoutes.php'));
            Route::middleware('shift')
                ->prefix('shift')
                ->group(base_path('routes/shiftRoutes.php'));
            Route::middleware('education')
                ->prefix('education')
                ->group(base_path('routes/educationRoutes.php'));
            Route::middleware('department')
                ->prefix('department')
                ->group(base_path('routes/departmentRoutes.php'));
            Route::middleware('designation-history')
                ->prefix('designation-history')
                ->group(base_path('routes/designationHistoryRoutes.php'));
            Route::middleware('employment-status')
                ->prefix('employment-status')
                ->group(base_path('routes/employmentStatusRoutes.php'));
            Route::middleware('salary-history')
                ->prefix('salary-history')
                ->group(base_path('routes/salaryHistoryRoutes.php'));
            Route::middleware('award')
                ->prefix('award')
                ->group(base_path('routes/awardRoutes.php'));
            Route::middleware('award-history')
                ->prefix('award-history')
                ->group(base_path('routes/awardHistoryRoutes.php'));
            Route::middleware('announcement')
                ->prefix('announcement')
                ->group(base_path('routes/announcementRoutes.php'));
            route::middleware('discount')
                ->prefix('discount')
                ->group(base_path('routes/discountRoutes.php'));
            route::middleware('currency')
                ->prefix('currency')
                ->group(base_path('routes/currencyRoutes.php'));
            route::middleware('product-reports')
                ->prefix('product-reports')
                ->group(base_path('routes/productReportRoutes.php'));
            route::middleware('product-attribute')
                ->prefix('product-attribute')
                ->group(base_path('routes/productAttributeRoutes.php'));
            route::middleware('product-attribute-value')
                ->prefix('product-attribute-value')
                ->group(base_path('routes/productAttributeValueRoutes.php'));
            route::middleware('product-product-attribute-value')
                ->prefix('product-product-attribute-value')
                ->group(base_path('routes/productProductAttributeValueRoutes.php'));
            route::middleware('googlelogin')
                ->prefix('googlelogin')
                ->group(base_path('routes/googleLoginRoutes.php'));
            route::middleware('payment-method')
                ->prefix('payment-method')
                ->group(base_path('routes/paymentMethodRoutes.php'));
            route::middleware('manual-payment')
                ->prefix('manual-payment')
                ->group(base_path('routes/manualPaymentRoutes.php'));
            route::middleware('customer-profileImage')
                ->prefix('customer-profileImage')
                ->group(base_path('routes/customerProfileImageRoutes.php'));
            route::middleware('terms-and-condition')
                ->prefix('terms-and-condition')
                ->group(base_path('routes/termsAndConditionRoutes.php'));
            route::middleware('uom')
                ->prefix('uom')
                ->group(base_path('routes/uomRoutes.php'));
            route::middleware('send-sms')
                ->prefix('send-sms')
                ->group(base_path('routes/sendSmsRoutes.php'));
            route::middleware('manufacturer')
                ->prefix('manufacturer')
                ->group(base_path('routes/manufacturerRoutes.php'));
            route::middleware('weight-unit')
                ->prefix('weight-unit')
                ->group(base_path('routes/weightUnitRoutes.php'));
            route::middleware('dimension-unit')
                ->prefix('dimension-unit')
                ->group(base_path('routes/dimensionUnitRoutes.php'));
        });
    }
}
