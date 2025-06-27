<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Category;

class AdminCategoryController extends BaseController
{
    public function index(): void
    {
        $category_model = new Category();
        $categories = $category_model->get_with_product_counts();
        
        $this->render('admin/categories/index.twig', [
            'categories' => $categories,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/categories/create.twig', [
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function store(): void
    {
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['name'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect('/admin/categories/create');
            return;
        }
        
        $slug = $this->generate_slug($data['name']);
        
        $category_data = [
            'name' => $this->sanitize_input($data['name']),
            'slug' => $slug,
            'description' => $this->sanitize_input($data['description'] ?? ''),
            'image' => $this->sanitize_input($data['image'] ?? ''),
        ];
        
        try {
            $category_model = new Category();
            $category_model->create($category_data);
            
            $this->add_flash_message('success', 'Categorie succesvol toegevoegd');
            $this->redirect('/admin/categories');
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het opslaan van de categorie');
            $this->redirect('/admin/categories/create');
        }
    }

    public function edit(int $id): void
    {
        $category_model = new Category();
        $category = $category_model->find_by_id($id);
        
        if (!$category) {
            $this->add_flash_message('error', 'Categorie niet gevonden');
            $this->redirect('/admin/categories');
            return;
        }
        
        $this->render('admin/categories/edit.twig', [
            'category' => $category,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function update(int $id): void
    {
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['name'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect("/admin/categories/{$id}/edit");
            return;
        }
        
        $slug = $this->generate_slug($data['name']);
        
        $category_data = [
            'name' => $this->sanitize_input($data['name']),
            'slug' => $slug,
            'description' => $this->sanitize_input($data['description'] ?? ''),
            'image' => $this->sanitize_input($data['image'] ?? ''),
        ];
        
        try {
            $category_model = new Category();
            $category_model->update($id, $category_data);
            
            $this->add_flash_message('success', 'Categorie succesvol bijgewerkt');
            $this->redirect('/admin/categories');
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het bijwerken van de categorie');
            $this->redirect("/admin/categories/{$id}/edit");
        }
    }

    public function delete(int $id): void
    {
        try {
            $category_model = new Category();
            $category = $category_model->find_by_id($id);
            
            if (!$category) {
                $this->add_flash_message('error', 'Categorie niet gevonden');
            } elseif ($category_model->has_products($id)) {
                $this->add_flash_message('error', 'Kan categorie niet verwijderen - er zijn nog producten gekoppeld');
            } else {
                $category_model->delete($id);
                $this->add_flash_message('success', 'Categorie succesvol verwijderd');
            }
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het verwijderen van de categorie');
        }
        
        $this->redirect('/admin/categories');
    }
}
