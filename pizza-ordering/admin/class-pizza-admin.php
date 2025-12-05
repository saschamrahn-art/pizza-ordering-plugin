<?php
/**
 * Pizza Admin
 *
 * Admin settings and menu for pizza ordering.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Admin Class
 */
class Pizza_Admin {

    /**
     * Single instance
     *
     * @var Pizza_Admin
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'activation_notice'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Pizza Ordering', 'pizza-ordering'),
            __('Pizza Ordering', 'pizza-ordering'),
            'manage_woocommerce',
            'pizza-ordering',
            array($this, 'render_settings_page'),
            'dashicons-carrot',
            56
        );

        // Settings submenu
        add_submenu_page(
            'pizza-ordering',
            __('Settings', 'pizza-ordering'),
            __('Settings', 'pizza-ordering'),
            'manage_woocommerce',
            'pizza-ordering',
            array($this, 'render_settings_page')
        );

        // Delivery Zones submenu
        add_submenu_page(
            'pizza-ordering',
            __('Delivery Zones', 'pizza-ordering'),
            __('Delivery Zones', 'pizza-ordering'),
            'manage_woocommerce',
            'pizza-delivery-zones',
            array($this, 'render_delivery_zones_page')
        );

        // Kitchen Dashboard submenu - handled by Pizza_Kitchen class
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('pizza_ordering_settings', 'pizza_ordering_enable_delivery');
        register_setting('pizza_ordering_settings', 'pizza_ordering_enable_pickup');
        register_setting('pizza_ordering_settings', 'pizza_ordering_prep_time');
        register_setting('pizza_ordering_settings', 'pizza_ordering_order_interval');
        register_setting('pizza_ordering_settings', 'pizza_ordering_max_orders_per_slot');
        register_setting('pizza_ordering_settings', 'pizza_ordering_opening_hours');

        // Sections
        add_settings_section(
            'pizza_ordering_general',
            __('General Settings', 'pizza-ordering'),
            null,
            'pizza-ordering'
        );

        add_settings_section(
            'pizza_ordering_hours',
            __('Opening Hours', 'pizza-ordering'),
            null,
            'pizza-ordering'
        );

        // Fields - General
        add_settings_field(
            'pizza_ordering_enable_delivery',
            __('Enable Delivery', 'pizza-ordering'),
            array($this, 'render_checkbox_field'),
            'pizza-ordering',
            'pizza_ordering_general',
            array('id' => 'pizza_ordering_enable_delivery')
        );

        add_settings_field(
            'pizza_ordering_enable_pickup',
            __('Enable Pickup', 'pizza-ordering'),
            array($this, 'render_checkbox_field'),
            'pizza-ordering',
            'pizza_ordering_general',
            array('id' => 'pizza_ordering_enable_pickup')
        );

        add_settings_field(
            'pizza_ordering_prep_time',
            __('Preparation Time (minutes)', 'pizza-ordering'),
            array($this, 'render_number_field'),
            'pizza-ordering',
            'pizza_ordering_general',
            array('id' => 'pizza_ordering_prep_time', 'default' => 20)
        );

        add_settings_field(
            'pizza_ordering_order_interval',
            __('Time Slot Interval (minutes)', 'pizza-ordering'),
            array($this, 'render_number_field'),
            'pizza-ordering',
            'pizza_ordering_general',
            array('id' => 'pizza_ordering_order_interval', 'default' => 15)
        );

        add_settings_field(
            'pizza_ordering_max_orders_per_slot',
            __('Max Orders per Time Slot', 'pizza-ordering'),
            array($this, 'render_number_field'),
            'pizza-ordering',
            'pizza_ordering_general',
            array('id' => 'pizza_ordering_max_orders_per_slot', 'default' => 5)
        );

        // Fields - Opening Hours
        add_settings_field(
            'pizza_ordering_opening_hours',
            __('Opening Hours', 'pizza-ordering'),
            array($this, 'render_opening_hours_field'),
            'pizza-ordering',
            'pizza_ordering_hours'
        );
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field arguments
     */
    public function render_checkbox_field($args) {
        $value = get_option($args['id'], 'yes');
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($args['id']); ?>" value="yes" <?php checked($value, 'yes'); ?>>
            <?php esc_html_e('Enable', 'pizza-ordering'); ?>
        </label>
        <?php
    }

    /**
     * Render number field
     *
     * @param array $args Field arguments
     */
    public function render_number_field($args) {
        $value = get_option($args['id'], $args['default'] ?? '');
        ?>
        <input type="number" name="<?php echo esc_attr($args['id']); ?>" 
               value="<?php echo esc_attr($value); ?>" min="0" class="small-text">
        <?php
    }

