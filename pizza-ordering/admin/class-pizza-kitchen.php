<?php
/**
 * Pizza Kitchen Dashboard
 *
 * Live kitchen dashboard for managing pizza orders.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Kitchen Class
 */
class Pizza_Kitchen {

    /**
     * Single instance
     *
     * @var Pizza_Kitchen
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Kitchen
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
        add_action('admin_menu', array($this, 'add_kitchen_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_kitchen_assets'));
    }

    /**
     * Add kitchen dashboard menu
     */
    public function add_kitchen_menu() {
        add_submenu_page(
            'pizza-ordering',
            __('Kitchen Dashboard', 'pizza-ordering'),
            __('Kitchen Dashboard', 'pizza-ordering'),
            'edit_shop_orders',
            'pizza-kitchen',
            array($this, 'render_kitchen_dashboard')
        );
    }

    /**
     * Enqueue kitchen assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_kitchen_assets($hook) {
        if ($hook !== 'pizza-ordering_page_pizza-kitchen') {
            return;
        }

        wp_enqueue_style(
            'pizza-kitchen-style',
            PIZZA_ORDERING_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            PIZZA_ORDERING_VERSION
        );

        wp_enqueue_script(
            'pizza-kitchen-script',
            PIZZA_ORDERING_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            PIZZA_ORDERING_VERSION,
            true
        );

        wp_localize_script('pizza-kitchen-script', 'pizzaKitchen', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('pizza_kitchen_nonce'),
            'refreshRate'  => 30000,
            'statuses'     => array(
                'received'        => __('Received', 'pizza-ordering'),
                'preparing'       => __('Preparing', 'pizza-ordering'),
                'ready'           => __('Ready', 'pizza-ordering'),
                'out_for_delivery' => __('Out for Delivery', 'pizza-ordering'),
                'delivered'       => __('Delivered', 'pizza-ordering'),
                'picked_up'       => __('Picked Up', 'pizza-ordering'),
            ),
        ));
    }

    /**
     * Render kitchen dashboard
     */
    public function render_kitchen_dashboard() {
        // Get orders
        $received_orders = $this->get_orders_by_status('received');
        $preparing_orders = $this->get_orders_by_status('preparing');
        $ready_orders = $this->get_orders_by_status('ready');
        ?>
        <div class="wrap pizza-kitchen-wrap">
            <h1>
                🍕 <?php esc_html_e('Kitchen Dashboard', 'pizza-ordering'); ?>
                <span class="pizza-kitchen-time"><?php echo esc_html(current_time('H:i')); ?></span>
                <button type="button" id="pizza-kitchen-refresh" class="button">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Refresh', 'pizza-ordering'); ?>
                </button>
            </h1>

            <div class="pizza-kitchen-controls">
                <label>
                    <input type="checkbox" id="pizza-auto-refresh" checked>
                    <?php esc_html_e('Auto-refresh every 30 seconds', 'pizza-ordering'); ?>
                </label>
                <label>
                    <input type="checkbox" id="pizza-sound-alerts" checked>
                    <?php esc_html_e('Sound alerts for new orders', 'pizza-ordering'); ?>
                </label>
            </div>

            <div class="pizza-kitchen-columns">
                <!-- Received Orders -->
                <div class="pizza-kitchen-column" data-status="received">
                    <div class="pizza-kitchen-column-header pizza-status-received">
                        <h2>
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('New Orders', 'pizza-ordering'); ?>
                            <span class="pizza-order-count"><?php echo count($received_orders); ?></span>
                        </h2>
                    </div>
                    <div class="pizza-kitchen-orders" id="orders-received">
                        <?php foreach ($received_orders as $order) : ?>
                            <?php $this->render_order_card($order); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Preparing Orders -->
                <div class="pizza-kitchen-column" data-status="preparing">
                    <div class="pizza-kitchen-column-header pizza-status-preparing">
                        <h2>
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Preparing', 'pizza-ordering'); ?>
                            <span class="pizza-order-count"><?php echo count($preparing_orders); ?></span>
                        </h2>
                    </div>
                    <div class="pizza-kitchen-orders" id="orders-preparing">
                        <?php foreach ($preparing_orders as $order) : ?>
                            <?php $this->render_order_card($order); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ready Orders -->
                <div class="pizza-kitchen-column" data-status="ready">
                    <div class="pizza-kitchen-column-header pizza-status-ready">
                        <h2>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Ready', 'pizza-ordering'); ?>
                            <span class="pizza-order-count"><?php echo count($ready_orders); ?></span>
                        </h2>
                    </div>
                    <div class="pizza-kitchen-orders" id="orders-ready">
                        <?php foreach ($ready_orders as $order) : ?>
                            <?php $this->render_order_card($order); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sound for new orders -->
            <audio id="pizza-new-order-sound" preload="auto">
                <source src="<?php echo esc_url(PIZZA_ORDERING_PLUGIN_URL . 'admin/sounds/new-order.mp3'); ?>" type="audio/mpeg">
            </audio>
        </div>
        <?php
    }

