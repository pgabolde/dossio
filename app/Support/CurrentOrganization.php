<?php

namespace App\Support;

use App\Exceptions\NoCurrentOrganization;
use App\Models\Organization;

class CurrentOrganization
{
    private ?Organization $organization = null;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): Organization
    {
        if ($this->organization === null) {
            throw new NoCurrentOrganization;
        }

        return $this->organization;
    }

    public function id(): int
    {
        return $this->get()->id;
    }
}
