<x-dashboard-layout title="Dashboard Eksekutif" active="dashboard">
    <div class="space-y-6">

        <!-- Welcome Banner -->
        <div
            class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-6 rounded-3xl shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest block mb-1">DASHBOARD
                    EKSEKUTIF</span>
                <h1 class="text-xl font-black">Selamat Datang, {{ auth()->user()->username }}!</h1>
                <p class="text-xs text-slate-400 mt-1">Pantau seluruh pergerakan omzet, transaksi toko, dan stok gudang
                    secara real-time.</p>
            </div>
            <a href="{{ route('sales.index') }}"
                class="px-4 py-2.5 bg-abs-green-600 hover:bg-abs-green-700 text-white font-bold text-xs rounded-xl shadow-lg transition active:scale-95">
                Laporan Penjualan Lengkap
            </a>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                    <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Omzet Hari
                        Ini</span>
                    <h3 class="text-lg font-black text-slate-900">Rp {{ number_format($totalOmsetToday, 0, ',', '.') }}
                    </h3>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Omzet Bulan
                        Ini</span>
                    <h3 class="text-lg font-black text-slate-900">Rp {{ number_format($totalOmsetMonth, 0, ',', '.') }}
                    </h3>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                    <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">PO Menunggu</span>
                    <h3 class="text-lg font-black text-amber-600">{{ $pendingPO }} Transaksi</h3>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl">
                    <i data-lucide="truck" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Shipment Dalam
                        Jalan</span>
                    <h3 class="text-lg font-black text-purple-600">{{ $pendingShipment }} Pengiriman</h3>
                </div>
            </div>
        </div>

        <!-- CHART.JS SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart 1: Line Chart Tren Penjualan 7 Hari -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Tren Penjualan 7 Hari Terakhir</h3>
                        <p class="text-[10px] text-slate-400">Total omzet gabungan seluruh cabang toko</p>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Bar Chart Perbandingan Toko Bulan Ini -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Omzet per Toko Cabang Bulan Ini</h3>
                        <p class="text-[10px] text-slate-400">Perbandingan performa penjualan antar cabang</p>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="storeSalesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- BOTTOM GRID (Peringatan Stok Toko Menipis & Transaksi Terakhir) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Peringatan Stok Menipis -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3
                        class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5 text-red-600">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        <span>Peringatan Stok Toko Menipis</span>
                    </h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($lowStoreStocks as $st)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $st->product->name ?? '-' }}</span>
                                <span
                                    class="text-[10px] text-slate-400 font-medium">{{ $st->store->name ?? '-' }}</span>
                            </div>
                            <span class="font-black text-red-600 px-2 py-1 bg-red-50 rounded-lg">
                                {{ round($st->stock, 1) }} {{ $st->product->storeUnit->name ?? 'Kg' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">Seluruh stok eceran toko dalam batas aman.
                        </p>
                    @endforelse
                </div>
            </div>

            <!-- Transaksi Penjualan Terakhir -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">5 Transaksi Terakhir</h3>
                    <a href="{{ route('sales.index') }}"
                        class="text-[11px] font-bold text-abs-green-600 hover:underline">Lihat Semua</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentSales as $sale)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $sale->code }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $sale->store->name ?? '-' }} |
                                    {{ $sale->created_at->format('H:i') }} WIB</span>
                            </div>
                            <span class="font-black text-abs-green-700">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">Belum ada transaksi hari ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- SCRIPT INITIALIZE CHART.JS -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Line Chart Tren Sales
            const ctx1 = document.getElementById('salesTrendChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartDates) !!},
                    datasets: [{
                        label: 'Omzet (Rp)',
                        data: {!! json_encode($chartSalesData) !!},
                        borderColor: '#15803d',
                        backgroundColor: 'rgba(21, 128, 61, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#15803d'
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
                                callback: (value) => 'Rp ' + value.toLocaleString('id-ID')
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });

            // 2. Bar Chart Sales per Store
            const ctx2 = document.getElementById('storeSalesChart').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($storeNames) !!},
                    datasets: [{
                        label: 'Omzet Bulan Ini',
                        data: {!! json_encode($storeSalesData) !!},
                        backgroundColor: '#22c55e',
                        borderRadius: 8
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
                                callback: (value) => 'Rp ' + value.toLocaleString('id-ID')
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-dashboard-layout>
