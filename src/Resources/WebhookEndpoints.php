<?php

namespace ReavaPay\Gwinto\Resources;

use ReavaPay\Gwinto\Services\GwintoManager;

class WebhookEndpoints
{
    public function __construct(protected GwintoManager $manager) {}

    public function create(array $data): array
    {
        return $this->manager->post('/webhooks', $data);
    }

    public function list(): array
    {
        return $this->manager->get('/webhooks');
    }

    public function update(string $id, array $data): array
    {
        return $this->manager->put("/webhooks/{$id}", $data);
    }

    public function delete(string $id): array
    {
        return $this->manager->delete("/webhooks/{$id}");
    }
}
