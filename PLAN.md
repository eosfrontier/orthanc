# Orthanc v3 — Virtual Storage API Plan

---

## Context (Technical)

The existing Orthanc project is a PHP REST API managing characters, skills, bank, and events for a LARP. We need a **Virtual Storage System** so characters can hold stackable digital items and in-game currency, transfer items to each other, and GMs can manage item types and inventories.

Orthanc v3 is a **Laravel 12 API** living at `orthanc/v3/`. All storage endpoints are built here from the start — no v2 storage endpoints exist. Existing v2 endpoints (joomla, characters, etc.) remain untouched.

**External system access:** Any trusted external system (prop scanners, event scripts, puzzle integrations, etc.) can interact with the storage directly via the Orthanc `/v3/storage/` endpoints using **Laravel Sanctum** bearer tokens with ability-based scopes. No Joomla session is required. External systems call Orthanc v3 directly (bypassing the storage-app layer) and can mint, burn, transfer, and query inventory programmatically. Tokens are issued via artisan commands by whoever administers the Orthanc instance.

**Tech stack:** Laravel 12 (Orthanc v3).
**Auth flow:** Storage app calls Orthanc's `GET /v2/joomla/` (returns `{id, groups[]}`) — this existing v2 endpoint is kept for Joomla session resolution. All storage operations go through `/v3/storage/` using a Sanctum bearer token.

---

## Critical Reference Files

| File | Why it matters |
|---|---|
| `includes/classes/Bank.php` | Old append-only log pattern for reference (**deprecated** — superseded by `ecc_storage_log`) |
| `includes/classes/Joomla.php` | `get_joomla_user_and_group()` — returns `{id, groups[]}` |
| `v2/joomla/index.php` + `_get.php` | Live Joomla proxy endpoint — kept in v2; storage-app still calls this for session auth |

**Old bank system:** `Bank.php`, `Atm.php`, `/v2/bank/`, and `/v2/atm/` handle the legacy currency system. The Virtual Storage system is completely independent — it starts fresh with its own `ecc_storage_log` table and does not migrate any data from `bank_logging`. The old bank endpoints will be deprecated separately once storage is live.

**Why v3 directly (no v2 storage endpoints):** The storage system is brand new — nothing consumes `/v2/storage/` because it doesn't exist yet. Building v2 first and then migrating to v3 would be double work. We build the storage API once, in Laravel 12 with Sanctum auth, at `/v3/storage/`.

---

## Phase 1 — Database Schema

All tables use `ecc_` prefix. Unix epoch integers for timestamps.

### `ecc_storage_categories`
```sql
CREATE TABLE ecc_storage_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    created_at  INT UNSIGNED NOT NULL,
    created_by  INT UNSIGNED NOT NULL,
    deleted_at  INT UNSIGNED NULL,
    is_system   TINYINT(1)   NOT NULL DEFAULT 0
);
```
- Soft-delete so existing item types referencing a deleted category remain consistent in the audit log
- `get_all()` excludes soft-deleted records by default (`WHERE deleted_at IS NULL`)
- Case-insensitive uniqueness of `name` enforced at app level
- `is_system = 1` protects a category from rename or deletion; used by the seeded "Currency" category

**Seed INSERT (run after table creation):**
```sql
INSERT INTO ecc_storage_categories (id, name, created_at, created_by, is_system)
VALUES (1, 'Currency', UNIX_TIMESTAMP(), 0, 1);
```

