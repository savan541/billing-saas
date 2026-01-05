<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceActivity;

class ClientService
{
    public function getStatsForUser($user): array
    {
        $userInvoices = $user->invoices();
        
        return [
            'totalClients' => $user->clients()->count(),
            'totalInvoices' => $userInvoices->count(),
            'totalRevenue' => $userInvoices->where('status', 'paid')->sum('total'),
            'recentActivity' => $this->getRecentActivity($user),
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

    private function getRecentActivity($user): array
    {
        return InvoiceActivity::whereHas('invoice', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['invoice.client'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->activity_type,
                'description' => $activity->description,
                'invoice_number' => $activity->invoice->invoice_number,
                'client_name' => $activity->invoice->client->name,
                'created_at' => $activity->created_at->diffForHumans(),
            ];
        })
        ->toArray();
    }
}
