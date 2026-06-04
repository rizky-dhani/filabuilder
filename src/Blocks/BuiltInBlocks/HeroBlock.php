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
        return '<i class="fas fa-newspaper" style="font-size:32px;color:#5865f2"></i>';
    }
}
