<?php

namespace App\Policies;

class PostPolicy
{
    public function view(): bool
    {
        return true;
    }

    public function boot(): void
    {
        $gate = new class {
            public function view(): bool
            {
                return true;
            }
        };

        function update(): bool
        {
            return true;
        }
    }
}

interface CommentPolicy
{
    public function update(): bool;
}

trait AuthorizesPolicy
{
    public function create(): bool
    {
        return true;
    }
}

enum AccessPolicy
{
    case OPEN;

    public function viewAny(): bool
    {
        return true;
    }
}

class PolicyRegistry
{
    public function view(): bool
    {
        return true;
    }
}

class Userpolicy
{
    public function view(): bool
    {
        return true;
    }
}

function view(): bool
{
    return true;
}
