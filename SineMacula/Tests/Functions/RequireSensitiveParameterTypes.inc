<?php

namespace App;

class Credential
{
}

class Store
{
    public function model(Credential $credential): void
    {
    }

    public function nullableModel(?Credential $credential): void
    {
    }

    public function qualifiedModel(\App\Models\Credential $credential): void
    {
    }

    public function unionedWithNull(Credential|null $credential): void
    {
    }

    public function anyObject(object $credential): void
    {
    }

    public function ownType(self $credential): void
    {
    }

    public function intersected(\Countable&\ArrayAccess $credential): void
    {
    }

    public function disjunctive((\Countable&\ArrayAccess)|null $credential): void
    {
    }

    public function disjunctiveWithString((\Countable&\ArrayAccess)|string $credential): void
    {
    }

    public function string(string $credential): void
    {
    }

    public function shouted(STRING $credential): void
    {
    }

    public function nullableString(?string $credential): void
    {
    }

    public function unionedWithString(Credential|string $credential): void
    {
    }

    public function list(array $credentials): void
    {
    }

    public function anything(mixed $credential): void
    {
    }

    public function untyped($credential): void
    {
    }

    public function promoted(
        public readonly Credential $credential,
        public readonly string $password,
    ) {
    }
}
