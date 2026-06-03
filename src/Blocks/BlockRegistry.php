<?php

namespace Filabuilder\Blocks;

use Illuminate\Support\Collection;

class BlockRegistry
{
    /** @var array<string, Block> */
    protected array $blocks = [];

    protected static ?self $instance = null;

    public static function make(): static
    {
        return self::$instance ??= new self;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function register(string|Block $block): static
    {
        if (is_string($block)) {
            $block = new $block;
        }

        $this->blocks[$block::getId()] = $block;

        return $this;
    }

    public function get(string $id): ?Block
    {
        return $this->blocks[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->blocks[$id]);
    }

    public function all(): Collection
    {
        return collect($this->blocks)
            ->sortBy(fn (Block $b) => $b::getOrder())
            ->values();
    }

    public function byCategory(string $category): Collection
    {
        return $this->all()
            ->filter(fn (Block $b) => $b::getCategory() === $category);
    }

    public function categories(): Collection
    {
        return $this->all()
            ->map(fn (Block $b) => $b::getCategory())
            ->unique()
            ->values();
    }

    public function toArray(): array
    {
        return [
            'blocks' => $this->all()->map(fn (Block $b) => $b->toArray())->values()->all(),
            'categories' => $this->categories()->all(),
        ];
    }
}