### `ecc_storage_item_types`
```sql
CREATE TABLE ecc_storage_item_types (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL UNIQUE,
    description     TEXT          NULL,
    icon            VARCHAR(100)  NULL,
    category_id     INT UNSIGNED  NOT NULL,
    stackable       TINYINT(1)    NOT NULL DEFAULT 1,
    max_quantity    INT UNSIGNED  NULL,                    -- per-character cap; NULL = unlimited
    is_system       TINYINT(1)    NOT NULL DEFAULT 0,
    created_at      INT UNSIGNED  NOT NULL,
    created_by      INT UNSIGNED  NOT NULL,
    updated_at      INT UNSIGNED  NULL,
    deleted_at      INT UNSIGNED  NULL,
    CONSTRAINT fk_item_type_category
        FOREIGN KEY (category_id) REFERENCES ecc_storage_categories(id)
);
```
- `category_id` is NOT NULL — every item type must belong to a category
- `max_quantity` is optional (NULL = no cap). When set, no character may hold more than this quantity of the item. Enforced at both Orthanc v3 (mint) and storage-app (mint + transfer receive) layers.
- `is_system = 1` marks rows that the app must protect from rename or deletion. Only Sonuren ships with this set. There is no user-managed currency flag — Sonuren is the single, permanent in-game currency.
- Soft-delete via `deleted_at` preserves audit log references
- `get_all()` excludes soft-deleted records by default (`WHERE deleted_at IS NULL`); deleted item type names still resolve correctly in log/inventory JOINs because the FK row is retained
- Case-insensitive uniqueness of `name` enforced at app level (DB UNIQUE constraint handles exact duplicates)

**Seed INSERT (run after table creation):**
```sql
INSERT INTO ecc_storage_item_types (id, name, description, category_id, stackable, is_system, created_at, created_by)
VALUES (1, 'Sonuren', 'In-game currency', 1, 1, 1, UNIX_TIMESTAMP(), 0);
```

### `ecc_storage_inventory`
```sql
CREATE TABLE ecc_storage_inventory (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    character_id   INT UNSIGNED NOT NULL,
    item_type_id   INT UNSIGNED NOT NULL,
    quantity       INT          NOT NULL DEFAULT 0,
    updated_at     INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_char_item (character_id, item_type_id)
);
```
- One row per (character, item_type) — fast O(1) balance reads
- Never goes below 0 (enforced at app level)

### `ecc_storage_log`
```sql
CREATE TABLE ecc_storage_log (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_type_id     INT UNSIGNED NOT NULL,
    quantity         INT          NOT NULL,
    source_char_id   INT UNSIGNED NULL,
    target_char_id   INT UNSIGNED NULL,
    actor_joomla_id  INT UNSIGNED NOT NULL,  -- 0 = external system (app token); store token name in note field
    action           VARCHAR(30)  NOT NULL,  -- 'mint' | 'burn' | 'transfer' | 'broker_fee' | 'label_burn' | 'label_claim'
    brokered         TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = via broker; hidden from player logs
    note             VARCHAR(255) NULL,
    created_at       INT UNSIGNED NOT NULL,

    INDEX idx_source_char (source_char_id, created_at),
    INDEX idx_target_char (target_char_id, created_at),
    INDEX idx_item_type   (item_type_id, created_at)
);
```
- Append-only
- Single row per operation (both source + target in one row)
- `actor_joomla_id` is the GM or player who initiated — separate from source character. External systems (app token auth) use `0` as sentinel; the token name is stored in `note`
- Compound indexes with `created_at` support filtered + sorted paginated queries on 1M+ rows without full table scans

### `ecc_storage_settings`
```sql
CREATE TABLE ecc_storage_settings (
    key_name   VARCHAR(50)  NOT NULL PRIMARY KEY,
    value      VARCHAR(255) NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL  -- Joomla user ID (0 = system)
);

INSERT INTO ecc_storage_settings (key_name, value, updated_at, updated_by)
VALUES ('transfers_enabled', '1', UNIX_TIMESTAMP(), 0);
```
- `transfers_enabled = '1'` means open; `'0'` means locked
- GM-only writes; readable by all authenticated users

### `ecc_storage_label_tokens`

