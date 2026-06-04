# SYSTEM PROMPT: PROJECT CONTEXT & ARCHITECTURAL BLUEPRINT (UPDATED)

## 🏷️ 1. Project Identity & Technical Stack
- **Project Name:** volt-erp (alternatively: battery-khata)
- **Target Stack:** Laravel 13 + Filament v5 + PHP 8.5
- **Application Type:** Cashbook-Centric Operational ERP & Operations Management System (OMS)
- **Target Market:** SMB (Small & Medium Business) Battery Retailers & Wholesalers in Bangladesh.

---

## 🎯 2. Core Operational Engines & Business Context
This is an event-driven, lifecycle-heavy application, NOT a static CRUD system. You must design all features around these core functional loops:
1. **Serialized Inventory Engine:** Every battery has an immutable physical identity (`battery_serials`). All movements (Purchase, Sale, Transfers, Warranty, Returns) are tracked as immutable state logs in `inventory_transactions`. Stock states must be dynamically derived or safely snapshotted.
2. **Stock Transfers Meta-Document:** Bulk movements of stock between storage godowns and showrooms are initiated via `stock_transfers` which groups the actual individual serial transactions. This prevents lost stock and tracks items that are `InTransit`.
3. **Cashbook-Centric Cash Drawer Engine (Golla / গল্লা):** All floor operations flow through terminal sessions (`cash_register_sessions`). Every single drop of cash moving through the physical drawer registers an immutable transaction in `cashbook_entries` with explicit direction (`In`/`Out`). This includes invoice collections, utility expenses, *Dalal* commission payouts, and staff advances (*Hawlat*). Digital MFS (bKash/Nagad) and Bank payments go directly to accounts and are logged separately to prevent physical cash drawer discrepancy.
4. **POS & Decoupled Payment Allocation Engine:** Supports retail, wholesale, and set-based pricing (such as Easy-bike sets of 4 or 5 batteries, Misuk sets of 3 or 4 batteries). Invoices never store mutable total balance states prone to historical desyncs. All collections flow through a standalone `payments` engine and map across outstanding bills via `payment_allocations` pivots.
5. **Scrap/Core-Exchange Barter Engine (ভাঙ্গা মাল):** Customers exchange old scrap batteries to offset dues or buy new stock. Scrap collections are completely decoupled from invoices, allowing standalone scrap inflows tracked by piece or weight (`KG`) inside `scrap_collections`. Bulk outflows of scrap back to suppliers/factories for credit or cash are tracked in `scrap_disposals`.
6. **Warranty Lifecycle & Buffer Stock Logistics:** State machine tracking (`Received` ➔ `SentToSupplier` ➔ `Approved/Rejected` ➔ `Resolved`). Tracks temporary fallback units issued to clients (`buffer_battery_issues`) so active stock never mysteriously disappears. Replaced units require binding the new replacement serial number to the claim.
7. **Micro-Loans, Staff advances (Hawlat) & Invariant Ledgers:** Differentiates short-term supplier/customer trade payables from long-term capital injections and staff advances (`loan_accounts` supporting Supplier, Customer, Staff, or External lender types). Enforces an immutable chronological `running_balance` in `customer_ledgers` and `supplier_ledgers` to ensure sub-second dashboard rendering speeds.

---

## 💾 3. 3rd Normal Form (3NF) DBML Schema Specification
Use this exact relational design for migrations, models, and relations:

