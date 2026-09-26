<x-dashboard-layout title="Dashboard Eksekutif" active="dashboard">
    <div class="space-y-6">

        <!-- Top Header & Filter Bulan + Tahun -->
        <div
            class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-extrabold text-abs-green-600 uppercase tracking-widest block">DASHBOARD
                    EKSEKUTIF</span>
                <h1 class="text-lg font-black text-slate-900">Selamat Datang, {{ auth()->user()->username }}! </h1>
                <p class="text-xs text-slate-400">Ringkasan performa penjualan, pengeluaran PO, dan aktivitas distribusi
                    logistik.</p>
            </div>

            <!-- Form Filter Bulan & Tahun -->
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                <select name="month"
                    class="rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                        </option>
                    @endfor
                </select>

                <select name="year"
                    class="rounded-xl border-0 bg-slate-50 py-2 px-3 text-xs font-bold text-slate-800 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-abs-green-600">
                    @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                            {{ $y }}</option>
                    @endfor
                </select>

                <button type="submit"
                    class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition active:scale-95 shadow-md">
                    Filter
                </button>
            </form>
        </div>

        <!-- Metric KPI Cards (Warna-warni) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                class="bg-gradient-to-br from-emerald-500 to-teal-700 text-white p-5 rounded-3xl shadow-lg shadow-emerald-500/10 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-100 block">Total Omzet
                        Penjualan</span>
                    <h3 class="text-xl font-black mt-1">Rp {{ number_format($totalOmset, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl"><i data-lucide="trending-up"
                        class="w-6 h-6 text-white"></i></div>
            </div>

            <div
                class="bg-gradient-to-br from-rose-500 to-red-700 text-white p-5 rounded-3xl shadow-lg shadow-rose-500/10 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-100 block">Pengeluaran PO
                        (Gudang)</span>
                    <h3 class="text-xl font-black mt-1">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl"><i data-lucide="shopping-bag"
                        class="w-6 h-6 text-white"></i></div>
            </div>

            <div
                class="bg-gradient-to-br from-blue-500 to-indigo-700 text-white p-5 rounded-3xl shadow-lg shadow-blue-500/10 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-100 block">Total Transaksi
                        Kasir</span>
                    <h3 class="text-xl font-black mt-1">{{ number_format($totalTransaksi, 0, ',', '.') }} Struk</h3>
                </div>
                <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl"><i data-lucide="receipt"
                        class="w-6 h-6 text-white"></i></div>
            </div>

            <div
                class="bg-gradient-to-br from-amber-500 to-orange-600 text-white p-5 rounded-3xl shadow-lg shadow-amber-500/10 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-100 block">PO Menunggu
                        Datang</span>
                    <h3 class="text-xl font-black mt-1">{{ $pendingPO }} Transaksi</h3>
                </div>
                <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl"><i data-lucide="clock"
                        class="w-6 h-6 text-white"></i></div>
            </div>
        </div>

        <!-- 1. FULL WIDTH CHART: Line Chart Penjualan Tanggal 1 sampai Akhir Bulan -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Laporan Penjualan Harian (Tanggal 1 - Akhir Bulan)
                    </h3>
                    <p class="text-xs text-slate-400">Tren akumulasi omzet harian seluruh toko cabang</p>
                </div>
            </div>
            <div class="h-72 w-full">
                <canvas id="fullWidthSalesChart"></canvas>
            </div>
        </div>

        <!-- 2. GRID 2 CHART: Donut Chart (Omzet Per Toko) & Pie Chart (Pengeluaran Per Gudang) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Donut Chart -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Omzet per Toko Cabang</h3>
                    <p class="text-xs text-slate-400">Proporsi kontribusi pendapatan masing-masing toko</p>
                </div>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="storeDonutChart"></canvas>
                </div>
            </div>

            <!-- Pie Chart -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Pengeluaran Uang per Gudang</h3>
                    <p class="text-xs text-slate-400">Total akumulasi modal pembelian PO yang diselesaikan</p>
                </div>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="warehouseExpensePieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. TIGA TABEL UTAMA: PO Terakhir, Pengiriman Terakhir, dan Penjualan Terakhir -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- TABEL 1: PO Terakhir (Purchase Order) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-slate-900">PO Terakhir</h3>
                        <p class="text-[10px] text-slate-400">Pembelian ke supplier</p>
                    </div>
                    <a href="{{ route('purchase-orders.index') }}"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-xl transition">
                        Lihat Semua
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="bg-slate-50 text-slate-500 uppercase text-[9px] font-extrabold border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-2">Kode / Supplier</th>
                                <th class="py-2.5 px-2">Total Nilai</th>
                                <th class="py-2.5 px-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($recentPOs as $po)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-2">
                                        <span
                                            class="font-bold text-slate-900 block truncate max-w-[100px]">{{ $po->po_code }}</span>
                                        <span
                                            class="text-[10px] text-slate-400 truncate block max-w-[100px]">{{ $po->supplier->name ?? 'Non-Supplier' }}</span>
                                    </td>
                                    <td class="py-2.5 px-2 font-bold text-slate-800">
                                        Rp {{ number_format($po->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-2">
                                        @if ($po->status === 'pending')
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-black text-[9px]">PENDING</span>
                                        @elseif($po->status === 'completed')
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-black text-[9px]">SELESAI</span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-red-100 text-red-800 font-black text-[9px]">BATAL</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-400">Belum ada PO.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABEL 2: Pengiriman Terakhir (Shipments) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Pengiriman Terakhir</h3>
                        <p class="text-[10px] text-slate-400">Gudang ke Toko Cabang</p>
                    </div>
                    <a href="{{ route('shipments.index') }}"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-xl transition">
                        Lihat Semua
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="bg-slate-50 text-slate-500 uppercase text-[9px] font-extrabold border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-2">Kode / Asal</th>
                                <th class="py-2.5 px-2">Toko Tujuan</th>
                                <th class="py-2.5 px-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($recentShipments as $shp)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-2">
                                        <span
                                            class="font-bold text-slate-900 block truncate max-w-[100px]">{{ $shp->code }}</span>
                                        <span
                                            class="text-[10px] text-slate-400 truncate block max-w-[100px]">{{ $shp->warehouse->name ?? '-' }}</span>
                                    </td>
                                    <td class="py-2.5 px-2 font-medium text-slate-800 truncate max-w-[100px]">
                                        {{ $shp->store->name ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-2">
                                        @if ($shp->status === 'pending')
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-black text-[9px]">PENDING</span>
                                        @elseif($shp->status === 'completed')
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-black text-[9px]">SELESAI</span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-red-100 text-red-800 font-black text-[9px]">BATAL</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-400">Belum ada pengiriman.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABEL 3: Penjualan Terakhir (Sales) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Penjualan Terakhir</h3>
                        <p class="text-[10px] text-slate-400">Transaksi Kasir Eceran</p>
                    </div>
                    <a href="{{ route('sales.index') }}"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-xl transition">
                        Lihat Semua
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="bg-slate-50 text-slate-500 uppercase text-[9px] font-extrabold border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-2">Invoice / Toko</th>
                                <th class="py-2.5 px-2">Metode</th>
                                <th class="py-2.5 px-2">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($recentSales as $sale)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-2">
                                        <span
                                            class="font-bold text-slate-900 block truncate max-w-[100px]">{{ $sale->code }}</span>
                                        <span
                                            class="text-[10px] text-slate-400 truncate block max-w-[100px]">{{ $sale->store->name ?? '-' }}</span>
                                    </td>
                                    <td class="py-2.5 px-2 font-extrabold text-slate-700 uppercase text-[9px]">
                                        {{ $sale->payment_method }}
                                    </td>
                                    <td class="py-2.5 px-2 font-black text-emerald-700">
                                        Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-400">Belum ada penjualan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- INSIALISASI CHART.JS -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartColors = [
                '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899',
                '#06b6d4', '#f97316', '#14b8a6', '#6366f1', '#84cc16'
            ];

            // 1. Line Chart Full Width
            const ctx1 = document.getElementById('fullWidthSalesChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: {!! json_encode($lineLabels) !!},
                    datasets: [{
                        label: 'Penjualan (Rp)',
                        data: {!! json_encode($lineSalesData) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#047857'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 10
                                },
                                callback: (v) => 'Rp ' + v.toLocaleString('id-ID')
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        }
                    }
                }
            });

            // 2. Donut Chart
            const ctx2 = document.getElementById('storeDonutChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($donutLabels) !!},
                    datasets: [{
                        data: {!! json_encode($donutData) !!},
                        backgroundColor: chartColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });

            // 3. Pie Chart
            const ctx3 = document.getElementById('warehouseExpensePieChart').getContext('2d');
            new Chart(ctx3, {
                type: 'pie',
                data: {
                    labels: {!! json_encode($pieLabels) !!},
                    datasets: [{
                        data: {!! json_encode($pieExpenseData) !!},
                        backgroundColor: ['#f43f5e', '#fb923c', '#a855f7', '#0284c7', '#22c55e'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-dashboard-layout>
