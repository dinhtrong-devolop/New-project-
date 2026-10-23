<?php

namespace Tests\Feature;

use App\Models\ProductInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_shopkey_tables_and_columns_exist(): void
    {
        foreach ([
            'users',
            'personal_access_tokens',
            'categories',
            'products',
            'product_images',
            'product_inventories',
            'carts',
            'cart_items',
            'vouchers',
            'voucher_usages',
            'orders',
            'order_items',
            'order_deliveries',
            'wallets',
            'wallet_transactions',
            'card_deposits',
            'bank_accounts',
            'bank_deposits',
            'bank_transactions',
            'site_settings',
            'support_conversations',
            'support_messages',
            'reviews',
            'notifications',
            'activity_logs',
            'cron_jobs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('product_inventories', [
            'encrypted_payload',
            'payload_fingerprint',
            'status',
            'reservation_token',
            'sold_at',
        ]));

        $this->assertTrue(Schema::hasColumns('wallet_transactions', [
            'amount',
            'balance_before',
            'balance_after',
            'reference_type',
            'reference_id',
            'idempotency_key',
        ]));
    }

    public function test_inventory_payload_is_encrypted_at_rest(): void
    {
        $inventory = ProductInventory::factory()->create([
            'encrypted_payload' => ['key' => 'AAAAA-BBBBB-CCCCC-DDDDD-EEEEE'],
        ]);

        $storedValue = DB::table('product_inventories')
            ->where('id', $inventory->id)
            ->value('encrypted_payload');

        $this->assertStringNotContainsString('AAAAA-BBBBB', $storedValue);
        $this->assertSame(
            'AAAAA-BBBBB-CCCCC-DDDDD-EEEEE',
            $inventory->fresh()->encrypted_payload['key']
        );
    }
}
