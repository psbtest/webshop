<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Page;

class AdminPageController extends BaseController
{
    public function index(): void
    {
        $page_model = new Page();
        $pages = $page_model->find_all();
        
        $this->render('admin/pages/index.twig', [
            'pages' => $pages,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/pages/create.twig', [
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function store(): void
    {
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['title', 'content'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect('/admin/pages/create');
            return;
        }
        
        $slug = $this->generate_slug($data['title']);
        
        // Check if slug already exists
        $page_model = new Page();
        if ($page_model->slug_exists($slug)) {
            $slug = $slug . '-' . time();
        }
        
        $page_data = [
            'title' => $this->sanitize_input($data['title']),
            'slug' => $slug,
            'content' => $data['content'], // Don't sanitize content to allow HTML
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
        
        try {
            $page_model->create($page_data);
            
            $this->add_flash_message('success', 'Pagina succesvol toegevoegd');
            $this->redirect('/admin/pages');
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het opslaan van de pagina');
            $this->redirect('/admin/pages/create');
        }
    }

    public function edit(int $id): void
    {
        $page_model = new Page();
        $page = $page_model->find_by_id($id);
        
        if (!$page) {
            $this->add_flash_message('error', 'Pagina niet gevonden');
            $this->redirect('/admin/pages');
            return;
        }
        
        $this->render('admin/pages/edit.twig', [
            'page' => $page,
            'flash_messages' => $this->get_flash_messages(),
        ]);
    }

    public function update(int $id): void
    {
        $data = $this->get_post_data();
        
        // Validate required fields
        $required_fields = ['title', 'content'];
        $errors = $this->validate_required_fields($data, $required_fields);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_flash_message('error', $error);
            }
            $this->redirect("/admin/pages/{$id}/edit");
            return;
        }
        
        $slug = $this->generate_slug($data['title']);
        
        // Check if slug already exists (excluding current page)
        $page_model = new Page();
        if ($page_model->slug_exists($slug, $id)) {
            $slug = $slug . '-' . time();
        }
        
        $page_data = [
            'title' => $this->sanitize_input($data['title']),
            'slug' => $slug,
            'content' => $data['content'], // Don't sanitize content to allow HTML
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
        
        try {
            $page_model->update($id, $page_data);
            
            $this->add_flash_message('success', 'Pagina succesvol bijgewerkt');
            $this->redirect('/admin/pages');
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het bijwerken van de pagina');
            $this->redirect("/admin/pages/{$id}/edit");
        }
    }

    public function delete(int $id): void
    {
        try {
            $page_model = new Page();
            $page = $page_model->find_by_id($id);
            
            if (!$page) {
                $this->add_flash_message('error', 'Pagina niet gevonden');
            } else {
                $page_model->delete($id);
                $this->add_flash_message('success', 'Pagina succesvol verwijderd');
            }
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het verwijderen van de pagina');
        }
        
        $this->redirect('/admin/pages');
    }

    public function toggle_status(int $id): void
    {
        try {
            $page_model = new Page();
            
            if ($page_model->toggle_status($id)) {
                $this->add_flash_message('success', 'Pagina status succesvol gewijzigd');
            } else {
                $this->add_flash_message('error', 'Kon pagina status niet wijzigen');
            }
        } catch (\Exception $e) {
            $this->add_flash_message('error', 'Er is een fout opgetreden bij het wijzigen van de status');
        }
        
        $this->redirect('/admin/pages');
    }
}
