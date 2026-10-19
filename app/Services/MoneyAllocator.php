<?php

namespace App\Services;

class MoneyAllocator
{
    /**
     * Pembulatan Half-Up (round half away from zero).
     */
    public static function roundHalfUp(float|int $value): int
    {
        return (int) round($value, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Hitung total transaksi (gross, discount, taxable, tax, total).
     */
    public static function computeTotals(
        array $items,
        int $discountCents = 0,
        bool $taxEnabled = true,
        int $taxRateBps = 800
    ): array {
        $grossSubtotal = 0;
        foreach ($items as $item) {
            $grossSubtotal += ($item['unit_price_cents'] ?? 0) * ($item['quantity'] ?? 0);
        }

        $discountCents = max(0, min($discountCents, $grossSubtotal));
        $taxableSubtotal = max(0, $grossSubtotal - $discountCents);
        $taxCents = $taxEnabled
            ? self::roundHalfUp(($taxableSubtotal * $taxRateBps) / 10000)
            : 0;
        $totalCents = $taxableSubtotal + $taxCents;

        return [
            'gross_subtotal_cents' => $grossSubtotal,
            'discount_cents' => $discountCents,
            'taxable_subtotal_cents' => $taxableSubtotal,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
        ];
    }

    /**
     * Largest Remainder Method (Hamilton Method) menggunakan aritmatika integer.
     * Tie-breaker: remainder tertinggi descending, ID item ascending.
     * Invariant: array_sum($allocations) === $totalCents.
     */
    public static function allocateByLargestRemainder(
        array $weights,
        int $totalCents,
        array $itemKeys = []
    ): array {
        $n = count($weights);
        if ($n === 0) {
            return [];
        }
        if ($totalCents === 0) {
            return array_fill(0, $n, 0);
        }

        $totalWeight = array_sum($weights);
        if ($totalWeight === 0) {
            // Jika semua bobot 0, bagi rata
            $each = intdiv($totalCents, $n);
            $allocations = array_fill(0, $n, $each);
            $rem = $totalCents - ($each * $n);
            for ($i = 0; $i < $rem; $i++) {
                $allocations[$n - 1 - $i]++;
            }
            return $allocations;
        }

        $allocations = [];
        $remainders = [];

        for ($i = 0; $i < $n; $i++) {
            $w = $weights[$i];
            $floor = intdiv($totalCents * $w, $totalWeight);
            $allocations[$i] = $floor;
            // remainder pecahan yang diskalakan dengan totalWeight
            $remainders[$i] = ($totalCents * $w) - ($floor * $totalWeight);
        }

        $allocated = array_sum($allocations);
        $remainderCents = $totalCents - $allocated;

        if ($remainderCents > 0) {
            $indices = range(0, $n - 1);
            usort($indices, function ($a, $b) use ($remainders, $itemKeys) {
                if ($remainders[$a] !== $remainders[$b]) {
                    return $remainders[$b] <=> $remainders[$a]; // remainder descending
                }
                $keyA = $itemKeys[$a] ?? (string) $a;
                $keyB = $itemKeys[$b] ?? (string) $b;
                return strcmp($keyA, $keyB); // tie-breaker: ID/key ascending
            });

            for ($i = 0; $i < $remainderCents; $i++) {
                $idx = $indices[$i];
                $allocations[$idx]++;
            }
        }

        return $allocations;
    }

    /**
     * Alokasikan diskon dan pajak ke setiap item.
     */
    public static function allocateToItems(
        array $items,
        int $totalDiscountCents,
        int $totalTaxCents,
        ?array $eligibleProductIds = null
    ): array {
        $n = count($items);
        if ($n === 0) {
            return [];
        }

        // 1. Alokasi diskon
        $discountWeights = [];
        $itemKeys = [];
        foreach ($items as $i => $item) {
            $productId = $item['product_id'] ?? (string) $i;
            $itemKeys[$i] = $productId;
            $gross = ($item['unit_price_cents'] ?? 0) * ($item['quantity'] ?? 0);
            $isEligible = ($eligibleProductIds === null) || in_array($productId, $eligibleProductIds);
            $discountWeights[$i] = $isEligible ? $gross : 0;
        }

        $discountAllocations = self::allocateByLargestRemainder(
            $discountWeights,
            $totalDiscountCents,
            $itemKeys
        );

        // 2. Alokasi pajak proporsional terhadap net subtotal
        $netWeights = [];
        for ($i = 0; $i < $n; $i++) {
            $gross = ($items[$i]['unit_price_cents'] ?? 0) * ($items[$i]['quantity'] ?? 0);
            $disc = $discountAllocations[$i] ?? 0;
            $net = max(0, $gross - $disc);
            $netWeights[$i] = $net;
        }

        $taxAllocations = self::allocateByLargestRemainder(
            $netWeights,
            $totalTaxCents,
            $itemKeys
        );

        $result = [];
        for ($i = 0; $i < $n; $i++) {
            $gross = ($items[$i]['unit_price_cents'] ?? 0) * ($items[$i]['quantity'] ?? 0);
            $disc = $discountAllocations[$i] ?? 0;
            $net = max(0, $gross - $disc);
            $tax = $taxAllocations[$i] ?? 0;
            $total = $net + $tax;

            $result[$i] = [
                'gross_subtotal_cents' => $gross,
                'allocated_discount_cents' => $disc,
                'net_subtotal_cents' => $net,
                'allocated_tax_cents' => $tax,
                'total_cents' => $total,
            ];
        }

        return $result;
    }

    /**
     * Alokasikan nominal per unit item.
     * Tie-breaker: unit index DESCENDING untuk sisa sen (unit terakhir dapat sisa).
     */
    public static function allocateUnits(int $totalCents, int $quantity): array
    {
        if ($quantity <= 0) {
            return [];
        }
        $each = intdiv($totalCents, $quantity);
        $remainder = $totalCents - ($each * $quantity);

        $out = array_fill(0, $quantity, $each);
        for ($i = 0; $i < $remainder; $i++) {
            $out[$quantity - 1 - $i]++;
        }

        return $out;
    }

    /**
     * Bangun alokasi lengkap per unit item untuk sale item: [{unit_index, net_cents, tax_cents, total_cents}].
     */
    public static function buildUnitAllocations(int $netCents, int $taxCents, int $quantity): array
    {
        $unitNets = self::allocateUnits($netCents, $quantity);
        $unitTaxes = self::allocateUnits($taxCents, $quantity);

        $allocations = [];
        for ($i = 0; $i < $quantity; $i++) {
            $n = $unitNets[$i];
            $t = $unitTaxes[$i];
            $allocations[] = [
                'unit_index' => $i,
                'net_cents' => $n,
                'tax_cents' => $t,
                'total_cents' => $n + $t,
            ];
        }

        return $allocations;
    }
}
