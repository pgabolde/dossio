<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Client::class);

        $search = $request->string('search')->trim()->value();
        $clients = Client::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereLike('siret', "%{$search}%");
                });
            })
            ->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('clients/Index', [
            'clients' => ClientResource::collection($clients),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('clients/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create($request->validated());

        return to_route('clients.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): Response
    {
        Gate::authorize('view', $client);

        return Inertia::render('clients/Show', [
            'client' => new ClientResource($client),
            'can' => [
                'update' => Gate::allows('update', $client),
                'delete' => Gate::allows('delete', $client),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client): Response
    {
        Gate::authorize('update', $client);

        return Inertia::render('clients/Edit', [
            'client' => new ClientResource($client),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return to_route('clients.show', $client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client): RedirectResponse
    {
        Gate::authorize('delete', $client);

        $client->delete();

        return to_route('clients.index');
    }
}
