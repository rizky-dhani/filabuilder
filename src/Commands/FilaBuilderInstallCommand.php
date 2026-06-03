<?php

namespace Filabuilder\Commands;

use Illuminate\Console\Command;

class FilaBuilderInstallCommand extends Command
{
    protected $signature = 'filabuilder:install';

    protected $description = 'Install and configure FilaBuilder';

    public function handle(): int
    {
        $this->info('Installing FilaBuilder...');

        $this->call('vendor:publish', [
            '--tag' => 'filabuilder-config',
        ]);

        $this->call('migrate');

        $this->info('FilaBuilder installed successfully!');
        $this->warn('Add Filabuilder\FilaBuilder::make() to your AdminPanelProvider to get started.');

        return self::SUCCESS;
    }
}
