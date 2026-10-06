<?php

use App\Exceptions\NoCurrentOrganization;
use App\Models\Client;
use App\Models\User;
use App\Support\CurrentOrganization;

beforeEach(function () {
    $this->userA = User::factory()->withOrganization()->create();
    $this->clientA = $this->userA->currentOrganization->clients()->create(['name' => 'Client de USER A']);
    $this->userB = User::factory()->withOrganization()->create();
    $this->clientB = $this->userB->currentOrganization->clients()->create(['name' => 'Client de USER B']);
});

test('user only sees clients of their current organization', function () {
    app(CurrentOrganization::class)->set($this->userA->currentOrganization);
    expect(Client::pluck('name')->all())->toBe(['Client de USER A']);
});

test('querying clients without a current organization throws an exception', function () {
    Client::count();
})->throws(NoCurrentOrganization::class);

test('forged organization_id is ignored when creating a client', function () {
    app(CurrentOrganization::class)->set($this->userA->currentOrganization);
    $client = Client::create(['name' => 'Client de test', 'organization_id' => $this->userB->current_organization_id]);
    expect($client->organization_id)->toBe($this->userA->current_organization_id);
});

test('a user cannot access a client of another organization by forging the url', function () {
    $this->actingAs($this->userA)
        ->get(route('clients.show', $this->clientB))
        ->assertNotFound();
});

test('a user removed from their organization gets a 403', function () {
    $this->userA->organizations()->detach($this->userA->current_organization_id);
    $this->actingAs($this->userA)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('only an owner can delete a client', function () {
    $member = User::factory()->create([
        'current_organization_id' => $this->userA->current_organization_id,
    ]);
    $this->userA->currentOrganization->members()->attach($member);

    expect($member->can('delete', $this->clientA))->toBeFalse();
    expect($this->userA->can('delete', $this->clientA))->toBeTrue();
});
