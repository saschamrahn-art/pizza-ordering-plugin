<?php
/**
 * Pizza Product Type
 *
 * Custom WooCommerce product type for pizzas.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Product Type Class
 */
class Pizza_Product_Type {

    /**
     * Single instance
     *
     * @var Pizza_Product_Type
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Product_Type
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
        // Register product type
        add_action('init', array($this, 'register_product_type'));
        
        // Add product type to selector
        add_filter('product_type_selector', array($this, 'add_product_type_selector'));
        
        // Add product data tabs
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        
        // Add product data panels
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        
        // Save product data
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        
        // Show pizza tab for pizza products
        add_action('woocommerce_product_options_general_product_data', array($this, 'show_price_for_pizza'));
        
        // Add to cart handling
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2);
        add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
        add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 10, 1);
        
        // Order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);
    }

    /**
     * Register the product type
     */
    public function register_product_type() {
        require_once PIZZA_ORDERING_PLUGIN_DIR . 'includes/class-wc-product-pizza.php';
    }

    /**
     * Add product type to selector
     *
     * @param array $types Product types
     * @return array
     */
    public function add_product_type_selector($types) {
        $types['pizza'] = __('Pizza', 'pizza-ordering');
        return $types;
    }

    /**
     * Add product data tab
     *
     * @param array $tabs Product data tabs
     * @return array
     */
    public function add_product_data_tab($tabs) {
        $tabs['pizza_options'] = array(
            'label'    => __('Pizza Options', 'pizza-ordering'),
            'target'   => 'pizza_options_data',
            'class'    => array('show_if_pizza'),
            'priority' => 21,
        );
        return $tabs;
    }

