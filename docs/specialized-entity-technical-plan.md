# Specialized Entity (SE) — Technical Planning Document

**Version:** 1.2
**Date:** 2026-03-25
**Status:** Ready for Implementation
**Business Reference:** [Specialized Entity — Confluence](https://lynktech.atlassian.net/wiki/spaces/PM/pages/463765509/Specialized+Entity)
**Jira:** [LYNKMRBHA-5767](https://lynktech.atlassian.net/browse/LYNKMRBHA-5767)

---

## 1. Business Context

### 1.1 Problem Statement

In the current Murabaha process for LYNK Auto Trade Requests, when a client sells their commodity units, ownership is transferred from the client back to the **Local Market Pool**. Units then wait in the pool until a new incoming order purchases them.

This creates three operational problems:

1. **Units may sit in the pool for an extended period** before being re-purchased by new orders.
2. **The Sell Confirmation Certificate cannot be issued** until all units associated with the order have been re-sold — the client is blocked waiting for an unrelated future order.
3. **The Sell Confirmation Certificate is issued manually** by a LYNK Admin after the settlement check API confirms re-purchase, introducing human dependency and delay.

### 1.2 Solution

Introduce a **Specialized Entity (SE)** — a system-configured legal entity that immediately purchases commodity units from clients at the moment of the sell action. Because the SE is internal to the Lynk system, settlement is instantaneous. Units are returned to the pool immediately after the SE purchases them.

**Updated ownership flow:**

```
Supplier (OriginalSupplier)
    ↓ PurchaseCommodity
Company / Lender
    ↓ BorrowerOwnershipTransfer
Customer (Client)
    ↓ SellCommodity
Specialized Entity         ← NEW: immediate, internal
    ↓ units returned to pool
Available for future orders
```

**Previous ownership flow (to be replaced):**

```
Customer (Client)
    ↓ SellCommodity
TraderOrder (Local Market Pool)
    ↓ [async — CheckTraderOrderSettlementJob]
    ↓ External API confirms re-purchase
is_commodities_settled = true  →  Sell Confirmation issued manually
```

### 1.3 Certificate Terminology

Two certificates exist in the system. They are distinct and must not be confused:

| Certificate | Arabic | Code Reference | When Generated |
|-------------|--------|----------------|----------------|
| **Sell Commitment Certificate** (شهادة التزام بالبيع) | Pledge | `LynkSalePledgeCertificate` / `SELLING_PLEDGE_CERTIFICATE` | Auto-generated when client initiates the sell (`MurabhaOfferIssued` step). **Removed for new orders after SE deployment.** |
| **Sell Confirmation Certificate** (شهادة إتمام بالبيع) | Confirmation | `SellConfirmationDocument` / `SELL_CONFIRMATION_DOCUMENT` | Previously: issued manually after settlement check. **After SE: generated immediately and automatically.** |

### 1.4 Personas Affected

| Persona | Impact |
|---------|--------|
| **LYNK Admin** | Gains ability to create, edit, activate/deactivate Specialized Entities. "Check Sell Status" admin button is removed. |
| **Financial Institution (FI)** | Sell Confirmation Certificate is generated immediately — no longer waiting for units to be re-purchased. Sell Commitment Certificate no longer generated for new orders. |
| **Specialized Entity** | System-managed entity. No user interaction. Units owned by SE are queryable via `specialized_entity_id` on inventory units. |

### 1.5 Default Specialized Entity (Seed Data)

A default SE must exist in the system before deployment:

| Field | Value |
|-------|-------|
| Name | شركة البناء المنتظم للمقاولات شركة شخص واحد |
| Unique Identifier (`unique_name`) | BMC001 |
| CR Number (`company_cr`) | 2051162721 |
| Status | Active (`CompanyStatus::Approved`) |
| Type | `CompanyType::SpecializedEntity` |

### 1.6 Multi-Entity Logic

Multiple SEs may exist. When more than one active SE is present, transactions are distributed **sequentially** (round-robin):

- First order sells → Entity 1
- Second order sells → Entity 2
- Third order sells → Entity 1 ...

Distribution considers only **active** entities and automatically skips inactive ones.

> **Note:** Based on current business decision, only **one SE will exist at a time**. If a new SE is added, it replaces the existing active one (the old one is deactivated). The sequential logic is documented for completeness and future-proofing, but the initial implementation targets a single-entity scenario.

---

## 2. Order Categories Post-Deployment

After deployment, all orders fall into one of four categories. Each has different certificate and unit-handling behaviour.

### Category A — New Orders (Created After Deployment)

- Sell flow uses SE from the start.
- Ownership: Customer → SE (immediate).
- Sell Confirmation Certificate generated immediately → shows **SE name**.
- Sell Commitment Certificate is **not generated**.

### Category B — Pre-Deployment Orders, Before Client Wakala Step

- Order exists in the system before deployment but the client has not yet signed the Wakala (has not reached the sell step).
- When the sell step occurs after deployment, the SE flow applies.
- Identical behaviour to Category A.

### Category C — Pre-Deployment Orders, Partially Sold

- Some units were already sold under the old flow (TraderOrder/pool).
- Some units remain unsold at deployment time.
- **Action:** Data migration command moves remaining units to SE.
- Sell Confirmation Certificate generated → shows **"عملاء متفرقين"** (because some units went through the old flow before SE existed).
- Sell Commitment Certificate remains available and unchanged.

### Category D — Pre-Deployment Orders, Fully Sold

- All units were sold under the old flow before deployment.
- `is_commodities_settled` may still be 0 (settlement API was never confirmed).
- **Action:** Data migration command marks these as settled (no units to move).
- Sell Confirmation Certificate generated → shows **"عملاء متفرقين"**.
- Sell Commitment Certificate remains available and unchanged.

### Summary Table

| Category | Units Action | Sell Commitment | Sell Confirmation | Buyer Name |
|----------|-------------|-----------------|-------------------|------------|
| A — New orders | SE from start | Not generated | Immediate | SE name |
| B — Pre-deploy, before Wakala | SE flow applies | Not generated | Immediate | SE name |
| C — Pre-deploy, partially sold | Remaining → SE | Keep existing | Generated | عملاء متفرقين |
| D — Pre-deploy, fully sold | None | Keep existing | Generated | عملاء متفرقين |

---

## 3. Current Settlement Infrastructure (To Be Removed)

| Component | Location | Purpose |
|-----------|----------|---------|
| `trader_order_settlements` table | DB | Tracks settlement check attempts per trader order |
| `TraderOrderSettlement` model | `app/Models/TraderOrderSettlement.php` | ORM for above |
| `TraderOrderSettlementStatus` enum | `app/Enums/TraderOrderSettlementStatus.php` | Pending / InProgress / Completed / Failed |
| `InitiateTraderOrderSettlementAction` | `app/Actions/Orders/TraderOrders/` | Creates settlement record + dispatches job |
| `CheckTraderOrderSettlementAction` | `app/Actions/Orders/TraderOrders/` | Calls `LynkClient::checkOrderSettlement()` |
| `CheckOrderSettlementAction` | `app/Actions/LocalMarket/` | Checks inventory units for local market order |
| `CheckTraderOrderSettlementJob` | `app/Jobs/TraderOrder/` | Queue job on `local_market_commodities_settlement` queue |
| `CheckSettlement` controller | `app/Http/Controllers/.../TraderOrders/CheckSettlement.php` | Admin API endpoint — "Check Sell Status" button |
| `LynkClient::checkOrderSettlement()` | `app/Support/Traders/Clients/LynkClient.php:78` | Delegates to `CheckOrderSettlement` contract |
| `CheckOrderSettlement` contract | `app/Actions/Contracts/LocalMarket/` | Interface for above |
| `InitiateTraderOrderSettlement` contract | `app/Actions/Contracts/Orders/TraderOrders/` | Interface for initiate action |
| `is_commodities_settled` column | `local_market_orders` table | Tracks settlement per order |
| `TraderOrder::latestSettlement()` | `app/Models/TraderOrder.php:153` | HasOne to latest settlement record |
| `TraderOrder::settlements()` | `app/Models/TraderOrder.php:148` | HasMany to all settlement records |
| `TraderOrder::isCommoditiesSettled()` | `app/Models/TraderOrder.php:346` | Checks if latest settlement is settled |
| `TraderOrder::hasPendingSettlementCheck()` | `app/Models/TraderOrder.php:354` | Checks pending state |
| `TraderOrder::canBeSettled()` | `app/Models/TraderOrder.php:340` | Gate check before settlement |
| `LocalMarketOrder::markAsSettled()` | `app/Models/LocalMarketOrder.php:94` | Updates `is_commodities_settled` to true |
| `LocalMarketOrder::isCommoditiesSettled()` | `app/Models/LocalMarketOrder.php:104` | Boolean check |
| `TraderOrderSettlementTransformer` | `app/Transformers/` | Serialises settlement record for API |
| `settlement_details` in API response | `AbstractTraderHistoryTransformer:269` | Wraps sell confirmation behind settlement check |

---

## 4. Data Model Changes

### 4.1 New Enum Values

**`app/Enums/CompanyType.php`**

```php
const SpecializedEntity = 6;
```

> Current values: Lender=1, Supplier=3, SPV=4, TimeDeposit=5. Value 6 is next available.

**`app/Enums/LocalMarket/OwnershipTypes.php`**

```php
const SpecializedEntity = 5;
```

> Current values: Company=1, Customer=2, OriginalSupplier=3, TraderOrder=4. Value 5 is next available.

**`app/Enums/LocalMarket/UnitOwnershipAction.php`** — no change needed. `SellCommodity = 3` remains the correct action. Update the inline comment from `customer => trader order` to `customer => specialized entity`.

### 4.2 New Column: `specialized_entity_id` on `local_market_inventory_units`

**Purpose:** Denormalized FK that stores the SE company ID when `current_owner_type = SpecializedEntity`. Enables direct querying of which units belong to a given SE (for certificates, reporting, and any future SE-facing features) without type-casting the polymorphic `current_owner` string field.

**Migration:**

```php
Schema::table('local_market_inventory_units', function (Blueprint $table) {
    $table->unsignedBigInteger('specialized_entity_id')->nullable()->after('last_purchasing_order_id');
    $table->foreign('specialized_entity_id')->references('id')->on('companies');
});
```

**Update rule:** When `changeOrderUnitsOwnershipTo()` is called with `OwnershipTypes::SpecializedEntity`, also set `specialized_entity_id = $ownerIdentifier`. For all other ownership types, set `specialized_entity_id = null`.

### 4.3 Columns to Drop (Post-Data Migration)

| Table | Column | Migration |
|-------|--------|-----------|
| `local_market_orders` | `is_commodities_settled` | `drop_is_commodities_settled_from_local_market_orders` |

### 4.4 Tables to Drop (Post-Data Migration)

| Table | Migration |
|-------|-----------|
| `trader_order_settlements` | `drop_trader_order_settlements_table` |

> **Important:** Both drops must happen **after** the data migration command runs successfully in production (see Section 7).

---

## 5. Ownership Flow Changes

### 5.1 SE Resolution

**Decision:** Query `companies` for the first active SE (`status = Approved`, `type = SpecializedEntity`). If none found, throw an exception — the job fails to `FailedSell` and surfaces the misconfiguration.

Add `getActiveSpecializedEntity()` to the `Company` model:

```php
// app/Models/Company.php
public static function getActiveSpecializedEntity(): self
{
    return self::where('type', CompanyType::SpecializedEntity)
               ->where('status', CompanyStatus::Approved)
               ->firstOr(fn () => throw new NoActiveSpecializedEntityException());
}
```

> If `NoActiveSpecializedEntityException` is thrown inside `PendingSellOrderStatus`, the existing `catch (\Throwable $e)` block changes the order status to `FailedSell` and logs the error — no silent failures.

**Future multi-entity sequential distribution:** When more than one SE is active, the query should rotate through them based on the last assigned order. This can be implemented with an `orderBy('id')` and selecting the SE whose ID comes after the last used SE ID (wrapping around). For now, with a single SE, `firstOrFail()` is sufficient.

### 5.2 `PendingSellOrderStatus` Change

**File:** `app/Jobs/LocalMarket/states/PendingSellOrderStatus.php:32`

**Before:**
```php
$this->unitService->changeOrderUnitsOwnershipTo(
    $this->localMarketOrder,
    OwnershipTypes::TraderOrder,
    $this->localMarketOrder->external_order_no,
    UnitOwnershipAction::SellCommodity
);
```

**After:**
```php
$se = Company::getActiveSpecializedEntity();

$this->unitService->changeOrderUnitsOwnershipTo(
    $this->localMarketOrder,
    OwnershipTypes::SpecializedEntity,
    $se->id,
    UnitOwnershipAction::SellCommodity
);
```

### 5.3 `UnitService::changeOrderUnitsOwnershipTo()` Change

**File:** `app/Services/LocalMarket/UnitService.php:421`

Add `specialized_entity_id` to the bulk update:

```php
DB::table('local_market_inventory_units')
    ->where('hold_for', $localMarketOrder->id)
    ->update([
        'previous_owner'        => DB::raw('current_owner'),
        'previous_owner_type'   => DB::raw('current_owner_type'),
        'current_owner'         => $ownerIdentifier,
        'current_owner_type'    => $ownerType,
        'last_action'           => $action,
        'specialized_entity_id' => $ownerType === OwnershipTypes::SpecializedEntity ? $ownerIdentifier : null,
        'updated_at'            => now(),
    ]);
```

### 5.4 `LocalMarketInventoryUnits::getLastValidOwner()` Change

**File:** `app/Models/LocalMarketInventoryUnits.php:53`

Used by `UnitService::revertInventoryUnitOwnership()` when an order is cancelled or reverted.

**Before:**
```php
public function getLastValidOwner(): array
{
    if (is_null($this->last_completed_order_id)) {
        return ['current_owner' => $this->inventory->company_id, 'current_owner_type' => OwnershipTypes::OriginalSupplier];
    } else {
        return ['current_owner' => $this->completedOrder->external_order_no, 'current_owner_type' => OwnershipTypes::TraderOrder];
    }
}
```

**After:**
```php
public function getLastValidOwner(): array
{
    if (is_null($this->last_completed_order_id)) {
        return ['current_owner' => $this->inventory->company_id, 'current_owner_type' => OwnershipTypes::OriginalSupplier];
    }

    if ($this->current_owner_type === OwnershipTypes::SpecializedEntity) {
        return ['current_owner' => $this->specialized_entity_id, 'current_owner_type' => OwnershipTypes::SpecializedEntity];
    }

    return ['current_owner' => $this->completedOrder->external_order_no, 'current_owner_type' => OwnershipTypes::TraderOrder];
}
```

> **`previous_owner_type` / `previous_owner` correctness:** When SE takes ownership, the bulk update shifts `current_owner` (customer identifier) → `previous_owner` and `current_owner_type` (Customer) → `previous_owner_type`. No special-casing needed.

> ⚠️ **To Discuss:** `getLastValidOwner()` is called during order cancellation/revert via `revertInventoryUnitOwnership()`. Once a unit is owned by the SE, reverting to SE ownership may not be the intended behaviour — it is unclear whether a cancellation after the sell step should return units to the SE or to the original supplier/pool. This needs to be confirmed before implementing this change.

---

## 6. API & Webhook Changes

### 6.1 Step Data: `MurabahaSaleCompleted`

**File:** `app/Transformers/TraderHistoryTransformers/AbstractTraderHistoryTransformer.php:190`

**Current response (`MurabahaSaleCompleted` step):**
```json
{
  "warranty_document": { "url": "...selling_pledge_certificate...", "date": "..." },
  "settlement_details": { "is_commodities_settled": true, ... },
  "sell_confirmation_document": { "url": "...sell_confirmation_document...", "date": "..." }
}
```

**New response (Lynk orders):**
```json
{
  "sell_confirmation_document": { "url": "...sell_confirmation_document...", "date": "..." }
}
```

Changes:
- `warranty_document` is **removed entirely** for Lynk orders.
- `settlement_details` is **removed entirely**.
- `sell_confirmation_document` remains and is now always present for Lynk orders with a completed sell step — no settlement gate.

**Change to `includeMurabahaSaleCompleted()`:**
```php
// Remove warranty_document for Lynk
// Remove getLynkSettlementData() (was settlement_details + gated sell_confirmation_document)
// Add sell_confirmation_document directly
if ($this->traderOrder->provider === TraderEnum::Lynk) {
    unset($data['warranty_document']);
    $data['sell_confirmation_document'] = [
        'url' => $history ? formatMediaUrl(route('api.v1.admins.generate', [
            'document_type' => DocumentType::SELL_CONFIRMATION_DOCUMENT,
            'context' => ['trader_order_id' => $this->traderOrder->id],
        ])) : null,
        'date' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
    ];
}
```

### 6.2 Step Data: `MurabhaOfferIssued` (Sell Commitment Certificate)

**File:** `app/Transformers/TraderHistoryTransformers/AbstractTraderHistoryTransformer.php:168`

The `mpo_document` field currently returns the Sell Commitment Certificate URL. For new SE orders (Category A/B), this certificate is no longer generated.

**Detection logic:** If the associated local market order's units have `specialized_entity_id` set, it is a new SE order → `mpo_document` = null.

```php
// In includeMurabhaOfferIssued(), for Lynk orders:
$isSeOrder = $this->localMarketOrderHasSeUnits(); // check specialized_entity_id on units

$data['mpo_document'] = [
    'url' => ($history && !$isSeOrder) ? $this->getCertificateLURL(...) : null,
    'date' => ($history && !$isSeOrder) ? ... : null,
];
```

For legacy orders (Categories C/D) where the Sell Commitment Certificate was already generated, the existing media file will be returned as before.

### 6.3 Webhook: `warranty_document_url`

**File:** `app/Actions/Orders/Webhooks/FireWebhookWhenStatusIsMurabhaSaleCompletedAction.php:44`

Currently, `warranty_document_url` in the webhook payload points to `getSellingPledgeCertificateType()` which returns `SELLING_PLEDGE_CERTIFICATE` for Lynk.

After SE, `warranty_document_url` for Lynk must point to `SELL_CONFIRMATION_DOCUMENT` since `warranty_document` is removed from the step data and `sell_confirmation_document` is the authoritative source.

**Before:**
```php
'warranty_document_url' => route('api.v1.admins.generate', [
    'document_type' => DocumentType::getSellingPledgeCertificateType($traderOrder->provider),
    ...
]),
```

**After:**
```php
'warranty_document_url' => route('api.v1.admins.generate', [
    'document_type' => $traderOrder->provider === Trader::Lynk
        ? DocumentType::SELL_CONFIRMATION_DOCUMENT
        : DocumentType::getSellingPledgeCertificateType($traderOrder->provider),
    ...
]),
```

### 6.4 Frontend Impact

- Use `sell_confirmation_document` directly from the `MurabahaSaleCompleted` step data.
- `warranty_document` is no longer present for Lynk orders — remove any frontend usage of it for Lynk.
- Remove all logic gating the certificate behind `settlement_details.is_commodities_settled`.
- "Check Sell Status" button (calls `CheckSettlement` endpoint) must be removed.

---

## 7. Sell Confirmation Certificate — Buyer Name Logic

### 7.1 Detection: SE Order vs Legacy Order

Since "عملاء متفرقين" applies to legacy orders (Categories C and D) where some or all units went through the old TraderOrder flow, the certificate generator must detect which case applies.

**Detection rule:** Check whether any units for this order were sold through the old flow (have `last_purchasing_order_id` set but `specialized_entity_id = null`). Include soft-deleted units (`withTrashed`) because old-flow units are soft-deleted once re-sold.

```php
$hasOldFlowUnits = LocalMarketInventoryUnits::withTrashed()
    ->where('last_purchasing_order_id', $localMarketOrder->id)
    ->whereNull('specialized_entity_id')
    ->exists();

$buyerName = $hasOldFlowUnits
    ? 'عملاء متفرقين'
    : LocalMarketInventoryUnits::withTrashed()
          ->where('last_purchasing_order_id', $localMarketOrder->id)
          ->whereNotNull('specialized_entity_id')
          ->with('specializedEntity')
          ->first()
          ->specializedEntity
          ->name;
```

| Scenario | `specialized_entity_id` null units | Result |
|----------|-----------------------------------|--------|
| New SE order (A/B) | None | SE name |
| Partially sold legacy (C) | Some (old flow) | عملاء متفرقين |
| Fully sold legacy (D) | All | عملاء متفرقين |

### 7.2 `SellConfirmationDocumentPdf::prepareData()` Change

**File:** `app/Support/DocumentEngine/Generators/SellConfirmationDocumentPdf.php`

```php
protected function prepareData(): array
{
    $traderOrder = $this->getTraderOrder();
    $financeOrder = $traderOrder->order;
    $localMarketOrder = LocalMarketOrder::where('external_order_no', $traderOrder->reference)->firstOrFail();

    $hasOldFlowUnits = LocalMarketInventoryUnits::withTrashed()
        ->where('last_purchasing_order_id', $localMarketOrder->id)
        ->whereNull('specialized_entity_id')
        ->exists();

    if ($hasOldFlowUnits) {
        $buyerName = 'عملاء متفرقين';
    } else {
        $unit = LocalMarketInventoryUnits::withTrashed()
            ->where('last_purchasing_order_id', $localMarketOrder->id)
            ->with('specializedEntity')
            ->first();
        $buyerName = $unit->specializedEntity->name;
    }

    $data = [
        'trader_order_reference' => $traderOrder->reference,
        'amount'                 => $financeOrder->amount->convertAndFormatByDecimal(separator: ','),
        'borrower_name'          => $financeOrder->getBorrowerInfo()['name'],
        'buyer_name'             => $buyerName,
        'current_date'           => saudi_now('Y-m-d'),
        'current_time'           => saudi_now('H:i:s'),
    ];
    // ... products logic unchanged
}
```

Add `specializedEntity` relationship to `LocalMarketInventoryUnits`:

```php
public function specializedEntity()
{
    return $this->belongsTo(Company::class, 'specialized_entity_id');
}
```

### 7.3 Blade Template Change

**File:** `resources/views/local-commodity-market/sell-confirmation-certificate.blade.php:796`

**Before:**
```blade
إلى <span style="font-weight: bold">عملاء متفرقين.</span>
```

**After:**
```blade
إلى <span style="font-weight: bold">{{ $buyer_name }}.</span>
```

---

## 8. Data Migration (Post-Deployment)

### 8.1 Scope

The migration command handles all `local_market_orders` that were processed under the old flow and may have unsettled or partially settled units.

| Filter | Meaning |
|--------|---------|
| `status = 10` (CommoditiesSell) | Order completed the sell step |
| `is_commodities_settled = 0` | Settlement was never confirmed |

### 8.2 Artisan Command

Create: `app/Console/Commands/LocalMarket/SettlePendingOrdersCommand.php`
Register as: `artisan local-market:settle-pending-orders`

**Logic:**

```
$se = Company::getActiveSpecializedEntity()
      // If no SE found → abort entire command with error

foreach LocalMarketOrder where status=10 AND is_commodities_settled=0:

    $remainingUnits = LocalMarketInventoryUnits
        ->where('hold_for', order.id)
        ->whereNull('specialized_entity_id')  // not yet moved to SE

    if $remainingUnits exists:
        // Category C — partially sold: move remaining units to SE
        changeOrderUnitsOwnershipTo(order, SpecializedEntity, $se->id, SellCommodity)
        completeOrderUnits(order)   // soft-delete units
        log "moved X units to SE for order Y"
    else:
        // Category D — fully sold in old flow: nothing to move
        log "order Y fully sold in old flow, marking as settled"

    order.markAsSettled()   // set is_commodities_settled = true
```

> Run this command **before** dropping the `is_commodities_settled` column and `trader_order_settlements` table.

### 8.3 Deployment Sequence

```
Step 1: Deploy Phase 1 code:
        ✓ New SE company type & ownership type enums
        ✓ specialized_entity_id column migration
        ✓ PendingSellOrderStatus → SE default
        ✓ UnitService::changeOrderUnitsOwnershipTo → populates specialized_entity_id
        ✓ is_commodities_settled column still present (not dropped yet)

Step 2: Seed default SE company (BMC001)

Step 3: Run: php artisan local-market:settle-pending-orders
        → Moves remaining units of all unsettled orders to SE
        → Marks all status=10, is_commodities_settled=0 orders as settled

Step 4: Verify: no orders remain with status=10 AND is_commodities_settled=0

Step 5: Deploy Phase 2 code:
        ✓ Drop is_commodities_settled from local_market_orders
        ✓ Drop trader_order_settlements table
        ✓ All settlement infrastructure removed
        ✓ API: warranty_document → Sell Confirmation for Lynk
        ✓ Webhook: warranty_document_url → Sell Confirmation for Lynk
        ✓ Sell Commitment Certificate removed for new orders
        ✓ Frontend updated
```

---

## 9. Settlement Infrastructure — Files to Delete

```
app/Actions/LocalMarket/CheckOrderSettlementAction.php
app/Actions/Contracts/LocalMarket/CheckOrderSettlement.php
app/Actions/Orders/TraderOrders/CheckTraderOrderSettlementAction.php
app/Actions/Orders/TraderOrders/InitiateTraderOrderSettlementAction.php
app/Actions/Contracts/Orders/TraderOrders/CheckTraderOrderSettlement.php
app/Actions/Contracts/Orders/TraderOrders/InitiateTraderOrderSettlement.php
app/Jobs/TraderOrder/CheckTraderOrderSettlementJob.php
app/Models/TraderOrderSettlement.php
app/Transformers/TraderOrderSettlementTransformer.php
app/Enums/TraderOrderSettlementStatus.php
app/Http/Controllers/Api/V1/Admin/Lenders/Orders/TraderOrders/CheckSettlement.php
tests/Feature/Endpoints/Api/V1/Admin/Lenders/Orders/TraderOrders/CheckSettlementTest.php
```

---

## 10. Open Items

| # | Question | Status |
|---|----------|--------|
| 1 | How is the default SE resolved? | ✅ Query `companies` for first `status=Approved, type=SpecializedEntity`. Throw if none found. |
| 2 | One SE per order? | ✅ Yes. Certificate shows SE name for new orders, "عملاء متفرقين" for legacy orders only. |
| 3 | SE notification for received units? | ✅ No notification. `specialized_entity_id` on inventory units is the source of truth. |

---

## 11. Summary of All File Changes

### New Files

| File | Purpose |
|------|---------|
| `database/migrations/..._add_specialized_entity_id_to_local_market_inventory_units.php` | Add SE FK column |
| `database/migrations/..._drop_is_commodities_settled_from_local_market_orders.php` | Remove settled flag (Phase 2) |
| `database/migrations/..._drop_trader_order_settlements_table.php` | Remove table (Phase 2) |
| `app/Console/Commands/LocalMarket/SettlePendingOrdersCommand.php` | Data migration command |
| `database/seeders/SpecializedEntitySeeder.php` | Seed default SE (BMC001) |

### Modified Files

| File | Change |
|------|--------|
| `app/Enums/CompanyType.php` | Add `SpecializedEntity = 6` |
| `app/Enums/LocalMarket/OwnershipTypes.php` | Add `SpecializedEntity = 5` |
| `app/Enums/LocalMarket/UnitOwnershipAction.php` | Update `SellCommodity` comment |
| `app/Models/Company.php` | Add `getActiveSpecializedEntity()` static method |
| `app/Jobs/LocalMarket/states/PendingSellOrderStatus.php` | Switch to SE ownership |
| `app/Services/LocalMarket/UnitService.php` | Populate `specialized_entity_id` in bulk update |
| `app/Models/LocalMarketInventoryUnits.php` | Add `specialized_entity_id` to `$fillable`; add `specializedEntity()` relationship; update `getLastValidOwner()` |
| `app/Models/LocalMarketOrder.php` | Remove `markAsSettled()`, `isCommoditiesSettled()`; remove `is_commodities_settled` from `$fillable` and `$casts` |
| `app/Models/TraderOrder.php` | Remove `settlements()`, `latestSettlement()`, `isCommoditiesSettled()`, `hasPendingSettlementCheck()`, `canBeSettled()`; remove `TraderOrderSettlementStatus` import |
| `app/Support/Traders/Clients/LynkClient.php` | Remove `checkOrderSettlement()` |
| `app/Transformers/TraderHistoryTransformers/AbstractTraderHistoryTransformer.php` | `warranty_document` → Sell Confirmation for Lynk; remove `settlement_details`; remove `sell_confirmation_document` field; `mpo_document` → null for SE orders |
| `app/Actions/Orders/Webhooks/FireWebhookWhenStatusIsMurabhaSaleCompletedAction.php` | `warranty_document_url` → Sell Confirmation for Lynk |
| `app/Support/DocumentEngine/Generators/SellConfirmationDocumentPdf.php` | Add `buyer_name` logic (SE name vs "عملاء متفرقين") |
| `resources/views/local-commodity-market/sell-confirmation-certificate.blade.php` | Use `{{ $buyer_name }}` variable |
| `routes/api/admin.php` | Remove `CheckSettlement` route |
| `docker-compose.yml` | Remove `local_market_commodities_settlement` queue worker |
| `docker-compose.mac.yml` | Remove `local_market_commodities_settlement` queue worker |

### Deleted Files

```
app/Actions/LocalMarket/CheckOrderSettlementAction.php
app/Actions/Contracts/LocalMarket/CheckOrderSettlement.php
app/Actions/Orders/TraderOrders/CheckTraderOrderSettlementAction.php
app/Actions/Orders/TraderOrders/InitiateTraderOrderSettlementAction.php
app/Actions/Contracts/Orders/TraderOrders/CheckTraderOrderSettlement.php
app/Actions/Contracts/Orders/TraderOrders/InitiateTraderOrderSettlement.php
app/Jobs/TraderOrder/CheckTraderOrderSettlementJob.php
app/Models/TraderOrderSettlement.php
app/Transformers/TraderOrderSettlementTransformer.php
app/Enums/TraderOrderSettlementStatus.php
app/Http/Controllers/Api/V1/Admin/Lenders/Orders/TraderOrders/CheckSettlement.php
tests/Feature/Endpoints/Api/V1/Admin/Lenders/Orders/TraderOrders/CheckSettlementTest.php
```
