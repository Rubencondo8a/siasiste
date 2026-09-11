<?php

namespace App\Middleware;

class RoleMiddleware
{
    private $roles;

    public function __construct($roles = [])
    {
        $this->roles = (array) $roles;
    }

    public function handle()
    {
        AuthMiddleware::role($this->roles);
    }
}
