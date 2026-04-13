<?php

namespace ReavaPay\Gwinto\Resources;

use ReavaPay\Gwinto\Services\GwintoManager;

class Subscriptions
{
    public function __construct(protected GwintoManager $manager) {}

    public function plans(array $filters = []): array
    {
        return $this->manager->get('/recurring/plans', $filters);
    }

    public function createPlan(array $data): array
    {
        return $this->manager->post('/recurring/plans', $data);
    }

    public function subscribe(string $planId, array $data): array
    {
        return $this->manager->post("/recurring/plans/{$planId}/subscriptions", $data);
    }

    public function cancel(string $planId, string $subscriptionId): array
    {
        return $this->manager->post("/recurring/plans/{$planId}/subscriptions/{$subscriptionId}/cancel");
    }

    public function list(string $planId, array $filters = []): array
    {
        return $this->manager->get("/recurring/plans/{$planId}/subscriptions", $filters);
    }
}
