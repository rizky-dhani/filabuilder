<?php

namespace Filabuilder\Blocks\BuiltInBlocks;

use Filabuilder\Blocks\Block;

class HeroBlock extends Block
{
    public static function getId(): string { return 'hero'; }
    public static function getName(): string { return 'Hero Section'; }
    public static function getCategory(): string { return 'Layout'; }
    public static function getOrder(): int { return 10; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<section class="bg-gradient-to-r from-indigo-500 to-purple-600 py-24 px-6 text-center text-white">
  <h1 class="text-5xl font-bold mb-4">Your Headline Here</h1>
  <p class="text-xl mb-8 opacity-90">Compelling subheading text goes here.</p>
  <a href="#" class="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold">Get Started</a>
</section>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }

    public static function getThumbnail(): ?string
    {
        return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 320"><rect fill="#1e1e2e" width="320" height="320"/><rect fill="#45475a" x="40" y="80" width="240" height="20" rx="6"/><rect fill="#585b70" x="60" y="116" width="200" height="10" rx="5"/><rect fill="#585b70" x="80" y="136" width="160" height="10" rx="5"/><rect fill="#5865f2" x="100" y="180" width="120" height="32" rx="16"/></svg>');
    }
}
