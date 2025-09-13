<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Automattic\WooCommerce\Client;
use Exception;

class DebugWooCommerceApi extends Command
{
    protected $signature = 'woocommerce:debug';
    protected $description = 'Debug WooCommerce API responses';

    public function handle()
    {
        try {
            $woocommerce = new Client(
                config('woocommerce.store_url'),
                config('woocommerce.client_key'),
                config('woocommerce.client_secret'),
                [
                    'version' => config('woocommerce.api_version'),
                    'timeout' => 30,
                ]
            );

            $this->info('=== WooCommerce API Debug ===');
            $this->newLine();

            
            $this->line('1. Products without status filter:');
            $response1 = $woocommerce->get('products', ['per_page' => 1]);
            $headers1 = $woocommerce->http->getResponse()->getHeaders();
            $total1 = isset($headers1['X-WP-Total']) ? $headers1['X-WP-Total'][0] : 'Not found';
            $this->line("   Total: {$total1}");
            $this->line("   Response count: " . count($response1));
            
            
            $this->line('2. Products with status=publish:');
            $response2 = $woocommerce->get('products', ['per_page' => 1, 'status' => 'publish']);
            $headers2 = $woocommerce->http->getResponse()->getHeaders();
            $total2 = isset($headers2['X-WP-Total']) ? $headers2['X-WP-Total'][0] : 'Not found';
            $this->line("   Total: {$total2}");
            $this->line("   Response count: " . count($response2));

            
            $this->line('3. Products with status=any:');
            $response3 = $woocommerce->get('products', ['per_page' => 1, 'status' => 'any']);
            $headers3 = $woocommerce->http->getResponse()->getHeaders();
            $total3 = isset($headers3['X-WP-Total']) ? $headers3['X-WP-Total'][0] : 'Not found';
            $this->line("   Total: {$total3}");
            $this->line("   Response count: " . count($response3));

            $this->newLine();

            
            $this->line('4. Product categories:');
            $response4 = $woocommerce->get('products/categories', ['per_page' => 1]);
            $headers4 = $woocommerce->http->getResponse()->getHeaders();
            $total4 = isset($headers4['X-WP-Total']) ? $headers4['X-WP-Total'][0] : 'Not found';
            $this->line("   Total: {$total4}");
            $this->line("   Response count: " . count($response4));

            $this->newLine();

        
            $this->line('5. First 5 published products (checking actual status):');
            $publishedProducts = $woocommerce->get('products', ['per_page' => 5, 'status' => 'publish']);
            foreach ($publishedProducts as $product) {
                $this->line("   ID: {$product->id}, Name: " . substr($product->name, 0, 40) . "..., Status: {$product->status}");
            }

            $this->newLine();

    
            $this->line('6. Product types breakdown:');
            $allProducts = $woocommerce->get('products', ['per_page' => 100, 'status' => 'any']);
            $statusCount = [];
            $typeCount = [];
            
            foreach ($allProducts as $product) {
                $statusCount[$product->status] = ($statusCount[$product->status] ?? 0) + 1;
                $typeCount[$product->type] = ($typeCount[$product->type] ?? 0) + 1;
            }
            
            $this->line('   Status breakdown:');
            foreach ($statusCount as $status => $count) {
                $this->line("     {$status}: {$count}");
            }
            
            $this->line('   Type breakdown:');
            foreach ($typeCount as $type => $count) {
                $this->line("     {$type}: {$count}");
            }

            $this->newLine();
            $this->info('Debug completed!');

        } catch (Exception $e) {
            $this->error('Debug failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
