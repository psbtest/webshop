<?php
declare(strict_types=1);

namespace App\Models;

class Category extends BaseModel
{
    protected string $table = 'categories';

    /**
     * @return array<string, mixed>|null
     */
    public function find_by_slug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ?");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function get_product_count(int $category_id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND is_active = 1");
        $stmt->execute([$category_id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_with_product_counts(): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1 
            GROUP BY c.id 
            ORDER BY c.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function has_products(int $category_id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$category_id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_popular_categories(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            INNER JOIN products p ON c.id = p.category_id AND p.is_active = 1 
            GROUP BY c.id 
            ORDER BY product_count DESC, c.name ASC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
