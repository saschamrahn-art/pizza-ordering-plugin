<?php
/**
 * Pizza Builder Template
 *
 * This template displays the pizza builder interface on product pages.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;

if (!$product || $product->get_type() !== 'pizza') {
    return;
}

$product_id = $product->get_id();
$product_image = wp_get_attachment_image_url($product->get_image_id(), 'large');
$is_preset = get_post_meta($product_id, '_pizza_is_preset', true) === 'yes';
$pizza_description = get_post_meta($product_id, '_pizza_description', true);
$is_popular = get_post_meta($product_id, '_pizza_popular', true) === 'yes';
$is_new = get_post_meta($product_id, '_pizza_new', true) === 'yes';
?>

<div class="pizza-builder-wrapper">
    <?php if ($is_preset && !empty($pizza_description)) : ?>
        <div class="pizza-preset-info">
            <?php if ($is_popular) : ?>
                <span class="pizza-badge pizza-badge-popular">⭐ <?php esc_html_e('Popular', 'pizza-ordering'); ?></span>
            <?php endif; ?>
            <?php if ($is_new) : ?>
                <span class="pizza-badge pizza-badge-new">🆕 <?php esc_html_e('New', 'pizza-ordering'); ?></span>
            <?php endif; ?>
            
            <div class="pizza-preset-description">
                <?php echo esc_html($pizza_description); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="pizza-builder-container" 
         data-product-id="<?php echo esc_attr($product_id); ?>"
         data-nonce="<?php echo esc_attr(wp_create_nonce('pizza_builder_nonce')); ?>">
        
        <!-- Loading state - will be replaced by JavaScript -->
        <div class="pizza-loading">
            <div class="pizza-loading-spinner"></div>
            <p><?php esc_html_e('Loading pizza builder...', 'pizza-ordering'); ?></p>
        </div>
        
    </div>
</div>

<style>
/* Inline critical styles for initial load */
.pizza-builder-wrapper {
    margin: 30px 0;
}

.pizza-preset-info {
    background: linear-gradient(135deg, #fff5f0 0%, #fff 100%);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ffe0cc;
}

.pizza-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 10px;
    margin-bottom: 10px;
}

.pizza-badge-popular {
    background: #fff3cd;
    color: #856404;
}

.pizza-badge-new {
    background: #d4edda;
    color: #155724;
}

.pizza-preset-description {
    color: #666;
    font-size: 15px;
    line-height: 1.6;
}

.pizza-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 15px;
}

.pizza-loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid #f0f0f0;
    border-top-color: #ff6b35;
    border-radius: 50%;
    animation: pizza-spin 1s linear infinite;
    margin-bottom: 15px;
}

@keyframes pizza-spin {
    to { transform: rotate(360deg); }
}
</style>