    /**
     * Get orders by pizza status
     *
     * @param string $status Pizza order status
     * @return array
     */
    private function get_orders_by_status($status) {
        return wc_get_orders(array(
            'limit'      => 50,
            'status'     => array('processing', 'on-hold'),
            'meta_key'   => '_pizza_order_status',
            'meta_value' => $status,
            'orderby'    => 'date',
            'order'      => 'ASC',
        ));
    }

    /**
     * Render order card
     *
     * @param WC_Order $order Order object
     */
    private function render_order_card($order) {
        $delivery_type = $order->get_meta('_pizza_delivery_type');
        $time_type = $order->get_meta('_pizza_time_type');
        $scheduled_date = $order->get_meta('_pizza_scheduled_date');
        $scheduled_time = $order->get_meta('_pizza_scheduled_time');
        $pizza_status = $order->get_meta('_pizza_order_status');
        $order_notes = $order->get_meta('_pizza_order_notes');
        
        $order_time = $order->get_date_created();
        $minutes_ago = round((time() - $order_time->getTimestamp()) / 60);
        
        $is_urgent = $minutes_ago > 20;
        ?>
        <div class="pizza-order-card <?php echo $is_urgent ? 'pizza-order-urgent' : ''; ?>" 
             data-order-id="<?php echo esc_attr($order->get_id()); ?>"
             data-status="<?php echo esc_attr($pizza_status); ?>">
            
            <div class="pizza-order-header">
                <span class="pizza-order-number">#<?php echo esc_html($order->get_order_number()); ?></span>
                <span class="pizza-order-type pizza-type-<?php echo esc_attr($delivery_type); ?>">
                    <?php echo $delivery_type === 'delivery' ? '🚗' : '🏪'; ?>
                    <?php echo $delivery_type === 'delivery' ? esc_html__('Delivery', 'pizza-ordering') : esc_html__('Pickup', 'pizza-ordering'); ?>
                </span>
            </div>

            <div class="pizza-order-time">
                <strong><?php esc_html_e('Ordered:', 'pizza-ordering'); ?></strong>
                <?php echo esc_html($order_time->format('H:i')); ?>
                <span class="pizza-time-ago">(<?php echo esc_html($minutes_ago); ?> min ago)</span>
                
                <?php if ($time_type === 'scheduled') : ?>
                    <br>
                    <strong><?php esc_html_e('Requested:', 'pizza-ordering'); ?></strong>
                    <?php echo esc_html($scheduled_date . ' ' . $scheduled_time); ?>
                <?php endif; ?>
            </div>

            <div class="pizza-order-customer">
                <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong>
                <?php if ($delivery_type === 'delivery') : ?>
                    <br>
                    <small><?php echo esc_html($order->get_billing_address_1() . ', ' . $order->get_billing_postcode()); ?></small>
                <?php endif; ?>
                <br>
                <a href="tel:<?php echo esc_attr($order->get_billing_phone()); ?>"><?php echo esc_html($order->get_billing_phone()); ?></a>
            </div>

            <div class="pizza-order-items">
                <?php foreach ($order->get_items() as $item) : ?>
                    <?php 
                    $pizza_config = $item->get_meta('_pizza_config');
                    ?>
                    <div class="pizza-order-item">
                        <div class="pizza-item-name">
                            <strong><?php echo esc_html($item->get_quantity()); ?>x <?php echo esc_html($item->get_name()); ?></strong>
                        </div>
                        
                        <?php if ($pizza_config) : ?>
                            <div class="pizza-item-details">
                                <?php if (isset($pizza_config['size_name'])) : ?>
                                    <span class="pizza-detail">📏 <?php echo esc_html($pizza_config['size_name']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (isset($pizza_config['base_name'])) : ?>
                                    <span class="pizza-detail">🍞 <?php echo esc_html($pizza_config['base_name']); ?></span>
                                <?php endif; ?>
                                
                                <?php if (isset($pizza_config['sauce_name'])) : ?>
                                    <span class="pizza-detail">🥫 <?php echo esc_html($pizza_config['sauce_name']); ?></span>
                                <?php endif; ?>
                                
                                <?php // NEW SYSTEM: Included toppings ?>
                                <?php if (isset($pizza_config['included_toppings']) && !empty($pizza_config['included_toppings'])) : ?>
                                    <div class="pizza-toppings pizza-included">
                                        <strong>✅ <?php esc_html_e('On pizza:', 'pizza-ordering'); ?></strong>
                                        <?php 
                                        $topping_names = array_column($pizza_config['included_toppings'], 'name');
                                        echo esc_html(implode(', ', $topping_names));
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php // NEW SYSTEM: REMOVED toppings - VERY IMPORTANT for kitchen! ?>
                                <?php if (isset($pizza_config['removed_toppings']) && !empty($pizza_config['removed_toppings'])) : ?>
                                    <div class="pizza-toppings pizza-removed" style="background: #ffebee; color: #c62828; padding: 8px; border-radius: 5px; margin: 5px 0;">
                                        <strong>❌ UDEN:</strong>
                                        <?php 
                                        $removed_names = array_column($pizza_config['removed_toppings'], 'name');
                                        echo esc_html(implode(', ', $removed_names));
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php // NEW SYSTEM: ADDED extra toppings ?>
                                <?php if (isset($pizza_config['added_toppings']) && !empty($pizza_config['added_toppings'])) : ?>
                                    <div class="pizza-toppings pizza-added" style="background: #fff3e0; color: #e65100; padding: 8px; border-radius: 5px; margin: 5px 0;">
                                        <strong>➕ TILFØJET:</strong>
                                        <?php 
                                        $added_names = array_column($pizza_config['added_toppings'], 'name');
                                        echo esc_html(implode(', ', $added_names));
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php // NEW SYSTEM: Extra portions ?>
                                <?php if (isset($pizza_config['extra_portions']) && !empty($pizza_config['extra_portions'])) : ?>
                                    <div class="pizza-toppings pizza-extra" style="background: #fce4ec; color: #c2185b; padding: 8px; border-radius: 5px; margin: 5px 0;">
                                        <strong>🔥 EKSTRA PORTION:</strong>
                                        <?php 
                                        $extra_names = array_column($pizza_config['extra_portions'], 'name');
                                        echo esc_html(implode(', ', $extra_names));
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php // LEGACY: Old toppings array ?>
                                <?php if (isset($pizza_config['toppings']) && !empty($pizza_config['toppings'])) : ?>
                                    <div class="pizza-toppings">
                                        <strong><?php esc_html_e('Toppings:', 'pizza-ordering'); ?></strong>
                                        <?php 
                                        $topping_names = array_column($pizza_config['toppings'], 'name');
                                        echo esc_html(implode(', ', $topping_names));
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($pizza_config['extra_toppings']) && !empty($pizza_config['extra_toppings'])) : ?>
                                    <div class="pizza-extra-toppings">
                                        <strong><?php esc_html_e('Extra:', 'pizza-ordering'); ?></strong>
                                        <?php 
                                        $extra_names = array_column($pizza_config['extra_toppings'], 'name');
                                        echo esc_html(implode(', ', $extra_names));
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($pizza_config['instructions']) && !empty($pizza_config['instructions'])) : ?>
                                    <div class="pizza-instructions">
                                        ⚠️ <?php echo esc_html($pizza_config['instructions']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($order_notes) : ?>
                <div class="pizza-order-notes">
                    <strong>📝 <?php esc_html_e('Notes:', 'pizza-ordering'); ?></strong>
                    <?php echo esc_html($order_notes); ?>
                </div>
            <?php endif; ?>

            <div class="pizza-order-total">
                <strong><?php esc_html_e('Total:', 'pizza-ordering'); ?></strong>
                <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
            </div>

            <div class="pizza-order-actions">
                <?php $this->render_status_buttons($pizza_status, $delivery_type); ?>
                
                <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" class="button pizza-btn-view" target="_blank">
                    <?php esc_html_e('View Order', 'pizza-ordering'); ?>
                </a>
                
                <button type="button" class="button pizza-btn-print" onclick="window.print();">
                    🖨️ <?php esc_html_e('Print', 'pizza-ordering'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render status action buttons
     *
     * @param string $current_status Current pizza status
     * @param string $delivery_type  Delivery type
     */
    private function render_status_buttons($current_status, $delivery_type) {
        $next_status = '';
        $button_text = '';
        $button_class = '';

        switch ($current_status) {
            case 'received':
                $next_status = 'preparing';
                $button_text = __('Start Preparing', 'pizza-ordering');
                $button_class = 'pizza-btn-preparing';
                break;
            case 'preparing':
                $next_status = 'ready';
                $button_text = __('Mark Ready', 'pizza-ordering');
                $button_class = 'pizza-btn-ready';
                break;
            case 'ready':
                if ($delivery_type === 'delivery') {
                    $next_status = 'out_for_delivery';
                    $button_text = __('Out for Delivery', 'pizza-ordering');
                    $button_class = 'pizza-btn-delivery';
                } else {
                    $next_status = 'picked_up';
                    $button_text = __('Picked Up', 'pizza-ordering');
                    $button_class = 'pizza-btn-complete';
                }
                break;
            case 'out_for_delivery':
                $next_status = 'delivered';
                $button_text = __('Delivered', 'pizza-ordering');
                $button_class = 'pizza-btn-complete';
                break;
        }

        if ($next_status) :
        ?>
        <button type="button" class="button button-primary pizza-status-btn <?php echo esc_attr($button_class); ?>" 
                data-next-status="<?php echo esc_attr($next_status); ?>">
            <?php echo esc_html($button_text); ?>
        </button>
        <?php
        endif;
        ?>
        <button type="button" class="button pizza-print-btn" title="<?php esc_attr_e('Print Order', 'pizza-ordering'); ?>">
            🖨️
        </button>
        <?php
    }
}
