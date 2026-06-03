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
}
