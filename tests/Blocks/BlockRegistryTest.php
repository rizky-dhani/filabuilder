<?php

use Filabuilder\Blocks\Block;
use Filabuilder\Blocks\BlockRegistry;

beforeEach(function () {
    BlockRegistry::reset();
});

it('can register and retrieve blocks', function () {
    $registry = BlockRegistry::make();

    $registry->register(new class extends Block {
        public static function getId(): string { return 'test-block'; }
        public static function getName(): string { return 'Test Block'; }
        public static function getCategory(): string { return 'Custom'; }
        public function getTemplate(): string { return '<div>Test</div>'; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });

    expect($registry->has('test-block'))->toBeTrue();
    expect($registry->get('test-block'))->not->toBeNull();
    expect($registry->all())->toHaveCount(1);
});

it('returns blocks grouped by category', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block {
        public static function getId(): string { return 'a'; }
        public static function getName(): string { return 'A'; }
        public static function getCategory(): string { return 'Cat1'; }
        public function getTemplate(): string { return ''; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });
    $registry->register(new class extends Block {
        public static function getId(): string { return 'b'; }
        public static function getName(): string { return 'B'; }
        public static function getCategory(): string { return 'Cat2'; }
        public function getTemplate(): string { return ''; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });

    expect($registry->categories())->toContain('Cat1', 'Cat2');
    expect($registry->byCategory('Cat1'))->toHaveCount(1);
});

it('serializes to array for API response', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block {
        public static function getId(): string { return 'hero'; }
        public static function getName(): string { return 'Hero'; }
        public static function getCategory(): string { return 'Layout'; }
        public function getTemplate(): string { return '<section>Hero</section>'; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });

    $data = $registry->toArray();
    expect($data)->toHaveKeys(['blocks', 'categories']);
    expect($data['blocks'][0]['id'])->toBe('hero');
});
