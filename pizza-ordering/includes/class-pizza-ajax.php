<?php
/**
 * Pizza Ajax
 *
 * Handles AJAX requests for the pizza ordering system.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Ajax Class
 */
class Pizza_Ajax {

    /**
     * Single instance
     *
     * @var Pizza_Ajax
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Ajax
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Get pizza data
        add_action('wp_ajax_pizza_get_builder_data', array($this, 'get_builder_data'));
        add_action('wp_ajax_nopriv_pizza_get_builder_data', array($this, 'get_builder_data'));

        // Calculate price
        add_action('wp_ajax_pizza_calculate_price', array($this, 'calculate_price'));
        add_action('wp_ajax_nopriv_pizza_calculate_price', array($this, 'calculate_price'));

        // Add to cart
        add_action('wp_ajax_pizza_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_nopriv_pizza_add_to_cart', array($this, 'add_to_cart'));

        // Get available times
        add_action('wp_ajax_pizza_get_available_times', array($this, 'get_available_times'));
        add_action('wp_ajax_nopriv_pizza_get_available_times', array($this, 'get_available_times'));

        // Update order status (admin only)
        add_action('wp_ajax_pizza_update_order_status', array($this, 'update_order_status'));

        // Get kitchen orders (admin only)
        add_action('wp_ajax_pizza_get_kitchen_orders', array($this, 'get_kitchen_orders'));
    }

    /**
     * Get pizza builder data
     */
    public function get_builder_data() {
        check_ajax_referer('pizza_builder_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product.', 'pizza-ordering')));
        }

        $product = wc_get_product($product_id);

        if (!$product || $product->get_type() !== 'pizza') {
            wp_send_json_error(array('message' => __('Product not found.', 'pizza-ordering')));
        }

        $data = array(
            'product' => array(
                'id'                  => $product->get_id(),
                'name'                => $product->get_name(),
                'price'               => $product->get_price(),
                'image'               => wp_get_attachment_image_url($product->get_image_id(), 'large'),
                'is_preset'           => get_post_meta($product_id, '_pizza_is_preset', true) === 'yes',
                'allow_customization' => get_post_meta($product_id, '_pizza_allow_customization', true) !== 'no',
                'free_toppings'       => absint(get_post_meta($product_id, '_pizza_free_toppings', true)),
                'max_toppings'        => absint(get_post_meta($product_id, '_pizza_max_toppings', true)),
                'default_toppings'    => get_post_meta($product_id, '_pizza_default_toppings', true) ?: array(),
                'description'         => get_post_meta($product_id, '_pizza_description', true),
            ),
            'sizes'    => Pizza_Post_Types::get_sizes(),
            'bases'    => Pizza_Post_Types::get_bases(),
            'sauces'   => Pizza_Post_Types::get_sauces(),
            'toppings' => Pizza_Post_Types::get_toppings(),
            'currency' => get_woocommerce_currency_symbol(),
        );

        wp_send_json_success($data);
    }

    /**
     * Calculate pizza price
     */
    public function calculate_price() {
        check_ajax_referer('pizza_builder_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $size_id = isset($_POST['size_id']) ? absint($_POST['size_id']) : 0;
        $base_id = isset($_POST['base_id']) ? absint($_POST['base_id']) : 0;
        $sauce_id = isset($_POST['sauce_id']) ? absint($_POST['sauce_id']) : 0;
        $topping_ids = isset($_POST['topping_ids']) && is_array($_POST['topping_ids']) ? array_map('absint', $_POST['topping_ids']) : array();
        $extra_topping_ids = isset($_POST['extra_topping_ids']) && is_array($_POST['extra_topping_ids']) ? array_map('absint', $_POST['extra_topping_ids']) : array();

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found.', 'pizza-ordering')));
        }

        $price = 0;
        $breakdown = array();

        // Size price
        if ($size_id) {
            $size_price = floatval(get_post_meta($size_id, '_size_base_price', true));
            $size_name = get_the_title($size_id);
            $price += $size_price;
            $breakdown['size'] = array(
                'name'  => $size_name,
                'price' => $size_price,
            );
        } else {
            // Use product base price
            $price += floatval($product->get_price());
            $breakdown['base'] = array(
                'name'  => __('Base price', 'pizza-ordering'),
                'price' => floatval($product->get_price()),
            );
        }

        // Base extra price
        if ($base_id) {
            $base_price = floatval(get_post_meta($base_id, '_base_extra_price', true));
            if ($base_price > 0) {
                $price += $base_price;
                $breakdown['base_extra'] = array(
                    'name'  => get_the_title($base_id),
                    'price' => $base_price,
                );
            }
        }

        // Sauce extra price
        if ($sauce_id) {
            $sauce_price = floatval(get_post_meta($sauce_id, '_sauce_extra_price', true));
            if ($sauce_price > 0) {
                $price += $sauce_price;
                $breakdown['sauce_extra'] = array(
                    'name'  => get_the_title($sauce_id),
                    'price' => $sauce_price,
                );
            }
        }

        // Toppings
        $free_toppings = absint(get_post_meta($product_id, '_pizza_free_toppings', true));
        $topping_count = 0;
        $toppings_breakdown = array();

        // Determine size key for topping prices
        $size_key = '_topping_price';
        if ($size_id) {
            $size_name = strtolower(get_the_title($size_id));
            if (strpos($size_name, 'medium') !== false) {
                $size_key = '_topping_price_medium';
            } elseif (strpos($size_name, 'large') !== false || strpos($size_name, 'stor') !== false) {
                $size_key = '_topping_price_large';
            } elseif (strpos($size_name, 'family') !== false || strpos($size_name, 'familie') !== false) {
                $size_key = '_topping_price_family';
            }
        }

        foreach ($topping_ids as $topping_id) {
            $topping_count++;
            $topping_name = get_the_title($topping_id);
            
            // Get price based on size
            $topping_price = floatval(get_post_meta($topping_id, $size_key, true));
            if ($topping_price <= 0) {
                $topping_price = floatval(get_post_meta($topping_id, '_topping_price', true));
            }

            if ($topping_count > $free_toppings && $topping_price > 0) {
                $price += $topping_price;
                $toppings_breakdown[] = array(
                    'name'  => $topping_name,
                    'price' => $topping_price,
                );
            } else {
                $toppings_breakdown[] = array(
                    'name'  => $topping_name,
                    'price' => 0,
                    'free'  => true,
                );
            }
        }

        if (!empty($toppings_breakdown)) {
            $breakdown['toppings'] = $toppings_breakdown;
        }

        // Extra toppings (double portion)
        $extra_breakdown = array();
        foreach ($extra_topping_ids as $topping_id) {
            $topping_name = get_the_title($topping_id);
            $topping_price = floatval(get_post_meta($topping_id, $size_key, true));
            if ($topping_price <= 0) {
                $topping_price = floatval(get_post_meta($topping_id, '_topping_price', true));
            }

            $price += $topping_price;
            $extra_breakdown[] = array(
                'name'  => $topping_name . ' (' . __('extra', 'pizza-ordering') . ')',
                'price' => $topping_price,
            );
        }

        if (!empty($extra_breakdown)) {
            $breakdown['extras'] = $extra_breakdown;
        }

        wp_send_json_success(array(
            'price'          => $price,
            'price_formatted' => wc_price($price),
            'breakdown'      => $breakdown,
        ));
    }

    /**
     * Add pizza to cart
     */
    public function add_to_cart() {
        check_ajax_referer('pizza_builder_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product.', 'pizza-ordering')));
        }

        $product = wc_get_product($product_id);

        if (!$product || $product->get_type() !== 'pizza') {
            wp_send_json_error(array('message' => __('Product not found.', 'pizza-ordering')));
        }

        // Build pizza configuration
        $pizza_config = array();

        // Size
        if (isset($_POST['size_id']) && $_POST['size_id']) {
            $size_id = absint($_POST['size_id']);
            $pizza_config['size_id'] = $size_id;
            $pizza_config['size_name'] = get_the_title($size_id);
            $pizza_config['size_price'] = floatval(get_post_meta($size_id, '_size_base_price', true));
        }

        // Base
        if (isset($_POST['base_id']) && $_POST['base_id']) {
            $base_id = absint($_POST['base_id']);
            $pizza_config['base_id'] = $base_id;
            $pizza_config['base_name'] = get_the_title($base_id);
            $pizza_config['base_price'] = floatval(get_post_meta($base_id, '_base_extra_price', true));
        }

        // Sauce
        if (isset($_POST['sauce_id']) && $_POST['sauce_id']) {
            $sauce_id = absint($_POST['sauce_id']);
            $pizza_config['sauce_id'] = $sauce_id;
            $pizza_config['sauce_name'] = get_the_title($sauce_id);
            $pizza_config['sauce_price'] = floatval(get_post_meta($sauce_id, '_sauce_extra_price', true));
        }

        // Determine price key based on size
        $size_key = '_topping_price';
        if (isset($pizza_config['size_name'])) {
            $size_name = strtolower($pizza_config['size_name']);
            if (strpos($size_name, 'medium') !== false) {
                $size_key = '_topping_price_medium';
            } elseif (strpos($size_name, 'large') !== false || strpos($size_name, 'stor') !== false) {
                $size_key = '_topping_price_large';
            } elseif (strpos($size_name, 'family') !== false || strpos($size_name, 'familie') !== false) {
                $size_key = '_topping_price_family';
            }
        }

        // INCLUDED toppings (default toppings that are kept - NO extra cost)
        $pizza_config['included_toppings'] = array();
        if (isset($_POST['included_topping_ids']) && is_array($_POST['included_topping_ids'])) {
            foreach (array_map('absint', $_POST['included_topping_ids']) as $topping_id) {
                $pizza_config['included_toppings'][] = array(
                    'id'    => $topping_id,
                    'name'  => get_the_title($topping_id),
                    'price' => 0, // Included = no cost
                );
            }
        }

        // REMOVED toppings (default toppings that customer removed - for kitchen display)
        $pizza_config['removed_toppings'] = array();
        if (isset($_POST['removed_topping_ids']) && is_array($_POST['removed_topping_ids'])) {
            foreach (array_map('absint', $_POST['removed_topping_ids']) as $topping_id) {
                $pizza_config['removed_toppings'][] = array(
                    'id'   => $topping_id,
                    'name' => get_the_title($topping_id),
                );
            }
        }

        // ADDED toppings (extra toppings customer added - COSTS EXTRA)
        $pizza_config['added_toppings'] = array();
        if (isset($_POST['added_topping_ids']) && is_array($_POST['added_topping_ids'])) {
            foreach (array_map('absint', $_POST['added_topping_ids']) as $topping_id) {
                $price = floatval(get_post_meta($topping_id, $size_key, true));
                if ($price <= 0) {
                    $price = floatval(get_post_meta($topping_id, '_topping_price', true));
                }

                $pizza_config['added_toppings'][] = array(
                    'id'    => $topping_id,
                    'name'  => get_the_title($topping_id),
                    'price' => $price,
                );
            }
        }

        // EXTRA PORTIONS (double portion of a topping - COSTS EXTRA)
        $pizza_config['extra_portions'] = array();
        if (isset($_POST['extra_portion_ids']) && is_array($_POST['extra_portion_ids'])) {
            foreach (array_map('absint', $_POST['extra_portion_ids']) as $topping_id) {
                $price = floatval(get_post_meta($topping_id, $size_key, true));
                if ($price <= 0) {
                    $price = floatval(get_post_meta($topping_id, '_topping_price', true));
                }

                $pizza_config['extra_portions'][] = array(
                    'id'    => $topping_id,
                    'name'  => get_the_title($topping_id),
                    'price' => $price,
                );
            }
        }

        // SIDES (tilbehør)
        $pizza_config['sides'] = array();
        if (isset($_POST['side_ids']) && is_array($_POST['side_ids'])) {
            foreach (array_map('absint', $_POST['side_ids']) as $side_id) {
                $side_price = floatval(get_post_meta($side_id, '_side_price', true));
                $pizza_config['sides'][] = array(
                    'id'    => $side_id,
                    'name'  => get_the_title($side_id),
                    'price' => $side_price,
                );
            }
        }

        // COMBOS (tilbud)
        $pizza_config['combos'] = array();
        if (isset($_POST['combo_ids']) && is_array($_POST['combo_ids'])) {
            foreach (array_map('absint', $_POST['combo_ids']) as $combo_id) {
                $combo_price = floatval(get_post_meta($combo_id, '_combo_sale_price', true));
                $pizza_config['combos'][] = array(
                    'id'    => $combo_id,
                    'name'  => get_the_title($combo_id),
                    'price' => $combo_price,
                );
            }
        }

        // Calculate price
        $price = $this->calculate_pizza_price_internal($pizza_config, $product);
        $pizza_config['calculated_price'] = $price;

        // Add to cart
        $cart_item_data = array(
            'pizza_config' => $pizza_config,
            'unique_key'   => md5(microtime() . wp_rand()),
        );

        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);

        if ($cart_item_key) {
            wp_send_json_success(array(
                'message'       => __('Pizza added to cart!', 'pizza-ordering'),
                'cart_url'      => wc_get_cart_url(),
                'checkout_url'  => wc_get_checkout_url(),
                'cart_count'    => WC()->cart->get_cart_contents_count(),
                'cart_total'    => WC()->cart->get_cart_total(),
                'cart_item_key' => $cart_item_key,
            ));
        } else {
            wp_send_json_error(array('message' => __('Could not add pizza to cart.', 'pizza-ordering')));
        }
    }

    /**
     * Calculate pizza price internally
     *
     * @param array      $config  Pizza configuration
     * @param WC_Product $product Product object
     * @return float
     */
    private function calculate_pizza_price_internal($config, $product) {
        $price = 0;

        // Size base price
        if (isset($config['size_price'])) {
            $price += $config['size_price'];
        } else {
            $price += floatval($product->get_price());
        }

        // Base extra price
        if (isset($config['base_price'])) {
            $price += $config['base_price'];
        }

        // Sauce extra price
        if (isset($config['sauce_price'])) {
            $price += $config['sauce_price'];
        }

        // Included toppings - NO extra cost (they are included in base price)
        // $config['included_toppings'] - these are FREE

        // Removed toppings - NO price reduction
        // $config['removed_toppings'] - just for display, no price change

        // Added toppings - EXTRA COST
        if (isset($config['added_toppings']) && is_array($config['added_toppings'])) {
            foreach ($config['added_toppings'] as $topping) {
                $price += $topping['price'];
            }
        }

        // Extra portions - EXTRA COST
        if (isset($config['extra_portions']) && is_array($config['extra_portions'])) {
            foreach ($config['extra_portions'] as $topping) {
                $price += $topping['price'];
            }
        }

        // Legacy support: old toppings array
        if (isset($config['toppings']) && is_array($config['toppings'])) {
            $free_toppings = absint(get_post_meta($product->get_id(), '_pizza_free_toppings', true));
            $topping_count = 0;

            foreach ($config['toppings'] as $topping) {
                $topping_count++;
                if ($topping_count > $free_toppings) {
                    $price += $topping['price'];
                }
            }
        }

        // Legacy support: old extra_toppings array
        if (isset($config['extra_toppings']) && is_array($config['extra_toppings'])) {
            foreach ($config['extra_toppings'] as $topping) {
                $price += $topping['price'];
            }
        }

        // Sides (tilbehør) - EXTRA COST (not multiplied by pizza quantity)
        if (isset($config['sides']) && is_array($config['sides'])) {
            foreach ($config['sides'] as $side) {
                $price += $side['price'];
            }
        }

        // Combos (tilbud) - EXTRA COST (not multiplied by pizza quantity)
        if (isset($config['combos']) && is_array($config['combos'])) {
            foreach ($config['combos'] as $combo) {
                $price += $combo['price'];
            }
        }

        return $price;
    }

    /**
     * Get available time slots
     */
    public function get_available_times() {
        check_ajax_referer('pizza_times_nonce', 'nonce');

        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

        if (empty($date)) {
            wp_send_json_error(array('message' => __('Please select a date.', 'pizza-ordering')));
        }

        $opening_hours = get_option('pizza_ordering_opening_hours', array());
        $order_interval = get_option('pizza_ordering_order_interval', 15);
        $prep_time = get_option('pizza_ordering_prep_time', 20);

        // Get day of week
        $day_of_week = strtolower(date('l', strtotime($date)));

        // Check if open
        if (!isset($opening_hours[$day_of_week]) || $opening_hours[$day_of_week]['closed']) {
            wp_send_json_error(array('message' => __('We are closed on this day.', 'pizza-ordering')));
        }

        $open_time = $opening_hours[$day_of_week]['open'];
        $close_time = $opening_hours[$day_of_week]['close'];

        // Generate time slots
        $times = array();
        $current = strtotime($date . ' ' . $open_time);
        $end = strtotime($date . ' ' . $close_time);

        // If today, start from now + prep time
        if ($date === date('Y-m-d')) {
            $earliest = strtotime('+' . $prep_time . ' minutes');
            if ($current < $earliest) {
                $current = ceil($earliest / (60 * $order_interval)) * (60 * $order_interval);
            }
        }

        while ($current < $end) {
            $time_value = date('H:i', $current);
            $times[] = array(
                'value' => $time_value,
                'label' => date('H:i', $current),
            );
            $current += $order_interval * 60;
        }

        wp_send_json_success(array('times' => $times));
    }

    /**
     * Update order status (admin only)
     */
    public function update_order_status() {
        check_ajax_referer('pizza_kitchen_nonce', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'pizza-ordering')));
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

        if (!$order_id || !$status) {
            wp_send_json_error(array('message' => __('Invalid request.', 'pizza-ordering')));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'pizza-ordering')));
        }

        $valid_statuses = array('received', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'picked_up');

        if (!in_array($status, $valid_statuses, true)) {
            wp_send_json_error(array('message' => __('Invalid status.', 'pizza-ordering')));
        }

        $order->update_meta_data('_pizza_order_status', $status);
        $order->save();

        // Add order note
        $status_labels = array(
            'received'         => __('Order received', 'pizza-ordering'),
            'preparing'        => __('Preparing order', 'pizza-ordering'),
            'ready'            => __('Order ready', 'pizza-ordering'),
            'out_for_delivery' => __('Out for delivery', 'pizza-ordering'),
            'delivered'        => __('Order delivered', 'pizza-ordering'),
            'picked_up'        => __('Order picked up', 'pizza-ordering'),
        );

        $order->add_order_note(
            sprintf(
                /* translators: %s: status label */
                __('Pizza order status changed to: %s', 'pizza-ordering'),
                $status_labels[$status]
            )
        );

        wp_send_json_success(array(
            'message' => __('Status updated.', 'pizza-ordering'),
            'status'  => $status,
            'label'   => $status_labels[$status],
        ));
    }

    /**
     * Get kitchen orders (admin only)
     */
    public function get_kitchen_orders() {
        check_ajax_referer('pizza_kitchen_nonce', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'pizza-ordering')));
        }

        $statuses = isset($_POST['statuses']) && is_array($_POST['statuses']) ? array_map('sanitize_text_field', $_POST['statuses']) : array('received', 'preparing');

        $orders = wc_get_orders(array(
            'limit'      => 50,
            'status'     => array('processing', 'on-hold'),
            'meta_query' => array(
                array(
                    'key'     => '_pizza_order_status',
                    'value'   => $statuses,
                    'compare' => 'IN',
                ),
            ),
            'orderby'    => 'date',
            'order'      => 'ASC',
        ));

        $order_data = array();

        foreach ($orders as $order) {
            $items = array();
            foreach ($order->get_items() as $item) {
                $pizza_config = $item->get_meta('_pizza_config');
                $items[] = array(
                    'name'         => $item->get_name(),
                    'quantity'     => $item->get_quantity(),
                    'pizza_config' => $pizza_config,
                );
            }

            $order_data[] = array(
                'id'            => $order->get_id(),
                'order_number'  => $order->get_order_number(),
                'status'        => $order->get_meta('_pizza_order_status'),
                'delivery_type' => $order->get_meta('_pizza_delivery_type'),
                'time_type'     => $order->get_meta('_pizza_time_type'),
                'scheduled_date' => $order->get_meta('_pizza_scheduled_date'),
                'scheduled_time' => $order->get_meta('_pizza_scheduled_time'),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'items'         => $items,
                'total'         => $order->get_formatted_order_total(),
                'created'       => $order->get_date_created()->format('H:i'),
            );
        }

        wp_send_json_success(array('orders' => $order_data));
    }
}
