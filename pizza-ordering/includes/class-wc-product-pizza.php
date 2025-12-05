<?php
/**
 * WC Product Pizza Class
 *
 * Custom WooCommerce product type for pizzas.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate class declaration
if (class_exists('WC_Product_Pizza')) {
    return;
}

/**
 * Pizza Product Class
 */
class WC_Product_Pizza extends WC_Product {

    /**
     * Product type
     *
     * @var string
     */
    protected $product_type = 'pizza';

    /**
     * Constructor
     *
     * @param mixed $product Product ID or object
     */
    public function __construct($product = 0) {
        $this->supports[] = 'ajax_add_to_cart';
        parent::__construct($product);
    }

    /**
     * Get product type
     *
     * @return string
     */
    public function get_type() {
        return 'pizza';
    }

    /**
     * Check if product is purchasable
     *
     * @return bool
     */
    public function is_purchasable() {
        return true;
    }

    /**
     * Check if product is in stock
     *
     * @return bool
     */
    public function is_in_stock() {
        return true;
    }

    /**
     * Check if product is sold individually
     *
     * @return bool
     */
    public function is_sold_individually() {
        return false;
    }

    /**
     * Get add to cart button text
     *
     * @return string
     */
    public function add_to_cart_text() {
        return __('Build Pizza', 'pizza-ordering');
    }

    /**
     * Get single add to cart button text
     *
     * @return string
     */
    public function single_add_to_cart_text() {
        return __('Add to Cart', 'pizza-ordering');
    }

    /**
     * Get price HTML
     *
     * @return string
     */
    public function get_price_html($deprecated = '') {
        $price = $this->get_price();
        
        if ($price > 0) {
            return sprintf(
                /* translators: %s: price */
                __('From %s', 'pizza-ordering'),
                wc_price($price)
            );
        }

        return parent::get_price_html();
    }
}

// Register the product type
add_filter('woocommerce_product_class', function($classname, $product_type) {
    if ($product_type === 'pizza') {
        return 'WC_Product_Pizza';
    }
    return $classname;
}, 10, 2);
