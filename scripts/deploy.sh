#!/bin/bash

echo "🚀 Deploying webshop..."

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
rm -rf storage/cache/twig/*

# Set production permissions
chmod -R 755 storage/
chmod -R 755 public/

echo "✅ Deployment complete!"