```sql
CREATE TABLE ecc_storage_label_tokens (
    id               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    token            CHAR(36)      NOT NULL UNIQUE,        -- UUID v4
    item_type_id     INT UNSIGNED  NOT NULL,
    quantity         INT UNSIGNED  NOT NULL DEFAULT 1,
    note             VARCHAR(255)  NULL,
    source           ENUM('burn','mint') NOT NULL DEFAULT 'mint',
    source_char_id   INT UNSIGNED  NULL,                   -- char whose inventory was burned (source='burn')
    created_by       INT UNSIGNED  NOT NULL,               -- Joomla user ID of creator
    created_at       INT UNSIGNED  NOT NULL,
    claimed_by       INT UNSIGNED  NULL,                   -- character_id that redeemed it
    claimed_at       INT UNSIGNED  NULL,
    expires_at       INT UNSIGNED  NULL                    -- optional expiry
);
```

- `source = 'burn'`: items already deducted from `source_char_id` at print time; on claim, items are minted to claimer
- `source = 'mint'`: no prior deduction; items are minted fresh on claim (GM standalone flow)
- Tokens are single-use; once `claimed_by` is set the token is inert

---

## Phase 2 — Orthanc v3 API (Laravel 12 + Sanctum)

### Why Laravel?

- `apiResource` routes, `FormRequest` validation, and API Resource transformers instead of manual dispatcher files
- **Laravel Sanctum** for auth — named per-consumer tokens with ability-based scopes
- Same `ecc_` MySQL database

### New tables (v3-only)

```sql
-- Sanctum token store (generated by php artisan install:api)
-- personal_access_tokens (standard Sanctum schema)

-- Named API consumers (one row per trusted external system)
CREATE TABLE api_consumers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    created_at INT UNSIGNED NOT NULL
);
```

### Authentication

v3 uses **Laravel Sanctum** with a custom `ApiConsumer` model (`HasApiTokens`). Joomla session/cookie auth is **not** used in v3 — that stays in the storage-app layer.

**Token abilities (scopes):**

| Ability | Grants |
|---|---|
| `storage:read` | GET on all endpoints |
| `storage:write` | POST/PATCH/DELETE on inventory, transfer, label claim |
| `storage:admin` | Create/update/delete categories & item types; bulk mint; PUT settings; create labels |

**Token issuance — artisan commands only** (no public endpoint):

```bash
php artisan orthanc:issue-token "storage-app" --abilities=storage:read,storage:write
php artisan orthanc:revoke-token "storage-app" {token_id}
```

### Route structure (`routes/api.php`)

All routes: `prefix('v3/storage')` + `middleware('auth:sanctum')` + `RequireAbility` middleware per group.

```
READ   (storage:read)    — GET categories, item-types, inventory, log, settings, labels
WRITE  (storage:write)   — POST/PATCH/DELETE inventory; POST transfer, labels/claim
ADMIN  (storage:admin)   — POST/PUT/DELETE categories & item-types; POST inventory/bulk; PUT settings; POST labels
```

`Route::apiResource` is used for `categories` and `item-types` (clean CRUD). Inventory uses explicit method routes (mint/burn/adjust are non-standard verbs on the same resource).

### Laravel structure

```
v3/
    app/
        Console/Commands/
            IssueToken.php          -- artisan orthanc:issue-token
            RevokeToken.php         -- artisan orthanc:revoke-token
        Enums/
            TokenAbility.php        -- storage:read, storage:write, storage:admin constants
        Http/
            Controllers/Api/
                CategoryController.php
                ItemTypeController.php
                InventoryController.php
                TransferController.php
                LogController.php
                SettingsController.php
                LabelController.php
            Middleware/
                RequireAbility.php  -- checks tokenCan(); aborts 403
            Requests/               -- one FormRequest per write operation
            Resources/              -- one API Resource per model
        Models/
            ApiConsumer.php         -- HasApiTokens; backed by api_consumers table
            StorageCategory.php     -- ecc_storage_categories; scopeActive
            StorageItemType.php     -- ecc_storage_item_types; scopeActive; belongsTo Category
            StorageInventory.php    -- ecc_storage_inventory; belongsTo ItemType
            StorageLog.php          -- ecc_storage_log
            StorageSetting.php      -- ecc_storage_settings; PK = key_name (string)
            StorageLabelToken.php   -- ecc_storage_label_tokens
        Services/
            InventoryService.php    -- mint/burn/adjust/bulk inside DB::transaction; max_quantity cap (422)
            TransferService.php     -- brokered logic; transfers_enabled gate (423); receiver cap check
            LabelService.php        -- token creation, claim flow
    database/migrations/
        -- ecc_storage_categories + seed
        -- ecc_storage_item_types + seed
        -- ecc_storage_inventory
        -- ecc_storage_log
        -- ecc_storage_settings + seed
        -- ecc_storage_label_tokens
        -- personal_access_tokens (from install:api)
        -- api_consumers
    routes/
        api.php
```

