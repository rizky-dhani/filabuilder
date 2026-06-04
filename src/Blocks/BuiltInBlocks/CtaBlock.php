<?php

namespace Filabuilder\Blocks\BuiltInBlocks;

use Filabuilder\Blocks\Block;

class CtaBlock extends Block
{
    public static function getId(): string { return 'cta'; }
    public static function getName(): string { return 'Call to Action'; }
    public static function getCategory(): string { return 'Marketing'; }
    public static function getOrder(): int { return 30; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<section class="bg-gray-900 py-16 px-6 text-center">
  <h2 class="text-3xl font-bold text-white mb-4">Ready to Get Started?</h2>
  <p class="text-gray-400 mb-8 max-w-2xl mx-auto">Join thousands of satisfied customers today.</p>
  <a href="#" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700">Sign Up Now</a>
</section>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }

    public static function getThumbnail(): ?string
    {
        return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 220"><rect fill="#1e1e2e" width="320" height="220"/><rect fill="#45475a" x="30" y="60" width="260" height="16" rx="6"/><rect fill="#585b70" x="50" y="88" width="220" height="10" rx="5"/><rect fill="#585b70" x="70" y="108" width="180" height="10" rx="5"/><rect fill="#5865f2" x="100" y="150" width="120" height="32" rx="16"/></svg>');
    }
}
