<?php
// Validation script for Admin Panel installation

$errors = [];
$warnings = [];

// Check required directories
$required_dirs = [
    'templates/admin',
    'templates/admin/products',
    'public/assets/css',
    'public/assets/js'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        $errors[] = "Directory missing: $dir";
    }
}

// Check required files
$required_files = [
    'templates/admin/base.twig',
    'templates/admin/login.twig',
    'templates/admin/dashboard.twig',
    'templates/admin/products/index.twig',
    'templates/admin/products/create.twig',
    'public/assets/css/admin.css',
    'public/assets/js/admin.js'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $errors[] = "File missing: $file";
    }
}

// Output results
echo "Admin Panel Installation Validation Results:\n";
echo "==========================================\n\n";

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
    echo "🎉 Admin Panel templates installation completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Create admin controllers\n";
    echo "2. Set up admin authentication\n";
    echo "3. Configure admin routes\n";
    echo "4. Test the admin functionality\n";
} else {
    echo "❌ Please fix the errors above before proceeding.\n";
    exit(1);
}
