<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Akses penuh ke seluruh modul dan pengaturan sistem',
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Akses manajerial operasional toko',
        ]);

        $kasirRole = Role::create([
            'name' => 'Kasir',
            'slug' => 'kasir',
            'description' => 'Akses transaksi POS, shift kasir, dan retur penjualan',
        ]);

        $gudangRole = Role::create([
            'name' => 'Gudang',
            'slug' => 'gudang',
            'description' => 'Akses manajemen stok, produk, supplier, dan pembelian',
        ]);

        $ownerRole = Role::create([
            'name' => 'Owner/Manager',
            'slug' => 'owner',
            'description' => 'Akses melihat dashboard eksekutif, audit, dan laporan keuangan',
        ]);

        // 2. Permissions
        $permissions = [
            ['name' => 'Akses POS / Kasir', 'slug' => 'pos.access', 'group' => 'pos'],
            ['name' => 'Kelola Produk', 'slug' => 'products.manage', 'group' => 'inventory'],
            ['name' => 'Lihat Produk', 'slug' => 'products.view', 'group' => 'inventory'],
            ['name' => 'Kelola Stok & Opname', 'slug' => 'stocks.manage', 'group' => 'inventory'],
            ['name' => 'Kelola Pembelian', 'slug' => 'purchases.manage', 'group' => 'purchase'],
            ['name' => 'Kelola Retur', 'slug' => 'returns.manage', 'group' => 'pos'],
            ['name' => 'Lihat Laporan', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name' => 'Lihat Laba Rugi', 'slug' => 'reports.profit', 'group' => 'reports'],
            ['name' => 'Kelola Pengguna', 'slug' => 'users.manage', 'group' => 'settings'],
            ['name' => 'Kelola Pengaturan Toko', 'slug' => 'settings.manage', 'group' => 'settings'],
        ];

        foreach ($permissions as $perm) {
            $createdPerm = Permission::create($perm);

            // Assign permissions
            if (in_array($createdPerm->slug, ['pos.access', 'products.view', 'returns.manage'])) {
                $kasirRole->permissions()->attach($createdPerm->id);
            }
            if (in_array($createdPerm->slug, ['products.manage', 'products.view', 'stocks.manage', 'purchases.manage'])) {
                $gudangRole->permissions()->attach($createdPerm->id);
            }
            if (in_array($createdPerm->slug, ['reports.view', 'reports.profit', 'products.view'])) {
                $ownerRole->permissions()->attach($createdPerm->id);
            }
            if (!in_array($createdPerm->slug, ['settings.manage', 'users.manage'])) {
                $adminRole->permissions()->attach($createdPerm->id);
            }
        }

        // 3. Users
        $superAdmin = User::create([
            'role_id' => $superAdminRole->id,
            'name' => 'Super Administrator',
            'username' => 'admin',
            'email' => 'admin@pos.test',
            'phone' => '081234567890',
            'password' => Hash::make('password'),
            'status' => true,
        ]);

        $kasir = User::create([
            'role_id' => $kasirRole->id,
            'name' => 'Kasir Utama',
            'username' => 'kasir',
            'email' => 'kasir@pos.test',
            'phone' => '081234567891',
            'password' => Hash::make('password'),
            'status' => true,
        ]);

        $gudang = User::create([
            'role_id' => $gudangRole->id,
            'name' => 'Staf Gudang',
            'username' => 'gudang',
            'email' => 'gudang@pos.test',
            'phone' => '081234567892',
            'password' => Hash::make('password'),
            'status' => true,
        ]);

        $owner = User::create([
            'role_id' => $ownerRole->id,
            'name' => 'Owner / Direktur',
            'username' => 'owner',
            'email' => 'owner@pos.test',
            'phone' => '081234567893',
            'password' => Hash::make('password'),
            'status' => true,
        ]);

        // 4. Units
        $pcs = Unit::create(['name' => 'Pcs (Pieces)', 'short_name' => 'pcs']);
        $box = Unit::create(['name' => 'Box', 'short_name' => 'box']);
        $kg  = Unit::create(['name' => 'Kilogram', 'short_name' => 'kg']);
        $btl = Unit::create(['name' => 'Botol', 'short_name' => 'btl']);

        // 5. Categories
        $catMinuman = Category::create([
            'name' => 'Minuman',
            'slug' => 'minuman',
            'description' => 'Aneka minuman segar, kemasan, kopi dan teh',
            'status' => true,
        ]);

        $catMakanan = Category::create([
            'name' => 'Makanan Ringan',
            'slug' => 'makanan-ringan',
            'description' => 'Snack, biskuit, dan makanan siap saji',
            'status' => true,
        ]);

        $catSembako = Category::create([
            'name' => 'Sembako & Kebutuhan Pokok',
            'slug' => 'sembako',
            'description' => 'Beras, minyak goreng, gula, dan bumbu dapur',
            'status' => true,
        ]);

        $catAlatTulis = Category::create([
            'name' => 'Alat Tulis & Kantor',
            'slug' => 'alat-tulis',
            'description' => 'Buku, pulpen, kertas dan perlengkapan kasir',
            'status' => true,
        ]);

        // 6. Suppliers
        $supIndofood = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'PT Indofood Sukses Makmur',
            'phone' => '021-57958822',
            'email' => 'distribusi@indofood.co.id',
            'address' => 'Sudirman Plaza, Indofood Tower, Jakarta',
            'contact_person' => 'Budi Santoso',
            'notes' => 'Supplier aneka mie instan dan bumbu makanan',
            'status' => true,
        ]);

        $supMayora = Supplier::create([
            'code' => 'SUP-002',
            'name' => 'PT Mayora Indah Tbk',
            'phone' => '021-80637000',
            'email' => 'sales@mayora.co.id',
            'address' => 'Jl. Tomang Raya No. 21-23, Jakarta Barat',
            'contact_person' => 'Dewi Anggraini',
            'notes' => 'Supplier aneka biskuit dan kopi',
            'status' => true,
        ]);

        $supWings = Supplier::create([
            'code' => 'SUP-003',
            'name' => 'PT Wings Surya',
            'phone' => '031-5312345',
            'email' => 'contact@wingscorp.com',
            'address' => 'Jl. Tipar Cakung Kav. F 5-7, Jakarta Timur',
            'contact_person' => 'Rudy Hartono',
            'notes' => 'Supplier kebutuhan rumah tangga & minuman',
            'status' => true,
        ]);

        // 7. Customers
        Customer::create([
            'code' => 'CUST-001',
            'name' => 'Umum / Walk-in Customer',
            'phone' => '-',
            'email' => null,
            'address' => 'Toko Kasir',
            'member_level' => 'regular',
            'points' => 0,
            'status' => true,
        ]);

        Customer::create([
            'code' => 'CUST-002',
            'name' => 'Ahmad Fauzi',
            'phone' => '081298765432',
            'email' => 'fauzi@gmail.com',
            'address' => 'Jl. Mawar No. 14, Sukasenang',
            'member_level' => 'gold',
            'points' => 150,
            'status' => true,
        ]);

        Customer::create([
            'code' => 'CUST-003',
            'name' => 'Siti Nurhaliza',
            'phone' => '085712345678',
            'email' => 'siti.nur@gmail.com',
            'address' => 'Jl. Anggrek No. 8, Ciawi',
            'member_level' => 'silver',
            'points' => 50,
            'status' => true,
        ]);

        // 8. Sample Products
        $products = [
            [
                'category_id' => $catMinuman->id,
                'unit_id' => $btl->id,
                'sku' => 'PRD-MNM-001',
                'barcode' => '8992761111014',
                'name' => 'Aqua Air Mineral 600ml',
                'cost_price' => 2800,
                'selling_price' => 4000,
                'stock' => 120,
                'min_stock' => 20,
                'status' => true,
            ],
            [
                'category_id' => $catMinuman->id,
                'unit_id' => $btl->id,
                'sku' => 'PRD-MNM-002',
                'barcode' => '8999908123456',
                'name' => 'Teh Botol Sosro 450ml',
                'cost_price' => 4500,
                'selling_price' => 6500,
                'stock' => 75,
                'min_stock' => 15,
                'status' => true,
            ],
            [
                'category_id' => $catMinuman->id,
                'unit_id' => $btl->id,
                'sku' => 'PRD-MNM-003',
                'barcode' => '8992753123457',
                'name' => 'Kopi Kenangan Mantan Ready to Drink 220ml',
                'cost_price' => 7500,
                'selling_price' => 10500,
                'stock' => 40,
                'min_stock' => 10,
                'status' => true,
            ],
            [
                'category_id' => $catMakanan->id,
                'unit_id' => $pcs->id,
                'sku' => 'PRD-MKN-001',
                'barcode' => '8998866200112',
                'name' => 'Indomie Goreng Original 85g',
                'cost_price' => 2800,
                'selling_price' => 3500,
                'stock' => 250,
                'min_stock' => 30,
                'status' => true,
            ],
            [
                'category_id' => $catMakanan->id,
                'unit_id' => $pcs->id,
                'sku' => 'PRD-MKN-002',
                'barcode' => '8998866200129',
                'name' => 'Indomie Kuah Ayam Bawang 75g',
                'cost_price' => 2750,
                'selling_price' => 3500,
                'stock' => 180,
                'min_stock' => 25,
                'status' => true,
            ],
            [
                'category_id' => $catMakanan->id,
                'unit_id' => $pcs->id,
                'sku' => 'PRD-MKN-003',
                'barcode' => '8992775210111',
                'name' => 'Chitato Sapi Panggang 68g',
                'cost_price' => 8500,
                'selling_price' => 11500,
                'stock' => 50,
                'min_stock' => 10,
                'status' => true,
            ],
            [
                'category_id' => $catMakanan->id,
                'unit_id' => $box->id,
                'sku' => 'PRD-MKN-004',
                'barcode' => '8991001100223',
                'name' => 'Roma Kelapa Biskuit 300g',
                'cost_price' => 9500,
                'selling_price' => 13000,
                'stock' => 35,
                'min_stock' => 8,
                'status' => true,
            ],
            [
                'category_id' => $catSembako->id,
                'unit_id' => $kg->id,
                'sku' => 'PRD-SBK-001',
                'barcode' => '8993175112233',
                'name' => 'Beras Pandan Wangi Premium 5Kg',
                'cost_price' => 68000,
                'selling_price' => 77500,
                'stock' => 25,
                'min_stock' => 5,
                'status' => true,
            ],
            [
                'category_id' => $catSembako->id,
                'unit_id' => $btl->id,
                'sku' => 'PRD-SBK-002',
                'barcode' => '8996001300445',
                'name' => 'Minyak Goreng Bimoli 2 Liter Pouch',
                'cost_price' => 33000,
                'selling_price' => 38000,
                'stock' => 40,
                'min_stock' => 10,
                'status' => true,
            ],
            [
                'category_id' => $catSembako->id,
                'unit_id' => $kg->id,
                'sku' => 'PRD-SBK-003',
                'barcode' => '8993005200118',
                'name' => 'Gula Pasir Gulaku Premium 1Kg',
                'cost_price' => 15500,
                'selling_price' => 18500,
                'stock' => 4, // contoh stok menipis (dibawah min_stock 10)
                'min_stock' => 10,
                'status' => true,
            ],
            [
                'category_id' => $catAlatTulis->id,
                'unit_id' => $pcs->id,
                'sku' => 'PRD-ATK-001',
                'barcode' => '8997001400552',
                'name' => 'Kertas Thermal Struk Kasir 58mmx30mm',
                'cost_price' => 3500,
                'selling_price' => 6000,
                'stock' => 60,
                'min_stock' => 15,
                'status' => true,
            ],
        ];

        foreach ($products as $pData) {
            $product = Product::create($pData);

            // Record initial stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $superAdmin->id,
                'type' => 'initial',
                'reference_type' => null,
                'reference_id' => null,
                'qty_in' => $product->stock,
                'qty_out' => 0,
                'stock_before' => 0,
                'stock_after' => $product->stock,
                'notes' => 'Saldo stok awal sistem POS',
            ]);
        }

        // 9. Store Settings
        $defaultSettings = [
            'store_name' => 'POS KASIR BERKAH',
            'store_tagline' => 'Solusi Cepat, Transaksi Mudah & Terpercaya',
            'store_address' => 'Jl. Raya Singaparna No. 123, Tasikmalaya, Jawa Barat',
            'store_phone' => '0812-3456-7890',
            'store_email' => 'toko.berkah@pos.test',
            'tax_percentage' => '0', // Default 0% (bisa diset 10% atau 11%)
            'currency_symbol' => 'Rp',
            'receipt_footer' => "Terima kasih atas kunjungan Anda!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian.",
            'invoice_prefix' => 'INV',
            'purchase_prefix' => 'PO',
            'low_stock_alert' => '5',
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::set($key, $val, 'general', 'Pengaturan bawaan sistem');
        }
    }
}
