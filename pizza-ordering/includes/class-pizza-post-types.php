<?php
/**
 * Pizza Post Types
 *
 * Registers custom post types for toppings, pizza bases, and sizes.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Post Types Class
 */
class Pizza_Post_Types {

    /**
     * Single instance
     *
     * @var Pizza_Post_Types
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Post_Types
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
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Toppings
        register_post_type('pizza_topping', array(
            'labels' => array(
                'name'               => __('Toppings', 'pizza-ordering'),
                'singular_name'      => __('Topping', 'pizza-ordering'),
                'menu_name'          => __('Toppings', 'pizza-ordering'),
                'add_new'            => __('Add New', 'pizza-ordering'),
                'add_new_item'       => __('Add New Topping', 'pizza-ordering'),
                'edit_item'          => __('Edit Topping', 'pizza-ordering'),
                'new_item'           => __('New Topping', 'pizza-ordering'),
                'view_item'          => __('View Topping', 'pizza-ordering'),
                'search_items'       => __('Search Toppings', 'pizza-ordering'),
                'not_found'          => __('No toppings found', 'pizza-ordering'),
                'not_found_in_trash' => __('No toppings found in trash', 'pizza-ordering'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'pizza-ordering',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));

        // Pizza Sizes
        register_post_type('pizza_size', array(
            'labels' => array(
                'name'               => __('Pizza Sizes', 'pizza-ordering'),
                'singular_name'      => __('Pizza Size', 'pizza-ordering'),
                'menu_name'          => __('Sizes', 'pizza-ordering'),
                'add_new'            => __('Add New', 'pizza-ordering'),
                'add_new_item'       => __('Add New Size', 'pizza-ordering'),
                'edit_item'          => __('Edit Size', 'pizza-ordering'),
                'new_item'           => __('New Size', 'pizza-ordering'),
                'view_item'          => __('View Size', 'pizza-ordering'),
                'search_items'       => __('Search Sizes', 'pizza-ordering'),
                'not_found'          => __('No sizes found', 'pizza-ordering'),
                'not_found_in_trash' => __('No sizes found in trash', 'pizza-ordering'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'pizza-ordering',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));

        // Pizza Bases/Crusts
        register_post_type('pizza_base', array(
            'labels' => array(
                'name'               => __('Pizza Bases', 'pizza-ordering'),
                'singular_name'      => __('Pizza Base', 'pizza-ordering'),
                'menu_name'          => __('Bases', 'pizza-ordering'),
                'add_new'            => __('Add New', 'pizza-ordering'),
                'add_new_item'       => __('Add New Base', 'pizza-ordering'),
                'edit_item'          => __('Edit Base', 'pizza-ordering'),
                'new_item'           => __('New Base', 'pizza-ordering'),
                'view_item'          => __('View Base', 'pizza-ordering'),
                'search_items'       => __('Search Bases', 'pizza-ordering'),
                'not_found'          => __('No bases found', 'pizza-ordering'),
                'not_found_in_trash' => __('No bases found in trash', 'pizza-ordering'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'pizza-ordering',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));

        // Side Products (drinks, sides, desserts)
        register_post_type('pizza_side', array(
            'labels' => array(
                'name'               => __('Side Products', 'pizza-ordering'),
                'singular_name'      => __('Side Product', 'pizza-ordering'),
                'menu_name'          => __('Sides & Drinks', 'pizza-ordering'),
                'add_new'            => __('Add New', 'pizza-ordering'),
                'add_new_item'       => __('Add New Side Product', 'pizza-ordering'),
                'edit_item'          => __('Edit Side Product', 'pizza-ordering'),
                'new_item'           => __('New Side Product', 'pizza-ordering'),
                'view_item'          => __('View Side Product', 'pizza-ordering'),
                'search_items'       => __('Search Side Products', 'pizza-ordering'),
                'not_found'          => __('No side products found', 'pizza-ordering'),
                'not_found_in_trash' => __('No side products found in trash', 'pizza-ordering'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'pizza-ordering',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));

        // Combo Deals
        register_post_type('pizza_combo', array(
            'labels' => array(
                'name'               => __('Combo Deals', 'pizza-ordering'),
                'singular_name'      => __('Combo Deal', 'pizza-ordering'),
                'menu_name'          => __('Combo Deals', 'pizza-ordering'),
                'add_new'            => __('Add New', 'pizza-ordering'),
                'add_new_item'       => __('Add New Combo', 'pizza-ordering'),
                'edit_item'          => __('Edit Combo', 'pizza-ordering'),
                'new_item'           => __('New Combo', 'pizza-ordering'),
                'view_item'          => __('View Combo', 'pizza-ordering'),
                'search_items'       => __('Search Combos', 'pizza-ordering'),
                'not_found'          => __('No combos found', 'pizza-ordering'),
                'not_found_in_trash' => __('No combos found in trash', 'pizza-ordering'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'pizza-ordering',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail', 'editor'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));

        // Sauces
        register_post_type('pizza_sauce', array(
            'labels' => array(
                'name'               => __('Sauces', 'pizza-ordering'),
                'singular_name'      => __('Sauce', 'pizza-ordering'),
                'menu_name'          => __('Sauces', 'pizza-ordering'),
                'add_new'            => __('Add New', 'pizza-ordering'),
                'add_new_item'       => __('Add New Sauce', 'pizza-ordering'),
                'edit_item'          => __('Edit Sauce', 'pizza-ordering'),
                'new_item'           => __('New Sauce', 'pizza-ordering'),
                'view_item'          => __('View Sauce', 'pizza-ordering'),
                'search_items'       => __('Search Sauces', 'pizza-ordering'),
                'not_found'          => __('No sauces found', 'pizza-ordering'),
                'not_found_in_trash' => __('No sauces found in trash', 'pizza-ordering'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'pizza-ordering',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        ));
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Topping categories
        register_taxonomy('topping_category', 'pizza_topping', array(
            'labels' => array(
                'name'              => __('Topping Categories', 'pizza-ordering'),
                'singular_name'     => __('Topping Category', 'pizza-ordering'),
                'search_items'      => __('Search Categories', 'pizza-ordering'),
                'all_items'         => __('All Categories', 'pizza-ordering'),
                'parent_item'       => __('Parent Category', 'pizza-ordering'),
                'parent_item_colon' => __('Parent Category:', 'pizza-ordering'),
                'edit_item'         => __('Edit Category', 'pizza-ordering'),
                'update_item'       => __('Update Category', 'pizza-ordering'),
                'add_new_item'      => __('Add New Category', 'pizza-ordering'),
                'new_item_name'     => __('New Category Name', 'pizza-ordering'),
                'menu_name'         => __('Categories', 'pizza-ordering'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
        ));

        // Side product categories (drinks, sides, desserts)
        register_taxonomy('side_category', 'pizza_side', array(
            'labels' => array(
                'name'              => __('Side Categories', 'pizza-ordering'),
                'singular_name'     => __('Side Category', 'pizza-ordering'),
                'search_items'      => __('Search Categories', 'pizza-ordering'),
                'all_items'         => __('All Categories', 'pizza-ordering'),
                'edit_item'         => __('Edit Category', 'pizza-ordering'),
                'update_item'       => __('Update Category', 'pizza-ordering'),
                'add_new_item'      => __('Add New Category', 'pizza-ordering'),
                'new_item_name'     => __('New Category Name', 'pizza-ordering'),
                'menu_name'         => __('Categories', 'pizza-ordering'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Topping meta box
        add_meta_box(
            'pizza_topping_details',
            __('Topping Details', 'pizza-ordering'),
            array($this, 'render_topping_meta_box'),
            'pizza_topping',
            'normal',
            'high'
        );

        // Size meta box
        add_meta_box(
            'pizza_size_details',
            __('Size Details', 'pizza-ordering'),
            array($this, 'render_size_meta_box'),
            'pizza_size',
            'normal',
            'high'
        );

        // Base meta box
        add_meta_box(
            'pizza_base_details',
            __('Base Details', 'pizza-ordering'),
            array($this, 'render_base_meta_box'),
            'pizza_base',
            'normal',
            'high'
        );

        // Sauce meta box
        add_meta_box(
            'pizza_sauce_details',
            __('Sauce Details', 'pizza-ordering'),
            array($this, 'render_sauce_meta_box'),
            'pizza_sauce',
            'normal',
            'high'
        );

        // Side product meta box
        add_meta_box(
            'pizza_side_details',
            __('Side Product Details', 'pizza-ordering'),
            array($this, 'render_side_meta_box'),
            'pizza_side',
            'normal',
            'high'
        );

        // Combo deal meta box
        add_meta_box(
            'pizza_combo_details',
            __('Combo Deal Details', 'pizza-ordering'),
            array($this, 'render_combo_meta_box'),
            'pizza_combo',
            'normal',
            'high'
        );
    }

    /**
     * Render topping meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_topping_meta_box($post) {
        wp_nonce_field('pizza_topping_meta', 'pizza_topping_nonce');

        $price = get_post_meta($post->ID, '_topping_price', true);
        $price_medium = get_post_meta($post->ID, '_topping_price_medium', true);
        $price_large = get_post_meta($post->ID, '_topping_price_large', true);
        $price_family = get_post_meta($post->ID, '_topping_price_family', true);
        $is_premium = get_post_meta($post->ID, '_topping_is_premium', true);
        $allergens = get_post_meta($post->ID, '_topping_allergens', true);
        $sort_order = get_post_meta($post->ID, '_topping_sort_order', true);
        $image_id = get_post_meta($post->ID, '_topping_image_id', true);
        $emoji = get_post_meta($post->ID, '_topping_emoji', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
        ?>
        <table class="form-table">
            <tr>
                <th><label>Billede / Emoji</label></th>
                <td>
                    <div class="pizza-media-upload" style="margin-bottom: 15px;">
                        <div class="pizza-image-preview" style="margin-bottom: 10px;">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100px; height: auto; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="topping_image_id" id="topping_image_id" value="<?php echo esc_attr($image_id); ?>">
                        <button type="button" class="button pizza-upload-image" data-target="topping_image_id">
                            <?php echo $image_id ? 'Skift billede' : 'Upload billede'; ?>
                        </button>
                        <?php if ($image_id) : ?>
                            <button type="button" class="button pizza-remove-image" data-target="topping_image_id">Fjern billede</button>
                        <?php endif; ?>
                    </div>
                    <div class="pizza-emoji-select">
                        <label><strong>Eller vælg emoji:</strong></label><br>
                        <select name="topping_emoji" id="topping_emoji" style="font-size: 20px; padding: 5px 10px; min-width: 200px;">
                            <option value="">-- Ingen emoji --</option>
                            <option value="🧀" <?php selected($emoji, '🧀'); ?>>🧀 Ost</option>
                            <option value="🍖" <?php selected($emoji, '🍖'); ?>>🍖 Pepperoni/Kød</option>
                            <option value="🥓" <?php selected($emoji, '🥓'); ?>>🥓 Bacon/Skinke</option>
                            <option value="🍗" <?php selected($emoji, '🍗'); ?>>🍗 Kylling</option>
                            <option value="🍄" <?php selected($emoji, '🍄'); ?>>🍄 Champignon</option>
                            <option value="🧅" <?php selected($emoji, '🧅'); ?>>🧅 Løg</option>
                            <option value="🫑" <?php selected($emoji, '🫑'); ?>>🫑 Peberfrugt</option>
                            <option value="🫒" <?php selected($emoji, '🫒'); ?>>🫒 Oliven</option>
                            <option value="🍅" <?php selected($emoji, '🍅'); ?>>🍅 Tomat</option>
                            <option value="🍍" <?php selected($emoji, '🍍'); ?>>🍍 Ananas</option>
                            <option value="🌶️" <?php selected($emoji, '🌶️'); ?>>🌶️ Jalapeño/Chili</option>
                            <option value="🧄" <?php selected($emoji, '🧄'); ?>>🧄 Hvidløg</option>
                            <option value="🌿" <?php selected($emoji, '🌿'); ?>>🌿 Basilikum/Krydderurter</option>
                            <option value="🥬" <?php selected($emoji, '🥬'); ?>>🥬 Rucola/Salat</option>
                            <option value="🌽" <?php selected($emoji, '🌽'); ?>>🌽 Majs</option>
                            <option value="🥚" <?php selected($emoji, '🥚'); ?>>🥚 Æg</option>
                            <option value="🦐" <?php selected($emoji, '🦐'); ?>>🦐 Rejer</option>
                            <option value="🐟" <?php selected($emoji, '🐟'); ?>>🐟 Fisk/Tun</option>
                            <option value="🥩" <?php selected($emoji, '🥩'); ?>>🥩 Bøf/Oksekød</option>
                            <option value="🌰" <?php selected($emoji, '🌰'); ?>>🌰 Nødder</option>
                            <option value="🍕" <?php selected($emoji, '🍕'); ?>>🍕 Pizza (generisk)</option>
                        </select>
                        <p class="description">Billede prioriteres over emoji hvis begge er valgt</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="topping_price">Pris (Small)</label></th>
                <td>
                    <input type="number" id="topping_price" name="topping_price" 
                           value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description">Pris for lille pizza</p>
                </td>
            </tr>
            <tr>
                <th><label for="topping_price_medium">Pris (Medium)</label></th>
                <td>
                    <input type="number" id="topping_price_medium" name="topping_price_medium" 
                           value="<?php echo esc_attr($price_medium); ?>" step="0.01" min="0" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="topping_price_large">Pris (Large)</label></th>
                <td>
                    <input type="number" id="topping_price_large" name="topping_price_large" 
                           value="<?php echo esc_attr($price_large); ?>" step="0.01" min="0" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="topping_price_family">Pris (Family)</label></th>
                <td>
                    <input type="number" id="topping_price_family" name="topping_price_family" 
                           value="<?php echo esc_attr($price_family); ?>" step="0.01" min="0" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="topping_is_premium">Premium Topping</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="topping_is_premium" name="topping_is_premium" 
                               value="1" <?php checked($is_premium, '1'); ?>>
                        Dette er en premium topping
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="topping_allergens">Allergener</label></th>
                <td>
                    <input type="text" id="topping_allergens" name="topping_allergens" 
                           value="<?php echo esc_attr($allergens); ?>" class="regular-text">
                    <p class="description">Kommasepareret liste af allergener</p>
                </td>
            </tr>
            <tr>
                <th><label for="topping_sort_order">Sorteringsrækkefølge</label></th>
                <td>
                    <input type="number" id="topping_sort_order" name="topping_sort_order" 
                           value="<?php echo esc_attr($sort_order ?: 0); ?>" min="0" class="small-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render size meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_size_meta_box($post) {
        wp_nonce_field('pizza_size_meta', 'pizza_size_nonce');

        $base_price = get_post_meta($post->ID, '_size_base_price', true);
        $diameter = get_post_meta($post->ID, '_size_diameter', true);
        $slices = get_post_meta($post->ID, '_size_slices', true);
        $serves = get_post_meta($post->ID, '_size_serves', true);
        $sort_order = get_post_meta($post->ID, '_size_sort_order', true);
        $is_default = get_post_meta($post->ID, '_size_is_default', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="size_base_price"><?php esc_html_e('Base Price', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="size_base_price" name="size_base_price" 
                           value="<?php echo esc_attr($base_price); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description"><?php esc_html_e('Base price for this size (before toppings)', 'pizza-ordering'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="size_diameter"><?php esc_html_e('Diameter (cm)', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="size_diameter" name="size_diameter" 
                           value="<?php echo esc_attr($diameter); ?>" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="size_slices"><?php esc_html_e('Number of Slices', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="size_slices" name="size_slices" 
                           value="<?php echo esc_attr($slices); ?>" min="1" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="size_serves"><?php esc_html_e('Serves (persons)', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="text" id="size_serves" name="size_serves" 
                           value="<?php echo esc_attr($serves); ?>" class="small-text">
                    <p class="description"><?php esc_html_e('E.g., "1-2" or "3-4"', 'pizza-ordering'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="size_sort_order"><?php esc_html_e('Sort Order', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="size_sort_order" name="size_sort_order" 
                           value="<?php echo esc_attr($sort_order ?: 0); ?>" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="size_is_default"><?php esc_html_e('Default Size', 'pizza-ordering'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="size_is_default" name="size_is_default" 
                               value="1" <?php checked($is_default, '1'); ?>>
                        <?php esc_html_e('Set as default size', 'pizza-ordering'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render base meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_base_meta_box($post) {
        wp_nonce_field('pizza_base_meta', 'pizza_base_nonce');

        $extra_price = get_post_meta($post->ID, '_base_extra_price', true);
        $description = get_post_meta($post->ID, '_base_description', true);
        $is_default = get_post_meta($post->ID, '_base_is_default', true);
        $sort_order = get_post_meta($post->ID, '_base_sort_order', true);
        $image_id = get_post_meta($post->ID, '_base_image_id', true);
        $emoji = get_post_meta($post->ID, '_base_emoji', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
        ?>
        <table class="form-table">
            <tr>
                <th><label>Billede / Emoji</label></th>
                <td>
                    <div class="pizza-media-upload" style="margin-bottom: 15px;">
                        <div class="pizza-image-preview" style="margin-bottom: 10px;">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100px; height: auto; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="base_image_id" id="base_image_id" value="<?php echo esc_attr($image_id); ?>">
                        <button type="button" class="button pizza-upload-image" data-target="base_image_id">
                            <?php echo $image_id ? 'Skift billede' : 'Upload billede'; ?>
                        </button>
                        <?php if ($image_id) : ?>
                            <button type="button" class="button pizza-remove-image" data-target="base_image_id">Fjern billede</button>
                        <?php endif; ?>
                    </div>
                    <div class="pizza-emoji-select">
                        <label><strong>Eller vælg emoji:</strong></label><br>
                        <select name="base_emoji" id="base_emoji" style="font-size: 20px; padding: 5px 10px; min-width: 200px;">
                            <option value="">-- Ingen emoji --</option>
                            <option value="🍕" <?php selected($emoji, '🍕'); ?>>🍕 Klassisk</option>
                            <option value="🥖" <?php selected($emoji, '🥖'); ?>>🥖 Brød/Tynd</option>
                            <option value="🫓" <?php selected($emoji, '🫓'); ?>>🫓 Fladbrød</option>
                            <option value="🥯" <?php selected($emoji, '🥯'); ?>>🥯 Tyk bund</option>
                            <option value="🌾" <?php selected($emoji, '🌾'); ?>>🌾 Fuldkorn</option>
                            <option value="🥗" <?php selected($emoji, '🥗'); ?>>🥗 Glutenfri</option>
                            <option value="🧀" <?php selected($emoji, '🧀'); ?>>🧀 Ostekant</option>
                            <option value="🌿" <?php selected($emoji, '🌿'); ?>>🌿 Urte-infused</option>
                        </select>
                        <p class="description">Billede prioriteres over emoji hvis begge er valgt</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="base_extra_price">Ekstra pris</label></th>
                <td>
                    <input type="number" id="base_extra_price" name="base_extra_price" 
                           value="<?php echo esc_attr($extra_price); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description">Merpris for denne bund (0 for standard)</p>
                </td>
            </tr>
            <tr>
                <th><label for="base_description">Beskrivelse</label></th>
                <td>
                    <textarea id="base_description" name="base_description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="base_is_default">Standard bund</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="base_is_default" name="base_is_default" 
                               value="1" <?php checked($is_default, '1'); ?>>
                        Sæt som standard bund
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="base_sort_order">Sorteringsrækkefølge</label></th>
                <td>
                    <input type="number" id="base_sort_order" name="base_sort_order" 
                           value="<?php echo esc_attr($sort_order ?: 0); ?>" min="0" class="small-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render sauce meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_sauce_meta_box($post) {
        wp_nonce_field('pizza_sauce_meta', 'pizza_sauce_nonce');

        $extra_price = get_post_meta($post->ID, '_sauce_extra_price', true);
        $is_default = get_post_meta($post->ID, '_sauce_is_default', true);
        $sort_order = get_post_meta($post->ID, '_sauce_sort_order', true);
        $image_id = get_post_meta($post->ID, '_sauce_image_id', true);
        $emoji = get_post_meta($post->ID, '_sauce_emoji', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
        ?>
        <table class="form-table">
            <tr>
                <th><label>Billede / Emoji</label></th>
                <td>
                    <div class="pizza-media-upload" style="margin-bottom: 15px;">
                        <div class="pizza-image-preview" style="margin-bottom: 10px;">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100px; height: auto; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="sauce_image_id" id="sauce_image_id" value="<?php echo esc_attr($image_id); ?>">
                        <button type="button" class="button pizza-upload-image" data-target="sauce_image_id">
                            <?php echo $image_id ? 'Skift billede' : 'Upload billede'; ?>
                        </button>
                        <?php if ($image_id) : ?>
                            <button type="button" class="button pizza-remove-image" data-target="sauce_image_id">Fjern billede</button>
                        <?php endif; ?>
                    </div>
                    <div class="pizza-emoji-select">
                        <label><strong>Eller vælg emoji:</strong></label><br>
                        <select name="sauce_emoji" id="sauce_emoji" style="font-size: 20px; padding: 5px 10px; min-width: 200px;">
                            <option value="">-- Ingen emoji --</option>
                            <option value="🍅" <?php selected($emoji, '🍅'); ?>>🍅 Tomat</option>
                            <option value="🧄" <?php selected($emoji, '🧄'); ?>>🧄 Hvidløg</option>
                            <option value="🌿" <?php selected($emoji, '🌿'); ?>>🌿 Pesto/Basilikum</option>
                            <option value="🍖" <?php selected($emoji, '🍖'); ?>>🍖 BBQ</option>
                            <option value="🥛" <?php selected($emoji, '🥛'); ?>>🥛 Creme fraiche/Hvid</option>
                            <option value="🌶️" <?php selected($emoji, '🌶️'); ?>>🌶️ Chili/Stærk</option>
                            <option value="🫒" <?php selected($emoji, '🫒'); ?>>🫒 Olivenolie</option>
                            <option value="🧈" <?php selected($emoji, '🧈'); ?>>🧈 Smør/Hvidløgssmør</option>
                            <option value="🥜" <?php selected($emoji, '🥜'); ?>>🥜 Satay/Peanut</option>
                            <option value="🍯" <?php selected($emoji, '🍯'); ?>>🍯 Honning</option>
                            <option value="🥫" <?php selected($emoji, '🥫'); ?>>🥫 Sauce (generisk)</option>
                        </select>
                        <p class="description">Billede prioriteres over emoji hvis begge er valgt</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="sauce_extra_price">Ekstra pris</label></th>
                <td>
                    <input type="number" id="sauce_extra_price" name="sauce_extra_price" 
                           value="<?php echo esc_attr($extra_price); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description">Merpris for denne sauce (0 for standard)</p>
                </td>
            </tr>
            <tr>
                <th><label for="sauce_is_default">Standard sauce</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="sauce_is_default" name="sauce_is_default" 
                               value="1" <?php checked($is_default, '1'); ?>>
                        Sæt som standard sauce
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="sauce_sort_order">Sorteringsrækkefølge</label></th>
                <td>
                    <input type="number" id="sauce_sort_order" name="sauce_sort_order" 
                           value="<?php echo esc_attr($sort_order ?: 0); ?>" min="0" class="small-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render side product meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_side_meta_box($post) {
        wp_nonce_field('pizza_side_meta', 'pizza_side_nonce');

        $price = get_post_meta($post->ID, '_side_price', true);
        $description = get_post_meta($post->ID, '_side_description', true);
        $calories = get_post_meta($post->ID, '_side_calories', true);
        $allergens = get_post_meta($post->ID, '_side_allergens', true);
        $is_popular = get_post_meta($post->ID, '_side_is_popular', true);
        $sort_order = get_post_meta($post->ID, '_side_sort_order', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="side_price"><?php esc_html_e('Price', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="side_price" name="side_price" 
                           value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="side_description"><?php esc_html_e('Description', 'pizza-ordering'); ?></label></th>
                <td>
                    <textarea id="side_description" name="side_description" rows="2" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="side_calories"><?php esc_html_e('Calories (kcal)', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="side_calories" name="side_calories" 
                           value="<?php echo esc_attr($calories); ?>" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="side_allergens"><?php esc_html_e('Allergens', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="text" id="side_allergens" name="side_allergens" 
                           value="<?php echo esc_attr($allergens); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Comma-separated: gluten, dairy, nuts, eggs, soy, fish, shellfish', 'pizza-ordering'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="side_is_popular"><?php esc_html_e('Popular', 'pizza-ordering'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="side_is_popular" name="side_is_popular" 
                               value="1" <?php checked($is_popular, '1'); ?>>
                        <?php esc_html_e('Mark as popular item', 'pizza-ordering'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="side_sort_order"><?php esc_html_e('Sort Order', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="side_sort_order" name="side_sort_order" 
                           value="<?php echo esc_attr($sort_order ?: 0); ?>" min="0" class="small-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render combo deal meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_combo_meta_box($post) {
        wp_nonce_field('pizza_combo_meta', 'pizza_combo_nonce');

        $regular_price = get_post_meta($post->ID, '_combo_regular_price', true);
        $sale_price = get_post_meta($post->ID, '_combo_sale_price', true);
        $included_pizzas = get_post_meta($post->ID, '_combo_pizzas', true);
        $included_sides = get_post_meta($post->ID, '_combo_sides', true);
        $included_drinks = get_post_meta($post->ID, '_combo_drinks', true);
        $is_active = get_post_meta($post->ID, '_combo_is_active', true);
        $valid_from = get_post_meta($post->ID, '_combo_valid_from', true);
        $valid_until = get_post_meta($post->ID, '_combo_valid_until', true);

        // Get available pizzas (WC products of type pizza)
        $pizzas = wc_get_products(array(
            'type'   => 'pizza',
            'status' => 'publish',
            'limit'  => -1,
        ));

        // Get available sides
        $sides = get_posts(array(
            'post_type'      => 'pizza_side',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ));
        ?>
        <table class="form-table">
            <tr>
                <th><label for="combo_regular_price"><?php esc_html_e('Regular Price', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="combo_regular_price" name="combo_regular_price" 
                           value="<?php echo esc_attr($regular_price); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description"><?php esc_html_e('Total value if bought separately', 'pizza-ordering'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="combo_sale_price"><?php esc_html_e('Combo Price', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="number" id="combo_sale_price" name="combo_sale_price" 
                           value="<?php echo esc_attr($sale_price); ?>" step="0.01" min="0" class="regular-text" required>
                    <p class="description"><?php esc_html_e('Discounted combo price', 'pizza-ordering'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Included Items', 'pizza-ordering'); ?></label></th>
                <td>
                    <p><strong><?php esc_html_e('Number of pizzas:', 'pizza-ordering'); ?></strong></p>
                    <input type="number" name="combo_pizzas" value="<?php echo esc_attr($included_pizzas ?: 1); ?>" min="0" class="small-text">
                    <span class="description"><?php esc_html_e('Customer can choose any pizza', 'pizza-ordering'); ?></span>
                    
                    <p style="margin-top: 15px;"><strong><?php esc_html_e('Number of sides:', 'pizza-ordering'); ?></strong></p>
                    <input type="number" name="combo_sides" value="<?php echo esc_attr($included_sides ?: 0); ?>" min="0" class="small-text">
                    
                    <p style="margin-top: 15px;"><strong><?php esc_html_e('Number of drinks:', 'pizza-ordering'); ?></strong></p>
                    <input type="number" name="combo_drinks" value="<?php echo esc_attr($included_drinks ?: 0); ?>" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="combo_valid_from"><?php esc_html_e('Valid Period', 'pizza-ordering'); ?></label></th>
                <td>
                    <input type="date" id="combo_valid_from" name="combo_valid_from" 
                           value="<?php echo esc_attr($valid_from); ?>">
                    <?php esc_html_e('to', 'pizza-ordering'); ?>
                    <input type="date" id="combo_valid_until" name="combo_valid_until" 
                           value="<?php echo esc_attr($valid_until); ?>">
                    <p class="description"><?php esc_html_e('Leave empty for always valid', 'pizza-ordering'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="combo_is_active"><?php esc_html_e('Active', 'pizza-ordering'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="combo_is_active" name="combo_is_active" 
                               value="1" <?php checked($is_active, '1'); ?>>
                        <?php esc_html_e('Combo deal is active', 'pizza-ordering'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta boxes
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     */
    public function save_meta_boxes($post_id, $post) {
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save topping meta
        if ($post->post_type === 'pizza_topping') {
            if (!isset($_POST['pizza_topping_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_topping_nonce'])), 'pizza_topping_meta')) {
                return;
            }