    /**
     * Render opening hours field
     */
    public function render_opening_hours_field() {
        $hours = get_option('pizza_ordering_opening_hours', array());
        $days = array(
            'monday'    => __('Monday', 'pizza-ordering'),
            'tuesday'   => __('Tuesday', 'pizza-ordering'),
            'wednesday' => __('Wednesday', 'pizza-ordering'),
            'thursday'  => __('Thursday', 'pizza-ordering'),
            'friday'    => __('Friday', 'pizza-ordering'),
            'saturday'  => __('Saturday', 'pizza-ordering'),
            'sunday'    => __('Sunday', 'pizza-ordering'),
        );
        ?>
        <table class="widefat pizza-opening-hours">
            <thead>
                <tr>
                    <th><?php esc_html_e('Day', 'pizza-ordering'); ?></th>
                    <th><?php esc_html_e('Open', 'pizza-ordering'); ?></th>
                    <th><?php esc_html_e('Close', 'pizza-ordering'); ?></th>
                    <th><?php esc_html_e('Closed', 'pizza-ordering'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day_key => $day_name) : 
                    $day_hours = isset($hours[$day_key]) ? $hours[$day_key] : array('open' => '11:00', 'close' => '22:00', 'closed' => false);
                ?>
                <tr>
                    <td><?php echo esc_html($day_name); ?></td>
                    <td>
                        <input type="time" 
                               name="pizza_ordering_opening_hours[<?php echo esc_attr($day_key); ?>][open]" 
                               value="<?php echo esc_attr($day_hours['open']); ?>">
                    </td>
                    <td>
                        <input type="time" 
                               name="pizza_ordering_opening_hours[<?php echo esc_attr($day_key); ?>][close]" 
                               value="<?php echo esc_attr($day_hours['close']); ?>">
                    </td>
                    <td>
                        <input type="checkbox" 
                               name="pizza_ordering_opening_hours[<?php echo esc_attr($day_key); ?>][closed]" 
                               value="1" <?php checked(!empty($day_hours['closed'])); ?>>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Pizza Ordering Settings', 'pizza-ordering'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('pizza_ordering_settings');
                do_settings_sections('pizza-ordering');
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Quick Setup Guide', 'pizza-ordering'); ?></h2>
            <ol>
                <li>
                    <strong><?php esc_html_e('Add Pizza Sizes', 'pizza-ordering'); ?></strong> - 
                    <?php 
                    printf(
                        /* translators: %s: link to sizes */
                        esc_html__('Go to %s and add sizes like Small, Medium, Large', 'pizza-ordering'),
                        '<a href="' . esc_url(admin_url('edit.php?post_type=pizza_size')) . '">' . esc_html__('Sizes', 'pizza-ordering') . '</a>'
                    );
                    ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Add Pizza Bases', 'pizza-ordering'); ?></strong> - 
                    <?php 
                    printf(
                        /* translators: %s: link to bases */
                        esc_html__('Go to %s and add bases like Classic, Thin, Thick', 'pizza-ordering'),
                        '<a href="' . esc_url(admin_url('edit.php?post_type=pizza_base')) . '">' . esc_html__('Bases', 'pizza-ordering') . '</a>'
                    );
                    ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Add Sauces', 'pizza-ordering'); ?></strong> - 
                    <?php 
                    printf(
                        /* translators: %s: link to sauces */
                        esc_html__('Go to %s and add sauces like Tomato, Garlic, Pesto', 'pizza-ordering'),
                        '<a href="' . esc_url(admin_url('edit.php?post_type=pizza_sauce')) . '">' . esc_html__('Sauces', 'pizza-ordering') . '</a>'
                    );
                    ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Add Toppings', 'pizza-ordering'); ?></strong> - 
                    <?php 
                    printf(
                        /* translators: %s: link to toppings */
                        esc_html__('Go to %s and add all your toppings with prices', 'pizza-ordering'),
                        '<a href="' . esc_url(admin_url('edit.php?post_type=pizza_topping')) . '">' . esc_html__('Toppings', 'pizza-ordering') . '</a>'
                    );
                    ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Create Pizza Products', 'pizza-ordering'); ?></strong> - 
                    <?php 
                    printf(
                        /* translators: %s: link to add product */
                        esc_html__('Go to %s, select "Pizza" as product type, and configure your pizzas', 'pizza-ordering'),
                        '<a href="' . esc_url(admin_url('post-new.php?post_type=product')) . '">' . esc_html__('Add Product', 'pizza-ordering') . '</a>'
                    );
                    ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Set Delivery Zones', 'pizza-ordering'); ?></strong> - 
                    <?php 
                    printf(
                        /* translators: %s: link to delivery zones */
                        esc_html__('Go to %s and configure your delivery areas', 'pizza-ordering'),
                        '<a href="' . esc_url(admin_url('admin.php?page=pizza-delivery-zones')) . '">' . esc_html__('Delivery Zones', 'pizza-ordering') . '</a>'
                    );
                    ?>
                </li>
            </ol>
        </div>
        <?php
    }

