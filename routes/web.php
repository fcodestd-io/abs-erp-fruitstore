<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    // Menampilkan halaman login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

    // Memproses login user
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Logout user
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard utama
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Shipment Routes
    |--------------------------------------------------------------------------
    */

    // Menampilkan daftar pengiriman
    Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');

    // Form membuat pengiriman baru
    Route::get('shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');

    // Menyimpan pengiriman baru
    Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');

    // Menyelesaikan / menerima pengiriman
    Route::post('shipments/{shipment}/complete', [ShipmentController::class, 'complete'])->name('shipments.complete');

    // Membatalkan pengiriman
    Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');

    // Mengambil produk berdasarkan gudang
    Route::get('shipments/warehouse-products/{warehouse}', [ShipmentController::class, 'getWarehouseProducts']);

    // Mengambil detail item pengiriman
    Route::get('shipments/{shipment}/items', [ShipmentController::class, 'getItems']);


    /*
    |--------------------------------------------------------------------------
    | Role Specific Group
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Owner Only
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:owner')->group(function () {

        // Manajemen user
        Route::resource('users', UserController::class)
            ->except(['create', 'show', 'edit']);

        // Menampilkan daftar diskon
        Route::get('discounts', [DiscountController::class, 'index'])->name('discounts.index');

        // Form membuat diskon
        Route::get('discounts/create', [DiscountController::class, 'create'])->name('discounts.create');

        // Menyimpan diskon baru
        Route::post('discounts', [DiscountController::class, 'store'])->name('discounts.store');

        // Mengakhiri diskon lebih awal
        Route::post('discounts/{discount}/end-now', [DiscountController::class, 'endNow'])->name('discounts.end-now');

        // Mengambil produk berdasarkan toko
        Route::get('discounts/store-products/{store}', [DiscountController::class, 'getStoreProducts']);

        // Mengambil detail item diskon
        Route::get('discounts/{discount}/items', [DiscountController::class, 'getItems']);

    });


    /*
    |--------------------------------------------------------------------------
    | Product Read - Semua Role
    |--------------------------------------------------------------------------
    */

    // Semua role yang sudah login dapat melihat daftar produk
    Route::get('products', [ProductController::class, 'index'])->name('products.index');


    /*
    |--------------------------------------------------------------------------
    | Owner & Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:owner,admin')->group(function () {

        // Manajemen supplier
        Route::resource('suppliers', SupplierController::class)
            ->except(['create', 'show', 'edit']);

        // Membuat, mengubah, dan menghapus produk
        Route::resource('products', ProductController::class)
            ->except(['index', 'create', 'show', 'edit']);

        // Mengambil daftar satuan
        Route::get('/units-list', [UnitController::class, 'index'])->name('units.list');

        // Menambahkan satuan baru
        Route::post('/units-store', [UnitController::class, 'store'])->name('units.store');

        // Menghapus satuan
        Route::delete('/units-delete/{unit}', [UnitController::class, 'destroy'])->name('units.delete');

    });


    /*
    |--------------------------------------------------------------------------
    | Warehouse Supervisor
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:owner,admin,warehouse_supervisor')->group(function () {

        // Manajemen gudang
        Route::resource('warehouses', WarehouseController::class)
            ->except(['create', 'show', 'edit']);

        // Membuka halaman pengelolaan gudang
        Route::get('warehouses/{warehouse}/manage', [WarehouseController::class, 'manage'])
            ->name('warehouses.manage');

        // Menambahkan produk ke gudang
        Route::post('warehouses/{warehouse}/add-products', [WarehouseController::class, 'addProducts'])
            ->name('warehouses.add-products');

        // Menyimpan draft opname gudang
        Route::post('warehouses/{warehouse}/opname/draft', [WarehouseController::class, 'saveOpnameDraft'])
            ->name('warehouses.opname.draft');

        // Menyelesaikan opname gudang
        Route::post('warehouses/{warehouse}/opname/complete/{adjustment?}', [WarehouseController::class, 'completeOpname'])
            ->name('warehouses.opname.complete');

        // Membatalkan opname gudang
        Route::post('warehouses/{warehouse}/opname/cancel/{adjustment}', [WarehouseController::class, 'cancelOpname'])
            ->name('warehouses.opname.cancel');

        // Mengambil detail item opname gudang
        Route::get('warehouses/opname/{adjustment}/items', [WarehouseController::class, 'getOpnameItems'])
            ->name('warehouses.opname.items');


        /*
        |--------------------------------------------------------------------------
        | Purchase Order
        |--------------------------------------------------------------------------
        */

        // Menampilkan daftar purchase order
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
            ->name('purchase-orders.index');

        // Form membuat purchase order
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])
            ->name('purchase-orders.create');

        // Menyimpan purchase order baru
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
            ->name('purchase-orders.store');

        // Menyelesaikan purchase order
        Route::post('purchase-orders/{purchaseOrder}/complete', [PurchaseOrderController::class, 'complete'])
            ->name('purchase-orders.complete');

        // Membatalkan purchase order
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase-orders.cancel');

        // Mengambil produk berdasarkan gudang
        Route::get('purchase-orders/warehouse-products/{warehouse}', [PurchaseOrderController::class, 'getProductsByWarehouse']);

        // Mengambil detail item purchase order
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'getItems']);

    });


    /*
    |--------------------------------------------------------------------------
    | Cashier
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:owner,admin,cashier')->group(function () {

        // Manajemen toko
        Route::resource('stores', StoreController::class)
            ->except(['create', 'show', 'edit']);

        // Membuka halaman pengelolaan toko
        Route::get('stores/{store}/manage', [StoreController::class, 'manage'])
            ->name('stores.manage');

        // Menambahkan produk ke toko
        Route::post('stores/{store}/add-products', [StoreController::class, 'addProducts'])
            ->name('stores.add-products');

        // Menyimpan draft opname toko
        Route::post('stores/{store}/opname/draft', [StoreController::class, 'saveOpnameDraft'])
            ->name('stores.opname.draft');

        // Menyelesaikan opname toko
        Route::post('stores/{store}/opname/complete/{adjustment?}', [StoreController::class, 'completeOpname'])
            ->name('stores.opname.complete');

        // Membatalkan opname toko
        Route::post('stores/{store}/opname/cancel/{adjustment}', [StoreController::class, 'cancelOpname'])
            ->name('stores.opname.cancel');

        // Mengambil detail item opname toko
        Route::get('stores/opname/{adjustment}/items', [StoreController::class, 'getOpnameItems'])
            ->name('stores.opname.items');


        /*
        |--------------------------------------------------------------------------
        | Sales / POS
        |--------------------------------------------------------------------------
        */

        // Membuka halaman POS kasir
        Route::get('sales/pos', [SaleController::class, 'create'])
            ->name('sales.create');

        // Menyimpan transaksi penjualan
        Route::post('sales', [SaleController::class, 'store'])
            ->name('sales.store');

        // Mencetak struk penjualan
        Route::get('sales/{sale}/receipt', [SaleController::class, 'printReceipt'])
            ->name('sales.receipt');

        // Menampilkan laporan penjualan
        Route::get('sales', [SaleController::class, 'index'])
            ->name('sales.index');

        // Mengambil detail item penjualan
        Route::get('sales/{sale}/items', [SaleController::class, 'getItems']);

        // Mengambil produk dan stok toko untuk POS
        Route::get('sales/store-products/{store}', [SaleController::class, 'getStoreProducts']);

    });

});
