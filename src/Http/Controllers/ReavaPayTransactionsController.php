<?php

namespace ReavaPay\Gwinto\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ReavaPay\Gwinto\Services\GwintoManager;

class ReavaPayTransactionsController extends Controller
{
    public function __construct(protected GwintoManager $manager) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'channel' => $request->get('channel'),
            'type' => $request->get('type'),
            'page' => $request->get('page', 1),
            'per_page' => 20,
        ];

        $data = $this->fetchTransactions(array_filter($filters));
        $summary = $this->fetchSummary();

        return Inertia::render('GwintoReavaPay/Transactions/Index', [
            'transactions' => $data['transactions'],
            'pagination' => $data['pagination'],
            'summary' => $summary,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'channel' => $filters['channel'] ?? '',
                'type' => $filters['type'] ?? '',
            ],
        ]);
    }

    public function show(string $id): Response
    {
        try {
            $response = $this->manager->transactions()->get($id);
            $transaction = $response['data'] ?? $response;
        } catch (\Throwable $e) {
            Log::warning('Gwinto transaction fetch failed', ['id' => $id, 'error' => $e->getMessage()]);
            $transaction = null;
        }

        return Inertia::render('GwintoReavaPay/Transactions/Show', [
            'transaction' => $transaction,
            'transactionId' => $id,
        ]);
    }

    private function fetchTransactions(array $filters): array
    {
        try {
            $response = $this->manager->transactions()->list($filters);

            return [
                'transactions' => $response['data'] ?? [],
                'pagination' => [
                    'total' => $response['meta']['total'] ?? count($response['data'] ?? []),
                    'per_page' => $response['meta']['per_page'] ?? 20,
                    'current_page' => $response['meta']['current_page'] ?? 1,
                    'last_page' => $response['meta']['last_page'] ?? 1,
                    'from' => $response['meta']['from'] ?? null,
                    'to' => $response['meta']['to'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('Gwinto transactions list failed', ['error' => $e->getMessage()]);

            return ['transactions' => [], 'pagination' => ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'last_page' => 1]];
        }
    }

    private function fetchSummary(): array
    {
        try {
            $result = $this->manager->reconcile([
                'from' => now()->startOfMonth()->toIso8601String(),
                'to' => now()->endOfDay()->toIso8601String(),
            ]);

            $allTime = $this->manager->reconcile([
                'from' => now()->subYears(5)->toIso8601String(),
                'to' => now()->endOfDay()->toIso8601String(),
                'per_page' => 1,
            ]);

            return [
                'total_volume' => $allTime->summary->total_collected + $allTime->summary->total_settled,
                'month_volume' => $result->summary->total_collected,
                'transaction_count' => $result->summary->total_count,
                'success_rate' => $result->summary->total_count > 0
                    ? round(($result->summary->total_count - $result->summary->failed_count) / $result->summary->total_count * 100, 1)
                    : 0,
                'currency' => 'KES',
            ];
        } catch (\Throwable) {
            return ['total_volume' => 0, 'month_volume' => 0, 'transaction_count' => 0, 'success_rate' => 0, 'currency' => 'KES'];
        }
    }
}