    /**
     * Render delivery zones page
     */
    public function render_delivery_zones_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'pizza_delivery_zones';

        // Handle form submission
        if (isset($_POST['pizza_zone_action'])) {
            check_admin_referer('pizza_delivery_zones_nonce');

            $action = sanitize_text_field(wp_unslash($_POST['pizza_zone_action']));

            if ($action === 'add' || $action === 'edit') {
                $zone_data = array(
                    'zone_name'     => sanitize_text_field(wp_unslash($_POST['zone_name'])),
                    'postcodes'     => sanitize_text_field(wp_unslash($_POST['postcodes'])),
                    'delivery_fee'  => floatval($_POST['delivery_fee']),
                    'min_order'     => floatval($_POST['min_order']),
                    'delivery_time' => absint($_POST['delivery_time']),
                    'is_active'     => isset($_POST['is_active']) ? 1 : 0,
                );

                if ($action === 'add') {
                    $wpdb->insert($table, $zone_data);
                    echo '<div class="notice notice-success"><p>' . esc_html__('Zone added successfully.', 'pizza-ordering') . '</p></div>';
                } else {
                    $zone_id = absint($_POST['zone_id']);
                    $wpdb->update($table, $zone_data, array('id' => $zone_id));
                    echo '<div class="notice notice-success"><p>' . esc_html__('Zone updated successfully.', 'pizza-ordering') . '</p></div>';
                }
            }

            if ($action === 'delete') {
                $zone_id = absint($_POST['zone_id']);
                $wpdb->delete($table, array('id' => $zone_id));
                echo '<div class="notice notice-success"><p>' . esc_html__('Zone deleted successfully.', 'pizza-ordering') . '</p></div>';
            }
        }

        // Get zones
        $zones = $wpdb->get_results("SELECT * FROM {$table} ORDER BY zone_name ASC");
        $edit_zone = null;

