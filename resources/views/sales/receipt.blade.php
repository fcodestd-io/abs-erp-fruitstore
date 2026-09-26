<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Belanja - {{ $sale->code }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            width: 58mm;
            margin: 0 auto;
            padding: 8px;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-bottom: 1px dashed #000;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .no-print {
            display: flex;
            gap: 4px;
            margin-bottom: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="padding: 5px 10px; font-size: 11px; cursor: pointer;">Cetak</button>
        <button onclick="window.close()" style="padding: 5px 10px; font-size: 11px; cursor: pointer;">Tutup</button>
    </div>

    <!-- Header Toko -->
    <div class="text-center">
        <span class="bold" style="font-size: 13px;">ALAM BUAH SEGAR</span><br>
        <span>{{ $sale->store->name ?? 'Toko Cabang' }}</span><br>
        <span style="font-size: 9px;">{{ $sale->store->address ?? '-' }}</span>
    </div>

    <div class="line"></div>

    <!-- Meta Transaksi -->
    <div>
        <span>No : {{ $sale->code }}</span><br>
        <span>Tgl : {{ $sale->created_at->format('d/m/Y H:i') }}</span>
    </div>

    <div class="line"></div>

    <!-- Rincian Barang -->
    <table>
        @foreach ($sale->items as $item)
            <tr>
                <td colspan="2" class="bold">{{ $item->product->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>
                    {{ round($item->qty, 1) }} {{ $item->product->storeUnit->name ?? 'Kg' }} x
                    {{ number_format($item->price, 0, ',', '.') }}
                    @if ($item->discount > 0)
                        <br><small>(Disc: -{{ number_format($item->discount, 0, ',', '.') }})</small>
                    @endif
                </td>
                <td class="text-right bold">
                    {{ number_format($item->subtotal, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <!-- Total & Pembayaran -->
    <table>
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">{{ number_format($sale->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if ($sale->discount > 0)
            <tr>
                <td>Diskon:</td>
                <td class="text-right">-{{ number_format($sale->discount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="bold">
            <td>TOTAL:</td>
            <td class="text-right">{{ number_format($sale->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bayar ({{ strtoupper($sale->payment_method) }}):</td>
            <td class="text-right">{{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali:</td>
            <td class="text-right">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="text-center">
        <p style="margin: 4px 0;">Terima Kasih Atas Kunjungan Anda!</p>
        <small>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</small>
    </div>

</body>

</html>
