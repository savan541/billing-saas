<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Requests\DeleteClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(private ClientService $clientService)
    {
    }

    public function index(Request $request)
    {
        $clients = $this->clientService->getClientsForUser($request->user());
        
        return inertia('Clients/Index', [
            'clients' => $clients,
            'currencyOptions' => Currency::getOptions(),
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $this->clientService->createClientForUser($request->user(), $request->validated());

        return redirect()->back()->with('success', 'Client created successfully.');
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $this->clientService->updateClient($client, $request->validated());

        return redirect()->back()->with('success', 'Client updated successfully.');
    }

    public function destroy(DeleteClientRequest $request, Client $client)
    {
        $this->clientService->deleteClient($client);

        return redirect()->back()->with('success', 'Client deleted successfully.');
    }
}
