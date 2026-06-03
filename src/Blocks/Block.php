<?php

namespace Filabuilder\Blocks;

abstract class Block
{
    abstract public static function getId(): string;

    abstract public static function getName(): string;

    abstract public static function getCategory(): string;

    abstract public function getTemplate(): string;

    abstract public function getAssets(): array;

    public static function getOrder(): int
    {
        return 100;
    }

    public static function getThumbnail(): ?string
    {
        return null;
    }

    public function toArray(): array
    {
        return [
            'id' => static::getId(),
            'name' => static::getName(),
            'category' => static::getCategory(),
            'template' => $this->getTemplate(),
            'order' => static::getOrder(),
            'thumbnail' => static::getThumbnail(),
            'assets' => $this->getAssets(),
        ];
    }
}