All `ecc_` models use `$timestamps = false` (existing columns are Unix integers, not Laravel datetimes). Each model sets `$table` explicitly to the `ecc_` name.

### Error handling

Registered in `bootstrap/app.php`:

- `DomainException` → `{ "message": "..." }` with code from exception (default 422)
- `ModelNotFoundException` → 404
- `AuthenticationException` → 401

Standard response shape: `{ "data": { ... } }` — Laravel API Resource default.

### Coexistence with v2

- v2 files are not touched; v3 does not `require` any v2 files
- v2 auth (`eos_tokens`) and v3 auth (`personal_access_tokens` + `api_consumers`) are completely independent
- Apache: alias the `orthanc/v3/public/` directory so Laravel's front controller handles `/v3/` requests; v2 continues resolving via filesystem `index.php` dispatchers

### Endpoint spec

#### Categories — `CategoryController` (apiResource)
| Verb | Route | Action |
|---|---|---|
| GET | `/v3/storage/categories` | List all active categories (or `/{id}` for one) |
| POST | `/v3/storage/categories` | Create new category |
| PUT | `/v3/storage/categories/{id}` | Rename category |
| DELETE | `/v3/storage/categories/{id}` | Soft-delete — rejected if any active item types reference it |

- `update()` and `destroy()`: check `is_system = 1` → return 403 if true
- Index/show include `is_system` field so callers know which rows are protected

#### Item Types — `ItemTypeController` (apiResource)
| Verb | Route | Action |
|---|---|---|
| GET | `/v3/storage/item-types` | List all (or `/{id}` for one) |
| POST | `/v3/storage/item-types` | Create new item type |
| PUT | `/v3/storage/item-types/{id}` | Update item type |
| DELETE | `/v3/storage/item-types/{id}` | Soft-delete item type |

- `update()` and `destroy()`: check `is_system = 1` → return 403 if true
- Index/show include `is_system` and `max_quantity` fields
- `store()` and `update()`: accept optional `max_quantity` (positive integer or null); validated via FormRequest
- Index/show JOIN `ecc_storage_categories` to return `category_id` + `category_name` together

#### Inventory — `InventoryController`
| Verb | Route | Action |
|---|---|---|
| GET | `/v3/storage/inventory?char_id=N` | Full inventory joined with item type name/category |
| POST | `/v3/storage/inventory` | Mint items into a character's inventory |
| PATCH | `/v3/storage/inventory/{id}` | Adjust quantity by delta |
| DELETE | `/v3/storage/inventory/{id}` | Burn/remove items |

All writes use `DB::transaction()` and insert into `ecc_storage_log`.

**Max-quantity cap enforcement in mint:** Before inserting/updating the inventory row, look up `max_quantity` from `ecc_storage_item_types`. If set and `current_quantity + qty > max_quantity`, throw a domain exception (HTTP 422). This guard runs inside the transaction so it rolls back cleanly.

#### Inventory Bulk — `InventoryController@bulk`
| Verb | Route | Action |
|---|---|---|
| POST | `/v3/storage/inventory/bulk` | Mint the same item+qty to multiple characters (admin only) |

Each character is minted in its own independent transaction — partial success is acceptable. Returns:
```json
{
  "total_attempted": 4, "total_succeeded": 3, "total_failed": 1,
  "succeeded": [{"char_id": 12, "new_quantity": 150}],
  "failed":    [{"char_id": 23, "error": "..."}]
}
```
HTTP status: `201` (all OK) / `207` (partial) / `422` (all failed).
Guard: reject `count($char_ids) > 200` with 400.

