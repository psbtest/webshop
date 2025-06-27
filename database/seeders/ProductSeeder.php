<?php

namespace App\Database\Seeders;

use App\Core\Application;

class ProductSeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->get_database()->get_connection();
    }

    public function run(): void
    {
        $products = [
            // Electronics
            [
                'name' => 'Wireless Bluetooth Headphones Premium',
                'slug' => 'wireless-bluetooth-headphones-premium',
                'description' => 'High-quality wireless headphones with active noise cancellation, 30-hour battery life, and premium sound quality. Perfect for music lovers and professionals.',
                'price' => 129.99,
                'stock_quantity' => 25,
                'category_id' => 1
            ],
            [
                'name' => 'Smartphone 256GB Pro',
                'slug' => 'smartphone-256gb-pro',
                'description' => 'Latest flagship smartphone with 256GB storage, triple camera system, 5G connectivity, and all-day battery life.',
                'price' => 899.99,
                'stock_quantity' => 15,
                'category_id' => 1
            ],
            [
                'name' => 'Wireless Charging Pad',
                'slug' => 'wireless-charging-pad',
                'description' => 'Fast wireless charging pad compatible with all Qi-enabled devices. Sleek design with LED indicator.',
                'price' => 34.99,
                'stock_quantity' => 40,
                'category_id' => 1
            ],
            [
                'name' => 'Smart Watch Fitness Tracker',
                'slug' => 'smart-watch-fitness-tracker',
                'description' => 'Advanced fitness tracker with heart rate monitoring, GPS, sleep tracking, and 7-day battery life.',
                'price' => 199.99,
                'stock_quantity' => 20,
                'category_id' => 1
            ],
            
            // Clothing & Fashion
            [
                'name' => 'Premium Cotton T-Shirt',
                'slug' => 'premium-cotton-t-shirt',
                'description' => 'Comfortable 100% organic cotton t-shirt in various colors. Perfect fit and sustainable production.',
                'price' => 24.99,
                'stock_quantity' => 75,
                'category_id' => 2
            ],
            [
                'name' => 'Denim Jeans Classic Fit',
                'slug' => 'denim-jeans-classic-fit',
                'description' => 'Classic fit denim jeans made from premium denim fabric. Timeless style that never goes out of fashion.',
                'price' => 79.99,
                'stock_quantity' => 45,
                'category_id' => 2
            ],
            [
                'name' => 'Winter Wool Sweater',
                'slug' => 'winter-wool-sweater',
                'description' => 'Warm and cozy wool sweater perfect for cold weather. Available in multiple colors and sizes.',
                'price' => 89.99,
                'stock_quantity' => 30,
                'category_id' => 2
            ],
            
            // Books & Literature
            [
                'name' => 'Web Development Complete Guide',
                'slug' => 'web-development-complete-guide',
                'description' => 'Comprehensive guide to modern web development covering HTML, CSS, JavaScript, PHP, and best practices.',
                'price' => 39.99,
                'stock_quantity' => 35,
                'category_id' => 3
            ],
            [
                'name' => 'Digital Marketing Strategies',
                'slug' => 'digital-marketing-strategies',
                'description' => 'Learn effective digital marketing strategies including SEO, social media, content marketing, and analytics.',
                'price' => 34.99,
                'stock_quantity' => 28,
                'category_id' => 3
            ],
            
            // Home & Garden
            [
                'name' => 'Professional Garden Tools Set',
                'slug' => 'professional-garden-tools-set',
                'description' => 'Complete set of professional-grade garden tools including spade, rake, pruners, and trowel.',
                'price' => 129.99,
                'stock_quantity' => 18,
                'category_id' => 4
            ],
            [
                'name' => 'LED Desk Lamp Adjustable',
                'slug' => 'led-desk-lamp-adjustable',
                'description' => 'Modern LED desk lamp with adjustable brightness, color temperature control, and USB charging port.',
                'price' => 59.99,
                'stock_quantity' => 32,
                'category_id' => 4
            ],
            [
                'name' => 'Indoor Plant Starter Kit',
                'slug' => 'indoor-plant-starter-kit',
                'description' => 'Everything you need to start your indoor garden including pots, soil, seeds, and care instructions.',
                'price' => 29.99,
                'stock_quantity' => 50,
                'category_id' => 4
            ],
            
            // Sports & Outdoor
            [
                'name' => 'Yoga Mat Professional',
                'slug' => 'yoga-mat-professional',
                'description' => 'Non-slip professional yoga mat with superior grip and cushioning. Eco-friendly and durable.',
                'price' => 49.99,
                'stock_quantity' => 42,
                'category_id' => 5
            ],
            [
                'name' => 'Adjustable Dumbbells Set',
                'slug' => 'adjustable-dumbbells-set',
                'description' => 'Space-saving adjustable dumbbells with quick weight change system. Perfect for home workouts.',
                'price' => 199.99,
                'stock_quantity' => 12,
                'category_id' => 5
            ],
            [
                'name' => 'Hiking Backpack 40L',
                'slug' => 'hiking-backpack-40l',
                'description' => 'Durable 40L hiking backpack with multiple compartments, hydration system compatibility, and rain cover.',
                'price' => 89.99,
                'stock_quantity' => 22,
                'category_id' => 5
            ]
        ];

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO products (name, slug, description, price, stock_quantity, category_id, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($products as $product) {
            $stmt->execute([
                $product['name'],
                $product['slug'],
                $product['description'],
                $product['price'],
                $product['stock_quantity'],
                $product['category_id']
            ]);
        }

        echo "Products seeded successfully!\n";
    }
}
