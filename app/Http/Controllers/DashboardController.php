<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role;

        if (in_array($role, ['owner', 'admin'])) {
            return $this->getExecutiveDashboard();
        } elseif ($role === 'cashier') {
            return $this->getCashierDashboard($user);
        } else {
            return $this->getWarehouseSupervisorDashboard($user);
        }
    }

    // --------------------------------------------------------------------------
    // A. DASHBOARD EKSEKUTIF (OWNER & ADMIN)
    // --------------------------------------------------------------------------
    private function getExecutiveDashboard(?Request $request = null)
    {
        $request = $request ?? request();

        // Filter Bulan & Tahun (Default: Bulan & Tahun Ini)
        $selectedMonth = $request->query('month', date('n'));
        $selectedYear = $request->query('year', date('Y'));

        $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // 1. Chart 1 (Full Width Line Chart): Penjualan Harian Tanggal 1 s/d Akhir Bulan
        $lineLabels = [];
        $lineSalesData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = Carbon::createFromDate($selectedYear, $selectedMonth, $d)->format('Y-m-d');
            $lineLabels[] = $d.' '.$startDate->translatedFormat('M');
            $lineSalesData[] = (float) Sale::whereDate('created_at', $dateStr)->sum('total_amount');
        }

        // 2. Chart 2 (Donut Chart): Omzet per Toko Cabang pada Periode Terpilih
        $stores = Store::all();
        $donutLabels = [];
        $donutData = [];
        foreach ($stores as $store) {
            $donutLabels[] = $store->name;
            $donutData[] = (float) Sale::where('store_id', $store->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount');
        }

        // 3. Chart 3 (Pie Chart): Pengeluaran Uang (Total PO Selesai) per Gudang
        $warehouses = Warehouse::all();
        $pieLabels = [];
        $pieExpenseData = [];
        foreach ($warehouses as $wh) {
            $pieLabels[] = $wh->name;
            $pieExpenseData[] = (float) PurchaseOrder::where('warehouse_id', $wh->id)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount');
        }

        // KPI Summary
        $totalOmset = Sale::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
        $totalPengeluaran = PurchaseOrder::where('status', 'completed')->whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
        $totalTransaksi = Sale::whereBetween('created_at', [$startDate, $endDate])->count();
        $pendingPO = PurchaseOrder::where('status', 'pending')->count();

        // 3 Tabel Ringkasan Terakhir
        $recentPOs = PurchaseOrder::with(['supplier', 'warehouse'])->latest()->limit(5)->get();
        $recentShipments = Shipment::with(['warehouse', 'store'])->latest()->limit(5)->get();
        $recentSales = Sale::with('store')->latest()->limit(5)->get();

        return view('dashboard.executive', compact(
            'selectedMonth', 'selectedYear', 'totalOmset', 'totalPengeluaran', 'totalTransaksi', 'pendingPO',
            'lineLabels', 'lineSalesData', 'donutLabels', 'donutData', 'pieLabels', 'pieExpenseData',
            'recentPOs', 'recentShipments', 'recentSales'
        ));
    }

    // --------------------------------------------------------------------------
    // B. DASHBOARD KASIR (CASHIER)
    // --------------------------------------------------------------------------
    private function getCashierDashboard($user)
    {
        $storeId = $user->store_id;
        $store = Store::find($storeId);

        $today = now()->format('Y-m-d');
        $thisMonth = now()->month;

        $omsetToday = Sale::where('store_id', $storeId)->whereDate('created_at', $today)->sum('total_amount');
        $transCountToday = Sale::where('store_id', $storeId)->whereDate('created_at', $today)->count();
        $omsetMonth = Sale::where('store_id', $storeId)->whereMonth('created_at', $thisMonth)->sum('total_amount');

        // Incoming Shipments (Menunggu Diterima Kasir)
        $incomingShipments = Shipment::with('warehouse')
            ->where('store_id', $storeId)
            ->where('status', 'pending')
            ->get();

        // 5 Transaksi Terakhir Toko Ini
        $recentSales = Sale::where('store_id', $storeId)->latest()->limit(5)->get();

        // Warning Stok Toko Menipis
        $lowStocks = StoreStock::with('product.storeUnit')
            ->where('store_id', $storeId)
            ->where('stock', '<=', 5)
            ->get();

        return view('dashboard.cashier', compact(
            'store', 'omsetToday', 'transCountToday', 'omsetMonth',
            'incomingShipments', 'recentSales', 'lowStocks'
        ));
    }

    // --------------------------------------------------------------------------
    // C. DASHBOARD SUPERVISOR GUDANG (WAREHOUSE SUPERVISOR)
    // --------------------------------------------------------------------------
    private function getWarehouseSupervisorDashboard($user)
    {
        $warehouseId = $user->warehouse_id;
        $warehouse = Warehouse::find($warehouseId);

        $pendingPO = PurchaseOrder::where('warehouse_id', $warehouseId)->where('status', 'pending')->count();
        $pendingShipment = Shipment::where('warehouse_id', $warehouseId)->where('status', 'pending')->count();
        $totalItemsSKU = WarehouseStock::where('warehouse_id', $warehouseId)->count();

        // Incoming PO
        $recentPOs = PurchaseOrder::with('supplier')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'pending')
            ->latest()->get();

        // Warning Stok Gudang Menipis
        $lowStocks = WarehouseStock::with('product.warehouseUnit')
            ->where('warehouse_id', $warehouseId)
            ->where('stock', '<=', 10)
            ->get();

        return view('dashboard.warehouse', compact(
            'warehouse', 'pendingPO', 'pendingShipment', 'totalItemsSKU',
            'recentPOs', 'lowStocks'
        ));
    }
}
