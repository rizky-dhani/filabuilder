<?php

namespace Filabuilder\Blocks\BuiltInBlocks;

use Filabuilder\Blocks\Block;

class TextBlock extends Block
{
    public static function getId(): string { return 'text'; }
    public static function getName(): string { return 'Rich Text'; }
    public static function getCategory(): string { return 'Content'; }
    public static function getOrder(): int { return 20; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<div class="max-w-3xl mx-auto py-12 px-6 prose prose-lg">
  <h2>Section Title</h2>
  <p>Your content goes here. This is a rich text block that you can customize with any HTML content.</p>
  <ul>
    <li>Feature one</li>
    <li>Feature two</li>
    <li>Feature three</li>
  </ul>
</div>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }

    public static function getThumbnail(): ?string
    {
        return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 200"><rect fill="#1e1e2e" width="320" height="200"/><rect fill="#45475a" x="20" y="20" width="280" height="14" rx="6"/><rect fill="#585b70" x="20" y="48" width="200" height="10" rx="5"/><rect fill="#585b70" x="20" y="70" width="240" height="10" rx="5"/><rect fill="#585b70" x="20" y="92" width="160" height="10" rx="5"/><rect fill="#6c7086" x="20" y="122" width="280" height="10" rx="5"/><rect fill="#6c7086" x="20" y="144" width="220" height="10" rx="5"/><rect fill="#6c7086" x="20" y="166" width="180" height="10" rx="5"/></svg>');
    }
}
