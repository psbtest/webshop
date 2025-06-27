<?php
declare(strict_types=1);

namespace App\Models;

class Order extends BaseModel
{
    protected string $table = 'orders';

    /**
     * @param array<string, mixed> $order_data
     * @param array<int, array<string, mixed>> $items
     */
    public function create_with_items(array $order_data, array $items): int
    {
        $this->db->beginTransaction();
        
        try {
            $order_id = $this->create($order_data);
            
            foreach ($items as $item) {
                $this->db->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ")->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update stock
                $product_model = new Product();
                $product_model->update_stock($item['product_id'], $item['quantity']);
            }
            
            $this->db->commit();
            return $order_id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find_with_items(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(
                       CONCAT(oi.quantity, 'x ', p.name, ' (€', oi.price, ')') 
                       SEPARATOR ', '
                   ) as items_summary
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.id = ?
            GROUP BY o.id
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function find_items(int $order_id): array
    {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll();
    }

    public function update_status(int $id, string $status): bool
    {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function find_by_status(string $status): array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_recent_orders(int $limit = 10): array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function get_total_revenue(): float
    {
        $stmt = $this->db->prepare("SELECT SUM(total_amount) FROM orders WHERE status IN ('processing', 'shipped', 'delivered')");
        $stmt->execute();
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function get_order_stats(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                AVG(total_amount) as average_order_value
            FROM orders
        ");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    }
}
