<?php

namespace App\Services;

use App\Models\Client;

class ClientService
{
    public function getStatsForUser($user): array
    {
        return [
            'totalClients' => $user->clients()->count(),
            'totalInvoices' => 0,
            'totalRevenue' => 0,
        ];
    }

    public function getClientsForUser($user)
    {
        return Client::orderBy('name')->get();
    }

    public function createClientForUser($user, array $data): Client
    {
        return $user->clients()->create($data);
    }

    public function updateClient(Client $client, array $data): Client
    {
        $client->update($data);
        return $client->fresh();
    }

    public function deleteClient(Client $client): void
    {
        $client->delete();
    }

    public function getClientById(int $id): ?Client
    {
        return Client::find($id);
    }
}
