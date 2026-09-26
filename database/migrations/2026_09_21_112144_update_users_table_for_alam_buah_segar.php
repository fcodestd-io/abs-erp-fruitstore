<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'email',
                'email_verified_at',
            ]);

            $table->string('username')->unique()->after('id');

            $table->enum('role', [
                'owner',
                'admin',
                'warehouse_supervisor',
                'cashier',
            ])->after('password');

            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('role')
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->foreignId('store_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('stores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['store_id']);

            $table->dropColumn([
                'username',
                'role',
                'warehouse_id',
                'store_id',
            ]);

            $table->string('name')->after('id');
            $table->string('email')->unique()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }
};