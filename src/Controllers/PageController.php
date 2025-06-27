<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Page;

class PageController extends BaseController
{
    public function show(string $slug): void
    {
        $page_model = new Page();
        $page = $page_model->find_by_slug($slug);
        
        if (!$page) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }
        
        $this->render('pages/show.twig', [
            'page' => $page,
        ]);
    }

    public function sitemap(): void
    {
        $page_model = new Page();
        $pages = $page_model->get_menu_pages();
        
        $this->render('pages/sitemap.twig', [
            'pages' => $pages,
        ]);
    }
}
