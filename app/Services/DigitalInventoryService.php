<?php

namespace App\Services;

use App\Enums\InventoryStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DigitalInventoryService
{
    /** @return array{created: int, duplicates: int} */
    public function import(Product $product, string $rawInventory): array
    {
        $values = collect(preg_split('/\R/u', $rawInventory) ?: [])
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values();

        $created = 0;

        DB::transaction(function () use ($product, $values, &$created): void {
            foreach ($values as $value) {
                $fingerprint = hash('sha256', mb_strtolower($value));

                $inventory = $product->inventories()->firstOrCreate(
                    ['payload_fingerprint' => $fingerprint],
                    [
                        'inventory_type' => $product->product_type,
                        'encrypted_payload' => $this->payload($product, $value),
                        'display_hint' => $this->displayHint($product, $value),
                        'status' => InventoryStatus::Available,
                    ]
                );

                $created += $inventory->wasRecentlyCreated ? 1 : 0;
            }
        });

        return ['created' => $created, 'duplicates' => $values->count() - $created];
    }

    /** @return array<string, mixed> */
    private function payload(Product $product, string $value): array
    {
        if ($product->product_type !== ProductType::Account) {
            return ['key' => $value];
        }

        if (str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);

            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        $parts = array_pad(array_map('trim', explode('|', $value, 4)), 4, null);

        return array_filter([
            'email' => $parts[0],
            'username' => $parts[1],
            'password' => $parts[2],
            'additional_information' => $parts[3],
        ], fn ($part) => filled($part));
    }

    private function displayHint(Product $product, string $value): string
    {
        if ($product->product_type === ProductType::Account) {
            $identifier = trim(explode('|', $value, 2)[0]);

            if (str_contains($identifier, '@')) {
                [$name, $domain] = explode('@', $identifier, 2);

                return mb_substr($name, 0, 2).'***@'.$domain;
            }
        }

        return str_repeat('•', min(10, max(4, mb_strlen($value) - 4))).mb_substr($value, -4);
    }
}
