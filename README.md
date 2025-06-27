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
