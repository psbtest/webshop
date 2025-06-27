<?php
// Validation script for Pages Basic Setup installation

$errors = [];
$warnings = [];

// Check required directories
$required_dirs = [
    'templates/pages'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        $errors[] = "Directory missing: $dir";
    }
}

// Check required files
$required_files = [
    'templates/pages/show.twig'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $errors[] = "File missing: $file";
    }
}

// Check if CSS was updated
$css_file = 'public/assets/css/custom.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);
    if (strpos($css_content, 'Basic Pages Styles') === false) {
        $warnings[] = "CSS file may not have been updated with new styles";
    }
} else {
    $errors[] = "CSS file missing: $css_file";
}

// Output results
echo "Pages Basic Setup Installation Validation Results:\n";
echo "================================================\n\n";

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
    echo "🎉 Pages Basic Setup installation completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Run the Error Templates install script\n";
    echo "2. Run the Components install script\n";
    echo "3. Create page controllers and routes\n";
    echo "4. Test page functionality\n";
} else {
    echo "❌ Please fix the errors above before proceeding.\n";
    exit(1);
}
