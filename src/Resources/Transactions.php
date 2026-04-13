<?php

namespace ReavaPay\Gwinto\Resources;

use ReavaPay\Gwinto\Services\GwintoManager;

class Transactions
{
    public function __construct(protected GwintoManager $manager) {}

    public function list(array $filters = []): array
    {
        return $this->manager->get('/transactions', $filters);
    }

    public function get(string $id): array
    {
        return $this->manager->get("/transactions/{$id}");
    }
}
