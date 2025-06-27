<?php
// Validation script for Part 3 installation

$errors = [];
$warnings = [];

// Check required directories
$required_dirs = [
    'templates/categories',
    'templates/cart',
    'templates/components',
    'public/assets/css',
    'public/assets/js',
    'config'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        $errors[] = "Directory missing: $dir";
    }
}

// Check required files
$required_files = [
    'templates/categories/index.twig',
    'templates/categories/show.twig',
    'templates/cart/show.twig',
    'templates/cart/empty.twig',
    'templates/components/breadcrumbs.twig',
    'templates/components/product-card.twig',
    'public/assets/css/custom.css',
    'public/assets/js/app.js',
    'config/routes.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $errors[] = "File missing: $file";
    }
}

// Check file permissions
$writable_dirs = [
    'storage/logs',
    'storage/cache',
    'storage/uploads',
    'public/assets/images/uploads'
];

foreach ($writable_dirs as $dir) {
    if (is_dir($dir) && !is_writable($dir)) {
        $warnings[] = "Directory not writable: $dir";
    }
}

// Output results
echo "Part 3 Installation Validation Results:\n";
echo "=====================================\n\n";

if (empty($errors)) {
    echo "✅ All required files and directories are present!\n\n";
} else {
    echo "❌ Errors found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "🎉 Part 3: Category & Cart Templates installation completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Update your controllers to use these templates\n";
    echo "2. Test the category and cart functionality\n";
    echo "3. Customize the styling to match your brand\n";
    echo "4. Add any additional features you need\n";
} else {
    echo "❌ Please fix the errors above before proceeding.\n";
    exit(1);
}
