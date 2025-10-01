<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;

class NodeValue
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toInt(): int
    {
        return (int) $this->value;
    }

    public function unescape(): string
    {
        // Example: Converting escape sequences back
        $this->value = str_replace(['\\s', '\\p'], [' ', '|'], $this->value);
        return $this->value;
    }

    public function contains(string $needle): bool
    {
        return str_contains($this->value, $needle);
    }

    public function split(string $separator, ?int $limit = null): array
    {
        $parts = $limit !== null
            ? explode($separator, $this->value, $limit)
            : explode($separator, $this->value);

        return array_map(fn($p) => new self($p), $parts);
    }
}
