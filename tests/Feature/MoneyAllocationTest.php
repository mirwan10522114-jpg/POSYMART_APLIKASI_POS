<?php

namespace Tests\Feature;

use App\Services\MoneyAllocator;
use Tests\TestCase;

class MoneyAllocationTest extends TestCase
{
    /**
     * Test T1: Bagel 2 unit, unit_price = 280, tax = 45.
     * Expected: unitAllocations = [{0, 280, 22, 302}, {1, 280, 23, 303}].
     */
    public function test_bagel_two_units_allocation(): void
    {
        $allocs = MoneyAllocator::buildUnitAllocations(560, 45, 2);

        $this->assertCount(2, $allocs);
        $this->assertEquals(0, $allocs[0]['unit_index']);
        $this->assertEquals(280, $allocs[0]['net_cents']);
        $this->assertEquals(22, $allocs[0]['tax_cents']);
        $this->assertEquals(302, $allocs[0]['total_cents']);

        $this->assertEquals(1, $allocs[1]['unit_index']);
        $this->assertEquals(280, $allocs[1]['net_cents']);
        $this->assertEquals(23, $allocs[1]['tax_cents']);
        $this->assertEquals(303, $allocs[1]['total_cents']);

        $totalAlloc = $allocs[0]['total_cents'] + $allocs[1]['total_cents'];
        $this->assertEquals(605, $totalAlloc);
    }

    /**
     * Test T6: Largest remainder 3 items, equal weights, total 10.
     * Expected: [4, 3, 3].
     */
    public function test_largest_remainder_equal_weights(): void
    {
        $res = MoneyAllocator::allocateByLargestRemainder([1, 1, 1], 10, ['a', 'b', 'c']);
        $this->assertEquals([4, 3, 3], $res);
        $this->assertEquals(10, array_sum($res));
    }

    /**
     * Test T6 (Unit allocations): 3 units, total 10.
     * Expected with unit index descending: [3, 3, 4].
     */
    public function test_unit_allocation_descending_remainder(): void
    {
        $res = MoneyAllocator::allocateUnits(10, 3);
        $this->assertEquals([3, 3, 4], $res);
        $this->assertEquals(10, array_sum($res));
    }

    /**
     * Test T7: Largest remainder weights [1, 2, 3], total 100.
     * Expected: [17, 33, 50].
     */
    public function test_largest_remainder_proportional(): void
    {
        $res = MoneyAllocator::allocateByLargestRemainder([1, 2, 3], 100, ['p1', 'p2', 'p3']);
        $this->assertEquals([17, 33, 50], $res);
        $this->assertEquals(100, array_sum($res));
    }

    /**
     * Test Invariant: sum of item allocations equals totals.
     */
    public function test_item_allocations_sum_invariant(): void
    {
        $items = [
            ['product_id' => 'p1', 'unit_price_cents' => 35000, 'quantity' => 2],
            ['product_id' => 'p2', 'unit_price_cents' => 28000, 'quantity' => 1],
            ['product_id' => 'p3', 'unit_price_cents' => 55000, 'quantity' => 3],
        ];

        $totals = MoneyAllocator::computeTotals($items, 15000, true, 800);
        $allocs = MoneyAllocator::allocateToItems($items, $totals['discount_cents'], $totals['tax_cents']);

        $sumDiscount = array_sum(array_column($allocs, 'allocated_discount_cents'));
        $sumTax = array_sum(array_column($allocs, 'allocated_tax_cents'));
        $sumTotal = array_sum(array_column($allocs, 'total_cents'));

        $this->assertEquals($totals['discount_cents'], $sumDiscount);
        $this->assertEquals($totals['tax_cents'], $sumTax);
        $this->assertEquals($totals['total_cents'], $sumTotal);
    }
}
