#!/bin/bash

echo "🚀 Setting up PHP Webshop..."

# Install composer dependencies
if [ ! -f "vendor/autoload.php" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install
fi

# Create database if it doesn't exist
echo "🗄️  Setting up database..."
mysql -u${DB_USER:-root} -p${DB_PASS} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME:-webshop};"

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage/
chmod -R 755 public/assets/images/uploads/

# Create .gitkeep files
touch storage/logs/.gitkeep
touch storage/cache/.gitkeep
touch storage/uploads/.gitkeep
touch public/assets/images/uploads/.gitkeep

echo "✅ Setup complete!"
echo "🌐 Access your webshop at: http://localhost"
echo "🔐 Admin login: http://localhost/admin (admin/admin123)"
