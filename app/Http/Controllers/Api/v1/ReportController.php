<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PosReturn;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected function resolveDateRange(Request $request): array
    {
        $period = $request->input('period');
        $now = Carbon::now();

        if ($period === 'today') {
            $from = $request->input('date', $now->toDateString());
            $to = $from;
        } elseif ($period === 'week') {
            $from = $now->copy()->subDays(6)->toDateString();
            $to = $now->toDateString();
        } elseif ($period === 'month') {
            $month = $request->input('month', $now->format('Y-m'));
            $from = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
            $to = Carbon::parse($month . '-01')->endOfMonth()->toDateString();
        } elseif ($period === 'year') {
            $year = $request->input('year', $now->year);
            $from = "{$year}-01-01";
            $to = "{$year}-12-31";
        } else {
            $from = $request->input('date_from', $now->copy()->subDays(6)->toDateString());
            $to = $request->input('date_to', $now->toDateString());
        }

        return [$from, $to];
    }

    public function summary(Request $request): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $salesQuery = Sale::where('status', 'Completed')
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());

        $completedSalesCount = $salesQuery->count();
        $grossSubtotal = (int) $salesQuery->sum('subtotal_cents');
        $discountTotal = (int) $salesQuery->sum('discount_cents');
        $taxableSubtotal = (int) $salesQuery->sum('taxable_subtotal_cents');
        $taxTotal = (int) $salesQuery->sum('tax_cents');
        $totalPaid = (int) $salesQuery->sum('total_cents');

        $returnsQuery = PosReturn::where('status', 'Completed')
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());

        $refundCount = $returnsQuery->count();
        $totalRefund = (int) $returnsQuery->sum('total_refund_cents');
        $netCollected = $totalPaid - $totalRefund;
        $activeCashiers = Sale::where('status', 'Completed')->distinct('cashier_id')->count('cashier_id');

        // Cafe breakdown: Dine In vs Take Away
        $takeawayCount = (clone $salesQuery)->where(function ($q) {
            $q->whereRaw("LOWER(COALESCE(note, '')) LIKE '%takeaway%'")
              ->orWhereRaw("LOWER(COALESCE(note, '')) LIKE '%take away%'");
        })->count();
        $takeawaySales = (int) (clone $salesQuery)->where(function ($q) {
            $q->whereRaw("LOWER(COALESCE(note, '')) LIKE '%takeaway%'")
              ->orWhereRaw("LOWER(COALESCE(note, '')) LIKE '%take away%'");
        })->sum('total_cents');

        $dineInCount = $completedSalesCount - $takeawayCount;
        $dineInSales = $totalPaid - $takeawaySales;

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'completed_sales_count' => $completedSalesCount,
            'gross_subtotal_cents' => $grossSubtotal,
            'discount_cents' => $discountTotal,
            'taxable_subtotal_cents' => $taxableSubtotal,
            'tax_cents' => $taxTotal,
            'total_sales_cents' => $totalPaid,
            'refund_count' => $refundCount,
            'total_refund_cents' => $totalRefund,
            'net_collected_cents' => $netCollected,
            'dine_in_count' => $dineInCount,
            'dine_in_sales_cents' => $dineInSales,
            'takeaway_count' => $takeawayCount,
            'takeaway_sales_cents' => $takeawaySales,
            // Aliases for dashboard UI:
            'totalSales' => $totalPaid,
            'transactionCount' => $completedSalesCount,
            'netSales' => $netCollected,
            'grossProfit' => $grossSubtotal,
            'averageBasket' => $completedSalesCount > 0 ? (int) round($totalPaid / $completedSalesCount) : 0,
            'activeCashiers' => $activeCashiers,
        ]);
    }

    public function daily(Request $request): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $sales = Sale::where('status', 'Completed')
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay())
            ->get();

        $returns = PosReturn::where('status', 'Completed')
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay())
            ->get();

        $days = [];
        $current = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        while ($current->lte($end)) {
            $dStr = $current->format('Y-m-d');
            $days[$dStr] = [
                'date' => $dStr,
                'sales_count' => 0,
                'subtotal_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 0,
                'refund_cents' => 0,
                'net_cents' => 0,
            ];
            $current->addDay();
        }

        foreach ($sales as $s) {
            $d = $s->created_at->format('Y-m-d');
            if (isset($days[$d])) {
                $days[$d]['sales_count']++;
                $days[$d]['subtotal_cents'] += $s->subtotal_cents;
                $days[$d]['tax_cents'] += $s->tax_cents;
                $days[$d]['total_cents'] += $s->total_cents;
            }
        }

        foreach ($returns as $r) {
            $d = $r->created_at->format('Y-m-d');
            if (isset($days[$d])) {
                $days[$d]['refund_cents'] += $r->total_refund_cents;
            }
        }

        foreach ($days as &$item) {
            $item['net_cents'] = $item['total_cents'] - $item['refund_cents'];
        }
        unset($item);

        return response()->json(array_values($days));
    }

    public function paymentMethods(Request $request): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $methods = Sale::where('status', 'Completed')
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay())
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_cents) as total_cents'))
            ->groupBy('payment_method')
            ->get();

        return response()->json($methods);
    }

    public function topProducts(Request $request): JsonResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $top = SaleItem::whereHas('sale', function ($q) use ($dateFrom, $dateTo) {
            $q->where('status', 'Completed')
                ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
                ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        })
            ->select('name', DB::raw('sum(quantity) as total_quantity'), DB::raw('sum(total_cents) as total_revenue_cents'))
            ->groupBy('name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return response()->json($top);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $sales = Sale::where('status', 'Completed')
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay())
            ->orderBy('created_at')
            ->get();

        $sanitize = function ($value) {
            $str = (string) $value;
            // Anti-formula injection: prefix with single quote if starts with =, +, -, @
            if (in_array(substr($str, 0, 1), ['=', '+', '-', '@'])) {
                return "'" . $str;
            }
            return $str;
        };

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"posymart-laporan-{$dateFrom}-to-{$dateTo}.csv\"",
        ];

        return response()->stream(function () use ($sales, $sanitize) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Kode Transaksi',
                'Tanggal',
                'Kasir',
                'Pelanggan',
                'Metode Pembayaran',
                'Subtotal',
                'Diskon',
                'Pajak',
                'Total',
            ]);

            foreach ($sales as $s) {
                fputcsv($handle, [
                    $sanitize($s->tx_code),
                    $sanitize($s->created_at->format('Y-m-d H:i:s')),
                    $sanitize($s->cashier_name_snapshot),
                    $sanitize($s->customer_name_snapshot),
                    $sanitize($s->payment_method),
                    $s->subtotal_cents,
                    $s->discount_cents,
                    $s->tax_cents,
                    $s->total_cents,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
