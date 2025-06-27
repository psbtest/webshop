<?php

namespace App\Database\Seeders;

use App\Core\Application;

class CategorySeeder
{
    private $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->get_database()->get_connection();
    }

    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices, gadgets, and accessories for modern living'
            ],
            [
                'name' => 'Clothing & Fashion',
                'slug' => 'clothing-fashion',
                'description' => 'Trendy clothing, shoes, and fashion accessories for all ages'
            ],
            [
                'name' => 'Books & Literature',
                'slug' => 'books-literature',
                'description' => 'Books, eBooks, and educational materials across all genres'
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement, furniture, and garden supplies'
            ],
            [
                'name' => 'Sports & Outdoor',
                'slug' => 'sports-outdoor',
                'description' => 'Sports equipment, outdoor gear, and fitness accessories'
            ],
            [
                'name' => 'Health & Beauty',
                'slug' => 'health-beauty',
                'description' => 'Health products, beauty items, and personal care essentials'
            ],
            [
                'name' => 'Toys & Games',
                'slug' => 'toys-games',
                'description' => 'Toys, board games, and entertainment for children and adults'
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car accessories, tools, and automotive maintenance products'
            ]
        ];

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO categories (name, slug, description) 
            VALUES (?, ?, ?)
        ");

        foreach ($categories as $category) {
            $stmt->execute([
                $category['name'],
                $category['slug'],
                $category['description']
            ]);
        }

        echo "Categories seeded successfully!\n";
    }
}