        if (isset($_GET['edit'])) {
            $edit_id = absint($_GET['edit']);
            $edit_zone = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Delivery Zones', 'pizza-ordering'); ?></h1>

            <div class="pizza-zones-container" style="display: flex; gap: 30px;">
                <!-- Add/Edit Form -->
                <div class="pizza-zone-form" style="flex: 0 0 300px;">
                    <h2><?php echo $edit_zone ? esc_html__('Edit Zone', 'pizza-ordering') : esc_html__('Add New Zone', 'pizza-ordering'); ?></h2>
                    
                    <form method="post">
                        <?php wp_nonce_field('pizza_delivery_zones_nonce'); ?>
                        <input type="hidden" name="pizza_zone_action" value="<?php echo $edit_zone ? 'edit' : 'add'; ?>">
                        <?php if ($edit_zone) : ?>
                            <input type="hidden" name="zone_id" value="<?php echo esc_attr($edit_zone->id); ?>">
                        <?php endif; ?>

                        <table class="form-table">
                            <tr>
                                <th><label for="zone_name"><?php esc_html_e('Zone Name', 'pizza-ordering'); ?></label></th>
                                <td>
                                    <input type="text" id="zone_name" name="zone_name" class="regular-text" required
                                           value="<?php echo $edit_zone ? esc_attr($edit_zone->zone_name) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="postcodes"><?php esc_html_e('Postcodes', 'pizza-ordering'); ?></label></th>
                                <td>
                                    <textarea id="postcodes" name="postcodes" class="regular-text" rows="3" required><?php echo $edit_zone ? esc_textarea($edit_zone->postcodes) : ''; ?></textarea>
                                    <p class="description"><?php esc_html_e('Comma-separated. Use * as wildcard (e.g., 12* matches all starting with 12)', 'pizza-ordering'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="delivery_fee"><?php esc_html_e('Delivery Fee', 'pizza-ordering'); ?></label></th>
                                <td>
                                    <input type="number" id="delivery_fee" name="delivery_fee" step="0.01" min="0" 
                                           value="<?php echo $edit_zone ? esc_attr($edit_zone->delivery_fee) : '0'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="min_order"><?php esc_html_e('Minimum Order', 'pizza-ordering'); ?></label></th>
                                <td>
                                    <input type="number" id="min_order" name="min_order" step="0.01" min="0" 
                                           value="<?php echo $edit_zone ? esc_attr($edit_zone->min_order) : '0'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="delivery_time"><?php esc_html_e('Delivery Time (min)', 'pizza-ordering'); ?></label></th>
                                <td>
                                    <input type="number" id="delivery_time" name="delivery_time" min="0" 
                                           value="<?php echo $edit_zone ? esc_attr($edit_zone->delivery_time) : '30'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="is_active"><?php esc_html_e('Active', 'pizza-ordering'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="is_active" name="is_active" value="1" 
                                           <?php echo (!$edit_zone || $edit_zone->is_active) ? 'checked' : ''; ?>>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button($edit_zone ? __('Update Zone', 'pizza-ordering') : __('Add Zone', 'pizza-ordering')); ?>
                        
                        <?php if ($edit_zone) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=pizza-delivery-zones')); ?>" class="button">
                                <?php esc_html_e('Cancel', 'pizza-ordering'); ?>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Zones List -->
                <div class="pizza-zones-list" style="flex: 1;">
                    <h2><?php esc_html_e('Existing Zones', 'pizza-ordering'); ?></h2>
                    
                    <?php if (empty($zones)) : ?>
                        <p><?php esc_html_e('No delivery zones configured yet.', 'pizza-ordering'); ?></p>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Zone Name', 'pizza-ordering'); ?></th>
                                    <th><?php esc_html_e('Postcodes', 'pizza-ordering'); ?></th>
                                    <th><?php esc_html_e('Fee', 'pizza-ordering'); ?></th>
                                    <th><?php esc_html_e('Min Order', 'pizza-ordering'); ?></th>
                                    <th><?php esc_html_e('Time', 'pizza-ordering'); ?></th>
                                    <th><?php esc_html_e('Status', 'pizza-ordering'); ?></th>
                                    <th><?php esc_html_e('Actions', 'pizza-ordering'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zones as $zone) : ?>
                                <tr>
                                    <td><?php echo esc_html($zone->zone_name); ?></td>
                                    <td><code><?php echo esc_html($zone->postcodes); ?></code></td>
                                    <td><?php echo wc_price($zone->delivery_fee); ?></td>
                                    <td><?php echo wc_price($zone->min_order); ?></td>
                                    <td><?php echo esc_html($zone->delivery_time); ?> min</td>
                                    <td>
                                        <?php if ($zone->is_active) : ?>
                                            <span class="dashicons dashicons-yes" style="color: green;"></span>
                                        <?php else : ?>
                                            <span class="dashicons dashicons-no" style="color: red;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=pizza-delivery-zones&edit=' . $zone->id)); ?>" class="button button-small">
                                            <?php esc_html_e('Edit', 'pizza-ordering'); ?>
                                        </a>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('pizza_delivery_zones_nonce'); ?>
                                            <input type="hidden" name="pizza_zone_action" value="delete">
                                            <input type="hidden" name="zone_id" value="<?php echo esc_attr($zone->id); ?>">
                                            <button type="submit" class="button button-small button-link-delete" 
                                                    onclick="return confirm('<?php esc_attr_e('Are you sure?', 'pizza-ordering'); ?>');">
                                                <?php esc_html_e('Delete', 'pizza-ordering'); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        $pizza_post_types = array('pizza_topping', 'pizza_size', 'pizza_base', 'pizza_sauce', 'pizza_side', 'pizza_drink', 'pizza_combo');
        
        // Only load on our pages or pizza post types
        $is_pizza_page = strpos($hook, 'pizza') !== false;
        $is_pizza_post_type = $screen && in_array($screen->post_type, $pizza_post_types);
        
        if (!$is_pizza_page && !$is_pizza_post_type) {
            return;
        }

        // Load media uploader for image upload functionality
        wp_enqueue_media();

        wp_enqueue_style(
            'pizza-admin-style',
            PIZZA_ORDERING_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            PIZZA_ORDERING_VERSION
        );

        wp_enqueue_script(
            'pizza-admin-script',
            PIZZA_ORDERING_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            PIZZA_ORDERING_VERSION,
            true
        );
    }

    /**
     * Activation notice
     */
    public function activation_notice() {
        if (!get_transient('pizza_ordering_activated')) {
            return;
        }

        delete_transient('pizza_ordering_activated');
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                printf(
                    /* translators: %s: link to settings */
                    esc_html__('Thank you for installing Pizza Ordering! Please %s to get started.', 'pizza-ordering'),
                    '<a href="' . esc_url(admin_url('admin.php?page=pizza-ordering')) . '">' . esc_html__('configure the plugin', 'pizza-ordering') . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}
