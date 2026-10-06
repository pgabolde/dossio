<?php

namespace App\Exceptions;

use RuntimeException;

class NoCurrentOrganization extends RuntimeException
{
    protected $message = 'Aucune organisation courante définie.';
}
