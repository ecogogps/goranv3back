<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Automattic\WooCommerce\Client;
use Exception;

class TestWooCommerceConnection extends Command
{
    protected $signature = 'woocommerce:test-connection';
    protected $description = 'Tests the connection to the WooCommerce API and reports any errors.';

    public function handle()
    {
        $this->info('Attempting to connect to WooCommerce...');

        try {
            // configuración (sin secretos)
            $this->line('Store URL: ' . config('woocommerce.store_url'));
            $this->line('Client Key: ' . substr(config('woocommerce.client_key'), 0, 10) . '...');
            
            $woocommerce = new Client(
                config('woocommerce.store_url'),
                config('woocommerce.client_key'),
                config('woocommerce.client_secret'),
                [
                    'version' => 'wc/v3',
                    'timeout' => 30,
                    'verify_ssl' => false, // Solo para debugging, quitar en producción
                ]
            );

            $this->line('Client initialized. Testing different endpoints...');

            
            $publishedProducts = $woocommerce->get('products', ['status' => 'publish', 'per_page' => 1]);
            $this->line('Published products: ' . count($publishedProducts));

           
            $allProducts = $woocommerce->get('products', ['status' => 'any', 'per_page' => 100]);
            $this->line('All products (any status): ' . count($allProducts));

           
            if (count($allProducts) > 0) {
                $this->newLine();
                $this->line('Product details:');
                foreach ($allProducts as $product) {
                    $this->line("- ID: {$product->id}, Name: {$product->name}, Status: {$product->status}");
                }
            }

            
            $response = $woocommerce->get('products', ['per_page' => 1]);
            $headers = $woocommerce->http->getResponse()->getHeaders();
            
            $this->newLine();
            $this->line('Response headers:');
            if (isset($headers['X-WP-Total'])) {
                $this->line('X-WP-Total: ' . $headers['X-WP-Total'][0]);
            }
            if (isset($headers['X-WP-TotalPages'])) {
                $this->line('X-WP-TotalPages: ' . $headers['X-WP-TotalPages'][0]);
            }

            
            $this->newLine();
            $this->line('Testing store information...');
            $systemStatus = $woocommerce->get('system_status');
            $this->line('WooCommerce Version: ' . $systemStatus->environment->version);

            
            $categories = $woocommerce->get('products/categories', ['per_page' => 5]);
            $this->line('Product categories found: ' . count($categories));

            $this->newLine();
            $this->info('Connection test completed successfully!');

        } catch (Exception $e) {
            $this->error('Connection Failed!');
            $this->line('An error occurred while trying to connect to the WooCommerce API.');
            $this->newLine();
            $this->error('Error Message:');
            $this->line($e->getMessage());
            
           
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $this->newLine();
                $this->error('HTTP Response:');
                $this->line('Status Code: ' . $e->getResponse()->getStatusCode());
                $this->line('Body: ' . $e->getResponse()->getBody());
            }
        }
    }
}
