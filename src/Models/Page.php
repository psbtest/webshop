<?php
declare(strict_types=1);

namespace App\Models;

class Page extends BaseModel
{
    protected string $table = 'pages';

    /**
     * @return array<string, mixed>|null
     */
    public function find_by_slug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function find_active(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function slug_exists(string $slug, ?int $exclude_id = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($exclude_id !== null) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_menu_pages(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, title, slug 
            FROM {$this->table} 
            WHERE is_active = 1 
            ORDER BY title ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function toggle_status(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
