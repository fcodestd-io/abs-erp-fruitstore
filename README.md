# 🍎 ABS ERP — Fruit Store

**ABS ERP (Alam Buah Segar)** adalah aplikasi **Enterprise Resource Planning (ERP) internal** untuk membantu operasional toko buah, mulai dari pengelolaan master data, persediaan gudang dan toko, pembelian, distribusi barang, stock opname, hingga transaksi penjualan melalui POS.

Project ini dibangun sebagai aplikasi web menggunakan **Laravel** dengan pendekatan server-side rendering dan antarmuka berbasis **Tailwind CSS**.

---

## ✨ Features

### 📦 Product Management

* Manajemen produk buah
* Harga pokok dan harga jual
* Satuan toko
* Satuan gudang
* Pencarian produk
* Product list dapat diakses seluruh role yang sudah login
* CRUD produk dibatasi untuk Owner dan Admin

### 🏪 Store Management

* Manajemen cabang toko
* Pengelolaan stok produk per toko
* Penambahan produk ke toko
* Stock opname toko
* Riwayat sesi stock opname
* Pencatatan operator opname

### 🏭 Warehouse Management

* Manajemen gudang
* Pengelolaan stok produk per gudang
* Penambahan produk ke gudang
* Stock opname gudang
* Draft opname
* Penyelesaian opname
* Pembatalan sesi opname
* Riwayat stock adjustment

### 🛒 Purchase Order

* Membuat Purchase Order
* Pemilihan supplier
* Pemilihan gudang tujuan
* Detail item pembelian
* Status Purchase Order
* Penyelesaian Purchase Order
* Pembatalan Purchase Order
* Perubahan stok berdasarkan proses penerimaan

### 🚚 Shipment / Distribusi

Mendukung alur distribusi barang:

```text
Warehouse
    │
    │  Shipment
    ▼
Store
```

Fitur:

* Membuat surat jalan / shipment
* Memilih gudang asal
* Memilih toko tujuan
* Mengirim produk berdasarkan stok gudang
* Stok gudang otomatis berkurang ketika shipment dibuat
* Shipment memiliki status `pending`, `completed`, atau `canceled`
* Toko dapat menginput jumlah barang yang diterima
* Stok toko otomatis bertambah setelah penerimaan
* Pembatalan shipment mengembalikan stok ke gudang
* Pencatatan warehouse supervisor
* Pencatatan cashier yang menerima barang

### 🧾 POS / Sales

Point of Sale untuk transaksi penjualan toko.

Fitur:

* POS kasir
* Pemilihan toko
* Keranjang penjualan
* Validasi stok
* Pengurangan stok otomatis
* Perhitungan subtotal
* Diskon transaksi
* Total pembayaran
* Pembayaran:

  * Cash
  * QRIS
  * Transfer
* Perhitungan kembalian untuk pembayaran cash
* Nomor invoice otomatis
* Pencatatan cashier
* Cetak struk thermal
* Riwayat transaksi penjualan
* Filter berdasarkan tanggal dan toko
* Ringkasan omzet
* Total diskon
* Jumlah transaksi
* Rata-rata transaksi
* Breakdown omzet berdasarkan metode pembayaran

### 🏷️ Discount Management

Owner dapat membuat program diskon berdasarkan:

* Nama program
* Toko
* Periode mulai
* Periode berakhir
* Produk
* Persentase diskon
* Minimum quantity

Program diskon aktif otomatis digunakan oleh POS ketika transaksi dilakukan pada toko dan periode yang sesuai.

Discount juga dapat diakhiri secara manual sebelum tanggal berakhir.

### 👥 User & Role Management

Sistem menggunakan role-based access control.

Role yang tersedia:

| Role                   | Keterangan                                       |
| ---------------------- | ------------------------------------------------ |
| `owner`                | Akses penuh terhadap sistem dan pengelolaan user |
| `admin`                | Administrasi operasional                         |
| `warehouse_supervisor` | Operasional gudang dan distribusi                |
| `cashier`              | Operasional toko dan POS                         |

User dapat dikaitkan dengan:

* Warehouse
* Store

Pembatasan akses diterapkan melalui middleware role dan filtering pada controller.

Contohnya, warehouse supervisor hanya melihat shipment dari gudang yang ditugaskan, sedangkan cashier hanya melihat shipment yang ditujukan ke toko tempatnya bertugas.

---

## 🔄 Inventory Flow

### Purchase

```text
Supplier
   │
   ▼
Purchase Order
   │
   ▼
Warehouse
   │
   ▼
Warehouse Stock
```

### Distribution

```text
Warehouse Stock
      │
      │ Shipment
      ▼
    Store
      │
      ▼
 Store Stock
```

### Sales

```text
Store Stock
     │
     │ POS
     ▼
   Sale
     │
     ▼
Stock berkurang
```

Shipment memotong stok gudang ketika dibuat, kemudian menambahkan stok toko berdasarkan jumlah yang diterima ketika shipment diselesaikan.

---

## 📊 Stock Opname

Stock opname digunakan untuk mencocokkan stok sistem dengan stok fisik.

Alurnya:

```text
System Stock
     │
     ▼
Physical Counting
     │
     ▼
Actual Stock
     │
     ▼
Stock Adjustment
     │
     ▼
Updated Stock
```

Setiap item opname menyimpan:

