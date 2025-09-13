<?php

namespace App\Console\Commands;

use App\Services\WooCommerceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWooCommerceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woocommerce:sync 
                            {--categories : Only sync categories}
                            {--products : Only sync products}
                            {--force : Force sync all items regardless of last sync time}
                            {--batch-size=50 : Number of items to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes products and categories from WooCommerce to the local database.';

    protected $wooCommerceSyncService;

    public function __construct(WooCommerceSyncService $wooCommerceSyncService)
    {
        parent::__construct();
        $this->wooCommerceSyncService = $wooCommerceSyncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info('Starting WooCommerce data synchronization...');

        try {
            
            $syncCategories = !$this->option('products') || $this->option('categories');
            $syncProducts = !$this->option('categories') || $this->option('products');
            $force = $this->option('force');
            $batchSize = (int) $this->option('batch-size');

            
            $this->line('Sync options:');
            $this->line("- Categories: " . ($syncCategories ? 'Yes' : 'No'));
            $this->line("- Products: " . ($syncProducts ? 'Yes' : 'No'));
            $this->line("- Force sync: " . ($force ? 'Yes' : 'No'));
            $this->line("- Batch size: {$batchSize}");
            $this->newLine();

            
            $this->line('Fetching total counts from WooCommerce...');
            $counts = $this->wooCommerceSyncService->getCounts();
            
            $totalItems = 0;
            if ($syncCategories) $totalItems += $counts['categories'];
            if ($syncProducts) $totalItems += $counts['products'];

            if ($totalItems === 0) {
                $this->info('No products or categories found to sync.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$counts['categories']} categories and {$counts['products']} products in WooCommerce.");
            
            if ($syncCategories && $syncProducts) {
                $this->info("Will sync {$counts['categories']} categories and {$counts['products']} products.");
            } elseif ($syncCategories) {
                $this->info("Will sync {$counts['categories']} categories only.");
            } else {
                $this->info("Will sync {$counts['products']} products only.");
            }

           
            $progressBar = $this->output->createProgressBar($totalItems);
            $progressBar->setFormat('verbose');
            $progressBar->start();

            
            $syncResults = [
                'categories' => ['synced' => 0, 'errors' => 0],
                'products' => ['synced' => 0, 'errors' => 0]
            ];

            
            $progressCallback = function (string $type, string $action = 'processed', $item = null) use ($progressBar, &$syncResults) {
                if ($action === 'processed') {
                    $progressBar->advance();
                    if (isset($syncResults[$type])) {
                        $syncResults[$type]['synced']++;
                    }
                } elseif ($action === 'error') {
                    if (isset($syncResults[$type])) {
                        $syncResults[$type]['errors']++;
                    }
                    
                    Log::error("Error syncing {$type}: " . ($item['error'] ?? 'Unknown error'));
                }
                
               
                if ($item && isset($item['name'])) {
                    $progressBar->setMessage("Processing {$type}: " . substr($item['name'], 0, 30));
                }
            };

            
            $this->wooCommerceSyncService->syncAll(
                $progressCallback,
                $syncCategories,
                $syncProducts,
                $force,
                $batchSize
            );

           
            $progressBar->finish();
            $this->newLine(2);

           
            $this->showSyncResults($syncResults, $startTime);

        } catch (\Exception $e) {
            if (isset($progressBar)) {
                $progressBar->finish();
                $this->newLine();
            }
            
            $this->error('WooCommerce data synchronization failed!');
            $this->error('Error: ' . $e->getMessage());
            
           
            if ($this->getOutput()->isVerbose()) {
                $this->line('Stack trace:');
                $this->line($e->getTraceAsString());
            }
            
            Log::error('WooCommerce sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showSyncResults(array $results, float $startTime): void
    {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info('Synchronization completed!');
        $this->newLine();

        
        if (isset($results['categories'])) {
            $this->line("Categories:");
            $this->line("  - Successfully synced: <fg=green>{$results['categories']['synced']}</>");
            if ($results['categories']['errors'] > 0) {
                $this->line("  - Errors: <fg=red>{$results['categories']['errors']}</>");
            }
        }

       
        if (isset($results['products'])) {
            $this->line("Products:");
            $this->line("  - Successfully synced: <fg=green>{$results['products']['synced']}</>");
            if ($results['products']['errors'] > 0) {
                $this->line("  - Errors: <fg=red>{$results['products']['errors']}</>");
            }
        }

        $this->newLine();
        $this->line("Total execution time: <fg=yellow>{$duration} seconds</>");
        
       
        $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $this->line("Peak memory usage: <fg=yellow>{$memoryUsage} MB</>");

       
        $totalErrors = ($results['categories']['errors'] ?? 0) + ($results['products']['errors'] ?? 0);
        if ($totalErrors > 0) {
            $this->newLine();
            $this->warn("There were {$totalErrors} errors during synchronization.");
            $this->line("Check the logs for more details: <fg=cyan>tail -f storage/logs/laravel.log</>");
        }
    }
}
