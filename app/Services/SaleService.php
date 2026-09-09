<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\SaleCreated;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaleService
{
    public function createSale(array $data, int $userId): Sale
    {
        $subtotal = 0;
        $saleItems = [];

        foreach ($data['items'] as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('org_id', $data['org_id'])
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => [__('messa.sale_item_invalid_org')],
                ]);
            }

            $lineTotal = round($product->price * $item['quantity'], 2);
            $subtotal += $lineTotal;

            $saleItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'total_price' => $lineTotal,
                'preparation_status' => $product->requires_preparation ? SaleItem::PREPARATION_PENDING : null,
            ];
        }

        $discount = round((float) ($data['discount'] ?? 0), 2);
        $total = round($subtotal - $discount, 2);

        $requiresPreparation = collect($saleItems)->contains(function ($item) {
            $product = Product::find($item['product_id']);
            return $product?->requires_preparation === true;
        });

        return DB::transaction(function () use ($data, $userId, $subtotal, $discount, $total, $requiresPreparation, $saleItems) {
            $sale = null;
            $attempts = 0;

            while (!$sale) {
                $number = $this->generateSaleNumber($data['org_id']);

                try {
                    $sale = Sale::create([
                        'number' => $number,
                        'org_id' => $data['org_id'],
                        'customer_name' => $data['customer_name'] ?? null,
                        'customer_phone' => $data['customer_phone'] ?? null,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'total' => $total,
                        'status' => $requiresPreparation ? Sale::STATUS_PREPARING : Sale::STATUS_COMPLETED,
                        'created_by' => $userId,
                        'sold_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    $isDuplicate = (int) ($e->errorInfo[1] ?? 0) === 1062;

                    if (!$isDuplicate || ++$attempts >= 5) {
                        throw $e;
                    }
                }
            }

            foreach ($saleItems as $item) {
                $sale->items()->create($item);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $deduct = min($item['quantity'], $product->stock);
                    if ($deduct > 0) {
                        $product->decrement('stock', $deduct);
                    }
                }
            }

            if ($requiresPreparation) {
                event(new SaleCreated($sale));
            }

            return $sale;
        });
    }

    private function generateSaleNumber(int $orgId): string
    {
        return DB::transaction(function () use ($orgId) {
            $lastSale = Sale::where('org_id', $orgId)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastSale && preg_match('/^([A-Z0-9]{4})-(\d{2})$/', $lastSale->number, $matches)) {
                $prefix = $matches[1];
                $lastSeq = (int) $matches[2];

                if ($lastSeq < 99) {
                    $nextSeq = $lastSeq + 1;
                } else {
                    $prefix = $this->generateUniquePrefix($orgId);
                    $nextSeq = 1;
                }
            } else {
                $prefix = $this->generateUniquePrefix($orgId);
                $nextSeq = 1;
            }

            return $prefix . '-' . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        });
    }

    private function generateUniquePrefix(int $orgId): string
    {
        do {
            $prefix = $this->generateRandomCode(4);
            $exists = Sale::where('org_id', $orgId)
                ->where('number', 'like', $prefix . '-%')
                ->exists();
        } while ($exists);

        return $prefix;
    }

    private function generateRandomCode(int $length = 4): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }
}