    /**
     * Add product data panel
     */
    public function add_product_data_panel() {
        global $post;
        
        $selected_toppings = get_post_meta($post->ID, '_pizza_default_toppings', true);
        if (!is_array($selected_toppings)) {
            $selected_toppings = array();
        }
        
        $all_toppings = Pizza_Post_Types::get_toppings();
        ?>
        <div id="pizza_options_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox(array(
                    'id'          => '_pizza_is_preset',
                    'label'       => __('Preset Pizza', 'pizza-ordering'),
                    'description' => __('This is a pre-made pizza with default toppings', 'pizza-ordering'),
                ));

                woocommerce_wp_checkbox(array(
                    'id'          => '_pizza_allow_customization',
                    'label'       => __('Allow Customization', 'pizza-ordering'),
                    'description' => __('Customers can modify toppings', 'pizza-ordering'),
                    'value'       => get_post_meta($post->ID, '_pizza_allow_customization', true) ?: 'yes',
                ));

                woocommerce_wp_text_input(array(
                    'id'          => '_pizza_free_toppings',
                    'label'       => __('Free Toppings', 'pizza-ordering'),
                    'description' => __('Number of free toppings included', 'pizza-ordering'),
                    'type'        => 'number',
                    'custom_attributes' => array('min' => '0', 'step' => '1'),
                ));

                woocommerce_wp_text_input(array(
                    'id'          => '_pizza_max_toppings',
                    'label'       => __('Max Toppings', 'pizza-ordering'),
                    'description' => __('Maximum number of toppings allowed (0 = unlimited)', 'pizza-ordering'),
                    'type'        => 'number',
                    'custom_attributes' => array('min' => '0', 'step' => '1'),
                ));
                ?>
            </div>

            <div class="options_group">
                <p class="form-field">
                    <label><?php esc_html_e('Default Toppings', 'pizza-ordering'); ?></label>
                    <span class="description"><?php esc_html_e('Select default toppings for this pizza', 'pizza-ordering'); ?></span>
                </p>
                <div class="pizza-default-toppings" style="padding: 0 12px; max-height: 300px; overflow-y: auto;">
                    <?php foreach ($all_toppings as $topping) : ?>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" 
                                   name="_pizza_default_toppings[]" 
                                   value="<?php echo esc_attr($topping['id']); ?>"
                                   <?php checked(in_array($topping['id'], $selected_toppings)); ?>>
                            <?php echo esc_html($topping['name']); ?>
                            <?php if ($topping['price'] > 0) : ?>
                                <span class="price">(<?php echo wc_price($topping['price']); ?>)</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="options_group">
                <?php
                woocommerce_wp_textarea_input(array(
                    'id'          => '_pizza_description',
                    'label'       => __('Pizza Description', 'pizza-ordering'),
                    'description' => __('Short description shown in pizza builder', 'pizza-ordering'),
                    'rows'        => 3,
                ));

                woocommerce_wp_checkbox(array(
                    'id'          => '_pizza_popular',
                    'label'       => __('Popular', 'pizza-ordering'),
                    'description' => __('Mark as popular pizza', 'pizza-ordering'),
                ));

                woocommerce_wp_checkbox(array(
                    'id'          => '_pizza_new',
                    'label'       => __('New', 'pizza-ordering'),
                    'description' => __('Mark as new pizza', 'pizza-ordering'),
                ));
                ?>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Show/hide pizza options based on product type
                $('select#product-type').on('change', function() {
                    if ($(this).val() === 'pizza') {
                        $('.show_if_pizza').show();
                        $('.general_options').show();
                    } else {
                        $('.show_if_pizza').hide();
                    }
                }).trigger('change');

                // Show general tab for pizza
                if ($('select#product-type').val() === 'pizza') {
                    $('.general_options').show();
                }
            });
        </script>
        <?php
    }

    /**
     * Show price field for pizza products
     */
    public function show_price_for_pizza() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Show price fields for pizza products
                $('.show_if_simple').addClass('show_if_pizza');
            });
        </script>
        <?php
    }

    /**
     * Save product data
     *
     * @param int $post_id Product ID
     */
    public function save_product_data($post_id) {
        // Check nonce
        if (!isset($_POST['woocommerce_meta_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
            return;
        }

        // Save pizza options
        $checkboxes = array(
            '_pizza_is_preset',
            '_pizza_allow_customization',
            '_pizza_popular',
            '_pizza_new',
        );

        foreach ($checkboxes as $key) {
            $value = isset($_POST[$key]) ? 'yes' : 'no';
            update_post_meta($post_id, $key, $value);
        }

        // Save text fields
        if (isset($_POST['_pizza_free_toppings'])) {
            update_post_meta($post_id, '_pizza_free_toppings', absint($_POST['_pizza_free_toppings']));
        }

        if (isset($_POST['_pizza_max_toppings'])) {
            update_post_meta($post_id, '_pizza_max_toppings', absint($_POST['_pizza_max_toppings']));
        }

        if (isset($_POST['_pizza_description'])) {
            update_post_meta($post_id, '_pizza_description', sanitize_textarea_field(wp_unslash($_POST['_pizza_description'])));
        }

        // Save default toppings
        $default_toppings = array();
        if (isset($_POST['_pizza_default_toppings']) && is_array($_POST['_pizza_default_toppings'])) {
            $default_toppings = array_map('absint', $_POST['_pizza_default_toppings']);
        }
        update_post_meta($post_id, '_pizza_default_toppings', $default_toppings);
    }

    /**
     * Add custom data to cart item
     *
     * @param array $cart_item_data Cart item data
     * @param int   $product_id     Product ID
     * @param int   $variation_id   Variation ID
     * @return array
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Check if this is a pizza product
        $product = wc_get_product($product_id);
        if (!$product || $product->get_type() !== 'pizza') {
            return $cart_item_data;
        }

        // Verify nonce
        if (!isset($_POST['pizza_builder_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_builder_nonce'])), 'pizza_builder_add_to_cart')) {
            return $cart_item_data;
        }

        // Get pizza configuration
        $pizza_config = array();

        // Size
        if (isset($_POST['pizza_size'])) {
            $pizza_config['size_id'] = absint($_POST['pizza_size']);
            $size_post = get_post($pizza_config['size_id']);
            if ($size_post) {
                $pizza_config['size_name'] = $size_post->post_title;
                $pizza_config['size_price'] = floatval(get_post_meta($pizza_config['size_id'], '_size_base_price', true));
            }
        }

        // Base
        if (isset($_POST['pizza_base'])) {
            $pizza_config['base_id'] = absint($_POST['pizza_base']);
            $base_post = get_post($pizza_config['base_id']);
            if ($base_post) {
                $pizza_config['base_name'] = $base_post->post_title;
                $pizza_config['base_price'] = floatval(get_post_meta($pizza_config['base_id'], '_base_extra_price', true));
            }
        }

        // Sauce
        if (isset($_POST['pizza_sauce'])) {
            $pizza_config['sauce_id'] = absint($_POST['pizza_sauce']);
            $sauce_post = get_post($pizza_config['sauce_id']);
            if ($sauce_post) {
                $pizza_config['sauce_name'] = $sauce_post->post_title;
                $pizza_config['sauce_price'] = floatval(get_post_meta($pizza_config['sauce_id'], '_sauce_extra_price', true));
            }
        }

        // Toppings
        if (isset($_POST['pizza_toppings']) && is_array($_POST['pizza_toppings'])) {
            $pizza_config['toppings'] = array();
            $topping_ids = array_map('absint', $_POST['pizza_toppings']);
            
            foreach ($topping_ids as $topping_id) {
                $topping_post = get_post($topping_id);
                if ($topping_post) {
                    // Get price based on size
                    $price_key = '_topping_price';
                    if (isset($pizza_config['size_name'])) {
                        $size_lower = strtolower($pizza_config['size_name']);
                        if (strpos($size_lower, 'medium') !== false) {
                            $price_key = '_topping_price_medium';
                        } elseif (strpos($size_lower, 'large') !== false || strpos($size_lower, 'stor') !== false) {
                            $price_key = '_topping_price_large';
                        } elseif (strpos($size_lower, 'family') !== false || strpos($size_lower, 'familie') !== false) {
                            $price_key = '_topping_price_family';
                        }
                    }
                    
                    $price = floatval(get_post_meta($topping_id, $price_key, true));
                    if ($price <= 0) {
                        $price = floatval(get_post_meta($topping_id, '_topping_price', true));
                    }

                    $pizza_config['toppings'][] = array(
                        'id'    => $topping_id,
                        'name'  => $topping_post->post_title,
                        'price' => $price,
                    );
                }
            }
        }

        // Extra toppings (double portion)
        if (isset($_POST['pizza_extra_toppings']) && is_array($_POST['pizza_extra_toppings'])) {
            $pizza_config['extra_toppings'] = array();
            $extra_ids = array_map('absint', $_POST['pizza_extra_toppings']);
            
            foreach ($extra_ids as $topping_id) {
                $topping_post = get_post($topping_id);
                if ($topping_post) {
                    $price = floatval(get_post_meta($topping_id, '_topping_price', true));
                    $pizza_config['extra_toppings'][] = array(
                        'id'    => $topping_id,
                        'name'  => $topping_post->post_title,
                        'price' => $price,
                    );
                }
            }
        }

        // Special instructions
        if (isset($_POST['pizza_instructions'])) {
            $pizza_config['instructions'] = sanitize_textarea_field(wp_unslash($_POST['pizza_instructions']));
        }

        // Calculate total price
        $pizza_config['calculated_price'] = $this->calculate_pizza_price($pizza_config, $product);

        // Store configuration
        $cart_item_data['pizza_config'] = $pizza_config;
        $cart_item_data['unique_key'] = md5(microtime() . wp_rand());

        return $cart_item_data;
    }

    /**
     * Calculate pizza price
     *
     * @param array      $config  Pizza configuration
     * @param WC_Product $product Product object
     * @return float
     */
    private function calculate_pizza_price($config, $product) {
        $price = 0;

        // Size base price
        if (isset($config['size_price'])) {
            $price += $config['size_price'];
        } else {
            // Use product price as base
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

        // Toppings
        $free_toppings = absint(get_post_meta($product->get_id(), '_pizza_free_toppings', true));
        $topping_count = 0;

        if (isset($config['toppings']) && is_array($config['toppings'])) {
            foreach ($config['toppings'] as $topping) {
                $topping_count++;
                if ($topping_count > $free_toppings) {
                    $price += $topping['price'];
                }
            }
        }

        // Extra toppings (always charged)
        if (isset($config['extra_toppings']) && is_array($config['extra_toppings'])) {
            foreach ($config['extra_toppings'] as $topping) {
                $price += $topping['price'];
            }
        }

        return $price;
    }

    /**
     * Get cart item from session
     *
     * @param array $cart_item      Cart item
     * @param array $cart_item_data Cart item data from session
     * @return array
     */
    public function get_cart_item_from_session($cart_item, $cart_item_data) {
        if (isset($cart_item_data['pizza_config'])) {
            $cart_item['pizza_config'] = $cart_item_data['pizza_config'];
        }
        return $cart_item;
    }

    /**
     * Display pizza configuration in cart
     *
     * @param array $item_data Item data for display
     * @param array $cart_item Cart item
     * @return array
     */
    public function get_item_data($item_data, $cart_item) {
        if (!isset($cart_item['pizza_config'])) {
            return $item_data;
        }

        $config = $cart_item['pizza_config'];

        // Size
        if (isset($config['size_name'])) {
            $item_data[] = array(
                'key'   => __('Size', 'pizza-ordering'),
                'value' => $config['size_name'],
            );
        }

        // Base
        if (isset($config['base_name'])) {
            $item_data[] = array(
                'key'   => __('Base', 'pizza-ordering'),
                'value' => $config['base_name'],
            );
        }

        // Sauce
        if (isset($config['sauce_name'])) {
            $item_data[] = array(
                'key'   => __('Sauce', 'pizza-ordering'),
                'value' => $config['sauce_name'],
            );
        }

        // NEW SYSTEM: Included toppings (what's on the pizza)
        if (isset($config['included_toppings']) && !empty($config['included_toppings'])) {
            $topping_names = array_column($config['included_toppings'], 'name');
            $item_data[] = array(
                'key'   => '✅ ' . __('Included', 'pizza-ordering'),
                'value' => implode(', ', $topping_names),
            );
        }

        // NEW SYSTEM: Removed toppings (what customer doesn't want)
        if (isset($config['removed_toppings']) && !empty($config['removed_toppings'])) {
            $removed_names = array_column($config['removed_toppings'], 'name');
            $item_data[] = array(
                'key'   => '❌ ' . __('Without', 'pizza-ordering'),
                'value' => implode(', ', $removed_names),
            );
        }

        // NEW SYSTEM: Added extra toppings (costs extra)
        if (isset($config['added_toppings']) && !empty($config['added_toppings'])) {
            $added_names = array_map(function($t) {
                return $t['name'] . ' (+' . wc_price($t['price']) . ')';
            }, $config['added_toppings']);
            $item_data[] = array(
                'key'   => '➕ ' . __('Added', 'pizza-ordering'),
                'value' => implode(', ', $added_names),
            );
        }

        // NEW SYSTEM: Extra portions (double)
        if (isset($config['extra_portions']) && !empty($config['extra_portions'])) {
            $extra_names = array_map(function($t) {
                return $t['name'] . ' x2 (+' . wc_price($t['price']) . ')';
            }, $config['extra_portions']);
            $item_data[] = array(
                'key'   => '🔥 ' . __('Extra portion', 'pizza-ordering'),
                'value' => implode(', ', $extra_names),
            );
        }

        // LEGACY: Old toppings array (backwards compatibility)
        if (isset($config['toppings']) && !empty($config['toppings'])) {
            $topping_names = array_column($config['toppings'], 'name');
            $item_data[] = array(
                'key'   => __('Toppings', 'pizza-ordering'),
                'value' => implode(', ', $topping_names),
            );
        }

        // LEGACY: Old extra toppings
        if (isset($config['extra_toppings']) && !empty($config['extra_toppings'])) {
            $extra_names = array_map(function($t) {
                return $t['name'] . ' (' . __('extra', 'pizza-ordering') . ')';
            }, $config['extra_toppings']);
            $item_data[] = array(
                'key'   => __('Extra', 'pizza-ordering'),
                'value' => implode(', ', $extra_names),
            );
        }

        // Sides (tilbehør)
        if (isset($config['sides']) && !empty($config['sides'])) {
            $side_names = array_map(function($s) {
                return $s['name'] . ' (+' . wc_price($s['price']) . ')';
            }, $config['sides']);
            $item_data[] = array(
                'key'   => '🍽️ Tilbehør',
                'value' => implode(', ', $side_names),
            );
        }

        // Combos (tilbud)
        if (isset($config['combos']) && !empty($config['combos'])) {
            $combo_names = array_map(function($c) {
                return $c['name'] . ' (+' . wc_price($c['price']) . ')';
            }, $config['combos']);
            $item_data[] = array(
                'key'   => '🔥 Tilbud',
                'value' => implode(', ', $combo_names),
            );
        }

        // Instructions (if any - now optional)
        if (isset($config['instructions']) && !empty($config['instructions'])) {
            $item_data[] = array(
                'key'   => __('Special Instructions', 'pizza-ordering'),
                'value' => $config['instructions'],
            );
        }

        return $item_data;
    }

    /**
     * Update cart item price
     *
     * @param WC_Cart $cart Cart object
     */
    public function before_calculate_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['pizza_config']['calculated_price'])) {
                $cart_item['data']->set_price($cart_item['pizza_config']['calculated_price']);
            }
        }
    }

    /**
     * Add pizza config to order item meta
     *
     * @param WC_Order_Item_Product $item          Order item
     * @param string                $cart_item_key Cart item key
     * @param array                 $values        Cart item values
     * @param WC_Order              $order         Order object
     */
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (!isset($values['pizza_config'])) {
            return;
        }

        $config = $values['pizza_config'];

        // Store full config for reference
        $item->add_meta_data('_pizza_config', $config, true);

        // Store readable meta
        if (isset($config['size_name'])) {
            $item->add_meta_data(__('Size', 'pizza-ordering'), $config['size_name']);
        }

        if (isset($config['base_name'])) {
            $item->add_meta_data(__('Base', 'pizza-ordering'), $config['base_name']);
        }

        if (isset($config['sauce_name'])) {
            $item->add_meta_data(__('Sauce', 'pizza-ordering'), $config['sauce_name']);
        }

        // NEW SYSTEM: Included toppings
        if (isset($config['included_toppings']) && !empty($config['included_toppings'])) {
            $topping_names = array_column($config['included_toppings'], 'name');
            $item->add_meta_data('✅ ' . __('Included', 'pizza-ordering'), implode(', ', $topping_names));
        }

        // NEW SYSTEM: Removed toppings (important for kitchen!)
        if (isset($config['removed_toppings']) && !empty($config['removed_toppings'])) {
            $removed_names = array_column($config['removed_toppings'], 'name');
            $item->add_meta_data('❌ ' . __('WITHOUT', 'pizza-ordering'), implode(', ', $removed_names));
        }

        // NEW SYSTEM: Added toppings
        if (isset($config['added_toppings']) && !empty($config['added_toppings'])) {
            $added_names = array_column($config['added_toppings'], 'name');
            $item->add_meta_data('➕ ' . __('Added', 'pizza-ordering'), implode(', ', $added_names));
        }

        // NEW SYSTEM: Extra portions
        if (isset($config['extra_portions']) && !empty($config['extra_portions'])) {
            $extra_names = array_map(function($t) {
                return $t['name'] . ' x2';
            }, $config['extra_portions']);
            $item->add_meta_data('🔥 ' . __('Extra portion', 'pizza-ordering'), implode(', ', $extra_names));
        }

        // LEGACY: Old toppings
        if (isset($config['toppings']) && !empty($config['toppings'])) {
            $topping_names = array_column($config['toppings'], 'name');
            $item->add_meta_data(__('Toppings', 'pizza-ordering'), implode(', ', $topping_names));
        }

        if (isset($config['extra_toppings']) && !empty($config['extra_toppings'])) {
            $extra_names = array_column($config['extra_toppings'], 'name');
            $item->add_meta_data(__('Extra Toppings', 'pizza-ordering'), implode(', ', $extra_names));
        }

        if (isset($config['instructions']) && !empty($config['instructions'])) {
            $item->add_meta_data(__('Special Instructions', 'pizza-ordering'), $config['instructions']);
        }
    }
}
