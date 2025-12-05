<?php
/**
 * Pizza Cart
 *
 * Handles cart modifications for pizza orders including delivery/pickup.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Cart Class
 */
class Pizza_Cart {

    /**
     * Single instance
     *
     * @var Pizza_Cart
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Cart
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
        // Add delivery/pickup fields to checkout
        add_action('woocommerce_before_order_notes', array($this, 'add_delivery_fields'));
        
        // Validate delivery fields
        add_action('woocommerce_checkout_process', array($this, 'validate_delivery_fields'));
        
        // Save delivery fields to order
        add_action('woocommerce_checkout_create_order', array($this, 'save_delivery_fields'));
        
        // Display delivery info in order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_delivery_info_admin'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_delivery_info_frontend'));
        
        // Add delivery fee
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_delivery_fee'));
        
        // Store delivery type in session
        add_action('wp_ajax_pizza_set_delivery_type', array($this, 'ajax_set_delivery_type'));
        add_action('wp_ajax_nopriv_pizza_set_delivery_type', array($this, 'ajax_set_delivery_type'));
        
        // Check postcode for delivery
        add_action('wp_ajax_pizza_check_postcode', array($this, 'ajax_check_postcode'));
        add_action('wp_ajax_nopriv_pizza_check_postcode', array($this, 'ajax_check_postcode'));
    }

    /**
     * Add delivery/pickup fields to checkout
     *
     * @param WC_Checkout $checkout Checkout object
     */
    public function add_delivery_fields($checkout) {
        $enable_delivery = get_option('pizza_ordering_enable_delivery', 'yes');
        $enable_pickup = get_option('pizza_ordering_enable_pickup', 'yes');
        
        if ($enable_delivery !== 'yes' && $enable_pickup !== 'yes') {
            return;
        }

        $delivery_type = WC()->session->get('pizza_delivery_type', 'pickup');
        $opening_hours = get_option('pizza_ordering_opening_hours', array());
        $order_interval = get_option('pizza_ordering_order_interval', 15);
        $prep_time = get_option('pizza_ordering_prep_time', 20);

        ?>
        <div id="pizza-delivery-options" class="pizza-checkout-section">
            <h3><?php esc_html_e('Order Type', 'pizza-ordering'); ?></h3>
            
            <div class="pizza-delivery-type-wrapper">
                <?php if ($enable_pickup === 'yes') : ?>
                    <label class="pizza-delivery-type-option <?php echo $delivery_type === 'pickup' ? 'active' : ''; ?>">
                        <input type="radio" name="pizza_delivery_type" value="pickup" 
                               <?php checked($delivery_type, 'pickup'); ?>>
                        <span class="pizza-delivery-type-icon">🏪</span>
                        <span class="pizza-delivery-type-label"><?php esc_html_e('Pickup', 'pizza-ordering'); ?></span>
                    </label>
                <?php endif; ?>
                
                <?php if ($enable_delivery === 'yes') : ?>
                    <label class="pizza-delivery-type-option <?php echo $delivery_type === 'delivery' ? 'active' : ''; ?>">
                        <input type="radio" name="pizza_delivery_type" value="delivery" 
                               <?php checked($delivery_type, 'delivery'); ?>>
                        <span class="pizza-delivery-type-icon">🚗</span>
                        <span class="pizza-delivery-type-label"><?php esc_html_e('Delivery', 'pizza-ordering'); ?></span>
                    </label>
                <?php endif; ?>
            </div>

            <div class="pizza-delivery-postcode-check" style="display: <?php echo $delivery_type === 'delivery' ? 'block' : 'none'; ?>;">
                <p class="form-row">
                    <label for="pizza_check_postcode"><?php esc_html_e('Check if we deliver to you', 'pizza-ordering'); ?></label>
                    <input type="text" id="pizza_check_postcode" name="pizza_check_postcode" 
                           placeholder="<?php esc_attr_e('Enter postcode', 'pizza-ordering'); ?>">
                    <button type="button" id="pizza_check_postcode_btn" class="button">
                        <?php esc_html_e('Check', 'pizza-ordering'); ?>
                    </button>
                    <span id="pizza_postcode_result"></span>
                </p>
            </div>

            <h3><?php esc_html_e('When do you want it?', 'pizza-ordering'); ?></h3>
            
            <div class="pizza-time-selection">
                <p class="form-row form-row-wide">
                    <label class="pizza-time-option">
                        <input type="radio" name="pizza_time_type" value="asap" checked>
                        <?php 
                        printf(
                            /* translators: %d: minutes */
                            esc_html__('As soon as possible (approx. %d min)', 'pizza-ordering'),
                            $prep_time
                        );
                        ?>
                    </label>
                </p>
                <p class="form-row form-row-wide">
                    <label class="pizza-time-option">
                        <input type="radio" name="pizza_time_type" value="scheduled">
                        <?php esc_html_e('Schedule for later', 'pizza-ordering'); ?>
                    </label>
                </p>
            </div>

            <div class="pizza-scheduled-time" style="display: none;">
                <p class="form-row form-row-first">
                    <label for="pizza_order_date"><?php esc_html_e('Date', 'pizza-ordering'); ?></label>
                    <input type="date" id="pizza_order_date" name="pizza_order_date" 
                           min="<?php echo esc_attr(date('Y-m-d')); ?>"
                           max="<?php echo esc_attr(date('Y-m-d', strtotime('+7 days'))); ?>">
                </p>
                <p class="form-row form-row-last">
                    <label for="pizza_order_time"><?php esc_html_e('Time', 'pizza-ordering'); ?></label>
                    <select id="pizza_order_time" name="pizza_order_time">
                        <option value=""><?php esc_html_e('Select time', 'pizza-ordering'); ?></option>
                    </select>
                </p>
                <div class="clear"></div>
            </div>

            <p class="form-row form-row-wide">
                <label for="pizza_order_notes"><?php esc_html_e('Order Notes', 'pizza-ordering'); ?></label>
                <textarea id="pizza_order_notes" name="pizza_order_notes" rows="3" 
                          placeholder="<?php esc_attr_e('Any special instructions for your order...', 'pizza-ordering'); ?>"></textarea>
            </p>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Delivery type toggle
                $('input[name="pizza_delivery_type"]').on('change', function() {
                    var type = $(this).val();
                    $('.pizza-delivery-type-option').removeClass('active');
                    $(this).closest('.pizza-delivery-type-option').addClass('active');
                    
                    if (type === 'delivery') {
                        $('.pizza-delivery-postcode-check').slideDown();
                    } else {
                        $('.pizza-delivery-postcode-check').slideUp();
                    }

                    // Update session
                    $.post(wc_checkout_params.ajax_url, {
                        action: 'pizza_set_delivery_type',
                        type: type,
                        nonce: '<?php echo wp_create_nonce('pizza_delivery_nonce'); ?>'
                    }, function() {
                        $('body').trigger('update_checkout');
                    });
                });

                // Time type toggle
                $('input[name="pizza_time_type"]').on('change', function() {
                    if ($(this).val() === 'scheduled') {
                        $('.pizza-scheduled-time').slideDown();
                    } else {
                        $('.pizza-scheduled-time').slideUp();
                    }
                });

                // Date change - load available times
                $('#pizza_order_date').on('change', function() {
                    var date = $(this).val();
                    if (!date) return;

                    var $timeSelect = $('#pizza_order_time');
                    $timeSelect.html('<option value=""><?php esc_html_e('Loading...', 'pizza-ordering'); ?></option>');

                    $.post(wc_checkout_params.ajax_url, {
                        action: 'pizza_get_available_times',
                        date: date,
                        nonce: '<?php echo wp_create_nonce('pizza_times_nonce'); ?>'
                    }, function(response) {
                        if (response.success && response.data.times) {
                            var options = '<option value=""><?php esc_html_e('Select time', 'pizza-ordering'); ?></option>';
                            $.each(response.data.times, function(i, time) {
                                options += '<option value="' + time.value + '">' + time.label + '</option>';
                            });
                            $timeSelect.html(options);
                        }
                    });
                });

                // Check postcode
                $('#pizza_check_postcode_btn').on('click', function() {
                    var postcode = $('#pizza_check_postcode').val();
                    var $result = $('#pizza_postcode_result');

                    if (!postcode) {
                        $result.html('<span class="error"><?php esc_html_e('Please enter a postcode', 'pizza-ordering'); ?></span>');
                        return;
                    }

                    $result.html('<span class="loading"><?php esc_html_e('Checking...', 'pizza-ordering'); ?></span>');

                    $.post(wc_checkout_params.ajax_url, {
                        action: 'pizza_check_postcode',
                        postcode: postcode,
                        nonce: '<?php echo wp_create_nonce('pizza_postcode_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $result.html('<span class="success">✓ ' + response.data.message + '</span>');
                        } else {
                            $result.html('<span class="error">✗ ' + response.data.message + '</span>');
                        }
                    });
                });
            });
        </script>

        <style>
            .pizza-checkout-section { margin-bottom: 30px; }
            .pizza-delivery-type-wrapper { display: flex; gap: 15px; margin-bottom: 20px; }
            .pizza-delivery-type-option {
                flex: 1;
                padding: 20px;
                border: 2px solid #ddd;
                border-radius: 8px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .pizza-delivery-type-option:hover { border-color: #999; }
            .pizza-delivery-type-option.active { border-color: #c9302c; background: #fff5f5; }
            .pizza-delivery-type-option input { display: none; }
            .pizza-delivery-type-icon { display: block; font-size: 32px; margin-bottom: 5px; }
            .pizza-delivery-type-label { font-weight: bold; }
            .pizza-time-option { display: block; padding: 10px 15px; background: #f9f9f9; border-radius: 5px; margin-bottom: 5px; }
            .pizza-time-option:hover { background: #f0f0f0; }
            #pizza_postcode_result .success { color: #28a745; }
            #pizza_postcode_result .error { color: #dc3545; }
            #pizza_postcode_result .loading { color: #6c757d; }
        </style>
        <?php
    }

    /**
     * Validate delivery fields
     */
    public function validate_delivery_fields() {
        $delivery_type = isset($_POST['pizza_delivery_type']) ? sanitize_text_field(wp_unslash($_POST['pizza_delivery_type'])) : 'pickup';
        $time_type = isset($_POST['pizza_time_type']) ? sanitize_text_field(wp_unslash($_POST['pizza_time_type'])) : 'asap';

        // Validate scheduled time
        if ($time_type === 'scheduled') {
            if (empty($_POST['pizza_order_date'])) {
                wc_add_notice(__('Please select a date for your order.', 'pizza-ordering'), 'error');
            }
            if (empty($_POST['pizza_order_time'])) {
                wc_add_notice(__('Please select a time for your order.', 'pizza-ordering'), 'error');
            }
        }

        // Validate delivery postcode
        if ($delivery_type === 'delivery') {
            $billing_postcode = isset($_POST['billing_postcode']) ? sanitize_text_field(wp_unslash($_POST['billing_postcode'])) : '';
            if (!empty($billing_postcode)) {
                $zone = $this->get_delivery_zone($billing_postcode);
                if (!$zone) {
                    wc_add_notice(__('Sorry, we do not deliver to your area.', 'pizza-ordering'), 'error');
                }
            }
        }
    }

    /**
     * Save delivery fields to order
     *
     * @param WC_Order $order Order object
     */
    public function save_delivery_fields($order) {
        $delivery_type = isset($_POST['pizza_delivery_type']) ? sanitize_text_field(wp_unslash($_POST['pizza_delivery_type'])) : 'pickup';
        $time_type = isset($_POST['pizza_time_type']) ? sanitize_text_field(wp_unslash($_POST['pizza_time_type'])) : 'asap';

        $order->update_meta_data('_pizza_delivery_type', $delivery_type);
        $order->update_meta_data('_pizza_time_type', $time_type);

        if ($time_type === 'scheduled') {
            $order_date = isset($_POST['pizza_order_date']) ? sanitize_text_field(wp_unslash($_POST['pizza_order_date'])) : '';
            $order_time = isset($_POST['pizza_order_time']) ? sanitize_text_field(wp_unslash($_POST['pizza_order_time'])) : '';
            $order->update_meta_data('_pizza_scheduled_date', $order_date);
            $order->update_meta_data('_pizza_scheduled_time', $order_time);
        }

        if (isset($_POST['pizza_order_notes'])) {
            $order->update_meta_data('_pizza_order_notes', sanitize_textarea_field(wp_unslash($_POST['pizza_order_notes'])));
        }

        // Set initial order status
        $order->update_meta_data('_pizza_order_status', 'received');
    }

    /**
     * Display delivery info in admin
     *
     * @param WC_Order $order Order object
     */
    public function display_delivery_info_admin($order) {
        $delivery_type = $order->get_meta('_pizza_delivery_type');
        $time_type = $order->get_meta('_pizza_time_type');
        $pizza_status = $order->get_meta('_pizza_order_status');

        if (!$delivery_type) {
            return;
        }
        ?>
        <div class="pizza-order-info" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
            <h3 style="margin-top: 0;">🍕 <?php esc_html_e('Pizza Order Details', 'pizza-ordering'); ?></h3>
            
            <p>
                <strong><?php esc_html_e('Order Type:', 'pizza-ordering'); ?></strong>
                <?php echo $delivery_type === 'delivery' ? esc_html__('Delivery', 'pizza-ordering') : esc_html__('Pickup', 'pizza-ordering'); ?>
            </p>

            <p>
                <strong><?php esc_html_e('Requested Time:', 'pizza-ordering'); ?></strong>
                <?php 
                if ($time_type === 'asap') {
                    esc_html_e('As soon as possible', 'pizza-ordering');
                } else {
                    $date = $order->get_meta('_pizza_scheduled_date');
                    $time = $order->get_meta('_pizza_scheduled_time');
                    echo esc_html($date . ' ' . $time);
                }
                ?>
            </p>

            <?php 
            $notes = $order->get_meta('_pizza_order_notes');
            if ($notes) : 
            ?>
            <p>
                <strong><?php esc_html_e('Order Notes:', 'pizza-ordering'); ?></strong><br>
                <?php echo esc_html($notes); ?>
            </p>
            <?php endif; ?>

            <p>
                <strong><?php esc_html_e('Kitchen Status:', 'pizza-ordering'); ?></strong>
                <span class="pizza-status pizza-status-<?php echo esc_attr($pizza_status ?: 'received'); ?>">
                    <?php echo esc_html($this->get_status_label($pizza_status)); ?>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Display delivery info on frontend
     *
     * @param WC_Order $order Order object
     */
    public function display_delivery_info_frontend($order) {
        $delivery_type = $order->get_meta('_pizza_delivery_type');
        $pizza_status = $order->get_meta('_pizza_order_status');

        if (!$delivery_type) {
            return;
        }
        ?>
        <h2><?php esc_html_e('Order Status', 'pizza-ordering'); ?></h2>
        <table class="woocommerce-table shop_table pizza-order-status">
            <tr>
                <th><?php esc_html_e('Order Type', 'pizza-ordering'); ?></th>
                <td><?php echo $delivery_type === 'delivery' ? esc_html__('Delivery', 'pizza-ordering') : esc_html__('Pickup', 'pizza-ordering'); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Status', 'pizza-ordering'); ?></th>
                <td class="pizza-status pizza-status-<?php echo esc_attr($pizza_status ?: 'received'); ?>">
                    <?php echo esc_html($this->get_status_label($pizza_status)); ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Get status label
     *
     * @param string $status Status key
     * @return string
     */
    private function get_status_label($status) {
        $statuses = array(
            'received'   => __('Order Received', 'pizza-ordering'),
            'preparing'  => __('Preparing', 'pizza-ordering'),
            'ready'      => __('Ready', 'pizza-ordering'),
            'out_for_delivery' => __('Out for Delivery', 'pizza-ordering'),
            'delivered'  => __('Delivered', 'pizza-ordering'),
            'picked_up'  => __('Picked Up', 'pizza-ordering'),
        );

        return isset($statuses[$status]) ? $statuses[$status] : __('Processing', 'pizza-ordering');
    }

    /**
     * Add delivery fee
     *
     * @param WC_Cart $cart Cart object
     */
    public function add_delivery_fee($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $delivery_type = WC()->session->get('pizza_delivery_type', 'pickup');

        if ($delivery_type !== 'delivery') {
            return;
        }

        // Get billing postcode from session or checkout
        $postcode = '';
        if (isset($_POST['post_data'])) {
            parse_str(sanitize_text_field(wp_unslash($_POST['post_data'])), $post_data);
            $postcode = isset($post_data['billing_postcode']) ? sanitize_text_field($post_data['billing_postcode']) : '';
        }

        if (empty($postcode) && WC()->customer) {
            $postcode = WC()->customer->get_billing_postcode();
        }

        if (empty($postcode)) {
            return;
        }

        $zone = $this->get_delivery_zone($postcode);

        if ($zone && $zone->delivery_fee > 0) {
            $cart->add_fee(__('Delivery Fee', 'pizza-ordering'), $zone->delivery_fee);
        }
    }

    /**
     * Get delivery zone for postcode
     *
     * @param string $postcode Postcode
     * @return object|null
     */
    private function get_delivery_zone($postcode) {
        global $wpdb;

        $postcode = sanitize_text_field($postcode);
        $table = $wpdb->prefix . 'pizza_delivery_zones';

        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if (!$table_exists) {
            return null;
        }

        $zones = $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1");

        foreach ($zones as $zone) {
            $postcodes = array_map('trim', explode(',', $zone->postcodes));
            foreach ($postcodes as $zone_postcode) {
                // Support wildcards (e.g., "12*" matches "1234")
                $pattern = str_replace('*', '.*', preg_quote($zone_postcode, '/'));
                if (preg_match('/^' . $pattern . '$/i', $postcode)) {
                    return $zone;
                }
            }
        }

        return null;
    }

    /**
     * AJAX: Set delivery type
     */
    public function ajax_set_delivery_type() {
        check_ajax_referer('pizza_delivery_nonce', 'nonce');

        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'pickup';
        WC()->session->set('pizza_delivery_type', $type);

        wp_send_json_success();
    }

    /**
     * AJAX: Check postcode
     */
    public function ajax_check_postcode() {
        check_ajax_referer('pizza_postcode_nonce', 'nonce');

        $postcode = isset($_POST['postcode']) ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '';

        if (empty($postcode)) {
            wp_send_json_error(array('message' => __('Please enter a postcode.', 'pizza-ordering')));
        }

        $zone = $this->get_delivery_zone($postcode);

        if ($zone) {
            $message = sprintf(
                /* translators: 1: delivery time, 2: minimum order */
                __('We deliver to you! Estimated time: %1$d min. Minimum order: %2$s', 'pizza-ordering'),
                $zone->delivery_time,
                wc_price($zone->min_order)
            );
            wp_send_json_success(array('message' => $message, 'zone' => $zone));
        } else {
            wp_send_json_error(array('message' => __('Sorry, we do not deliver to this area.', 'pizza-ordering')));
        }
    }
}