#### Transfer — `TransferController`
| Verb | Route | Action |
|---|---|---|
| POST | `/v3/storage/transfer` | Transfer items between two characters |

Uses `DB::transaction()`. Validates sender has sufficient quantity. Checks receiver's `max_quantity` cap — if `receiver_current + qty > max_quantity`, reject with 422. Inserts one log row with `action = 'transfer'`.

**Brokered transfer:** If `brokered = true` in request body, a 20 Sonuren broker fee is deducted from the sender in the same transaction (log row: `action = 'broker_fee'`, `brokered = 1`). The transfer log row is also written with `brokered = 1`. Both the fee deduction and the transfer are atomic. Fails with 422 if the sender has fewer than 20 Sonuren for the fee. The `BROKER_FEE_SONUREN = 20` constant is defined in the Laravel config.

#### Log — `LogController`
| Verb | Route | Action |
|---|---|---|
| GET | `/v3/storage/log` | Filtered audit log (`?char_id=N`, `?item_type_id=N`) |

**Brokered visibility:**
- Player-facing queries add `AND brokered = 0` so brokered rows are never returned to players.
- GM-facing queries include all rows regardless of `brokered`.

All log queries use `ORDER BY created_at DESC LIMIT ? OFFSET ?`. Character queries use `UNION ALL` of two indexed sub-queries (one on `source_char_id`, one on `target_char_id`) rather than `OR`, so each sub-query hits its own index cleanly:
```sql
SELECT * FROM ecc_storage_log WHERE source_char_id = ? ORDER BY created_at DESC
UNION ALL
SELECT * FROM ecc_storage_log WHERE target_char_id = ? ORDER BY created_at DESC
ORDER BY created_at DESC LIMIT 50 OFFSET 0
```

#### Settings — `SettingsController`
| Verb | Route | Action |
|---|---|---|
| GET | `/v3/storage/settings` | Returns all settings as `{ transfers_enabled: bool }` |
| PUT | `/v3/storage/settings` | Update a setting (admin only) — body: `{ "key": "transfers_enabled", "value": "0" }` |

- `set()` rejects unknown keys with 400
- GET is publicly readable (player UI needs to know whether to show the transfer form)

#### Labels — `LabelController`

| Endpoint | Verb | Description |
|---|---|---|
| `/v3/storage/labels` | POST | Create label token(s) |
| `/v3/storage/labels` | GET | `?token=<uuid>` — single token info; `?unclaimed=1` — all unclaimed tokens (admin) |
| `/v3/storage/labels/claim` | POST | Redeem token → mint to character |

**`LabelService::create(array $data, int $actor_joomla_id): array`**
- If `source = 'burn'`: burns items from `source_char_id` + inserts token in one `DB::transaction()`; inserts a log row with `action = 'label_burn'`
- If `source = 'mint'`: inserts token only, no inventory change; no log row written until claimed
- Returns array of created token records

**`LabelService::getByToken(string $token): ?StorageLabelToken`**
- Returns token with eager-loaded `itemType`; returns `null` if not found

**`LabelService::getUnclaimed(): Collection`**
- Returns all rows where `claimed_by IS NULL`, ordered by `created_at DESC`
- Eager-loads `itemType` name and creator info

**`LabelService::claim(string $token, int $char_id, int $actor_joomla_id): array`**
1. Look up token — 404 if not found, 410 if `claimed_by` already set or `expires_at` < now
2. Call `InventoryService::mint(...)` in a transaction; log row uses `action = 'label_claim'`
3. Set `claimed_by = char_id`, `claimed_at = now()` in the same transaction
4. Returns updated inventory row

---

## Implementation Order

