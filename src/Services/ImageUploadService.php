<?php
declare(strict_types=1);

namespace App\Services;

class ImageUploadService
{
    private string $upload_path;
    private array $allowed_types;
    private int $max_file_size;
    private array $image_dimensions;

    public function __construct()
    {
        $this->upload_path = __DIR__ . '/../../storage/uploads/';
        $this->allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $this->max_file_size = 5 * 1024 * 1024; // 5MB
        $this->image_dimensions = [
            'max_width' => 2000,
            'max_height' => 2000,
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
        ];
        
        $this->ensure_upload_directories();
    }

    /**
     * Upload and process an image file
     * 
     * @param array<string, mixed> $file $_FILES array element
     * @param string $category Upload category (products, categories, etc.)
     * @return array<string, mixed> Upload result with file paths
     */
    public function upload_image(array $file, string $category = 'general'): array
    {
        try {
            // Validate file
            $validation_result = $this->validate_file($file);
            if (!$validation_result['valid']) {
                return [
                    'success' => false,
                    'error' => $validation_result['error'],
                ];
            }

            // Generate unique filename
            $file_extension = $this->get_file_extension($file['name']);
            $filename = $this->generate_unique_filename($file_extension);
            
            // Create category directory
            $category_path = $this->upload_path . $category . '/';
            $this->ensure_directory_exists($category_path);
            
            $file_path = $category_path . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                return [
                    'success' => false,
                    'error' => 'Failed to move uploaded file',
                ];
            }

            // Process image (resize if needed)
            $processed_image = $this->process_image($file_path);
            
            // Generate thumbnail
            $thumbnail_path = $this->generate_thumbnail($file_path, $category);

            return [
                'success' => true,
                'data' => [
                    'original_name' => $file['name'],
                    'filename' => $filename,
                    'file_path' => $file_path,
                    'relative_path' => "/uploads/{$category}/{$filename}",
                    'thumbnail_path' => $thumbnail_path,
                    'file_size' => filesize($file_path),
                    'mime_type' => $file['type'],
                    'dimensions' => $processed_image['dimensions'],
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate uploaded file
     * 
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    private function validate_file(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => $this->get_upload_error_message($file['error']),
            ];
        }

        // Check file size
        if ($file['size'] > $this->max_file_size) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . ($this->max_file_size / 1024 / 1024) . 'MB',
            ];
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $this->allowed_types)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Allowed types: ' . implode(', ', $this->allowed_types),
            ];
        }

        // Check if file is actually an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return [
                'valid' => false,
                'error' => 'File is not a valid image',
            ];
        }

        return ['valid' => true];
    }

    private function get_upload_error_message(int $error_code): string
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Process image (resize if needed)
     * 
     * @return array<string, mixed>
     */
    private function process_image(string $file_path): array
    {
        $image_info = getimagesize($file_path);
        $width = $image_info[0];
        $height = $image_info[1];
        $mime_type = $image_info['mime'];

        // Check if resize is needed
        if ($width <= $this->image_dimensions['max_width'] && $height <= $this->image_dimensions['max_height']) {
            return [
                'resized' => false,
                'dimensions' => ['width' => $width, 'height' => $height],
            ];
        }

        // Calculate new dimensions
        $ratio = min(
            $this->image_dimensions['max_width'] / $width,
            $this->image_dimensions['max_height'] / $height
        );
        
        $new_width = (int) ($width * $ratio);
        $new_height = (int) ($height * $ratio);

        // Create image resource
        $source_image = $this->create_image_from_file($file_path, $mime_type);
        if (!$source_image) {
            throw new \Exception('Failed to create image resource');
        }

        // Create resized image
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagecolortransparent($resized_image, imagecolorallocatealpha($resized_image, 0, 0, 0, 127));
            imagealphablending($resized_image, false);
            imagesavealpha($resized_image, true);
        }

        // Resize image
        imagecopyresampled(
            $resized_image, $source_image,
            0, 0, 0, 0,
            $new_width, $new_height, $width, $height
        );

        // Save resized image
        $this->save_image($resized_image, $file_path, $mime_type);

        // Clean up
        imagedestroy($source_image);
        imagedestroy($resized_image);

        return [
            'resized' => true,
            'original_dimensions' => ['width' => $width, 'height' => $height],
            'dimensions' => ['width' => $new_width, 'height' => $new_height],
        ];
    }

    /**
     * Generate thumbnail
     */
    private function generate_thumbnail(string $file_path, string $category): string
    {
        $image_info = getimagesize($file_path);
        $width = $image_info[0];
        $height = $image_info[1];
        $mime_type = $image_info['mime'];

        // Calculate thumbnail dimensions (square crop)
        $thumb_size = min($this->image_dimensions['thumbnail_width'], $this->image_dimensions['thumbnail_height']);
        $crop_size = min($width, $height);
        
        $src_x = ($width - $crop_size) / 2;
        $src_y = ($height - $crop_size) / 2;

        // Create image resources
        $source_image = $this->create_image_from_file($file_path, $mime_type);
        $thumbnail = imagecreatetruecolor($thumb_size, $thumb_size);

        // Preserve transparency
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        // Create thumbnail
        imagecopyresampled(
            $thumbnail, $source_image,
            0, 0, (int) $src_x, (int) $src_y,
            $thumb_size, $thumb_size, $crop_size, $crop_size
        );

        // Save thumbnail
        $thumbnail_filename = 'thumb_' . basename($file_path);
        $thumbnail_path = dirname($file_path) . '/' . $thumbnail_filename;
        
        $this->save_image($thumbnail, $thumbnail_path, $mime_type);

        // Clean up
        imagedestroy($source_image);
        imagedestroy($thumbnail);

        return "/uploads/{$category}/{$thumbnail_filename}";
    }

    /**
     * Create image resource from file
     * 
     * @return resource|false
     */
    private function create_image_from_file(string $file_path, string $mime_type)
    {
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($file_path);
            case 'image/png':
                return imagecreatefrompng($file_path);
            case 'image/gif':
                return imagecreatefromgif($file_path);
            case 'image/webp':
                return imagecreatefromwebp($file_path);
            default:
                return false;
        }
    }

    /**
     * Save image resource to file
     * 
     * @param resource $image
     */
    private function save_image($image, string $file_path, string $mime_type): bool
    {
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagejpeg($image, $file_path, 85);
            case 'image/png':
                return imagepng($image, $file_path, 8);
            case 'image/gif':
                return imagegif($image, $file_path);
            case 'image/webp':
                return imagewebp($image, $file_path, 85);
            default:
                return false;
        }
    }

    private function generate_unique_filename(string $extension): string
    {
        return uniqid('img_', true) . '.' . $extension;
    }

    private function get_file_extension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    private function ensure_upload_directories(): void
    {
        $directories = ['products', 'categories', 'pages', 'general'];
        
        foreach ($directories as $dir) {
            $this->ensure_directory_exists($this->upload_path . $dir . '/');
        }
    }

    private function ensure_directory_exists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Delete uploaded image and its thumbnail
     */
    public function delete_image(string $relative_path): bool
    {
        $file_path = __DIR__ . '/../../storage' . $relative_path;
        $thumbnail_path = dirname($file_path) . '/thumb_' . basename($file_path);
        
        $success = true;
        
        if (file_exists($file_path)) {
            $success = unlink($file_path) && $success;
        }
        
        if (file_exists($thumbnail_path)) {
            $success = unlink($thumbnail_path) && $success;
        }
        
        return $success;
    }

    /**
     * Get image dimensions
     * 
     * @return array<string, int>|null
     */
    public function get_image_dimensions(string $file_path): ?array
    {
        $image_info = getimagesize($file_path);
        
        if ($image_info === false) {
            return null;
        }
        
        return [
            'width' => $image_info[0],
            'height' => $image_info[1],
        ];
    }
}