            $fields = array(
                'topping_price' => '_topping_price',
                'topping_price_medium' => '_topping_price_medium',
                'topping_price_large' => '_topping_price_large',
                'topping_price_family' => '_topping_price_family',
                'topping_allergens' => '_topping_allergens',
                'topping_sort_order' => '_topping_sort_order',
                'topping_image_id' => '_topping_image_id',
                'topping_emoji' => '_topping_emoji',
            );

            foreach ($fields as $field => $meta_key) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$field])));
                }
            }

            $is_premium = isset($_POST['topping_is_premium']) ? '1' : '0';
            update_post_meta($post_id, '_topping_is_premium', $is_premium);
        }

        // Save size meta
        if ($post->post_type === 'pizza_size') {
            if (!isset($_POST['pizza_size_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_size_nonce'])), 'pizza_size_meta')) {
                return;
            }

            $fields = array(
                'size_base_price' => '_size_base_price',
                'size_diameter' => '_size_diameter',
                'size_slices' => '_size_slices',
                'size_serves' => '_size_serves',
                'size_sort_order' => '_size_sort_order',
            );

            foreach ($fields as $field => $meta_key) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$field])));
                }
            }

            $is_default = isset($_POST['size_is_default']) ? '1' : '0';
            update_post_meta($post_id, '_size_is_default', $is_default);
        }

        // Save base meta
        if ($post->post_type === 'pizza_base') {
            if (!isset($_POST['pizza_base_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_base_nonce'])), 'pizza_base_meta')) {
                return;
            }

            if (isset($_POST['base_extra_price'])) {
                update_post_meta($post_id, '_base_extra_price', sanitize_text_field(wp_unslash($_POST['base_extra_price'])));
            }
            if (isset($_POST['base_description'])) {
                update_post_meta($post_id, '_base_description', sanitize_textarea_field(wp_unslash($_POST['base_description'])));
            }
            if (isset($_POST['base_sort_order'])) {
                update_post_meta($post_id, '_base_sort_order', sanitize_text_field(wp_unslash($_POST['base_sort_order'])));
            }
            if (isset($_POST['base_image_id'])) {
                update_post_meta($post_id, '_base_image_id', sanitize_text_field(wp_unslash($_POST['base_image_id'])));
            }
            if (isset($_POST['base_emoji'])) {
                update_post_meta($post_id, '_base_emoji', sanitize_text_field(wp_unslash($_POST['base_emoji'])));
            }

            $is_default = isset($_POST['base_is_default']) ? '1' : '0';
            update_post_meta($post_id, '_base_is_default', $is_default);
        }

        // Save sauce meta
        if ($post->post_type === 'pizza_sauce') {
            if (!isset($_POST['pizza_sauce_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_sauce_nonce'])), 'pizza_sauce_meta')) {
                return;
            }

            if (isset($_POST['sauce_extra_price'])) {
                update_post_meta($post_id, '_sauce_extra_price', sanitize_text_field(wp_unslash($_POST['sauce_extra_price'])));
            }
            if (isset($_POST['sauce_sort_order'])) {
                update_post_meta($post_id, '_sauce_sort_order', sanitize_text_field(wp_unslash($_POST['sauce_sort_order'])));
            }
            if (isset($_POST['sauce_image_id'])) {
                update_post_meta($post_id, '_sauce_image_id', sanitize_text_field(wp_unslash($_POST['sauce_image_id'])));
            }
            if (isset($_POST['sauce_emoji'])) {
                update_post_meta($post_id, '_sauce_emoji', sanitize_text_field(wp_unslash($_POST['sauce_emoji'])));
            }

            $is_default = isset($_POST['sauce_is_default']) ? '1' : '0';
            update_post_meta($post_id, '_sauce_is_default', $is_default);
        }

        // Save side product meta
        if ($post->post_type === 'pizza_side') {
            if (!isset($_POST['pizza_side_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_side_nonce'])), 'pizza_side_meta')) {
                return;
            }

            $fields = array(
                'side_price'       => '_side_price',
                'side_description' => '_side_description',
                'side_calories'    => '_side_calories',
                'side_allergens'   => '_side_allergens',
                'side_sort_order'  => '_side_sort_order',
            );

            foreach ($fields as $field => $meta_key) {
                if (isset($_POST[$field])) {
                    $value = ($field === 'side_description') 
                        ? sanitize_textarea_field(wp_unslash($_POST[$field]))
                        : sanitize_text_field(wp_unslash($_POST[$field]));
                    update_post_meta($post_id, $meta_key, $value);
                }
            }

            $is_popular = isset($_POST['side_is_popular']) ? '1' : '0';
            update_post_meta($post_id, '_side_is_popular', $is_popular);
        }

        // Save combo deal meta
        if ($post->post_type === 'pizza_combo') {
            if (!isset($_POST['pizza_combo_nonce']) || 
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pizza_combo_nonce'])), 'pizza_combo_meta')) {
                return;
            }

            $fields = array(
                'combo_regular_price' => '_combo_regular_price',
                'combo_sale_price'    => '_combo_sale_price',
                'combo_pizzas'        => '_combo_pizzas',
                'combo_sides'         => '_combo_sides',
                'combo_drinks'        => '_combo_drinks',
                'combo_valid_from'    => '_combo_valid_from',
                'combo_valid_until'   => '_combo_valid_until',
            );

            foreach ($fields as $field => $meta_key) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$field])));
                }
            }

            $is_active = isset($_POST['combo_is_active']) ? '1' : '0';
            update_post_meta($post_id, '_combo_is_active', $is_active);
        }
    }

    /**
     * Get all toppings
     *
     * @param array $args Query arguments
     * @return array
     */
    public static function get_toppings($args = array()) {
        $defaults = array(
            'post_type'      => 'pizza_topping',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_topping_sort_order',
            'order'          => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        $toppings = array();

        foreach ($posts as $post) {
            // Get image - prioritize custom image_id over post thumbnail
            $image_id = get_post_meta($post->ID, '_topping_image_id', true);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : get_the_post_thumbnail_url($post->ID, 'thumbnail');
            $emoji = get_post_meta($post->ID, '_topping_emoji', true);
            
            $toppings[] = array(
                'id'           => $post->ID,
                'name'         => $post->post_title,
                'price'        => floatval(get_post_meta($post->ID, '_topping_price', true)),
                'price_medium' => floatval(get_post_meta($post->ID, '_topping_price_medium', true)),
                'price_large'  => floatval(get_post_meta($post->ID, '_topping_price_large', true)),
                'price_family' => floatval(get_post_meta($post->ID, '_topping_price_family', true)),
                'is_premium'   => get_post_meta($post->ID, '_topping_is_premium', true) === '1',
                'allergens'    => get_post_meta($post->ID, '_topping_allergens', true),
                'image'        => $image_url,
                'emoji'        => $emoji,
                'categories'   => wp_get_post_terms($post->ID, 'topping_category', array('fields' => 'names')),
            );
        }

        return $toppings;
    }

    /**
     * Get all sizes
     *
     * @return array
     */
    public static function get_sizes() {
        $posts = get_posts(array(
            'post_type'      => 'pizza_size',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_size_sort_order',
            'order'          => 'ASC',
        ));

        $sizes = array();

        foreach ($posts as $post) {
            $sizes[] = array(
                'id'         => $post->ID,
                'name'       => $post->post_title,
                'base_price' => floatval(get_post_meta($post->ID, '_size_base_price', true)),
                'diameter'   => get_post_meta($post->ID, '_size_diameter', true),
                'slices'     => get_post_meta($post->ID, '_size_slices', true),
                'serves'     => get_post_meta($post->ID, '_size_serves', true),
                'is_default' => get_post_meta($post->ID, '_size_is_default', true) === '1',
            );
        }

        return $sizes;
    }

    /**
     * Get all bases
     *
     * @return array
     */
    public static function get_bases() {
        $posts = get_posts(array(
            'post_type'      => 'pizza_base',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_base_sort_order',
            'order'          => 'ASC',
        ));

        $bases = array();

        foreach ($posts as $post) {
            $image_id = get_post_meta($post->ID, '_base_image_id', true);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
            $emoji = get_post_meta($post->ID, '_base_emoji', true);
            
            $bases[] = array(
                'id'          => $post->ID,
                'name'        => $post->post_title,
                'extra_price' => floatval(get_post_meta($post->ID, '_base_extra_price', true)),
                'description' => get_post_meta($post->ID, '_base_description', true),
                'is_default'  => get_post_meta($post->ID, '_base_is_default', true) === '1',
                'image'       => $image_url,
                'emoji'       => $emoji,
            );
        }

        return $bases;
    }

    /**
     * Get all sauces
     *
     * @return array
     */
    public static function get_sauces() {
        $posts = get_posts(array(
            'post_type'      => 'pizza_sauce',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_sauce_sort_order',
            'order'          => 'ASC',
        ));

        $sauces = array();

        foreach ($posts as $post) {
            $image_id = get_post_meta($post->ID, '_sauce_image_id', true);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
            $emoji = get_post_meta($post->ID, '_sauce_emoji', true);
            
            $sauces[] = array(
                'id'          => $post->ID,
                'name'        => $post->post_title,
                'extra_price' => floatval(get_post_meta($post->ID, '_sauce_extra_price', true)),
                'is_default'  => get_post_meta($post->ID, '_sauce_is_default', true) === '1',
                'image'       => $image_url,
                'emoji'       => $emoji,
            );
        }

        return $sauces;
    }

    /**
     * Get all side products
     *
     * @param array $args Query arguments
     * @return array
     */
    public static function get_sides($args = array()) {
        $defaults = array(
            'post_type'      => 'pizza_side',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_side_sort_order',
            'order'          => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        $sides = array();

        foreach ($posts as $post) {
            $sides[] = array(
                'id'          => $post->ID,
                'name'        => $post->post_title,
                'price'       => floatval(get_post_meta($post->ID, '_side_price', true)),
                'description' => get_post_meta($post->ID, '_side_description', true),
                'calories'    => get_post_meta($post->ID, '_side_calories', true),
                'allergens'   => get_post_meta($post->ID, '_side_allergens', true),
                'is_popular'  => get_post_meta($post->ID, '_side_is_popular', true) === '1',
                'image'       => get_the_post_thumbnail_url($post->ID, 'medium'),
                'categories'  => wp_get_post_terms($post->ID, 'side_category', array('fields' => 'names')),
            );
        }

        return $sides;
    }

    /**
     * Get all active combo deals
     *
     * @return array
     */
    public static function get_combos() {
        $today = date('Y-m-d');

        $posts = get_posts(array(
            'post_type'      => 'pizza_combo',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_combo_is_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        ));

        $combos = array();

        foreach ($posts as $post) {
            $valid_from = get_post_meta($post->ID, '_combo_valid_from', true);
            $valid_until = get_post_meta($post->ID, '_combo_valid_until', true);

            // Check validity period
            if (!empty($valid_from) && $today < $valid_from) {
                continue;
            }
            if (!empty($valid_until) && $today > $valid_until) {
                continue;
            }

            $regular_price = floatval(get_post_meta($post->ID, '_combo_regular_price', true));
            $sale_price = floatval(get_post_meta($post->ID, '_combo_sale_price', true));
            $savings = $regular_price - $sale_price;

            $combos[] = array(
                'id'            => $post->ID,
                'name'          => $post->post_title,
                'description'   => $post->post_content,
                'regular_price' => $regular_price,
                'sale_price'    => $sale_price,
                'savings'       => $savings,
                'savings_pct'   => $regular_price > 0 ? round(($savings / $regular_price) * 100) : 0,
                'pizzas'        => absint(get_post_meta($post->ID, '_combo_pizzas', true)),
                'sides'         => absint(get_post_meta($post->ID, '_combo_sides', true)),
                'drinks'        => absint(get_post_meta($post->ID, '_combo_drinks', true)),
                'image'         => get_the_post_thumbnail_url($post->ID, 'large'),
                'valid_from'    => $valid_from,
                'valid_until'   => $valid_until,
            );
        }

        return $combos;
    }

    /**
     * Get allergen icons mapping
     *
     * @return array
     */
    public static function get_allergen_icons() {
        return array(
            'gluten'    => '🌾',
            'dairy'     => '🥛',
            'nuts'      => '🥜',
            'eggs'      => '🥚',
            'soy'       => '🫘',
            'fish'      => '🐟',
            'shellfish' => '🦐',
            'celery'    => '🥬',
            'mustard'   => '🟡',
            'sesame'    => '⚪',
            'sulfites'  => '🍷',
            'lupin'     => '🌸',
            'molluscs'  => '🐚',
        );
    }

    /**
     * Format allergens with icons
     *
     * @param string $allergens Comma-separated allergen list
     * @return string
     */
    public static function format_allergens($allergens) {
        if (empty($allergens)) {
            return '';
        }

        $icons = self::get_allergen_icons();
        $allergen_list = array_map('trim', explode(',', strtolower($allergens)));
        $formatted = array();

        foreach ($allergen_list as $allergen) {
            $icon = isset($icons[$allergen]) ? $icons[$allergen] : '⚠️';
            $formatted[] = $icon . ' ' . ucfirst($allergen);
        }

        return implode(', ', $formatted);
    }
}
