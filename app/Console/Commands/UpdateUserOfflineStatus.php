<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\StatusService;
use Illuminate\Console\Command;

class UpdateUserOfflineStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-offline-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users to offline status if inactive for 3 minutes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $statusService = new StatusService();
        $updatedCount = $statusService->updateOfflineStatus();

        if ($updatedCount > 0) {
            $this->info("Updated {$updatedCount} users to offline status");
        } else {
            $this->info("No users to update");
        }

        return Command::SUCCESS;
    }
}

