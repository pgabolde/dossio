<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        return $this->belongsToCurrentOrganization($user, $client);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return $this->belongsToCurrentOrganization($user, $client);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $this->belongsToCurrentOrganization($user, $client) && $user->isOwnerOf($user->currentOrganization);
    }

    private function belongsToCurrentOrganization(User $user, Client $client): bool
    {
        return $client->organization_id === $user->current_organization_id;
    }
}
