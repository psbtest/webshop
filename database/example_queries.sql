-- Example queries for categories and cart functionality

-- Get all categories with product count
SELECT 
    c.*,
    COUNT(p.id) as products_count
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
GROUP BY c.id
ORDER BY c.name ASC;

-- Get products by category with pagination
SELECT 
    p.*,
    c.name as category_name,
    c.slug as category_slug
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE c.slug = :category_slug
    AND p.status = 'active'
ORDER BY p.created_at DESC
LIMIT :limit OFFSET :offset;

-- Get cart items with product details
SELECT 
    ci.*,
    p.name as product_name,
    p.slug as product_slug,
    p.price as product_price,
    p.image as product_image,
    (ci.quantity * p.price) as item_total
FROM cart_items ci
JOIN products p ON ci.product_id = p.id
WHERE ci.session_id = :session_id
ORDER BY ci.created_at DESC;

-- Update cart item quantity
UPDATE cart_items 
SET quantity = :quantity, 
    updated_at = NOW()
WHERE id = :item_id 
    AND session_id = :session_id;

-- Remove cart item
DELETE FROM cart_items 
WHERE id = :item_id 
    AND session_id = :session_id;

-- Clear entire cart
DELETE FROM cart_items 
WHERE session_id = :session_id;

-- Get cart totals
SELECT 
    COUNT(*) as items_count,
    SUM(ci.quantity) as total_quantity,
    SUM(ci.quantity * p.price) as subtotal
FROM cart_items ci
JOIN products p ON ci.product_id = p.id
WHERE ci.session_id = :session_id;

