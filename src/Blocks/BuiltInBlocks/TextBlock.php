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
        return '<i class="fas fa-paragraph" style="font-size:32px;color:#5865f2"></i>';
    }
}
