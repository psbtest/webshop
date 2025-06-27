<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Product;
use App\Models\Category;

class AdminProductController extends BaseController
{
    public function index(): void
    {
        $product_model = new Product();
        $products = $product_model->find_all();
        
        $this->render('admin/products/index.twig', [
            'products' => $products,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function create(): void
    {
        $category_model = new Category();
        $categories = $category_model->find_all();
        
        $this->render('admin/products/create.twig', [
            'categories' => $categories,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function store(): void
    {
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['name', 'price', 'stock_quantity'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect('/admin/products/create');
            return;
        }
        
        // Validate price and stock
        if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
            $this->add_flash_message('error', 'Prijs moet een geldig getal zijn');
            $this->redirect('/admin/products/create');
            return;
        }
        
        if (!is_numeric($data['stock_quantity']) || (int) $data['stock_quantity'] < 0) {
            $this->add_flash_message('error', 'Voorraad moet een geldig getal zijn');
            $this->redirect('/admin/products/create');
            return;
        }
        
        $slug = $this->generate_slug($data['name']);
        
        $product_data = [
            'name' => $this->sanitize_input($data['name']),
            'slug' => $slug,
            'description' => $this->sanitize_input($data['description'] ?? ''),
            'price' => (float) $data['price'],
            'stock_quantity' => (int) $data['stock_quantity'],
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'image' => $this->sanitize_input($data['image'] ?? ''),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
        
        try {
            $product_model = new Product();
            $product_model->create($product_data);
            
            $this->add_flash_message('success', 'Product succesvol toegevoegd');
            $this->redirect('/admin/products');
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het opslaan van het product');
            $this->redirect('/admin/products/create');
        }
    }

    public function edit(int $id): void
    {
        $product_model = new Product();
        $category_model = new Category();
        
        $product = $product_model->find_by_id($id);
        $categories = $category_model->find_all();
        
        if (!$product) {
            $this->add_flash_message('error', 'Product niet gevonden');
            $this->redirect('/admin/products');
            return;
        }
        
        $this->render('admin/products/edit.twig', [
            'product' => $product,
            'categories' => $categories,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function update(int $id): void
    {
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['name', 'price', 'stock_quantity'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect("/admin/products/{$id}/edit");
            return;
        }
        
        $slug = $this->generate_slug($data['name']);
        
        $product_data = [
            'name' => $this->sanitize_input($data['name']),
            'slug' => $slug,
            'description' => $this->sanitize_input($data['description'] ?? ''),
            'price' => (float) $data['price'],
            'stock_quantity' => (int) $data['stock_quantity'],
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'image' => $this->sanitize_input($data['image'] ?? ''),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
        
        try {
            $product_model = new Product();
            $product_model->update($id, $product_data);
            
            $this->add_flash_message('success', 'Product succesvol bijgewerkt');
            $this->redirect('/admin/products');
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het bijwerken van het product');
            $this->redirect("/admin/products/{$id}/edit");
        }
    }

    public function delete(int $id): void
    {
        try {
            $product_model = new Product();
            $product = $product_model->find_by_id($id);
            
            if (!$product) {
                $this->add_flash_message('error', 'Product niet gevonden');
            } else {
                $product_model->delete($id);
                $this->add_flash_message('success', 'Product succesvol verwijderd');
            }
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Kan product niet verwijderen - mogelijk wordt het nog gebruikt in bestellingen');
        }
        
        $this->redirect('/admin/products');
    }

    public function show(int $id): void
    {
        $product_model = new Product();
        $product = $product_model->find_with_category($id);
        
        if (!$product) {
            $this->add_flash_message('error', 'Product niet gevonden');
            $this->redirect('/admin/products');
            return;
        }
        
        $this->render('admin/products/show.twig', [
            'product' => $product,
        ]);
    }
}