```dbml
// =======================================================
// 1. USERS (Platform Internal Access Staff Only)
// =======================================================
Table users {
  id BIGINT [pk, increment]
  name VARCHAR [not null]
  phone VARCHAR [null]
  email VARCHAR [unique, not null]
  password VARCHAR [not null]
  role VARCHAR [not null, note: "Admin, Manager, CounterBoy"]
  is_active BOOLEAN [default: true]
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP
}

// =======================================================
// 2. PRODUCT CATALOG
// =======================================================
Table product_categories {
  id BIGINT [pk, increment]
  name VARCHAR [not null, unique]
  created_at TIMESTAMP
}

Table products {
  id BIGINT [pk, increment]
  category_id BIGINT [not null]
  brand_name VARCHAR [not null]
  model_name VARCHAR [not null]
  voltage VARCHAR [null]
  capacity_ah VARCHAR [null]
  plate_count INT [null, note: "Plate count for lead-acid batteries (e.g. 15, 19, 21, 25)"]
  warranty_months INT [default: 0]
  mrp_price DECIMAL(12,2) [not null]
  dealer_price DECIMAL(12,2) [not null]
  set_price DECIMAL(12,2) [null, note: "Discounted unit price when purchased as a set"]
  standard_set_qty INT [default: 4, note: "Typical count in a set (e.g. 3, 4, 5)"]
  purchase_cost DECIMAL(12,2) [default: 0]
  has_serial_tracking BOOLEAN [default: true]
  alert_threshold_qty INT [default: 5]
  is_active BOOLEAN [default: true]
  created_at TIMESTAMP
  updated_at TIMESTAMP
  deleted_at TIMESTAMP
}

// =======================================================
// 3. WAREHOUSES & STORAGE SPACES
// =======================================================
Table warehouses {
  id BIGINT [pk, increment]
  name VARCHAR [not null]
  location VARCHAR [null]
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

// =======================================================
// 4. SERIALIZED INVENTORY
// =======================================================
Table battery_serials {
  id BIGINT [pk, increment]
  product_id BIGINT [not null]
  serial_no VARCHAR [not null, unique]
  current_status VARCHAR [not null, default: "InStock", note: "Enum: InStock, Sold, Reserved, WarrantyClaim, BufferIssued, SupplierReturned, Scrap"]
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table inventory_transactions {
  id BIGINT [pk, increment]
  battery_serial_id BIGINT [not null]
  warehouse_id BIGINT [not null]
  transaction_type VARCHAR [not null, note: "Enum: Purchase, TransferIn, TransferOut, Sale, WarrantyIn, WarrantyOut, BufferIssue, BufferReturn"]
  reference_type VARCHAR [null]
  reference_id BIGINT [null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 5. STOCK TRANSFERS META-DOCUMENT
// =======================================================
Table stock_transfers {
  id BIGINT [pk, increment]
  source_warehouse_id BIGINT [not null]
  destination_warehouse_id BIGINT [not null]
  transfer_date DATE [not null]
  status VARCHAR [not null, default: "Completed", note: "Pending, InTransit, Completed"]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 6. SUPPLIERS & PURCHASE SUPPLY CHAIN
// =======================================================
Table suppliers {
  id BIGINT [pk, increment]
  name VARCHAR [not null]
  mobile VARCHAR [null]
  address TEXT [null]
  supplier_type VARCHAR [default: "Regular", note: "Regular, Mahajon"]
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table purchase_invoices {
  id BIGINT [pk, increment]
  supplier_id BIGINT [not null]
  warehouse_id BIGINT [not null]
  invoice_no VARCHAR [not null, unique]
  purchase_date DATE [not null]
  sub_total DECIMAL(12,2) [default: 0]
  discount_amount DECIMAL(12,2) [default: 0]
  grand_total DECIMAL(12,2) [default: 0]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table purchase_items {
  id BIGINT [pk, increment]
  purchase_invoice_id BIGINT [not null]
  product_id BIGINT [not null]
  battery_serial_id BIGINT [not null, unique]
  purchase_price DECIMAL(12,2) [not null]
  created_at TIMESTAMP
}

// =======================================================
// 7. CUSTOMERS & BROKERS (Upgraded BD Regional Identifiers)
// =======================================================
Table customers {
  id BIGINT [pk, increment]
  name VARCHAR [not null]
  mobile VARCHAR [not null, unique]
  national_id VARCHAR [null, unique, note: "National ID / NID card number for credit liability tracking (must be unique)"]
  image_path VARCHAR [null, note: "Path to uploaded customer profile image or photo for identity check"]
  division VARCHAR [null, note: "Bangladesh Regional State Layer"]
  district VARCHAR [null]
  upazila VARCHAR [null]
  address TEXT [null, note: "Detailed full structural text address type mapping"]
  customer_type VARCHAR [default: "Retail", note: "Enum: Retail, Dealer, Garage"]
  credit_limit DECIMAL(12,2) [default: 0]
  is_active BOOLEAN [default: true]
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table brokers {
  id BIGINT [pk, increment]
  name VARCHAR [not null]
  mobile VARCHAR [null]
  commission_rate DECIMAL(5,2) [default: 0]
  created_at TIMESTAMP
}

// =======================================================
// 8. CASH DRAWER SESSIONS
// =======================================================
Table cash_register_sessions {
  id BIGINT [pk, increment]
  opened_by BIGINT [not null]
  opened_at TIMESTAMP [not null]
  closed_at TIMESTAMP [null]
  opening_cash DECIMAL(12,2) [default: 0]
  expected_cash DECIMAL(12,2) [null, note: "Expected cash calculated for physical cash payment method only"]
  closing_cash DECIMAL(12,2) [null]
  shortage_excess DECIMAL(12,2) [null]
  denominations JSON [null, note: "JSON mapping of physical cash note counts (e.g. {1000:12, 500:8}) for audit checking"]
  notes TEXT [null]
  created_at TIMESTAMP
}

// =======================================================
// 9. FINANCIAL CORE CASHBOOK LEDGER
// =======================================================
Table cashbook_entries {
  id BIGINT [pk, increment]
  cash_register_session_id BIGINT [not null]
  entry_type VARCHAR [not null, note: "Sale, DueCollection, Expense, OwnerWithdrawal, SupplierPayment, LoanReceive, Adjustment"]
  direction VARCHAR [not null, note: "In, Out"]
  payment_method VARCHAR [not null, note: "Cash, Bkash, Nagad, Rocket, Bank"]
  amount DECIMAL(12,2) [not null]
  reference_type VARCHAR [null]
  reference_id BIGINT [null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 10. SALES / POS TRANSACTIONS
// =======================================================
Table invoices {
  id BIGINT [pk, increment]
  invoice_no VARCHAR [not null, unique]
  customer_id BIGINT [not null]
  broker_id BIGINT [null]
  cash_register_session_id BIGINT [not null]
  invoice_date TIMESTAMP [not null]
  sub_total DECIMAL(12,2) [default: 0]
  discount_amount DECIMAL(12,2) [default: 0]
  scrap_adjustment DECIMAL(12,2) [default: 0]
  grand_total DECIMAL(12,2) [default: 0]
  invoice_status VARCHAR [default: "Completed", note: "Draft, Completed, Cancelled"]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
  updated_at TIMESTAMP
}

Table invoice_items {
  id BIGINT [pk, increment]
  invoice_id BIGINT [not null]
  product_id BIGINT [not null]
  battery_serial_id BIGINT [not null, unique]
  sale_price DECIMAL(12,2) [not null]
  warranty_months INT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 11. DECOUPLED PAYOUTS / PAYMENTS LEDGER
// =======================================================
Table payments {
  id BIGINT [pk, increment]
  customer_id BIGINT [not null]
  cash_register_session_id BIGINT [not null]
  scrap_collection_id BIGINT [null]
  payment_date TIMESTAMP [not null]
  payment_method VARCHAR [not null, note: "Cash, Bkash, Nagad, Rocket, Bank, Cheque, ScrapAdjustment"]
  total_amount DECIMAL(12,2) [not null]
  service_charge DECIMAL(12,2) [default: 0, note: "MFS/Bank cashout fee or service fee"]
  reference_no VARCHAR [null]
  notes TEXT [null]
  received_by BIGINT [not null]
  created_at TIMESTAMP
}

Table payment_allocations {
  id BIGINT [pk, increment]
  payment_id BIGINT [not null]
  invoice_id BIGINT [not null]
  allocated_amount DECIMAL(12,2) [not null]
  created_at TIMESTAMP
}

// =======================================================
// 12. CUSTOMER STATEMENT LEDGER
// =======================================================
Table customer_ledgers {
  id BIGINT [pk, increment]
  customer_id BIGINT [not null]
  transaction_date TIMESTAMP [not null]
  transaction_type VARCHAR [note: "Invoice, Payment, ScrapAdjustment, Loan, ManualAdjustment"]
  reference_type VARCHAR [null]
  reference_id BIGINT [null]
  debit DECIMAL(12,2) [default: 0]
  credit DECIMAL(12,2) [default: 0]
  running_balance DECIMAL(12,2) [not null, default: 0]
  notes TEXT [null]
  created_at TIMESTAMP
}

// =======================================================
// 13. SUPPLIER LEDGER & PAYMENTS
// =======================================================
Table supplier_payments {
  id BIGINT [pk, increment]
  supplier_id BIGINT [not null]
  cash_register_session_id BIGINT [not null]
  payment_date TIMESTAMP [not null]
  payment_method VARCHAR [not null, note: "Cash, Bkash, Nagad, Rocket, Bank, Cheque"]
  amount DECIMAL(12,2) [not null]
  reference_no VARCHAR [null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

Table supplier_ledgers {
  id BIGINT [pk, increment]
  supplier_id BIGINT [not null]
  transaction_date TIMESTAMP [not null]
  transaction_type VARCHAR [note: "PurchaseInvoice, SupplierPayment, ReturnAdjustment, ManualAdjustment"]
  reference_type VARCHAR [null]
  reference_id BIGINT [null]
  debit DECIMAL(12,2) [default: 0, note: "Amount we paid or adjusted (reduces our liability)"]
  credit DECIMAL(12,2) [default: 0, note: "Amount of purchase or liability incurred"]
  running_balance DECIMAL(12,2) [not null, default: 0]
  notes TEXT [null]
  created_at TIMESTAMP
}

// =======================================================
// 14. SCRAP CORE INVENTORY & DISPOSAL
// =======================================================
Table scrap_collections {
  id BIGINT [pk, increment]
  customer_id BIGINT [null]
  invoice_id BIGINT [null]
  warehouse_id BIGINT [not null]
  scrap_type VARCHAR [not null, note: "EasyBike, Car, IPS, Motorcycle"]
  quantity DECIMAL(12,2) [default: 1]
  estimated_weight DECIMAL(12,2) [null]
  unit_value DECIMAL(12,2) [not null]
  total_value DECIMAL(12,2) [not null]
  status VARCHAR [not null, default: "InWarehouse", note: "InWarehouse, DisposedToFactory"]
  scrap_disposal_id BIGINT [null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

Table scrap_disposals {
  id BIGINT [pk, increment]
  supplier_id BIGINT [null, note: "If sold/returned to a manufacturer for supplier credit/cash"]
  disposal_date DATE [not null]
  payment_method VARCHAR [not null, note: "Cash, Bank, SupplierCredit"]
  total_received DECIMAL(12,2) [not null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 15. WARRANTY LIFECYCLE SYSTEM
// =======================================================
Table warranty_claims {
  id BIGINT [pk, increment]
  invoice_item_id BIGINT [not null]
  claim_no VARCHAR [not null, unique]
  claim_date DATE [not null]
  supplier_claim_no VARCHAR [null, note: "Warranty slip ticket number issued by the factory"]
  customer_issue TEXT [null]
  claim_status VARCHAR [not null, default: "Received", note: "Received, SentToSupplier, Approved, Rejected, Resolved"]
  supplier_sent_date DATE [null]
  resolved_date DATE [null]
  replacement_battery_serial_id BIGINT [null, note: "If claim resolved via replacement, records new serial issued to client"]
  resolution_notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

Table buffer_battery_issues {
  id BIGINT [pk, increment]
  warranty_claim_id BIGINT [not null]
  battery_serial_id BIGINT [not null]
  issued_date DATE [not null]
  returned_date DATE [null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 16. MICRO-LOANS MODULE
// =======================================================
Table loan_accounts {
  id BIGINT [pk, increment]
  lender_type VARCHAR [note: "Supplier, Customer, Staff, External"]
  lender_reference_id BIGINT [not null, note: "Maps to suppliers.id, customers.id, users.id based on type"]
  loan_name VARCHAR [not null]
  principal_amount DECIMAL(12,2) [not null]
  outstanding_balance DECIMAL(12,2) [not null]
  start_date DATE [not null]
  notes TEXT [null]
  created_at TIMESTAMP
}

Table loan_transactions {
  id BIGINT [pk, increment]
  loan_account_id BIGINT [not null]
  transaction_date DATE [not null]
  transaction_type VARCHAR [note: "Disbursement, Repayment"]
  amount DECIMAL(12,2) [not null]
  notes TEXT [null]
  created_at TIMESTAMP
}

// =======================================================
// 17. DAILY EXPENSE MATRIX
// =======================================================
Table expense_categories {
  id BIGINT [pk, increment]
  name VARCHAR [not null]
}

Table expenses {
  id BIGINT [pk, increment]
  expense_category_id BIGINT [not null]
  cash_register_session_id BIGINT [null]
  amount DECIMAL(12,2) [not null]
  payment_method VARCHAR [note: "Cash, Bkash, Nagad, Bank"]
  expense_date DATE [not null]
  notes TEXT [null]
  created_by BIGINT [not null]
  created_at TIMESTAMP
}

// =======================================================
// 18. POST-DATED CHEQUE PROMOTED REGISTRY
// =======================================================
Table post_dated_cheques {
  id BIGINT [pk, increment]
  customer_id BIGINT [not null]
  payment_id BIGINT [null]
  cheque_number VARCHAR [not null]
  bank_name VARCHAR [not null]
  amount DECIMAL(12,2) [not null]
  maturity_date DATE [not null]
  deposit_date DATE [null]
  cleared_date DATE [null]
  bounce_reason TEXT [null]
  status VARCHAR [default: "Pending", note: "Pending, Deposited, Cleared, Bounced"]
  created_at TIMESTAMP
}

// =======================================================
// 19. ABSTRACT COMMUNICATION LOGS
// =======================================================
Table notification_logs {
  id BIGINT [pk, increment]
  recipient VARCHAR [not null]
  notification_type VARCHAR [note: "Invoice, PaymentReminder, WarrantyUpdate"]
  payload TEXT [null]
  delivery_status VARCHAR [default: "Mocked", note: "Mocked, Sent, Failed"]
  created_at TIMESTAMP
}

// --- RELATIONAL CONSTRAINT BINDINGS ---
Ref: product_categories.id < products.category_id
Ref: products.id < battery_serials.product_id
Ref: battery_serials.id < inventory_transactions.battery_serial_id
Ref: warehouses.id < inventory_transactions.warehouse_id
Ref: suppliers.id < purchase_invoices.supplier_id
Ref: warehouses.id < purchase_invoices.warehouse_id
Ref: purchase_invoices.id < purchase_items.purchase_invoice_id
Ref: products.id < purchase_items.product_id
Ref: battery_serials.id - purchase_items.battery_serial_id
Ref: warehouses.id < stock_transfers.source_warehouse_id
Ref: warehouses.id < stock_transfers.destination_warehouse_id
Ref: users.id < cash_register_sessions.opened_by
Ref: cash_register_sessions.id < cashbook_entries.cash_register_session_id
Ref: customers.id < invoices.customer_id
Ref: brokers.id < invoices.broker_id
Ref: cash_register_sessions.id < invoices.cash_register_session_id
Ref: invoices.id < invoice_items.invoice_id
Ref: products.id < invoice_items.product_id
Ref: battery_serials.id - invoice_items.battery_serial_id
Ref: customers.id < payments.customer_id
Ref: cash_register_sessions.id < payments.cash_register_session_id
Ref: scrap_collections.id < payments.scrap_collection_id
Ref: payments.id < payment_allocations.payment_id
Ref: invoices.id < payment_allocations.invoice_id
Ref: customers.id < customer_ledgers.customer_id
Ref: suppliers.id < supplier_payments.supplier_id
Ref: cash_register_sessions.id < supplier_payments.cash_register_session_id
Ref: suppliers.id < supplier_ledgers.supplier_id
Ref: customers.id < scrap_collections.customer_id
Ref: invoices.id < scrap_collections.invoice_id
Ref: warehouses.id < scrap_collections.warehouse_id
Ref: scrap_disposals.id < scrap_collections.scrap_disposal_id
Ref: invoice_items.id < warranty_claims.invoice_item_id
Ref: battery_serials.id < warranty_claims.replacement_battery_serial_id
Ref: warranty_claims.id < buffer_battery_issues.warranty_claim_id
Ref: battery_serials.id < buffer_battery_issues.battery_serial_id
Ref: loan_accounts.id < loan_transactions.loan_account_id
Ref: expense_categories.id < expenses.expense_category_id
Ref: cash_register_sessions.id < expenses.cash_register_session_id
Ref: customers.id < post_dated_cheques.customer_id
Ref: payments.id < post_dated_cheques.payment_id
Ref: suppliers.id < scrap_disposals.supplier_id

```