* System stock
* Actual stock
* Adjustment
* Item note

Sesi opname juga memiliki:

* Operator
* Code
* Note
* Status
* Created at
* Completed at
* Canceled at

Opname dapat disimpan sebagai draft sebelum diselesaikan.

---

## 🔐 Access Control

Secara umum pembagian akses aplikasi:

### Owner

* User management
* Discount management
* Product management
* Supplier management
* Warehouse management
* Store management
* Purchase Order
* Shipment
* POS
* Sales report

### Admin

* Product management
* Supplier management
* Warehouse management
* Store management
* Purchase Order
* Shipment
* POS
* Sales report

### Warehouse Supervisor

* Warehouse
* Warehouse stock
* Stock opname
* Purchase Order
* Shipment

### Cashier

* Store
* Store stock
* Stock opname toko
* POS
* Sales
* Penerimaan shipment untuk toko yang ditugaskan

Seluruh route aplikasi berada di balik authentication, sedangkan fitur tertentu dibatasi menggunakan middleware role.

---

## 🛠️ Tech Stack

### Backend

* PHP 8.3+
* Laravel 13
* Laravel Eloquent ORM
* Laravel Blade

### Frontend

* Blade
* Tailwind CSS 4
* Alpine.js
* Vite

### Development Tools

* Composer
* NPM
* Vite
* PHPUnit
* Laravel Pint

Konfigurasi package project menunjukkan PHP `^8.3`, Laravel `^13.17`, Tailwind CSS `^4.0`, dan Vite `^8.0`.

---

## 🚀 Installation

### 1. Clone Repository

```bash
git clone https://github.com/fcodestd-io/abs-erp-fruitstore.git

cd abs-erp-fruitstore
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Setup Environment

Copy `.env.example` menjadi `.env`.

```bash
cp .env.example .env
```

Kemudian konfigurasi database pada `.env`.

Contoh:

```env
APP_NAME="ABS ERP"
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=abs_erp
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migration

```bash
php artisan migrate
```

### 6. Install Frontend Dependencies

```bash
npm install
```

### 7. Run Development Server

Jalankan Laravel:

```bash
php artisan serve
```

Kemudian jalankan Vite pada terminal lain:

```bash
npm run dev
```

Atau gunakan script development yang tersedia pada project:

```bash
composer run dev
```

---

## 🗂️ Project Structure

Struktur utama Laravel:

```text
abs-erp-fruitstore/
│
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │
│   └── Models/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│
├── routes/
│   └── web.php
│
├── public/
├── storage/
├── tests/
│
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

---

## 🧩 Main Modules

```text
ABS ERP
│
├── Authentication
│
├── Dashboard
│
├── User Management
│
├── Master Data
│   ├── Products
│   ├── Units
│   ├── Suppliers
│   ├── Stores
│   └── Warehouses
│
├── Inventory
│   ├── Warehouse Stock
│   ├── Store Stock
│   └── Stock Opname
│
├── Procurement
│   └── Purchase Order
│
├── Distribution
│   └── Shipment
│
├── Sales
│   ├── POS
│   ├── Sales History
│   └── Receipt
│
└── Promotion
    └── Discount
```

---

## 🔗 Application Flow

Secara keseluruhan, sistem dirancang dengan alur operasional:

```text
                ┌─────────────┐
                │   Supplier  │
                └──────┬──────┘
                       │
                       ▼
                ┌─────────────┐
                │ Purchase PO │
                └──────┬──────┘
                       │
                       ▼
                ┌─────────────┐
                │  Warehouse  │
                │    Stock    │
                └──────┬──────┘
                       │
                       │ Shipment
                       ▼
                ┌─────────────┐
                │    Store    │
                │    Stock    │
                └──────┬──────┘
                       │
                       │ POS
                       ▼
                ┌─────────────┐
                │    Sales    │
                └─────────────┘
```

Stock opname dapat dilakukan pada warehouse maupun store untuk melakukan penyesuaian berdasarkan kondisi stok fisik.

---

## 🧪 Testing

Menjalankan test suite:

```bash
php artisan test
```

Atau:

```bash
composer run test
```

---

## 🎯 Project Purpose

ABS ERP dibuat sebagai aplikasi operasional untuk mensimulasikan dan mengelola proses bisnis toko buah dengan beberapa titik persediaan.

Fokus utama project:

* Inventory management
* Procurement
* Warehouse management
* Store management
* Internal distribution
* Stock reconciliation
* Point of Sale
* Sales reporting
* Role-based access control

Project ini juga menjadi implementasi pembelajaran mengenai bagaimana proses bisnis nyata diterjemahkan menjadi **database, business logic, transaction flow, authorization, dan user interface** dalam sebuah aplikasi Laravel.

---

## 📌 Project Status

**Active Development**

Beberapa bagian sistem masih dapat dikembangkan lebih lanjut, terutama:

* Automated testing
* Reporting yang lebih lengkap
* Audit log
* Granular permission
* Financial/accounting module
* Dashboard analytics
* Deployment production
* Backup & recovery strategy

---

## 👨‍💻 Author

**FCodeStd**

GitHub:

https://github.com/fcodestd-io

Repository:

https://github.com/fcodestd-io/abs-erp-fruitstore

---

## 📄 License

This project is currently intended as a personal development / portfolio project.

The Laravel framework used by this project is open-source software licensed under the MIT license.
