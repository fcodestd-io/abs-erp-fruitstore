<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Master
        |--------------------------------------------------------------------------
        */

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Reference cost only, not historical transaction HPP.
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);

            $table->foreignId('store_unit_id')
                ->constrained('units');

            $table->foreignId('warehouse_unit_id')
                ->constrained('units');

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        // Schema::create('users', function (Blueprint $table) {
        //     $table->id();

        //     $table->string('username')->unique();
        //     $table->string('password');

        //     $table->enum('role', [
        //         'owner',
        //         'admin',
        //         'warehouse_supervisor',
        //         'cashier',
        //     ]);

        //     $table->foreignId('warehouse_id')
        //         ->nullable()
        //         ->constrained('warehouses')
        //         ->nullOnDelete();

        //     $table->foreignId('store_id')
        //         ->nullable()
        //         ->constrained('stores')
        //         ->nullOnDelete();

        //     $table->rememberToken();
        //     $table->timestamps();
        // });

        /*
        |--------------------------------------------------------------------------
        | Current Stock
        |--------------------------------------------------------------------------
        */

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->decimal('stock', 15, 1)->default(0);

            $table->timestamps();

            $table->unique([
                'warehouse_id',
                'product_id',
            ]);
        });

        Schema::create('store_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->decimal('stock', 15, 1)->default(0);

            $table->timestamps();

            $table->unique([
                'store_id',
                'product_id',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Purchasing
        |--------------------------------------------------------------------------
        */

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('po_code')->unique();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses');

            $table->foreignId('operator_id')
                ->constrained('users');

            $table->enum('status', [
                'pending',
                'completed',
                'canceled',
            ])->default('pending');

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->text('note')->nullable();

            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products');

            $table->decimal('qty_ordered', 15, 3);
            $table->decimal('qty_actual', 15, 3)->default(0);

            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Shipment / Logistic
        |--------------------------------------------------------------------------
        */

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses');

            $table->foreignId('store_id')
                ->constrained('stores');

            $table->foreignId('warehouse_supervisor_id')
                ->constrained('users');

            $table->foreignId('cashier_id')
                ->constrained('users');

            $table->enum('status', [
                'pending',
                'completed',
                'canceled',
            ])->default('pending');

            $table->text('note')->nullable();

            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products');

            // Warehouse unit
            $table->decimal('qty_sent', 15, 3);

            // Store unit
            $table->decimal('qty_received', 15, 3);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | POS / Sales
        |--------------------------------------------------------------------------
        */

        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores');

            $table->string('code')->unique();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);

            $table->enum('payment_method', [
                'cash',
                'qris',
                'transfer',
            ]);

            $table->text('note')->nullable();

            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products');

            $table->decimal('qty', 15, 3);
            $table->decimal('price', 15, 2);

            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Stock Adjustment
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->foreignId('operator_id')
                ->constrained('users');

            $table->enum('status', [
                'pending',
                'completed',
                'canceled',
            ])->default('pending');

            $table->text('note')->nullable();

            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_adjustment_id')
                ->constrained('stock_adjustments')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products');

            $table->decimal('system_stock', 15, 3);
            $table->decimal('actual_stock', 15, 3);
            $table->decimal('adjustment', 15, 3);

            $table->text('note')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Discount
        |--------------------------------------------------------------------------
        */

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->timestamp('start_at');
            $table->timestamp('end_at');

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::create('discount_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discount_id')
                ->constrained('discounts')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products');

            $table->decimal('discount_percentage', 5, 2);
            $table->decimal('minimum_quantity', 15, 3)->default(1);

            $table->timestamps();

            $table->unique([
                'discount_id',
                'product_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_items');
        Schema::dropIfExists('discounts');

        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');

        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');

        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');

        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');

        Schema::dropIfExists('store_stocks');
        Schema::dropIfExists('warehouse_stocks');

        Schema::dropIfExists('users');

        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('units');
    }
};