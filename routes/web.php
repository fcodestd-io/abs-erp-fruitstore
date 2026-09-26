<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Semua route untuk aplikasi web didefinisikan di file ini.
|
| Route dibagi menjadi dua kelompok utama:
|
| 1. Guest Routes
|    Route yang hanya dapat diakses oleh user yang BELUM login.
|    Contoh: halaman login.
|
| 2. Authenticated Routes
|    Route yang hanya dapat diakses oleh user yang SUDAH login.
|    Di dalamnya terdapat pembatasan berdasarkan role:
|
|    - owner
|    - admin
|    - warehouse_supervisor
|    - cashier
|
|--------------------------------------------------------------------------
*/

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
|
| Route di bawah hanya dapat diakses oleh user yang BELUM login.
|
| Middleware:
|   guest
|
| Jika user sudah login kemudian mencoba mengakses /login,
| Laravel akan mencegahnya mengakses halaman tersebut sesuai
| konfigurasi middleware guest.
|
*/

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Menampilkan Form Login
    |--------------------------------------------------------------------------
    |
    | GET /login
    |
    | Menampilkan halaman/form login.
    |
    | Controller:
    |   AuthController@showLoginForm
    |
    | Nama route:
    |   login
    |
    | Nama route ini penting karena middleware/auth Laravel biasanya
    | menggunakan route bernama "login" ketika user belum terautentikasi.
    |
    */

    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');


    /*
    |--------------------------------------------------------------------------
    | Proses Login
    |--------------------------------------------------------------------------
    |
    | POST /login
    |
    | Menerima username/password dari form login dan melakukan
    | proses autentikasi user.
    |
    | Controller:
    |   AuthController@login
    |
    | Nama route:
    |   login.store
    |
    */

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
|
| Semua route di dalam group ini hanya dapat diakses oleh user
| yang sudah login.
|
| Middleware:
|   auth
|
| Artinya user harus memiliki session/login yang valid sebelum
| dapat mengakses dashboard maupun fitur aplikasi lainnya.
|
*/

