<?php
/**
 * Plugin Name: Pizza Ordering for WooCommerce
 * Plugin URI: https://example.com/pizza-ordering
 * Description: Complete pizza ordering system with pizza builder, toppings management, pickup/delivery scheduling, and kitchen dashboard.
 * Version: 1.0.0
 * Author: Sascha Marc Rahn
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pizza-ordering
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package Pizza_Ordering
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('PIZZA_ORDERING_VERSION', '1.0.0');
define('PIZZA_ORDERING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PIZZA_ORDERING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PIZZA_ORDERING_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Pizza Ordering Class
 */
final class Pizza_Ordering {

    /**
     * Single instance
     *
     * @var Pizza_Ordering
     */
    private static $instance = null;

    /**
     * Debug mode
     *
     * @var bool
     */
    public $debug = false;

    /**
     * Get single instance
     *
     * @return Pizza_Ordering
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
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
        
        // Check dependencies
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'), 20);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        return true;
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Pizza Ordering requires WooCommerce to be installed and active.', 'pizza-ordering'); ?></p>
        </div>
        <?php
    }

    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        if (!$this->check_dependencies()) {
            return;
        }

        // Load text domain
        load_plugin_textdomain('pizza-ordering', false, dirname(PIZZA_ORDERING_PLUGIN_BASENAME) . '/languages');

        // Include required files
        $this->includes();

        // Initialize components
        $this->init_components();

        // Debug logging
        $this->log('Pizza Ordering plugin initialized');
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core includes
        require_once PIZZA_ORDERING_PLUGIN_DIR . 'includes/class-pizza-post-types.php';
        require_once PIZZA_ORDERING_PLUGIN_DIR . 'includes/class-pizza-product-type.php';
        require_once PIZZA_ORDERING_PLUGIN_DIR . 'includes/class-pizza-cart.php';
        require_once PIZZA_ORDERING_PLUGIN_DIR . 'includes/class-pizza-ajax.php';

        // Admin includes
        if (is_admin()) {
            require_once PIZZA_ORDERING_PLUGIN_DIR . 'admin/class-pizza-admin.php';
            require_once PIZZA_ORDERING_PLUGIN_DIR . 'admin/class-pizza-kitchen.php';
        }

        // Frontend includes
        if (!is_admin() || defined('DOING_AJAX')) {
            require_once PIZZA_ORDERING_PLUGIN_DIR . 'public/class-pizza-frontend.php';
        }
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Post types
        Pizza_Post_Types::instance();

        // Product type
        Pizza_Product_Type::instance();

        // Cart handling
        Pizza_Cart::instance();

        // AJAX handlers
        Pizza_Ajax::instance();

        // Admin
        if (is_admin()) {
            Pizza_Admin::instance();
            Pizza_Kitchen::instance();
        }

        // Frontend
        if (!is_admin() || defined('DOING_AJAX')) {
            Pizza_Frontend::instance();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom tables if needed
        $this->create_tables();

        // Create default options
        $this->create_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        set_transient('pizza_ordering_activated', true, 30);

        $this->log('Plugin activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        $this->log('Plugin deactivated');
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Delivery zones table
        $table_zones = $wpdb->prefix . 'pizza_delivery_zones';
        $sql_zones = "CREATE TABLE IF NOT EXISTS $table_zones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            zone_name varchar(255) NOT NULL,
            postcodes text NOT NULL,
            delivery_fee decimal(10,2) NOT NULL DEFAULT 0,
            min_order decimal(10,2) NOT NULL DEFAULT 0,
            delivery_time int(11) NOT NULL DEFAULT 30,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Time slots table
        $table_slots = $wpdb->prefix . 'pizza_time_slots';
        $sql_slots = "CREATE TABLE IF NOT EXISTS $table_slots (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slot_date date NOT NULL,
            slot_time time NOT NULL,
            max_orders int(11) NOT NULL DEFAULT 5,
            current_orders int(11) NOT NULL DEFAULT 0,
            is_blocked tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY slot_date (slot_date),
            KEY slot_time (slot_time)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_zones);
        dbDelta($sql_slots);
    }

    /**
     * Create default options
     */
    private function create_default_options() {
        $defaults = array(
            'pizza_ordering_opening_hours' => array(
                'monday'    => array('open' => '11:00', 'close' => '22:00', 'closed' => false),
                'tuesday'   => array('open' => '11:00', 'close' => '22:00', 'closed' => false),
                'wednesday' => array('open' => '11:00', 'close' => '22:00', 'closed' => false),
                'thursday'  => array('open' => '11:00', 'close' => '22:00', 'closed' => false),
                'friday'    => array('open' => '11:00', 'close' => '23:00', 'closed' => false),
                'saturday'  => array('open' => '12:00', 'close' => '23:00', 'closed' => false),
                'sunday'    => array('open' => '12:00', 'close' => '21:00', 'closed' => false),
            ),
            'pizza_ordering_order_interval' => 15,
            'pizza_ordering_max_orders_per_slot' => 5,
            'pizza_ordering_prep_time' => 20,
            'pizza_ordering_enable_delivery' => 'yes',
            'pizza_ordering_enable_pickup' => 'yes',
            'pizza_ordering_currency_symbol' => 'kr.',
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Debug logging
     *
     * @param string $message Log message
     * @param mixed  $data    Optional data to log
     */
    public function log($message, $data = null) {
        if (!$this->debug) {
            return;
        }

        $log_entry = '[Pizza Ordering] ' . $message;
        
        if (!is_null($data)) {
            $log_entry .= ' | Data: ' . print_r($data, true);
        }

        error_log($log_entry);
    }

    /**
     * Get plugin option
     *
     * @param string $key     Option key
     * @param mixed  $default Default value
     * @return mixed
     */
    public function get_option($key, $default = '') {
        return get_option('pizza_ordering_' . $key, $default);
    }

    /**
     * Update plugin option
     *
     * @param string $key   Option key
     * @param mixed  $value Option value
     * @return bool
     */
    public function update_option($key, $value) {
        return update_option('pizza_ordering_' . $key, $value);
    }
}

/**
 * Get main plugin instance
 *
 * @return Pizza_Ordering
 */
function pizza_ordering() {
    return Pizza_Ordering::instance();
}

// Initialize plugin
pizza_ordering();
