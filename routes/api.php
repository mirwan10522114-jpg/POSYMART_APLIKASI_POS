<?php

use App\Http\Controllers\Api\v1\AdminTableController;
use App\Http\Controllers\Api\v1\AdminTableOrderController;
use App\Http\Controllers\Api\v1\AuditLogController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CustomerController;
use App\Http\Controllers\Api\v1\DiscountController;
use App\Http\Controllers\Api\v1\GuestTableOrderController;
use App\Http\Controllers\Api\v1\InventoryController;
use App\Http\Controllers\Api\v1\OrderController;
use App\Http\Controllers\Api\v1\PaymentWebhookController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ReportController;
use App\Http\Controllers\Api\v1\ReturnController;
use App\Http\Controllers\Api\v1\SaleController;
use App\Http\Controllers\Api\v1\SettingController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RequireRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PosyMart REST API v1
|--------------------------------------------------------------------------
| Kontrak endpoint /api/v1 sesuai spesifikasi PosyMart-Backend-PRD.md
*/

Route::prefix('v1')->group(function () {

    // --- Public Authentication ---
    Route::post('/auth/login', [AuthController::class, 'login']);

    // --- Authenticated Staff Routes (Bearer Token & Web Session) ---
    Route::middleware(['auth:sanctum,web'])->group(function () {

        // Auth management
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Products & Categories
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/categories', [ProductController::class, 'categories']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::middleware(RequireRole::class . ':Admin,Manager')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::match(['patch', 'put'], '/products/{id}', [ProductController::class, 'update']);
            Route::post('/products/{id}/adjust-stock', [ProductController::class, 'adjustStock']);
            Route::post('/products/{id}/archive', [ProductController::class, 'archive']);
            Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
        });

        // Customers
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::get('/customers/{id}/activity', [CustomerController::class, 'activity']);
        Route::patch('/customers/{id}', [CustomerController::class, 'update']);
        Route::post('/customers/{id}/archive', [CustomerController::class, 'archive'])
            ->middleware(RequireRole::class . ':Admin,Manager');

        // Sales (List & Detail)
        Route::get('/sales', [SaleController::class, 'index']);
        Route::get('/sales/{id}', [SaleController::class, 'show']);

        // Sales (Draft Cart)
        Route::get('/sales/draft', [SaleController::class, 'getDraft']);
        Route::post('/sales/draft', [SaleController::class, 'createDraft']);
        Route::post('/sales/draft/items', [SaleController::class, 'addItem']);
        Route::patch('/sales/draft/items/{itemId}', [SaleController::class, 'updateItem']);
        Route::delete('/sales/draft/items/{itemId}', [SaleController::class, 'removeItem']);
        Route::post('/sales/draft/customer', [SaleController::class, 'setCustomer']);
        Route::post('/sales/draft/discount', [SaleController::class, 'applyDiscount']);
        Route::delete('/sales/draft/discount', [SaleController::class, 'removeDiscount']);

        // Sales (Pay & Void)
        Route::post('/sales/draft/pay', [SaleController::class, 'pay'])
            ->middleware(IdempotencyMiddleware::class);
        Route::post('/sales/{id}/void', [SaleController::class, 'void'])
            ->middleware([RequireRole::class . ':Admin,Manager', IdempotencyMiddleware::class]);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::patch('/orders/{id}/items', [OrderController::class, 'updateItems']);
        Route::post('/orders/{id}/transition', [OrderController::class, 'transition'])
            ->middleware(IdempotencyMiddleware::class);

        // Returns
        Route::get('/returns', [ReturnController::class, 'index']);
        Route::post('/returns', [ReturnController::class, 'store']);
        Route::get('/returns/{id}', [ReturnController::class, 'show']);
        Route::post('/returns/{id}/items', [ReturnController::class, 'addItem']);
        Route::post('/returns/{id}/submit', [ReturnController::class, 'submit']);
        Route::middleware(RequireRole::class . ':Admin,Manager')->group(function () {
            Route::post('/returns/{id}/approve', [ReturnController::class, 'approve']);
            Route::post('/returns/{id}/reject', [ReturnController::class, 'reject']);
            Route::post('/returns/{id}/process-refund', [ReturnController::class, 'processRefund'])
                ->middleware(IdempotencyMiddleware::class);
        });

        // Discounts
        Route::get('/discounts', [DiscountController::class, 'index']);
        Route::get('/discounts/{id}', [DiscountController::class, 'show']);
        Route::middleware(RequireRole::class . ':Admin,Manager')->group(function () {
            Route::post('/discounts', [DiscountController::class, 'store']);
            Route::patch('/discounts/{id}', [DiscountController::class, 'update']);
            Route::post('/discounts/{id}/archive', [DiscountController::class, 'archive']);
        });

        // Reports
        Route::middleware(RequireRole::class . ':Admin,Manager')->group(function () {
            Route::get('/reports/summary', [ReportController::class, 'summary']);
            Route::get('/reports/daily', [ReportController::class, 'daily']);
            Route::get('/reports/payment-methods', [ReportController::class, 'paymentMethods']);
            Route::get('/reports/top-products', [ReportController::class, 'topProducts']);
            Route::get('/reports/export', [ReportController::class, 'exportCsv']);
        });

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::middleware(RequireRole::class . ':Admin')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
            Route::patch('/users/{id}', [UserController::class, 'update']);
            Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
        });

        // Settings
        Route::get('/settings', [SettingController::class, 'show']);
        Route::put('/settings', [SettingController::class, 'update'])
            ->middleware(RequireRole::class . ':Admin');

        // Inventory
        Route::get('/inventory/movements', [InventoryController::class, 'movements']);
        Route::get('/inventory/products/{id}/movements', [InventoryController::class, 'productMovements']);

        // Audit Logs (Admin only)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware(RequireRole::class . ':Admin');

        // --- Table Ordering (Staff Operations) ---
        // Tables & QRs Management (Min 50 tables T001..T050)
        Route::get('/admin/tables', [AdminTableController::class, 'index']);
        Route::post('/admin/tables', [AdminTableController::class, 'store'])
            ->middleware(RequireRole::class . ':Admin');
        Route::patch('/admin/tables/{id}', [AdminTableController::class, 'update'])
            ->middleware(RequireRole::class . ':Admin');
        Route::post('/admin/table-qrs/{id}/rotate', [AdminTableController::class, 'rotateQr'])
            ->middleware(RequireRole::class . ':Admin');
        Route::post('/admin/table-qrs/{id}/toggle', [AdminTableController::class, 'toggleQr'])
            ->middleware(RequireRole::class . ':Admin');

        // Table Orders Queue & Operations
        Route::get('/admin/orders', [AdminTableOrderController::class, 'index']);
        Route::post('/admin/orders', [AdminTableOrderController::class, 'store']);
        Route::get('/admin/orders/{id}', [AdminTableOrderController::class, 'show']);
        Route::post('/admin/orders/{id}/transitions', [AdminTableOrderController::class, 'transition']);

        // Notifications (Live stream/polling & 60s escalation tracking)
        Route::get('/admin/notifications', [AdminTableOrderController::class, 'notifications']);
        Route::post('/admin/notifications/read-all', [AdminTableOrderController::class, 'readAllNotifications']);
        Route::post('/admin/notifications/{id}/read', [AdminTableOrderController::class, 'readNotification']);

        // Payments Monitoring & Reconciliation
        Route::get('/admin/payments', [AdminTableOrderController::class, 'payments']);
        Route::post('/admin/payments/{id}/reconcile', [AdminTableOrderController::class, 'reconcilePayment'])
            ->middleware(RequireRole::class . ':Admin');

        // Refunds Management
        Route::get('/admin/refunds', [AdminTableOrderController::class, 'refunds']);
        Route::post('/admin/orders/{id}/refund-requests', [AdminTableOrderController::class, 'requestRefund']);
        Route::post('/admin/refunds/{id}/approve', [AdminTableOrderController::class, 'approveRefund'])
            ->middleware(RequireRole::class . ':Admin,Manager');
    });

    // --- Guest Table Ordering (Public / Guest Session Scoped) ---
    Route::get('/qr/{publicToken}', [GuestTableOrderController::class, 'resolveQr']);
    Route::post('/guest-sessions', [GuestTableOrderController::class, 'createSession']);
    Route::post('/guest/login', [GuestTableOrderController::class, 'loginCustomer']);
    Route::post('/guest/register', [GuestTableOrderController::class, 'registerCustomer']);
    Route::get('/guest/menu', [GuestTableOrderController::class, 'getMenu']);
    Route::post('/guest/carts', [GuestTableOrderController::class, 'saveCart']);
    Route::post('/guest/checkouts', [GuestTableOrderController::class, 'checkout']);
    Route::get('/guest/orders', [GuestTableOrderController::class, 'listOrders']);
    Route::get('/guest/orders/{id}', [GuestTableOrderController::class, 'getOrder']);
    Route::post('/guest/orders/{id}/payment-status-check', [GuestTableOrderController::class, 'paymentStatusCheck']);
    Route::post('/guest/orders/{id}/simulate-pay', [GuestTableOrderController::class, 'simulatePay']);

    // --- Payment Provider Webhooks (Idempotent) ---
    Route::post('/webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle']);
});