### Step 1 — Laravel scaffold ([orthanc#96](https://github.com/eosfrontier/orthanc/issues/96))
- [x] V3-00 ([orthanc#68](https://github.com/eosfrontier/orthanc/issues/68)): `composer create-project laravel/laravel:^12.0 orthanc/v3` + `composer require laravel/sanctum` + `php artisan install:api`
- [x] V3-01 ([orthanc#69](https://github.com/eosfrontier/orthanc/issues/69)): `.env` (same DB as existing Orthanc) + `config/auth.php` (api_consumers guard) + disable web routes

### Step 2 — Auth + middleware ([orthanc#97](https://github.com/eosfrontier/orthanc/issues/97))
- [x] V3-02 ([orthanc#70](https://github.com/eosfrontier/orthanc/issues/70)): `api_consumers` migration + `ApiConsumer` model (`HasApiTokens`)
- [ ] V3-03 ([orthanc#71](https://github.com/eosfrontier/orthanc/issues/71)): `TokenAbility` enum + `RequireAbility` middleware; register in `bootstrap/app.php`
- [ ] V3-04 ([orthanc#72](https://github.com/eosfrontier/orthanc/issues/72)): `IssueToken` + `RevokeToken` artisan commands

### Step 3 — DB migrations ([orthanc#98](https://github.com/eosfrontier/orthanc/issues/98))
- [ ] V3-DB-00 ([orthanc#73](https://github.com/eosfrontier/orthanc/issues/73)): `ecc_storage_categories` migration (with `is_system` column) + seeder for "Currency" category row
- [ ] V3-DB-01 ([orthanc#74](https://github.com/eosfrontier/orthanc/issues/74)): `ecc_storage_item_types` migration (with `category_id` FK, NOT NULL; `is_system` column; `max_quantity INT UNSIGNED NULL`) + seeder for Sonuren row (`id=1, is_system=1`)
- [ ] V3-DB-02 ([orthanc#75](https://github.com/eosfrontier/orthanc/issues/75)): `ecc_storage_inventory` migration
- [ ] V3-DB-03 ([orthanc#76](https://github.com/eosfrontier/orthanc/issues/76)): `ecc_storage_log` migration (with `brokered` column)
- [ ] V3-DB-04 ([orthanc#77](https://github.com/eosfrontier/orthanc/issues/77)): `ecc_storage_settings` migration + seeder (`transfers_enabled = 1`)
- [ ] V3-DB-05 ([orthanc#78](https://github.com/eosfrontier/orthanc/issues/78)): Write rollback SQL

### Step 4 — Models + Resources + FormRequests ([orthanc#100](https://github.com/eosfrontier/orthanc/issues/100))
- [ ] V3-05 ([orthanc#82](https://github.com/eosfrontier/orthanc/issues/82)): All Eloquent models (`$timestamps = false`, explicit table names, scopes, relations): `StorageCategory`, `StorageItemType`, `StorageInventory`, `StorageLog`, `StorageSetting`, `StorageLabelToken`
- [ ] V3-06 ([orthanc#83](https://github.com/eosfrontier/orthanc/issues/83)): All API Resources (one per model) + all FormRequests (one per write operation)

### Step 5 — Services ([orthanc#101](https://github.com/eosfrontier/orthanc/issues/101))
- [ ] V3-07 ([orthanc#84](https://github.com/eosfrontier/orthanc/issues/84)): `InventoryService` — mint/burn/adjust/bulk inside `DB::transaction()`; `max_quantity` cap (422) + unit tests
- [ ] V3-08 ([orthanc#85](https://github.com/eosfrontier/orthanc/issues/85)): `TransferService` — brokered logic; `transfers_enabled` gate (423); receiver cap check + unit tests
- [ ] V3-09 ([orthanc#86](https://github.com/eosfrontier/orthanc/issues/86)): `LabelService` — token creation, claim flow + unit tests

### Step 6 — Controllers + routes ([orthanc#102](https://github.com/eosfrontier/orthanc/issues/102))
- [ ] V3-10 ([orthanc#87](https://github.com/eosfrontier/orthanc/issues/87)): `CategoryController` + `ItemTypeController` + apiResource routes + feature tests
- [ ] V3-11 ([orthanc#88](https://github.com/eosfrontier/orthanc/issues/88)): `InventoryController` + feature tests (bulk, cap enforcement, partial success 207)
- [ ] V3-12 ([orthanc#89](https://github.com/eosfrontier/orthanc/issues/89)): `TransferController` + feature tests (brokered, transfers locked 423, cap exceeded)
- [ ] V3-13 ([orthanc#90](https://github.com/eosfrontier/orthanc/issues/90)): `LogController` + UNION ALL query (verify index hits with EXPLAIN) + feature tests
- [ ] V3-14 ([orthanc#91](https://github.com/eosfrontier/orthanc/issues/91)): `SettingsController` + feature tests
- [ ] V3-15 ([orthanc#92](https://github.com/eosfrontier/orthanc/issues/92)): Apache/vhost config: serve `orthanc/v3/public/` alongside v2; confirm v2 joomla/chars endpoints still resolve
- Smoke-test all endpoints with curl using an issued Sanctum token

### Step 7 — Label Printing ([orthanc#103](https://github.com/eosfrontier/orthanc/issues/103))
- [ ] LBL-01 ([orthanc#93](https://github.com/eosfrontier/orthanc/issues/93)): `ecc_storage_label_tokens` migration (in `v3/database/migrations/`) + rollback SQL
- [ ] LBL-02 ([orthanc#94](https://github.com/eosfrontier/orthanc/issues/94)): `LabelController` + `LabelService` (already scaffolded in Step 5) + feature tests
- [ ] LBL-03 ([orthanc#95](https://github.com/eosfrontier/orthanc/issues/95)): Label routes in `routes/api.php`: POST `/v3/storage/labels`, GET `/v3/storage/labels`, POST `/v3/storage/labels/claim`

---

## Verification / Testing

### Orthanc v3 smoke test

```bash
cd v3 && php artisan test

# Issue a token and hit a live endpoint
TOKEN=$(php artisan orthanc:issue-token "smoke-test" --abilities=storage:read | tail -1)
curl -H "Authorization: Bearer $TOKEN" https://<host>/v3/storage/categories

# Confirm v2 joomla endpoint is untouched
curl -H "token: <v2-token>" https://<host>/v2/joomla/
```

### End-to-end checks

0. **Schema & seeding:** Run DB migrations → `ecc_storage_item_types` has `is_system` column (no `is_currency`); `ecc_storage_categories` has `is_system` column. Sonuren row exists (`id = 1, is_system = 1`); Currency category row exists (`id = 1, is_system = 1`).

0b. **Categories:** `POST /v3/storage/categories` → creates row. `POST /v3/storage/item-types` without `category_id` → 422. With valid `category_id` → 201 and response includes `category_name`. `DELETE /v3/storage/categories/1` while item types reference it → rejected with 409. `PUT /v3/storage/categories/1` (Currency) → 403. `DELETE /v3/storage/categories/1` → 403.

0c. **Sonuren guards:** `PUT /v3/storage/item-types/1` (Sonuren) → 403. `DELETE /v3/storage/item-types/1` → 403.

1. **Endpoints:** curl each endpoint with a valid Sanctum bearer token.
   - Verify `ecc_storage_inventory` rows created/updated correctly.
   - Verify `ecc_storage_log` is append-only and action field is correct.
   - Test transfer with insufficient quantity → should return 4xx, no DB change.

2. **Brokered transfer — happy path:** Player A has 50 Sonuren + 5 Health Potions. Transfer with `brokered=true` → Player A now has 30 Sonuren (−20 fee) + 3 Health Potions; Player B has 2 Health Potions. GM audit log: 2 entries — `broker_fee` + `transfer`, both with `brokered=1`.

3. **Brokered transfer — insufficient fee:** Sender with only 10 Sonuren → server returns 422.

4. **Labels:** GM creates mint label → token row created. Player claims → inventory updated, token marked claimed. Double claim → 410.
