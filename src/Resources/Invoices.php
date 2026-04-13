<?php

namespace ReavaPay\Gwinto\Resources;

use ReavaPay\Gwinto\Services\GwintoManager;

class Invoices
{
    public function __construct(protected GwintoManager $manager) {}

    /**
     * Create an invoice on Reava Pay.
     *
     * @param  array{
     *     customer_name: string,
     *     customer_email?: string,
     *     customer_phone?: string,
     *     currency_code?: string,
     *     issue_date: string,
     *     due_date?: string,
     *     items: array<array{description: string, quantity: float, unit_price: float, tax_rate?: float}>,
     *     notes?: string,
     *     reference?: string,
     *     allow_partial_payments?: bool,
     *     minimum_payment_amount?: float,
     *     metadata?: array,
     * }  $data
     */
    public function create(array $data): array
    {
        return $this->manager->post('/invoices', array_merge($data, [
            'metadata' => array_merge($data['metadata'] ?? [], [
                'source' => 'gwinto',
            ]),
        ]));
    }

    /**
     * List invoices with optional filters.
     *
     * @param  array{status?: string, from?: string, to?: string, page?: int, per_page?: int}  $filters
     */
    public function list(array $filters = []): array
    {
        return $this->manager->get('/invoices', $filters);
    }

    /**
     * Get a single invoice by ID or share code.
     */
    public function get(string $id): array
    {
        return $this->manager->get("/invoices/{$id}");
    }

    /**
     * Update invoice status (e.g. cancelled).
     */
    public function updateStatus(string $id, string $status, array $extra = []): array
    {
        return $this->manager->put("/invoices/{$id}/status", array_merge(['status' => $status], $extra));
    }

    /**
     * Record a manual payment against an invoice (for offline/reconciliation use).
     *
     * @param  array{amount: float, method: string, reference?: string, payer_name?: string, notes?: string}  $data
     */
    public function recordPayment(string $id, array $data): array
    {
        return $this->manager->post("/invoices/{$id}/payments", $data);
    }
}
