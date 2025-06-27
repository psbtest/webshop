<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Order;

class OrderApiController extends BaseController
{
    public function index(): void
    {
        if (!$this->authenticate_api()) {
            $this->json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        
        $order_model = new Order();
        $get_data = $this->get_get_data();
        
        // Pagination
        $page = isset($get_data['page']) ? max(1, (int) $get_data['page']) : 1;
        $per_page = isset($get_data['per_page']) ? min(100, max(1, (int) $get_data['per_page'])) : 20;
        
        // Status filter
        $status = $get_data['status'] ?? '';
        
        if (!empty($status)) {
            $orders = $order_model->find_by_status($status);
        } else {
            $orders = $order_model->paginate($page, $per_page);
        }
        
        // Add items to each order
        foreach ($orders as &$order) {
            $order['items'] = $order_model->find_items($order['id']);
        }
        
        $total_orders = $order_model->count();
        $total_pages = ceil($total_orders / $per_page);
        
        $this->json_response([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_orders' => $total_orders,
                    'total_pages' => $total_pages,
                ],
            ],
        ]);
    }

    public function show(int $id): void
    {
        if (!$this->authenticate_api()) {
            $this->json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        
        $order_model = new Order();
        $order = $order_model->find_by_id($id);
        
        if (!$order) {
            $this->json_response(['error' => 'Order not found'], 404);
            return;
        }
        
        $items = $order_model->find_items($id);
        $order['items'] = $items;
        
        $this->json_response([
            'success' => true,
            'data' => [
                'order' => $order,
            ],
        ]);
    }

    public function webhook(): void
    {
        // Verify webhook signature
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        
        if (empty($payload)) {
            $this->json_response(['error' => 'Empty payload'], 400);
            return;
        }
        
        $expected_signature = hash_hmac('sha256', $payload, $_ENV['WEBHOOK_SECRET'] ?? '');
        
        if (!hash_equals($signature, $expected_signature)) {
            $this->json_response(['error' => 'Invalid signature'], 401);
            return;
        }
        
        $data = json_decode($payload, true);
        
        if (!$data) {
            $this->json_response(['error' => 'Invalid JSON payload'], 400);
            return;
        }
        
        // Process webhook data
        if (isset($data['order_id']) && isset($data['status'])) {
            $order_model = new Order();
            
            if ($order_model->update_status((int) $data['order_id'], $data['status'])) {
                $this->json_response([
                    'success' => true,
                    'message' => 'Order status updated successfully',
                ]);
            } else {
                $this->json_response(['error' => 'Failed to update order status'], 400);
            }
        } else {
            $this->json_response(['error' => 'Missing required fields: order_id, status'], 400);
        }
    }

    public function stats(): void
    {
        if (!$this->authenticate_api()) {
            $this->json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        
        $order_model = new Order();
        $stats = $order_model->get_order_stats();
        $total_revenue = $order_model->get_total_revenue();
        
        $this->json_response([
            'success' => true,
            'data' => [
                'stats' => array_merge($stats, ['total_revenue' => $total_revenue]),
            ],
        ]);
    }

    public function export(): void
    {
        if (!$this->authenticate_api()) {
            $this->json_response(['error' => 'Unauthorized'], 401);
            return;
        }
        
        $order_model = new Order();
        $get_data = $this->get_get_data();
        
        $format = $get_data['format'] ?? 'json';
        $status = $get_data['status'] ?? '';
        
        if (!empty($status)) {
            $orders = $order_model->find_by_status($status);
        } else {
            $orders = $order_model->find_all();
        }
        
        // Add items to each order
        foreach ($orders as &$order) {
            $order['items'] = $order_model->find_items($order['id']);
        }
        
        if ($format === 'csv') {
            $this->export_csv($orders);
        } else {
            $this->json_response([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'exported_at' => date('Y-m-d H:i:s'),
                ],
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     */
    private function export_csv(array $orders): void
    {
        $filename = 'orders_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Customer Address',
            'Total Amount',
            'Status',
            'Items',
            'Created At',
            'Updated At'
        ]);
        
        // CSV data
        foreach ($orders as $order) {
            $items_summary = '';
            if (isset($order['items'])) {
                $item_strings = [];
                foreach ($order['items'] as $item) {
                    $item_strings[] = $item['quantity'] . 'x ' . $item['product_name'] . ' (€' . $item['price'] . ')';
                }
                $items_summary = implode('; ', $item_strings);
            }
            
            fputcsv($output, [
                $order['id'],
                $order['customer_name'],
                $order['customer_email'],
                $order['customer_phone'],
                $order['customer_address'],
                $order['total_amount'],
                $order['status'],
                $items_summary,
                $order['created_at'],
                $order['updated_at']
            ]);
        }
        
        fclose($output);
        exit;
    }

    private function authenticate_api(): bool
    {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
            // Simple token verification - in production, use proper JWT verification
            return $token === ($_ENV['JWT_SECRET'] ?? '');
        }
        
        return false;
    }
}
