# PHP Webshop

Modern PHP webshop built with Bramus Router, Twig templates, and TailwindCSS.

## Features

- **Frontend**: Product catalog, shopping cart, checkout
- **Admin Panel**: Product/category management, order processing, custom pages
- **API**: RESTful endpoints for order management
- **Webhook**: Integration with external systems

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure
4. Create MySQL database
5. Run setup script: `bash scripts/setup.sh`
6. Access admin at `/admin/login` (admin/admin123)

## Development

- **PHPStan**: `vendor/bin/phpstan analyse`
- **Tests**: `vendor/bin/phpunit`

## API Documentation

See `docs/api-documentation.md` for detailed API usage.


## TREE info

webshop/
├── composer.json
├── composer.lock
├── .env
├── .env.example
├── phpstan.neon
├── README.md
├── .gitignore
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/
│   │   │   └── custom.css
│   │   ├── js/
│   │   │   └── app.js
│   │   └── images/
│   │       ├── placeholder.jpg
│   │       └── uploads/
│   │           ├── products/
│   │           └── categories/
│   └── favicon.ico
│
├── src/
│   ├── Core/
│   │   ├── Application.php
│   │   └── Database.php
│   │
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Order.php
│   │   ├── Page.php
│   │   └── AdminUser.php
│   │
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── CartController.php
│   │   ├── OrderController.php
│   │   ├── PageController.php
│   │   │
│   │   ├── Admin/
│   │   │   ├── AdminController.php
│   │   │   ├── AdminProductController.php
│   │   │   ├── AdminCategoryController.php
│   │   │   ├── AdminOrderController.php
│   │   │   └── AdminPageController.php
│   │   │
│   │   └── Api/
│   │       └── OrderApiController.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── AdminMiddleware.php
│   │
│   ├── Services/
│   │   ├── ImageUploadService.php
│   │   ├── EmailService.php
│   │   └── PaymentService.php
│   │
│   └── Helpers/
│       ├── StringHelper.php
│       ├── ValidationHelper.php
│       └── SlugHelper.php
│
├── templates/
│   ├── base.twig
│   ├── home.twig
│   │
│   ├── products/
│   │   ├── index.twig
│   │   ├── show.twig
│   │   └── search.twig
│   │
│   ├── categories/
│   │   ├── index.twig
│   │   └── show.twig
│   │
│   ├── cart/
│   │   ├── show.twig
│   │   └── empty.twig
│   │
│   ├── orders/
│   │   ├── checkout.twig
│   │   ├── success.twig
│   │   └── failed.twig
│   │
│   ├── pages/
│   │   └── show.twig
│   │
│   ├── admin/
│   │   ├── base.twig
│   │   ├── login.twig
│   │   ├── dashboard.twig
│   │   │
│   │   ├── products/
│   │   │   ├── index.twig
│   │   │   ├── create.twig
│   │   │   ├── edit.twig
│   │   │   └── show.twig
│   │   │
│   │   ├── categories/
│   │   │   ├── index.twig
│   │   │   ├── create.twig
│   │   │   └── edit.twig
│   │   │
│   │   ├── orders/
│   │   │   ├── index.twig
│   │   │   └── show.twig
│   │   │
│   │   └── pages/
│   │       ├── index.twig
│   │       ├── create.twig
│   │       └── edit.twig
│   │
│   ├── errors/
│   │   ├── 404.twig
│   │   ├── 500.twig
│   │   └── 403.twig
│   │
│   └── components/
│       ├── navigation.twig
│       ├── product-card.twig
│       ├── pagination.twig
│       └── breadcrumbs.twig
│
├── config/
│   ├── database.php
│   ├── app.php
│   └── routes.php
│
├── storage/
│   ├── logs/
│   │   ├── app.log
│   │   └── error.log
│   ├── cache/
│   │   └── twig/
│   └── uploads/
│       ├── products/
│       └── categories/
│
├── database/
│   ├── migrations/
│   │   ├── 001_create_categories_table.sql
│   │   ├── 002_create_products_table.sql
│   │   ├── 003_create_orders_table.sql
│   │   ├── 004_create_order_items_table.sql
│   │   ├── 005_create_pages_table.sql
│   │   └── 006_create_admin_users_table.sql
│   │
│   ├── seeders/
│   │   ├── CategorySeeder.php
│   │   ├── ProductSeeder.php
│   │   ├── PageSeeder.php
│   │   └── AdminUserSeeder.php
│   │
│   └── schema.sql
│
├── tests/
│   ├── Unit/
│   │   ├── Models/
│   │   │   ├── ProductTest.php
│   │   │   ├── CategoryTest.php
│   │   │   └── OrderTest.php
│   │   │
│   │   └── Services/
│   │       └── PaymentServiceTest.php
│   │
│   ├── Integration/
│   │   ├── Controllers/
│   │   │   ├── ProductControllerTest.php
│   │   │   └── CartControllerTest.php
│   │   │
│   │   └── Api/
│   │       └── OrderApiTest.php
│   │
│   └── TestCase.php
│
├── vendor/
│   └── (Composer dependencies)
│
├── docs/
│   ├── installation.md
│   ├── api-documentation.md
│   ├── admin-guide.md
│   └── development.md
│
└── scripts/
    ├── deploy.sh
    ├── backup.sh
    └── setup.sh
File Details & Descriptions:
Root Files:

composer.json - Dependencies en autoload configuratie
.env - Environment variabelen (database, secrets)
phpstan.neon - Static analysis configuratie
.gitignore - Git ignore regels

Public Directory:

index.php - Entry point van de applicatie
.htaccess - Apache rewrite rules voor clean URLs
assets/ - Static files (CSS, JS, images)

Source Code (src/):

Core/ - Core applicatie classes
Models/ - Database models met Eloquent-style methods
Controllers/ - Request handling en business logic
Middleware/ - Authentication en authorization
Services/ - Business services (email, payment, upload)
Helpers/ - Utility functions

Templates (templates/):

Twig templates georganiseerd per functionaliteit
admin/ - Aparte admin interface templates
components/ - Herbruikbare componenten
errors/ - Error pages

Configuration (config/):

Configuratie bestanden voor verschillende aspecten

Storage (storage/):

logs/ - Application logs
cache/ - Template cache
uploads/ - User uploaded files

Database (database/):

migrations/ - Database schema wijzigingen
seeders/ - Test data generators
schema.sql - Complete database schema

Testing (tests/):

Unit tests voor models en services
Integration tests voor controllers en API

Documentation (docs/):

Installatie instructies
API documentatie
Admin handleiding
Development guide

Scripts (scripts/):

Deployment scripts
Backup scripts
Setup automation

Key Features per Directory:
Admin Panel Structure:
/admin/login          - Admin authentication
/admin/dashboard      - Statistics overview
/admin/products       - CRUD operations
/admin/categories     - Category management
/admin/orders         - Order management
/admin/pages          - Custom page creation
API Endpoints:
GET  /api/orders           - List all orders
GET  /api/orders/{id}      - Get specific order
POST /api/webhook/orders   - Webhook for external systems
Frontend Routes:
/                     - Homepage
/products             - Product catalog
/products/{id}        - Product detail
/categories           - Category listing
/categories/{id}      - Products by category
/cart                 - Shopping cart
/pages/{slug}         - Dynamic pages
Deze structuur biedt een schaalbare, maintainbare basis voor je webshop met duidelijke scheiding van concerns en moderne PHP practices!
