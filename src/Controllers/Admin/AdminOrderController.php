<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Order;

class AdminOrderController extends BaseController
{
    public function index(): void
    {
        $order_model = new Order();
        $get_data = $this->get_get_data();
        
        $status_filter = $get_data['status'] ?? '';
        
        if (!empty($status_filter)) {
            $orders = $order_model->find_by_status($status_filter);
        } else {
            $orders = $order_model->find_all();
        }
        
        $order_stats = $order_model->get_order_stats();
        
        $this->render('admin/orders/index.twig', [
            'orders' => $orders,
            'order_stats' => $order_stats,
            'status_filter' => $status_filter,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function show(int $id): void
    {
        $order_model = new Order();
        $order = $order_model->find_by_id($id);
        $items = $order_model->find_items($id);
        
        if (!$order) {
            $this->add_flash_message('error', 'Bestelling niet gevonden');
            $this->redirect('/admin/orders');
            return;
        }
        
        $this->render('admin/orders/show.twig', [
            'order' => $order,
            'items' => $items,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function update_status(int $id): void
    {
        $status = $_POST['status'] ?? '';
        
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            $this->add_flash_message('error', 'Ongeldige status');
            $this->redirect('/admin/orders');
            return;
        }
        
        try {
            $order_model = new Order();
            
            if ($order_model->update_status($id, $status)) {
                $this->add_flash_message('success', 'Bestelling status succesvol bijgewerkt');
            } else {
                $this->add_flash_message('error', 'Kon bestelling status niet bijwerken');
            }
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het bijwerken van de status');
        }
        
        $this->redirect('/admin/orders');
    }

    public function export(): void
    {
        $order_model = new Order();
        $orders = $order_model->find_all();
        
        $filename = 'orders_export_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Order ID',
            'Customer Name', 
            'Customer Email',
            'Customer Phone',
            'Total Amount',
            'Status',
            'Created At'
        ]);
        
        // CSV data
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['id'],
                $order['customer_name'],
                $order['customer_email'],
                $order['customer_phone'],
                $order['total_amount'],
                $order['status'],
                $order['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