Route::middleware('auth')->group(function () {


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    |
    | POST /logout
    |
    | Mengakhiri session/login user.
    |
    | Menggunakan POST karena logout merupakan sebuah action
    | yang mengubah state/session, bukan sekadar mengambil halaman.
    |
    */

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | GET /
    |
    | Halaman utama aplikasi setelah user berhasil login.
    |
    | Controller:
    |   DashboardController@index
    |
    | Nama route:
    |   dashboard
    |
    */

    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | SHIPMENTS / PENGIRIMAN
    |--------------------------------------------------------------------------
    |
    | Fitur pengiriman barang dari warehouse menuju store.
    |
    | Alur umumnya:
    |
    |   Warehouse
    |       ↓
    |   Shipment
    |       ↓
    |   Store
    |
    | Shipment memiliki status dan dapat:
    |
    | - dibuat
    | - dilihat
    | - diselesaikan/completed
    | - dibatalkan/canceled
    |
    */


    /*
    | Menampilkan daftar shipment.
    |
    | GET /shipments
    */

    Route::get('shipments', [ShipmentController::class, 'index'])
        ->name('shipments.index');


    /*
    | Menampilkan form pembuatan shipment baru.
    |
    | GET /shipments/create
    */

    Route::get('shipments/create', [ShipmentController::class, 'create'])
        ->name('shipments.create');


    /*
    | Menyimpan shipment baru.
    |
    | POST /shipments
    */

    Route::post('shipments', [ShipmentController::class, 'store'])
        ->name('shipments.store');


    /*
    | Menyelesaikan shipment.
    |
    | Ketika shipment completed, proses bisnis seperti pemindahan
    | stok warehouse → store dapat dieksekusi oleh controller.
    |
    | POST /shipments/{shipment}/complete
    |
    | {shipment} menggunakan implicit route model binding Laravel.
    */

    Route::post('shipments/{shipment}/complete', [ShipmentController::class, 'complete'])
        ->name('shipments.complete');


    /*
    | Membatalkan shipment.
    |
    | POST /shipments/{shipment}/cancel
    */

    Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])
        ->name('shipments.cancel');


    /*
    | Mengambil daftar produk yang tersedia pada warehouse tertentu.
    |
    | GET /shipments/warehouse-products/{warehouse}
    |
    | Biasanya digunakan oleh JavaScript/AJAX ketika user memilih
    | warehouse pada form shipment.
    |
    | Contoh:
    |   User memilih Warehouse A
    |       ↓
    |   Request ke endpoint ini
    |       ↓
    |   Laravel mengembalikan produk + stok warehouse
    |
    */

    Route::get(
        'shipments/warehouse-products/{warehouse}',
        [ShipmentController::class, 'getWarehouseProducts']
    );


    /*
    | Mengambil item-item dari shipment tertentu.
    |
    | GET /shipments/{shipment}/items
    |
    | Biasanya digunakan untuk menampilkan detail shipment
    | secara asynchronous/AJAX tanpa reload halaman.
    */

    Route::get(
        'shipments/{shipment}/items',
        [ShipmentController::class, 'getItems']
    );


    /*
    |--------------------------------------------------------------------------
    | ROLE SPECIFIC ROUTES
    |--------------------------------------------------------------------------
    |
    | Mulai bagian ini, akses route dibatasi berdasarkan role user.
    |
    | Middleware:
    |
    |   role:owner
    |   role:owner,admin
    |   role:owner,admin,warehouse_supervisor
    |   role:owner,admin,cashier
    |
    | Format:
    |
    |   role:owner,admin
    |
    | berarti user dengan role owner ATAU admin dapat mengakses
    | seluruh route di dalam group tersebut.
    |
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | OWNER ONLY
    |--------------------------------------------------------------------------
    |
    | Fitur yang hanya dapat diakses oleh Owner.
    |
    */

    Route::middleware('role:owner')->group(function () {


        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        |
        | Resource Controller Laravel secara otomatis membuat route CRUD.
        |
        | Namun create, show, dan edit dikecualikan.
        |
        | Yang tersedia:
        |
        | GET      /users
        | POST     /users
        | PUT/PATCH /users/{user}
        | DELETE   /users/{user}
        |
        | Dengan kata lain, form create/edit/detail kemungkinan dibuat
        | menggunakan mekanisme/custom UI sendiri atau tidak diperlukan
        | sebagai route terpisah.
        |
        */

        Route::resource('users', UserController::class)
            ->except(['create', 'show', 'edit']);


        /*
        |--------------------------------------------------------------------------
        | DISCOUNTS
        |--------------------------------------------------------------------------
        |
        | Owner dapat mengelola diskon toko.
        |
        | Diskon terdiri dari:
        |
        | - header discount
        | - periode aktif
        | - store
        | - item/product yang mendapatkan diskon
        |
        */


        /*
        | Menampilkan daftar diskon.
        |
        | GET /discounts
        */

        Route::get('discounts', [DiscountController::class, 'index'])
            ->name('discounts.index');


        /*
        | Menampilkan form membuat diskon.
        |
        | GET /discounts/create
        */

        Route::get('discounts/create', [DiscountController::class, 'create'])
            ->name('discounts.create');


        /*
        | Menyimpan diskon baru.
        |
        | POST /discounts
        */

        Route::post('discounts', [DiscountController::class, 'store'])
            ->name('discounts.store');


        /*
        | Mengakhiri diskon lebih awal.
        |
        | POST /discounts/{discount}/end-now
        |
        | Digunakan jika Owner ingin menghentikan diskon
        | sebelum tanggal berakhir yang telah ditentukan.
        */

        Route::post(
            'discounts/{discount}/end-now',
            [DiscountController::class, 'endNow']
        )->name('discounts.end-now');


        /*
        | Mengambil produk yang tersedia pada store tertentu.
        |
        | Biasanya digunakan AJAX ketika Owner memilih store
        | pada form pembuatan diskon.
        */

        Route::get(
            'discounts/store-products/{store}',
            [DiscountController::class, 'getStoreProducts']
        );


        /*
        | Mengambil item-item dari discount tertentu.
        |
        | Biasanya digunakan untuk menampilkan detail discount
        | melalui AJAX.
        */

        Route::get(
            'discounts/{discount}/items',
            [DiscountController::class, 'getItems']
        );
    });


    /*
    |--------------------------------------------------------------------------
    | OWNER & ADMIN
    |--------------------------------------------------------------------------
    |
    | Fitur master data yang dapat dikelola oleh Owner dan Admin.
    |
    */

    Route::middleware('role:owner,admin')->group(function () {


        /*
        |--------------------------------------------------------------------------
        | Suppliers
        |--------------------------------------------------------------------------
        |
        | CRUD supplier.
        |
        | create, show, edit dikecualikan seperti pada User resource.
        |
        */

        Route::resource('suppliers', SupplierController::class)
            ->except(['create', 'show', 'edit']);


        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        |
        | CRUD produk/buah.
        |
        | Data produk menjadi master data yang digunakan oleh:
        |
        | - warehouse
        | - store
        | - purchase order
        | - shipment
        | - sales/POS
        | - stock opname
        |
        */

        Route::resource('products', ProductController::class)
            ->except(['create', 'show', 'edit']);


        /*
        |--------------------------------------------------------------------------
        | Units
        |--------------------------------------------------------------------------
        |
        | Unit memiliki endpoint custom karena pengelolaannya sederhana
        | dan tidak membutuhkan resource controller penuh.
        |
        | Contoh:
        |
        | - kg
        | - pcs
        | - ikat
        | - dus
        |
        */


        /*
        | Mengambil/menampilkan daftar unit.
        |
        | GET /units-list
        */

        Route::get('/units-list', [UnitController::class, 'index'])
            ->name('units.list');


        /*
        | Membuat unit baru.
        |
        | POST /units-store
        */

        Route::post('/units-store', [UnitController::class, 'store'])
            ->name('units.store');


        /*
        | Menghapus unit.
        |
        | DELETE /units-delete/{unit}
        */

        Route::delete(
            '/units-delete/{unit}',
            [UnitController::class, 'destroy']
        )->name('units.delete');
    });


    /*
    |--------------------------------------------------------------------------
    | WAREHOUSE SUPERVISOR
    |--------------------------------------------------------------------------
    |
    | Route ini dapat diakses oleh:
    |
    | - Owner
    | - Admin
    | - Warehouse Supervisor
    |
    | Fokus utama:
    |
    | - Warehouse
    | - Stock Warehouse
    | - Stock Opname Warehouse
    | - Purchase Order
    |
    */

    Route::middleware('role:owner,admin,warehouse_supervisor')->group(function () {


        /*
        |--------------------------------------------------------------------------
        | WAREHOUSE MANAGEMENT
        |--------------------------------------------------------------------------
        |
        | CRUD warehouse.
        |
        */

        Route::resource('warehouses', WarehouseController::class)
            ->except(['create', 'show', 'edit']);


        /*
        | Halaman management warehouse tertentu.
        |
        | GET /warehouses/{warehouse}/manage
        |
        | Biasanya berisi:
        |
        | - daftar stok
        | - produk
        | - stock opname
        | - informasi warehouse
        |
        */

        Route::get(
            'warehouses/{warehouse}/manage',
            [WarehouseController::class, 'manage']
        )->name('warehouses.manage');


        /*
        | Menambahkan produk ke warehouse.
        |
        | POST /warehouses/{warehouse}/add-products
        |
        | Digunakan untuk memasukkan produk ke daftar produk
        | yang dikelola oleh warehouse tersebut.
        */

        Route::post(
            'warehouses/{warehouse}/add-products',
            [WarehouseController::class, 'addProducts']
        )->name('warehouses.add-products');


        /*
        |--------------------------------------------------------------------------
        | WAREHOUSE STOCK OPNAME
        |--------------------------------------------------------------------------
        |
        | Stock opname digunakan untuk mencocokkan:
        |
        |   current stock
        |        vs
        |   physical/opname stock
        |
        | Perubahan stok baru diterapkan ketika opname diselesaikan.
        |
        */


        /*
        | Menyimpan draft stock opname.
        |
        | POST /warehouses/{warehouse}/opname/draft
        |
        | Draft belum dianggap sebagai opname final.
        */

        Route::post(
            'warehouses/{warehouse}/opname/draft',
            [WarehouseController::class, 'saveOpnameDraft']
        )->name('warehouses.opname.draft');


        /*
        | Menyelesaikan stock opname.
        |
        | {adjustment?} bersifat optional.
        |
        | Artinya endpoint dapat digunakan untuk:
        |
        | - membuat sekaligus menyelesaikan opname baru
        | - menyelesaikan draft opname yang sudah ada
        |
        */

        Route::post(
            'warehouses/{warehouse}/opname/complete/{adjustment?}',
            [WarehouseController::class, 'completeOpname']
        )->name('warehouses.opname.complete');


        /*
        | Membatalkan stock opname.
        |
        | {adjustment} merupakan adjustment/opname yang akan dibatalkan.
        */

        Route::post(
            'warehouses/{warehouse}/opname/cancel/{adjustment}',
            [WarehouseController::class, 'cancelOpname']
        )->name('warehouses.opname.cancel');


        /*
        | Mengambil item-item dari stock opname tertentu.
        |
        | Biasanya digunakan oleh AJAX untuk membuka kembali
        | draft/detail opname.
        */

        Route::get(
            'warehouses/opname/{adjustment}/items',
            [WarehouseController::class, 'getOpnameItems']
        )->name('warehouses.opname.items');


        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDERS
        |--------------------------------------------------------------------------
        |
        | Purchase Order digunakan untuk mencatat pembelian barang
        | dari supplier menuju warehouse.
        |
        | Alur sederhananya:
        |
        |   Supplier
        |      ↓
        |   Purchase Order
        |      ↓
        |   Warehouse
        |      ↓
        |   Stock Warehouse
        |
        */


        /*
        | Menampilkan daftar Purchase Order.
        |
        | GET /purchase-orders
        */

        Route::get(
            'purchase-orders',
            [PurchaseOrderController::class, 'index']
        )->name('purchase-orders.index');


        /*
        | Form membuat Purchase Order.
        |
        | GET /purchase-orders/create
        */

        Route::get(
            'purchase-orders/create',
            [PurchaseOrderController::class, 'create']
        )->name('purchase-orders.create');


        /*
        | Menyimpan Purchase Order baru.
        |
        | POST /purchase-orders
        */

        Route::post(
            'purchase-orders',
            [PurchaseOrderController::class, 'store']
        )->name('purchase-orders.store');


        /*
        | Menyelesaikan Purchase Order.
        |
        | Ketika completed, stok warehouse dapat diperbarui
        | berdasarkan qty aktual yang diterima.
        */

        Route::post(
            'purchase-orders/{purchaseOrder}/complete',
            [PurchaseOrderController::class, 'complete']
        )->name('purchase-orders.complete');


        /*
        | Membatalkan Purchase Order.
        |
        | POST /purchase-orders/{purchaseOrder}/cancel
        */

        Route::post(
            'purchase-orders/{purchaseOrder}/cancel',
            [PurchaseOrderController::class, 'cancel']
        )->name('purchase-orders.cancel');


        /*
        | Mengambil produk yang tersedia berdasarkan warehouse.
        |
        | Digunakan oleh AJAX pada form Purchase Order.
        |
        | Contoh:
        |
        |   Pilih Warehouse
        |       ↓
        |   Request endpoint ini
        |       ↓
        |   Tampilkan produk yang tersedia/relevan
        */

        Route::get(
            'purchase-orders/warehouse-products/{warehouse}',
            [PurchaseOrderController::class, 'getProductsByWarehouse']
        );


        /*
        | Mengambil item dari Purchase Order tertentu.
        |
        | Biasanya untuk menampilkan detail PO tanpa reload halaman.
        */

        Route::get(
            'purchase-orders/{purchaseOrder}/items',
            [PurchaseOrderController::class, 'getItems']
        );
    });


    /*
    |--------------------------------------------------------------------------
    | CASHIER
    |--------------------------------------------------------------------------
    |
    | Route ini dapat diakses oleh:
    |
    | - Owner
    | - Admin
    | - Cashier
    |
    | Fokus utama:
    |
    | - Store
    | - Stock Store
    | - Stock Opname Store
    | - POS
    | - Penjualan
    | - Receipt/struk
    |
    */

    Route::middleware('role:owner,admin,cashier')->group(function () {


        /*
        |--------------------------------------------------------------------------
        | STORE MANAGEMENT
        |--------------------------------------------------------------------------
        |
        | CRUD store/toko.
        |
        */

        Route::resource('stores', StoreController::class)
            ->except(['create', 'show', 'edit']);


        /*
        | Halaman management store tertentu.
        |
        | GET /stores/{store}/manage
        |
        | Biasanya berisi:
        |
        | - daftar produk
        | - stok toko
        | - stock opname
        | - informasi toko
        |
        */

        Route::get(
            'stores/{store}/manage',
            [StoreController::class, 'manage']
        )->name('stores.manage');


        /*
        | Menambahkan produk ke store.
        |
        | POST /stores/{store}/add-products
        */

        Route::post(
            'stores/{store}/add-products',
            [StoreController::class, 'addProducts']
        )->name('stores.add-products');


        /*
        |--------------------------------------------------------------------------
        | STORE STOCK OPNAME
        |--------------------------------------------------------------------------
        |
        | Mekanismenya sama seperti warehouse stock opname,
        | tetapi target stoknya adalah store.
        |
        */


        /*
        | Menyimpan draft stock opname store.
        */

        Route::post(
            'stores/{store}/opname/draft',
            [StoreController::class, 'saveOpnameDraft']
        )->name('stores.opname.draft');


        /*
        | Menyelesaikan stock opname store.
        |
        | {adjustment?} optional karena dapat digunakan untuk
        | membuat/menyelesaikan opname.
        */

        Route::post(
            'stores/{store}/opname/complete/{adjustment?}',
            [StoreController::class, 'completeOpname']
        )->name('stores.opname.complete');


        /*
        | Membatalkan stock opname store.
        */

        Route::post(
            'stores/{store}/opname/cancel/{adjustment}',
            [StoreController::class, 'cancelOpname']
        )->name('stores.opname.cancel');


        /*
        | Mengambil item dari stock opname store tertentu.
        |
        | Biasanya digunakan oleh AJAX.
        */

        Route::get(
            'stores/opname/{adjustment}/items',
            [StoreController::class, 'getOpnameItems']
        )->name('stores.opname.items');


        /*
        |--------------------------------------------------------------------------
        | POINT OF SALE (POS)
        |--------------------------------------------------------------------------
        |
        | Bagian utama kasir.
        |
        | Alur:
        |
        |   Kasir membuka POS
        |       ↓
        |   Memilih produk
        |       ↓
        |   Menentukan quantity
        |       ↓
        |   Sistem menghitung subtotal
        |       ↓
        |   Discount diterapkan jika memenuhi aturan
        |       ↓
        |   Pembayaran
        |       ↓
        |   Sale dibuat
        |       ↓
        |   Stok store berkurang
        |
        */


        /*
        | Menampilkan halaman POS.
        |
        | GET /sales/pos
        |
        | Menggunakan method create() karena secara konsep
        | merupakan halaman untuk membuat transaksi sale baru.
        */

        Route::get(
            'sales/pos',
            [SaleController::class, 'create']
        )->name('sales.create');


        /*
        | Menyimpan transaksi penjualan.
        |
        | POST /sales
        |
        | Controller akan melakukan proses transaksi seperti:
        |
        | - validasi produk
        | - validasi stok
        | - perhitungan subtotal
        | - discount
        | - total
        | - pembayaran
        | - kembalian
        | - penyimpanan sale
        | - pengurangan stok store
        |
        */

        Route::post(
            'sales',
            [SaleController::class, 'store']
        )->name('sales.store');


        /*
        |--------------------------------------------------------------------------
        | THERMAL RECEIPT
        |--------------------------------------------------------------------------
        |
        | Menampilkan/cetak struk transaksi.
        |
        | GET /sales/{sale}/receipt
        |
        | {sale} menggunakan route model binding.
        |
        | Endpoint ini dapat menghasilkan halaman khusus
        | receipt/struk yang kemudian dicetak menggunakan
        | printer thermal.
        |
        */

        Route::get(
            'sales/{sale}/receipt',
            [SaleController::class, 'printReceipt']
        )->name('sales.receipt');


        /*
        |--------------------------------------------------------------------------
        | SALES REPORT
        |--------------------------------------------------------------------------
        |
        | Menampilkan daftar/laporan transaksi penjualan.
        |
        | GET /sales
        |
        */

        Route::get(
            'sales',
            [SaleController::class, 'index']
        )->name('sales.index');


        /*
        | Mengambil item dari transaksi sale tertentu.
        |
        | GET /sales/{sale}/items
        |
        | Biasanya digunakan untuk:
        |
        | - melihat detail transaksi
        | - modal detail penjualan
        | - AJAX
        |
        */

        Route::get(
            'sales/{sale}/items',
            [SaleController::class, 'getItems']
        );


        /*
        |--------------------------------------------------------------------------
        | POS PRODUCT API / AJAX HELPER
        |--------------------------------------------------------------------------
        |
        | Mengambil produk + informasi stok/diskon aktif
        | berdasarkan store yang dipilih.
        |
        | GET /sales/store-products/{store}
        |
        | Endpoint ini kemungkinan besar dipanggil menggunakan
        | JavaScript pada halaman POS.
        |
        | Contoh:
        |
        |   Kasir memilih Store A
        |          ↓
        |   Request endpoint
        |          ↓
        |   Laravel mengambil:
        |      - produk
        |      - stok store
        |      - harga
        |      - discount aktif
        |          ↓
        |   Data dikirim kembali ke browser
        |
        */

        Route::get(
            'sales/store-products/{store}',
            [SaleController::class, 'getStoreProducts']
        );
    });
});