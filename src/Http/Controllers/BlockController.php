<?php

namespace Filabuilder\Http\Controllers;

use Filabuilder\Blocks\BlockRegistry;

class BlockController
{
    public function index()
    {
        return response()->json(BlockRegistry::make()->toArray());
    }
}