---

## 📐 4. Crucial Mathematical Formula & Business Rules

When constructing backend services, Filament form wizard actions, or database triggers, you MUST enforce the following equations:

### A. Cash Session End Reconciliation Formula

$$\text{Expected Closing Cash (Physical)} = \text{Opening Cash} + \sum(\text{Inflow Entries where method = Cash}) - \sum(\text{Outflow Entries where method = Cash})$$

*Any variation must be instantly saved as: `shortage_excess = closing_cash - expected_cash`.*

### B. Customer Credit Exposure Derivation Rule

Before approving an invoice or a part-credit transaction, calculate the client's risk exposure using the immutable ledger tracking endpoint:

$$\text{Current Exposure} = \text{customer\_ledgers::where('customer\_id', \$id)->latest()->value('running\_balance')}$$

*Block transaction forms immediately if: $\text{Current Exposure} > \text{customers.credit\_limit}$.*

---

## 🚨 5. Strict Code Generation Directives for AI

1. **PHP Native Enums & Modern Model Casts (PHP 8.5 & Laravel 13):** All string notes/options in the DBML schema (e.g., `SerialStatus`, `TransactionType`, `PaymentMethod`) must be mapped to native backed PHP enums. In PostgreSQL migrations, these must be defined as standard string/varchar columns rather than native database ENUM types to maintain schema flexibility. Enable the PostgreSQL `pg_trgm` extension in migrations and define GIN indexes on fuzzy search columns (`customers.name`, `customers.mobile`, `customers.national_id`). Model attributes casting must be declared using the modern Laravel 11+ `protected function casts(): array` method instead of the deprecated `protected $casts = [...]` property.
2. **Filament Form Wizards Pattern:** Use multi-step `Forms\Components\Wizard` patterns for the POS checkout resource. 
   - **Step 1: Customer Info:** Lookups and dropdowns for Division/District/Upazila. Support searching/autocomplete of existing customers using either Phone Number or NID (both unique keys), with photo image displaying/uploading to verify driver/dealer identity.
   - **Step 2: Serialized Item Selections:** Custom batch input interface. If a product's standard set quantity is selected (e.g., 5 for Easy-Bike batteries), the wizard prompts for 5 serial numbers and auto-applies `set_price` instead of unit `mrp_price`.
   - **Step 3: Scrap Item Deductions:** Deducts old trade-ins (*Bhanga Mal*) and registers them directly into showroom warehouses.
   - **Step 4: Summary & Payout Binding:** Payment allocation details (Cash, Bank, bKash, Cheque) plus automatic logging of bKash/Nagad service charges.
3. **No Cascade Deletes on Core Data:** Soft deletes must be used on core user entities and all financial documents (`invoices`, `payments`, `scrap_collections`, `supplier_payments`). Enforce explicit status reversals instead of truncation.
4. **Decoupled Job Queues:** Mock SMS alert triggers must hook into standard Laravel background workers (`ShouldQueue`) to prevent floor operational lag.
5. **Data Protection & Offsite Backups:** Install and configure `spatie/laravel-backup` to run daily PostgreSQL database backups and upload them to offsite cloud storage (Google Drive or AWS S3) for disaster recovery.
6. **Invoice Printing & Number Formatting:** Implement local lightweight PHP helpers to output the invoice grand total amount in words for both English and Bengali Unicode on printed receipts and challans.
7. **Test-Driven Development (TDD) Workflow:** Follow a TDD workflow. Every model, service, or API action must have a corresponding Pest PHP test written before or alongside its implementation. Tests must be organized logically under `tests/Feature/Model/`, `tests/Feature/Service/`, and `tests/Feature/Filament/` namespaces. Ensure all relationship configurations, soft-delete triggers, enum casts, and business edge-cases are explicitly covered.
