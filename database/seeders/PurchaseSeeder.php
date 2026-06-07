<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\BatterySerial;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $ledgerService = app(LedgerService::class);

        $this->command->info('9. Seeding Inventory Purchases (Inflow)...');

        $admin = User::where('role', UserRole::Admin)->first();
        $godown = Warehouse::where('name', 'West Godown Store')->first();
        $showroom = Warehouse::where('name', 'Main Showroom')->first();
        $suppliers = Supplier::all();
        $products = Product::all();

        $serialCounter = 1;
        $purchaseDates = collect([]);
        for ($i = 200; $i >= 2; $i -= 3) {
            $purchaseDates->push(Carbon::instance(now()->subDays($i)));
        }

        foreach ($purchaseDates as $dateIndex => $pDate) {
            $supplier = $suppliers->random();
            $wh = ($dateIndex % 3 === 0) ? $godown : $showroom;

            $purchaseInvoice = PurchaseInvoice::create([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $wh->id,
                'invoice_no' => 'PUR-'.$pDate->format('Ymd').'-'.rand(1000, 9999),
                'purchase_date' => $pDate,
                'sub_total' => 0.00,
                'discount_amount' => 0.00,
                'grand_total' => 0.00,
                'notes' => 'Bulk supply arrival on '.$pDate->format('d M Y'),
                'created_by' => $admin->id,
            ]);

            $subTotal = 0.00;
            $itemCount = rand(3, 6);
            for ($k = 0; $k < $itemCount; $k++) {
                $product = $products->random();
                $qty = rand(4, 12);

                for ($q = 0; $q < $qty; $q++) {
                    $serialNo = strtoupper(substr($product->brand_name, 0, 3)).'-'.strtoupper(str_replace(' ', '', $product->model_name)).'-'.str_pad($serialCounter++, 6, '0', STR_PAD_LEFT);

                    $pItem = new PurchaseItem([
                        'purchase_invoice_id' => $purchaseInvoice->id,
                        'product_id' => $product->id,
                        'purchase_price' => $product->purchase_cost,
                    ]);
                    $pItem->serial_no = $serialNo;
                    $pItem->save(); // This automatically creates the BatterySerial in stock and InventoryTransaction!

                    $subTotal += (float) $product->purchase_cost;
                }
            }

            $purchaseInvoice->update([
                'sub_total' => $subTotal,
                'grand_total' => $subTotal,
            ]);

            // Record payable in SupplierLedger
            $ledgerService->recordSupplierTransaction(
                $supplier,
                0.00, // debit
                $subTotal, // credit (increases payable)
                'Purchase',
                $purchaseInvoice,
                'Purchase invoice logged #'.$purchaseInvoice->invoice_no,
                $pDate
            );
        }

        $totalSerialsCreated = BatterySerial::count();
        $this->command->info("   Created {$totalSerialsCreated} Battery Serials via Supplier Purchases.");
    }
}
