<?php

namespace App\Exceptions;

use RuntimeException;

class TenantNotResolvedException extends RuntimeException
{
    /**
     * Create a new exception instance when tenancy cannot be resolved.
     */
    public function __construct()
    {
        parent::__construct('Tenant context is not resolved.');
    }
}
