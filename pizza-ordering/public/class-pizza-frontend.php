<?php
/**
 * Pizza Frontend
 *
 * Handles frontend display and pizza builder.
 *
 * @package Pizza_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pizza Frontend Class
 */
class Pizza_Frontend {

    /**
     * Single instance
     *
     * @var Pizza_Frontend
     */
    private static $instance = null;

    /**
     * Get single instance
     *
     * @return Pizza_Frontend
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
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Replace add to cart button for pizza products
        add_action('woocommerce_before_single_product', array($this, 'setup_pizza_product'));
        
        // Pizza builder shortcode
        add_shortcode('pizza_builder', array($this, 'pizza_builder_shortcode'));
        
        // Pizza menu shortcode
        add_shortcode('pizza_menu', array($this, 'pizza_menu_shortcode'));
        
        // Combo deals shortcode
        add_shortcode('pizza_combos', array($this, 'pizza_combos_shortcode'));
        
        // Sides menu shortcode
        add_shortcode('pizza_sides', array($this, 'pizza_sides_shortcode'));
        
        // Override single product template for pizza
        add_filter('woocommerce_locate_template', array($this, 'locate_template'), 10, 3);
        
        // Add pizza builder modal
        add_action('wp_footer', array($this, 'render_pizza_builder_modal'));
        
        // Upselling on cart page
        add_action('woocommerce_after_cart_table', array($this, 'render_upsell_section'));
        
        // Upselling popup before checkout
        add_action('woocommerce_before_checkout_form', array($this, 'render_checkout_upsell'));
        
        // Add sides to cart via AJAX
        add_action('wp_ajax_pizza_add_side_to_cart', array($this, 'ajax_add_side_to_cart'));
        add_action('wp_ajax_nopriv_pizza_add_side_to_cart', array($this, 'ajax_add_side_to_cart'));
        
        // Add combo to cart via AJAX
        add_action('wp_ajax_pizza_add_combo_to_cart', array($this, 'ajax_add_combo_to_cart'));
        add_action('wp_ajax_nopriv_pizza_add_combo_to_cart', array($this, 'ajax_add_combo_to_cart'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
            // Check if we're on a page with pizza shortcode
            global $post;
            if (!$post || (strpos($post->post_content, '[pizza_') === false)) {
                return;
            }
        }

        wp_enqueue_style(
            'pizza-builder-style',
            PIZZA_ORDERING_PLUGIN_URL . 'public/css/pizza-builder.css',
            array(),
            PIZZA_ORDERING_VERSION
        );

        wp_enqueue_script(
            'pizza-builder-script',
            PIZZA_ORDERING_PLUGIN_URL . 'public/js/pizza-builder.js',
            array('jquery'),
            PIZZA_ORDERING_VERSION,
            true
        );

        wp_localize_script('pizza-builder-script', 'pizzaBuilder', array(
            'ajax_url'     => admin_url('admin-ajax.php'),
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('pizza_builder_nonce'),
            'currency'     => get_woocommerce_currency_symbol(),
            'cartUrl'      => wc_get_cart_url(),
            'checkoutUrl'  => wc_get_checkout_url(),
            'i18n'         => array(
                'addToCart'    => 'Læg i kurv',
                'adding'       => 'Tilføjer...',
                'added'        => 'Tilføjet!',
                'viewCart'     => 'Se kurv',
                'checkout'     => 'Til kassen',
                'selectSize'   => 'Vælg venligst en størrelse',
                'included'     => 'Inkluderet',
                'removed'      => 'Fjernet',
                'error'        => 'Der opstod en fejl',
                'loading'      => 'Indlæser...',
            ),
        ));
    }

    /**
     * Setup pizza product page
     */
    public function setup_pizza_product() {
        global $product;
        
        if (!$product || $product->get_type() !== 'pizza') {
            return;
        }

        // Remove ALL default WooCommerce single product elements for pizza
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
        
        // Remove product image gallery (we show our own)
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
        
        // Add pizza builder as the ONLY content
        add_action('woocommerce_before_single_product_summary', array($this, 'render_pizza_builder'), 5);
    }

    /**
     * Render pizza builder on product page
     */
    public function render_pizza_builder() {
        global $product;
        
        if (!$product || $product->get_type() !== 'pizza') {
            return;
        }

        $this->render_pizza_builder_form($product);
    }

    /**
     * Render pizza builder form
     *
     * @param WC_Product $product Product object
     */
    private function render_pizza_builder_form($product) {
        $product_id = $product->get_id();
        $sizes = Pizza_Post_Types::get_sizes();
        $bases = Pizza_Post_Types::get_bases();
        $sauces = Pizza_Post_Types::get_sauces();
        $toppings = Pizza_Post_Types::get_toppings();
        $sides = Pizza_Post_Types::get_sides();
        $combos = Pizza_Post_Types::get_combos();
        
        $is_preset = get_post_meta($product_id, '_pizza_is_preset', true) === 'yes';
        $allow_customization = get_post_meta($product_id, '_pizza_allow_customization', true) !== 'no';
        $free_toppings = absint(get_post_meta($product_id, '_pizza_free_toppings', true));
        $max_toppings = absint(get_post_meta($product_id, '_pizza_max_toppings', true));
        $default_toppings = get_post_meta($product_id, '_pizza_default_toppings', true) ?: array();
        if (!is_array($default_toppings)) {
            $default_toppings = array();
        }
        $description = get_post_meta($product_id, '_pizza_description', true);
        $product_image = wp_get_attachment_url($product->get_image_id());
        $product_categories = wc_get_product_category_list($product_id);

        // Get default selections
        $default_size = null;
        $default_size_price = 0;
        $default_base = null;
        $default_sauce = null;
        
        foreach ($sizes as $size) {
            if ($size['is_default']) {
                $default_size = $size['id'];
                $default_size_price = $size['base_price'];
                break;
            }
        }
        if (!$default_size && !empty($sizes)) {
            $default_size = $sizes[0]['id'];
            $default_size_price = $sizes[0]['base_price'];
        }
        
        foreach ($bases as $base) {
            if ($base['is_default']) {
                $default_base = $base['id'];
                break;
            }
        }
        if (!$default_base && !empty($bases)) {
            $default_base = $bases[0]['id'];
        }
        
        foreach ($sauces as $sauce) {
            if ($sauce['is_default']) {
                $default_sauce = $sauce['id'];
                break;
            }
        }
        if (!$default_sauce && !empty($sauces)) {
            $default_sauce = $sauces[0]['id'];
        }

        // Group toppings by category
        $grouped_toppings = array();
        foreach ($toppings as $topping) {
            $category = !empty($topping['categories']) ? $topping['categories'][0] : __('Other', 'pizza-ordering');
            $grouped_toppings[$category][] = $topping;
        }

        // Separate default toppings from extra toppings
        $included_toppings = array();
        $extra_toppings = array();
        foreach ($toppings as $topping) {
            if (in_array($topping['id'], $default_toppings)) {
                $included_toppings[] = $topping;
            } else {
                $extra_toppings[] = $topping;
            }
        }
        ?>
        
        <div class="pizza-builder-wrapper">
            <!-- HERO SECTION -->
            <section class="pizza-hero">
                <div class="pizza-hero-container">
                    <div class="pizza-hero-image-wrapper">
                        <?php if ($product->is_on_sale()) : ?>
                            <span class="pizza-hero-badge">🔥 Tilbud</span>
                        <?php endif; ?>
                        <?php if ($product_image) : ?>
                            <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="pizza-hero-image">
                        <?php else : ?>
                            <div class="pizza-hero-image" style="background:#ddd;display:flex;align-items:center;justify-content:center;font-size:80px;">🍕</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pizza-hero-info">
                        <div class="pizza-hero-category"><?php echo wp_strip_all_tags($product_categories); ?></div>
                        <h1 class="pizza-hero-title"><?php echo esc_html($product->get_name()); ?></h1>
                        
                        <?php if ($description || $product->get_short_description()) : ?>
                            <p class="pizza-hero-description"><?php echo esc_html($description ?: $product->get_short_description()); ?></p>
                        <?php endif; ?>
                        
                        <div class="pizza-hero-meta">
                            <div class="pizza-meta-item">
                                <span class="pizza-meta-icon">⏱️</span>
                                <div>
                                    <div class="pizza-meta-text">Tilberedningstid</div>
                                    <div class="pizza-meta-value">12-15 min</div>
                                </div>
                            </div>
                            <?php if (!empty($included_toppings)) : ?>
                            <div class="pizza-meta-item">
                                <span class="pizza-meta-icon">🧀</span>
                                <div>
                                    <div class="pizza-meta-text">Toppings</div>
                                    <div class="pizza-meta-value"><?php echo count($included_toppings); ?> inkluderet</div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pizza-hero-price">
                            <span class="pizza-price-from">Fra</span>
                            <span class="pizza-price-amount"><?php echo number_format($default_size_price, 0); ?></span>
                            <span class="pizza-price-currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- BUILDER SECTION -->
            <section class="pizza-builder-container">
                <div class="pizza-builder">
                    <div class="pizza-builder-header">
                        <h2 class="pizza-builder-title">
                            <span>🍕</span>
                            <span>Byg din pizza</span>
                        </h2>
                        <div class="pizza-builder-steps">
                            <span class="pizza-step-dot active" data-step="1"></span>
                            <span class="pizza-step-dot" data-step="2"></span>
                            <span class="pizza-step-dot" data-step="3"></span>
                            <span class="pizza-step-dot" data-step="4"></span>
                        </div>
                    </div>
                    
                    <div class="pizza-builder-content">
                        <form class="pizza-builder-options" id="pizza-builder-form-<?php echo esc_attr($product_id); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
                            <?php wp_nonce_field('pizza_builder_add_to_cart', 'pizza_builder_nonce'); ?>
                            <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                            <input type="hidden" name="default_toppings" value="<?php echo esc_attr(implode(',', $default_toppings)); ?>">

                            <!-- SIZE SELECTION -->
                            <?php if (!empty($sizes)) : ?>
                            <div class="pizza-option-section" data-step="1">
                                <div class="pizza-section-header">
                                    <span class="pizza-section-number">1</span>
                                    <span class="pizza-section-title">Vælg størrelse</span>
                                </div>
                                
                                <div class="pizza-sizes-grid">
                                    <?php foreach ($sizes as $index => $size) : ?>
                                    <div class="pizza-size-card <?php echo $size['id'] == $default_size ? 'selected' : ''; ?>" onclick="this.querySelector('input').click()">
                                        <input type="radio" name="pizza_size" value="<?php echo esc_attr($size['id']); ?>"
                                               data-price="<?php echo esc_attr($size['base_price']); ?>"
                                               data-name="<?php echo esc_attr($size['name']); ?>"
                                               <?php checked($size['id'], $default_size); ?>>
                                        <?php if ($index === 1) : ?>
                                            <span class="pizza-size-popular">Populær</span>
                                        <?php endif; ?>
                                        <div class="pizza-size-icon">🍕</div>
                                        <div class="pizza-size-name"><?php echo esc_html($size['name']); ?></div>
                                        <?php if ($size['diameter']) : ?>
                                            <div class="pizza-size-details"><?php echo esc_html($size['diameter']); ?> cm</div>
                                        <?php endif; ?>
                                        <div class="pizza-size-price"><?php echo wc_price($size['base_price']); ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- BASE SELECTION -->
                            <?php if (!empty($bases)) : ?>
                            <div class="pizza-option-section" data-step="2">
                                <div class="pizza-section-header">
                                    <span class="pizza-section-number">2</span>
                                    <span class="pizza-section-title">Vælg bund</span>
                                </div>
                                
                                <div class="pizza-pills">
                                    <?php foreach ($bases as $base) : ?>
                                    <div class="pizza-pill">
                                        <input type="radio" name="pizza_base" value="<?php echo esc_attr($base['id']); ?>"
                                               id="base-<?php echo esc_attr($base['id']); ?>"
                                               data-price="<?php echo esc_attr($base['extra_price']); ?>"
                                               data-name="<?php echo esc_attr($base['name']); ?>"
                                               <?php checked($base['id'], $default_base); ?>>
                                        <label for="base-<?php echo esc_attr($base['id']); ?>">
                                            <?php if (!empty($base['image'])) : ?>
                                                <img src="<?php echo esc_url($base['image']); ?>" alt="" class="pill-icon">
                                            <?php elseif (!empty($base['emoji'])) : ?>
                                                <span class="pill-emoji"><?php echo esc_html($base['emoji']); ?></span>
                                            <?php endif; ?>
                                            <span class="pill-text"><?php echo esc_html($base['name']); ?></span>
                                            <?php if ($base['extra_price'] > 0) : ?>
                                                <span class="extra-price">+<?php echo wc_price($base['extra_price']); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- SAUCE SELECTION -->
                            <?php if (!empty($sauces)) : ?>
                            <div class="pizza-option-section" data-step="3">
                                <div class="pizza-section-header">
                                    <span class="pizza-section-number">3</span>
                                    <span class="pizza-section-title">Vælg sauce</span>
                                </div>
                                
                                <div class="pizza-pills">
                                    <?php foreach ($sauces as $sauce) : ?>
                                    <div class="pizza-pill">
                                        <input type="radio" name="pizza_sauce" value="<?php echo esc_attr($sauce['id']); ?>"
                                               id="sauce-<?php echo esc_attr($sauce['id']); ?>"
                                               data-price="<?php echo esc_attr($sauce['extra_price']); ?>"
                                               data-name="<?php echo esc_attr($sauce['name']); ?>"
                                               <?php checked($sauce['id'], $default_sauce); ?>>
                                        <label for="sauce-<?php echo esc_attr($sauce['id']); ?>">
                                            <?php if (!empty($sauce['image'])) : ?>
                                                <img src="<?php echo esc_url($sauce['image']); ?>" alt="" class="pill-icon">
                                            <?php elseif (!empty($sauce['emoji'])) : ?>
                                                <span class="pill-emoji"><?php echo esc_html($sauce['emoji']); ?></span>
                                            <?php endif; ?>
                                            <span class="pill-text"><?php echo esc_html($sauce['name']); ?></span>
                                            <?php if ($sauce['extra_price'] > 0) : ?>
                                                <span class="extra-price">+<?php echo wc_price($sauce['extra_price']); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- TOPPINGS SELECTION - TIL/FRA SYSTEM -->
                            <?php if (!empty($toppings) && $allow_customization) : ?>
                            <div class="pizza-option-section" data-step="4">
                                <div class="pizza-section-header">
                                    <span class="pizza-section-number">4</span>
                                    <span class="pizza-section-title">Tilpas toppings</span>
                                </div>
                                
                                <div class="pizza-toppings-container">
                                    <!-- INCLUDED TOPPINGS BOX -->
                                    <?php if (!empty($included_toppings)) : ?>
                                    <div class="pizza-included-box">
                                        <div class="pizza-box-header">
                                            <div class="pizza-box-icon">✓</div>
                                            <div>
                                                <div class="pizza-box-title">Inkluderet på denne pizza</div>
                                                <div class="pizza-box-subtitle">Slå fra hvis du ikke ønsker dem</div>
                                            </div>
                                        </div>
                                        
                                        <div class="pizza-toggle-list">
                                            <?php foreach ($included_toppings as $topping) : ?>
                                            <div class="pizza-toggle-item" data-topping-id="<?php echo esc_attr($topping['id']); ?>">
                                                <?php if (!empty($topping['image'])) : ?>
                                                    <img src="<?php echo esc_url($topping['image']); ?>" alt="<?php echo esc_attr($topping['name']); ?>" class="topping-img">
                                                <?php elseif (!empty($topping['emoji'])) : ?>
                                                    <span class="topping-emoji"><?php echo esc_html($topping['emoji']); ?></span>
                                                <?php else : ?>
                                                    <span class="topping-placeholder">🧀</span>
                                                <?php endif; ?>
                                                <span class="topping-name"><?php echo esc_html($topping['name']); ?></span>
                                                <span class="topping-status on">Inkluderet</span>
                                                <label class="pizza-toggle-switch">
                                                    <input type="checkbox" class="pizza-included-toggle" 
                                                           name="included_toppings[]" 
                                                           value="<?php echo esc_attr($topping['id']); ?>"
                                                           data-name="<?php echo esc_attr($topping['name']); ?>"
                                                           data-price="<?php echo esc_attr($topping['price']); ?>"
                                                           checked>
                                                    <span class="pizza-toggle-slider"></span>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- EXTRA TOPPINGS BOX -->
                                    <div class="pizza-extra-box">
                                        <div class="pizza-box-header">
                                            <div class="pizza-box-icon">+</div>
                                            <div>
                                                <div class="pizza-box-title">Tilføj ekstra toppings</div>
                                                <div class="pizza-box-subtitle">Koster ekstra per topping</div>
                                            </div>
                                        </div>
                                        
                                        <div class="pizza-extra-grid">
                                            <?php foreach ($extra_toppings as $topping) : ?>
                                            <label class="pizza-extra-item" 
                                                 data-category="<?php echo esc_attr(sanitize_title(!empty($topping['categories']) ? $topping['categories'][0] : 'other')); ?>">
                                                <input type="checkbox" class="pizza-extra-checkbox" 
                                                       name="extra_toppings[]" 
                                                       value="<?php echo esc_attr($topping['id']); ?>"
                                                       data-name="<?php echo esc_attr($topping['name']); ?>"
                                                       data-price="<?php echo esc_attr($topping['price']); ?>">
                                                <?php if (!empty($topping['image'])) : ?>
                                                    <img src="<?php echo esc_url($topping['image']); ?>" alt="<?php echo esc_attr($topping['name']); ?>" class="item-img">
                                                <?php elseif (!empty($topping['emoji'])) : ?>
                                                    <span class="item-emoji"><?php echo esc_html($topping['emoji']); ?></span>
                                                <?php else : ?>
                                                    <span class="item-placeholder">🍕</span>
                                                <?php endif; ?>
                                                <div class="item-info">
                                                    <div class="item-name"><?php echo esc_html($topping['name']); ?></div>
                                                    <div class="item-price">+<?php echo wc_price($topping['price']); ?></div>
                                                </div>
                                                <span class="item-check">✓</span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- TILBEHØR SEKTION (Sides & Drinks) -->
                            <?php 
                            // Gruppér sides efter kategori
                            $sides_by_category = array();
                            foreach ($sides as $side) {
                                $cat = !empty($side['categories']) ? $side['categories'][0] : 'Andet';
                                if (!isset($sides_by_category[$cat])) {
                                    $sides_by_category[$cat] = array();
                                }
                                $sides_by_category[$cat][] = $side;
                            }
                            ?>
                            <?php if (!empty($sides)) : ?>
                            <div class="pizza-option-section pizza-extras-section" data-step="5">
                                <div class="pizza-section-header">
                                    <span class="pizza-section-number">5</span>
                                    <span class="pizza-section-title">Tilføj tilbehør</span>
                                </div>
                                
                                <div class="pizza-extras-container">
                                    <?php foreach ($sides_by_category as $category => $category_sides) : ?>
                                    <div class="pizza-extras-category">
                                        <div class="pizza-extras-category-header">
                                            <?php 
                                            // Vælg emoji baseret på kategori
                                            $cat_lower = strtolower($category);
                                            $cat_emoji = '🍽️';
                                            if (strpos($cat_lower, 'drik') !== false || strpos($cat_lower, 'drink') !== false) {
                                                $cat_emoji = '🥤';
                                            } elseif (strpos($cat_lower, 'salat') !== false) {
                                                $cat_emoji = '🥗';
                                            } elseif (strpos($cat_lower, 'brød') !== false || strpos($cat_lower, 'bread') !== false) {
                                                $cat_emoji = '🥖';
                                            } elseif (strpos($cat_lower, 'dip') !== false || strpos($cat_lower, 'sauce') !== false) {
                                                $cat_emoji = '🫕';
                                            } elseif (strpos($cat_lower, 'dessert') !== false) {
                                                $cat_emoji = '🍰';
                                            }
                                            ?>
                                            <span class="category-emoji"><?php echo $cat_emoji; ?></span>
                                            <span class="category-name"><?php echo esc_html($category); ?></span>
                                        </div>
                                        
                                        <div class="pizza-extras-grid">
                                            <?php foreach ($category_sides as $side) : ?>
                                            <label class="pizza-side-item">
                                                <input type="checkbox" class="pizza-side-checkbox" 
                                                       name="pizza_sides[]" 
                                                       value="<?php echo esc_attr($side['id']); ?>"
                                                       data-name="<?php echo esc_attr($side['name']); ?>"
                                                       data-price="<?php echo esc_attr($side['price']); ?>">
                                                <?php if (!empty($side['image'])) : ?>
                                                    <img src="<?php echo esc_url($side['image']); ?>" alt="<?php echo esc_attr($side['name']); ?>" class="side-img">
                                                <?php else : ?>
                                                    <span class="side-placeholder"><?php echo $cat_emoji; ?></span>
                                                <?php endif; ?>
                                                <div class="side-info">
                                                    <div class="side-name"><?php echo esc_html($side['name']); ?></div>
                                                    <?php if (!empty($side['description'])) : ?>
                                                        <div class="side-desc"><?php echo esc_html(wp_trim_words($side['description'], 5)); ?></div>
                                                    <?php endif; ?>
                                                    <div class="side-price">+<?php echo wc_price($side['price']); ?></div>
                                                </div>
                                                <span class="side-check">✓</span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- COMBO DEALS SEKTION -->
                            <?php if (!empty($combos)) : ?>
                            <div class="pizza-option-section pizza-combos-section" data-step="6">
                                <div class="pizza-section-header">
                                    <span class="pizza-section-number">🔥</span>
                                    <span class="pizza-section-title">Gode tilbud</span>
                                </div>
                                
                                <div class="pizza-combos-grid">
                                    <?php foreach ($combos as $combo) : ?>
                                    <label class="pizza-combo-card">
                                        <input type="checkbox" class="pizza-combo-checkbox" 
                                               name="pizza_combos[]" 
                                               value="<?php echo esc_attr($combo['id']); ?>"
                                               data-name="<?php echo esc_attr($combo['name']); ?>"
                                               data-price="<?php echo esc_attr($combo['sale_price']); ?>">
                                        <?php if (!empty($combo['image'])) : ?>
                                            <img src="<?php echo esc_url($combo['image']); ?>" alt="<?php echo esc_attr($combo['name']); ?>" class="combo-img">
                                        <?php else : ?>
                                            <div class="combo-placeholder">🎁</div>
                                        <?php endif; ?>
                                        <div class="combo-info">
                                            <div class="combo-name"><?php echo esc_html($combo['name']); ?></div>
                                            <?php if (!empty($combo['description'])) : ?>
                                                <div class="combo-desc"><?php echo esc_html(wp_trim_words($combo['description'], 8)); ?></div>
                                            <?php endif; ?>
                                            <div class="combo-pricing">
                                                <?php if ($combo['regular_price'] > $combo['sale_price']) : ?>
                                                    <span class="combo-regular-price"><?php echo wc_price($combo['regular_price']); ?></span>
                                                <?php endif; ?>
                                                <span class="combo-sale-price">+<?php echo wc_price($combo['sale_price']); ?></span>
                                                <?php if ($combo['savings'] > 0) : ?>
                                                    <span class="combo-savings">Spar <?php echo wc_price($combo['savings']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="combo-check">✓</span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                        </form>
                        
                        <!-- ORDER SUMMARY SIDEBAR -->
                        <div class="pizza-order-summary">
                            <div class="pizza-summary-header">
                                <div class="pizza-summary-icon">🍕</div>
                                <h3 class="pizza-summary-title">Din ordre</h3>
                                <div class="pizza-summary-product"><?php echo esc_html($product->get_name()); ?></div>
                            </div>
                            
                            <div class="pizza-summary-section">
                                <div class="pizza-summary-section-title">Valg</div>
                                <div class="pizza-summary-row highlight pizza-summary-size">
                                    <span class="pizza-summary-label">-</span>
                                    <span class="pizza-summary-value">-</span>
                                </div>
                                <div class="pizza-summary-row pizza-summary-base">
                                    <span class="pizza-summary-label">-</span>
                                    <span class="pizza-summary-value">—</span>
                                </div>
                                <div class="pizza-summary-row pizza-summary-sauce">
                                    <span class="pizza-summary-label">-</span>
                                    <span class="pizza-summary-value">—</span>
                                </div>
                            </div>
                            
                            <div class="pizza-summary-section pizza-summary-included-section" style="display:none;">
                                <div class="pizza-summary-section-title">✅ Inkluderet</div>
                                <div class="pizza-topping-tags pizza-included-tags"></div>
                            </div>
                            
                            <div class="pizza-summary-section pizza-summary-removed-section" style="display:none;">
                                <div class="pizza-summary-section-title">❌ Uden</div>
                                <div class="pizza-topping-tags pizza-removed-tags"></div>
                            </div>
                            
                            <div class="pizza-summary-section pizza-summary-added-section" style="display:none;">
                                <div class="pizza-summary-section-title">➕ Tilføjet</div>
                                <div class="pizza-summary-added-list"></div>
                            </div>
                            
                            <div class="pizza-quantity-section">
                                <button type="button" class="pizza-qty-btn pizza-qty-minus">−</button>
                                <span class="pizza-qty-value">1</span>
                                <button type="button" class="pizza-qty-btn pizza-qty-plus">+</button>
                                <input type="hidden" name="quantity" value="1" class="pizza-quantity-input">
                            </div>
                            
                            <div class="pizza-summary-total">
                                <div class="pizza-total-row">
                                    <span class="pizza-total-label">Total</span>
                                    <span class="pizza-total-price"><?php echo wc_price($default_size_price); ?></span>
                                </div>
                            </div>
                            
                            <button type="button" class="pizza-add-to-cart-btn" data-product-id="<?php echo esc_attr($product_id); ?>">
                                <span>🛒</span>
                                <span class="pizza-btn-text">Læg i kurv</span>
                            </button>
                            
                            <div class="pizza-success-message" style="display:none;">
                                <span class="success-icon">✅</span>
                                <div class="success-text">
                                    <div class="success-title">Pizza tilføjet til kurv!</div>
                                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Se kurv</a> | 
                                    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>">Til kassen</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php
    }

    public function pizza_builder_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id'         => 0,
            'product_id' => 0, // Alternative parameter name
        ), $atts, 'pizza_builder');

        // Accept both 'id' and 'product_id' parameters
        $product_id = absint($atts['id']);
        if (!$product_id) {
            $product_id = absint($atts['product_id']);
        }
        
        // If still no ID, try to get from current product page
        if (!$product_id) {
            global $product;
            if ($product && is_a($product, 'WC_Product')) {
                $product_id = $product->get_id();
            }
        }
        
        if (!$product_id) {
            return '<p class="pizza-error">' . esc_html__('Please specify a product ID.', 'pizza-ordering') . '</p>';
        }

        $product = wc_get_product($product_id);
        
        if (!$product) {
            return '<p class="pizza-error">' . esc_html__('Product not found.', 'pizza-ordering') . '</p>';
        }
        
        if ($product->get_type() !== 'pizza') {
            return '<p class="pizza-error">' . esc_html__('This product is not a pizza product. Please create it as a Pizza product type.', 'pizza-ordering') . '</p>';
        }

        ob_start();
        $this->render_pizza_builder_form($product);
        return ob_get_clean();
    }

    /**
     * Pizza menu shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function pizza_menu_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns'  => 3,
            'category' => '',
            'limit'    => -1,
        ), $atts, 'pizza_menu');

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => intval($atts['limit']),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'pizza',
                ),
            ),
        );

        if (!empty($atts['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => explode(',', $atts['category']),
            );
        }

        $products = new WP_Query($args);

        if (!$products->have_posts()) {
            return '<p>' . esc_html__('No pizzas found.', 'pizza-ordering') . '</p>';
        }

        ob_start();
        ?>
        <div class="pizza-menu pizza-columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php while ($products->have_posts()) : $products->the_post(); 
                global $product;
                $is_popular = get_post_meta($product->get_id(), '_pizza_popular', true) === 'yes';
                $is_new = get_post_meta($product->get_id(), '_pizza_new', true) === 'yes';
                $description = get_post_meta($product->get_id(), '_pizza_description', true);
                $default_toppings = get_post_meta($product->get_id(), '_pizza_default_toppings', true) ?: array();
            ?>
            <div class="pizza-menu-item">
                <div class="pizza-menu-image">
                    <?php if ($is_new) : ?>
                        <span class="pizza-badge pizza-badge-new"><?php esc_html_e('New', 'pizza-ordering'); ?></span>
                    <?php endif; ?>
                    <?php if ($is_popular) : ?>
                        <span class="pizza-badge pizza-badge-popular">Populær</span>
                    <?php endif; ?>
                    <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
                </div>
                <div class="pizza-menu-content">
                    <h3 class="pizza-menu-title"><?php the_title(); ?></h3>
                    
                    <?php if ($description) : ?>
                        <p class="pizza-menu-description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($default_toppings)) : ?>
                        <p class="pizza-menu-toppings">
                            <?php 
                            $topping_names = array();
                            foreach ($default_toppings as $topping_id) {
                                $topping_names[] = get_the_title($topping_id);
                            }
                            echo esc_html(implode(', ', $topping_names));
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="pizza-menu-footer">
                        <span class="pizza-menu-price">
                            <?php echo wp_kses_post($product->get_price_html()); ?>
                        </span>
                        <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="pizza-menu-order button">
                            <?php esc_html_e('Order Now', 'pizza-ordering'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Locate template
     *
     * @param string $template      Template path
     * @param string $template_name Template name
     * @param string $template_path Template path
     * @return string
     */
    public function locate_template($template, $template_name, $template_path) {
        global $product;
        
        if ($template_name === 'single-product/add-to-cart/simple.php' && 
            $product && $product->get_type() === 'pizza') {
            $custom_template = PIZZA_ORDERING_PLUGIN_DIR . 'public/templates/pizza-add-to-cart.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * Render pizza builder modal
     */
    public function render_pizza_builder_modal() {
        if (!is_woocommerce() && !has_shortcode(get_post()->post_content ?? '', 'pizza_menu')) {
            return;
        }
        ?>
        <div id="pizza-builder-modal" class="pizza-modal" style="display: none;">
            <div class="pizza-modal-overlay"></div>
            <div class="pizza-modal-content">
                <button type="button" class="pizza-modal-close">&times;</button>
                <div class="pizza-modal-body">
                    <!-- Pizza builder will be loaded here -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render combo deals shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function pizza_combos_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
        ), $atts);

        $combos = Pizza_Post_Types::get_combos();

        if (empty($combos)) {
            return '';
        }

        ob_start();
        ?>
        <div class="pizza-combos-section">
            <h2 class="pizza-combos-title">🎉 <?php esc_html_e('Combo Deals', 'pizza-ordering'); ?></h2>
            <div class="pizza-combos-grid">
                <?php foreach ($combos as $combo) : ?>
                    <div class="pizza-combo-card" data-combo-id="<?php echo esc_attr($combo['id']); ?>">
                        <?php if ($combo['image']) : ?>
                            <div class="pizza-combo-image">
                                <img src="<?php echo esc_url($combo['image']); ?>" alt="<?php echo esc_attr($combo['name']); ?>">
                                <?php if ($combo['savings_pct'] > 0) : ?>
                                    <span class="pizza-combo-badge">
                                        <?php printf(esc_html__('Save %d%%', 'pizza-ordering'), $combo['savings_pct']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="pizza-combo-content">
                            <h3 class="pizza-combo-name"><?php echo esc_html($combo['name']); ?></h3>
                            
                            <div class="pizza-combo-includes">
                                <?php if ($combo['pizzas'] > 0) : ?>
                                    <span>🍕 <?php printf(_n('%d Pizza', '%d Pizzas', $combo['pizzas'], 'pizza-ordering'), $combo['pizzas']); ?></span>
                                <?php endif; ?>
                                <?php if ($combo['sides'] > 0) : ?>
                                    <span>🍟 <?php printf(_n('%d Side', '%d Sides', $combo['sides'], 'pizza-ordering'), $combo['sides']); ?></span>
                                <?php endif; ?>
                                <?php if ($combo['drinks'] > 0) : ?>
                                    <span>🥤 <?php printf(_n('%d Drink', '%d Drinks', $combo['drinks'], 'pizza-ordering'), $combo['drinks']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($combo['description'])) : ?>
                                <p class="pizza-combo-desc"><?php echo esc_html($combo['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="pizza-combo-pricing">
                                <?php if ($combo['regular_price'] > 0) : ?>
                                    <span class="pizza-combo-regular"><?php echo wc_price($combo['regular_price']); ?></span>
                                <?php endif; ?>
                                <span class="pizza-combo-price"><?php echo wc_price($combo['sale_price']); ?></span>
                            </div>
                            
                            <button type="button" class="pizza-combo-btn" data-combo-id="<?php echo esc_attr($combo['id']); ?>">
                                <?php esc_html_e('Order Now', 'pizza-ordering'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .pizza-combos-section { margin: 40px 0; }
            .pizza-combos-title { text-align: center; margin-bottom: 30px; }
            .pizza-combos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
            .pizza-combo-card { background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); transition: transform 0.3s; }
            .pizza-combo-card:hover { transform: translateY(-5px); }
            .pizza-combo-image { position: relative; height: 180px; overflow: hidden; }
            .pizza-combo-image img { width: 100%; height: 100%; object-fit: cover; }
            .pizza-combo-badge { position: absolute; top: 10px; right: 10px; background: #ff6b35; color: #fff; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 14px; }
            .pizza-combo-content { padding: 20px; }
            .pizza-combo-name { margin: 0 0 10px 0; font-size: 20px; }
            .pizza-combo-includes { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 10px; font-size: 14px; color: #666; }
            .pizza-combo-desc { color: #666; font-size: 14px; margin-bottom: 15px; }
            .pizza-combo-pricing { margin-bottom: 15px; }
            .pizza-combo-regular { text-decoration: line-through; color: #999; margin-right: 10px; }
            .pizza-combo-price { font-size: 24px; font-weight: bold; color: #ff6b35; }
            .pizza-combo-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #ff6b35, #ff8c5a); border: none; border-radius: 8px; color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
            .pizza-combo-btn:hover { background: linear-gradient(135deg, #e55a28, #ff6b35); }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Render sides menu shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function pizza_sides_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit'    => -1,
        ), $atts);

        $args = array();
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'side_category',
                    'field'    => 'slug',
                    'terms'    => $atts['category'],
                ),
            );
        }

        $sides = Pizza_Post_Types::get_sides($args);

        if (empty($sides)) {
            return '';
        }

        // Group by category
        $categories = array();
        foreach ($sides as $side) {
            $cat = !empty($side['categories']) ? $side['categories'][0] : __('Other', 'pizza-ordering');
            if (!isset($categories[$cat])) {
                $categories[$cat] = array();
            }
            $categories[$cat][] = $side;
        }

        ob_start();
        ?>
        <div class="pizza-sides-section">
            <?php foreach ($categories as $cat_name => $cat_sides) : ?>
                <div class="pizza-sides-category">
                    <h3 class="pizza-sides-cat-title"><?php echo esc_html($cat_name); ?></h3>
                    <div class="pizza-sides-grid">
                        <?php foreach ($cat_sides as $side) : ?>
                            <div class="pizza-side-card" data-side-id="<?php echo esc_attr($side['id']); ?>">
                                <?php if ($side['image']) : ?>
                                    <div class="pizza-side-image">
                                        <img src="<?php echo esc_url($side['image']); ?>" alt="<?php echo esc_attr($side['name']); ?>">
                                        <?php if ($side['is_popular']) : ?>
                                            <span class="pizza-side-popular">⭐</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="pizza-side-content">
                                    <h4 class="pizza-side-name"><?php echo esc_html($side['name']); ?></h4>
                                    
                                    <?php if (!empty($side['description'])) : ?>
                                        <p class="pizza-side-desc"><?php echo esc_html($side['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="pizza-side-meta">
                                        <?php if (!empty($side['calories'])) : ?>
                                            <span class="pizza-side-calories">🔥 <?php echo esc_html($side['calories']); ?> kcal</span>
                                        <?php endif; ?>
                                        <?php if (!empty($side['allergens'])) : ?>
                                            <span class="pizza-side-allergens" title="<?php echo esc_attr($side['allergens']); ?>">
                                                <?php echo wp_kses_post(Pizza_Post_Types::format_allergens($side['allergens'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="pizza-side-footer">
                                        <span class="pizza-side-price"><?php echo wc_price($side['price']); ?></span>
                                        <button type="button" class="pizza-side-add-btn" 
                                                data-side-id="<?php echo esc_attr($side['id']); ?>"
                                                data-side-name="<?php echo esc_attr($side['name']); ?>"
                                                data-side-price="<?php echo esc_attr($side['price']); ?>">
                                            <span class="pizza-add-icon">+</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
            .pizza-sides-section { margin: 40px 0; }
            .pizza-sides-cat-title { margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
            .pizza-sides-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
            .pizza-side-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 3px 15px rgba(0,0,0,0.08); transition: all 0.3s; }
            .pizza-side-card:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(0,0,0,0.12); }
            .pizza-side-image { position: relative; height: 150px; overflow: hidden; }
            .pizza-side-image img { width: 100%; height: 100%; object-fit: cover; }
            .pizza-side-popular { position: absolute; top: 8px; right: 8px; font-size: 20px; }
            .pizza-side-content { padding: 15px; }
            .pizza-side-name { margin: 0 0 8px 0; font-size: 16px; }
            .pizza-side-desc { color: #666; font-size: 13px; margin-bottom: 10px; }
            .pizza-side-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; font-size: 12px; color: #888; }
            .pizza-side-footer { display: flex; justify-content: space-between; align-items: center; }
            .pizza-side-price { font-size: 18px; font-weight: bold; color: #ff6b35; }
            .pizza-side-add-btn { width: 36px; height: 36px; border: none; background: #ff6b35; color: #fff; border-radius: 50%; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
            .pizza-side-add-btn:hover { background: #e55a28; transform: scale(1.1); }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.pizza-side-add-btn').on('click', function() {
                var $btn = $(this);
                var sideId = $btn.data('side-id');
                
                $btn.prop('disabled', true).html('<span class="spinner"></span>');
                
                $.post(pizzaBuilder.ajaxUrl, {
                    action: 'pizza_add_side_to_cart',
                    nonce: pizzaBuilder.nonce,
                    side_id: sideId
                }, function(response) {
                    if (response.success) {
                        $btn.html('✓').css('background', '#28a745');
                        setTimeout(function() {
                            $btn.html('<span class="pizza-add-icon">+</span>').css('background', '#ff6b35').prop('disabled', false);
                        }, 1500);
                        
                        // Update cart count
                        $('.cart-contents .count, .cart-count').text(response.data.cart_count);
                    } else {
                        alert(response.data.message);
                        $btn.html('<span class="pizza-add-icon">+</span>').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render upsell section on cart page
     */
    public function render_upsell_section() {
        // Check if cart has pizza items
        $has_pizza = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product && $product->get_type() === 'pizza') {
                $has_pizza = true;
                break;
            }
        }

        if (!$has_pizza) {
            return;
        }

        $sides = Pizza_Post_Types::get_sides();
        
        // Get popular sides first
        usort($sides, function($a, $b) {
            return ($b['is_popular'] ? 1 : 0) - ($a['is_popular'] ? 1 : 0);
        });
        
        $sides = array_slice($sides, 0, 6);

        if (empty($sides)) {
            return;
        }
        ?>
        <div class="pizza-upsell-section">
            <h2>🍟 <?php esc_html_e('Add something extra?', 'pizza-ordering'); ?></h2>
            <div class="pizza-upsell-grid">
                <?php foreach ($sides as $side) : ?>
                    <div class="pizza-upsell-item">
                        <?php if ($side['image']) : ?>
                            <img src="<?php echo esc_url($side['image']); ?>" alt="<?php echo esc_attr($side['name']); ?>" class="pizza-upsell-img">
                        <?php endif; ?>
                        <div class="pizza-upsell-info">
                            <span class="pizza-upsell-name"><?php echo esc_html($side['name']); ?></span>
                            <span class="pizza-upsell-price"><?php echo wc_price($side['price']); ?></span>
                        </div>
                        <button type="button" class="pizza-upsell-add" data-side-id="<?php echo esc_attr($side['id']); ?>">
                            <?php esc_html_e('Add', 'pizza-ordering'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .pizza-upsell-section { margin: 30px 0; padding: 25px; background: linear-gradient(135deg, #fff5f0 0%, #fff 100%); border-radius: 15px; border: 1px solid #ffe0cc; }
            .pizza-upsell-section h2 { margin: 0 0 20px 0; font-size: 20px; }
            .pizza-upsell-grid { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; }
            .pizza-upsell-item { flex: 0 0 150px; background: #fff; border-radius: 10px; padding: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
            .pizza-upsell-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; }
            .pizza-upsell-info { margin-bottom: 10px; }
            .pizza-upsell-name { display: block; font-size: 13px; font-weight: 600; margin-bottom: 3px; }
            .pizza-upsell-price { font-size: 14px; color: #ff6b35; font-weight: bold; }
            .pizza-upsell-add { width: 100%; padding: 8px; background: #ff6b35; border: none; border-radius: 6px; color: #fff; font-size: 13px; cursor: pointer; transition: all 0.2s; }
            .pizza-upsell-add:hover { background: #e55a28; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.pizza-upsell-add').on('click', function() {
                var $btn = $(this);
                var sideId = $btn.data('side-id');
                
                $btn.prop('disabled', true).text('<?php esc_html_e('Adding...', 'pizza-ordering'); ?>');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'pizza_add_side_to_cart',
                    nonce: '<?php echo wp_create_nonce('pizza_builder_nonce'); ?>',
                    side_id: sideId
                }, function(response) {
                    if (response.success) {
                        $btn.text('✓ <?php esc_html_e('Added', 'pizza-ordering'); ?>').css('background', '#28a745');
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        alert(response.data.message);
                        $btn.text('<?php esc_html_e('Add', 'pizza-ordering'); ?>').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render checkout upsell
     */
    public function render_checkout_upsell() {
        // Check if cart has pizza items
        $has_pizza = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product && $product->get_type() === 'pizza') {
                $has_pizza = true;
                break;
            }
        }

        if (!$has_pizza) {
            return;
        }

        $sides = Pizza_Post_Types::get_sides();
        $popular_sides = array_filter($sides, function($s) { return $s['is_popular']; });
        $popular_sides = array_slice($popular_sides, 0, 3);

        if (empty($popular_sides)) {
            return;
        }
        ?>
        <div class="pizza-checkout-upsell" id="pizza-checkout-upsell">
            <div class="pizza-checkout-upsell-header">
                <span class="pizza-upsell-icon">🍟</span>
                <span><?php esc_html_e("Don't forget your sides!", 'pizza-ordering'); ?></span>
                <button type="button" class="pizza-upsell-close" onclick="document.getElementById('pizza-checkout-upsell').style.display='none';">&times;</button>
            </div>
            <div class="pizza-checkout-upsell-items">
                <?php foreach ($popular_sides as $side) : ?>
                    <div class="pizza-checkout-upsell-item">
                        <span class="pizza-item-name"><?php echo esc_html($side['name']); ?></span>
                        <span class="pizza-item-price"><?php echo wc_price($side['price']); ?></span>
                        <button type="button" class="pizza-quick-add" data-side-id="<?php echo esc_attr($side['id']); ?>">+</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .pizza-checkout-upsell { background: linear-gradient(135deg, #fff5f0, #fff); border: 2px solid #ff6b35; border-radius: 12px; padding: 15px; margin-bottom: 25px; }
            .pizza-checkout-upsell-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-weight: 600; }
            .pizza-upsell-icon { font-size: 24px; }
            .pizza-upsell-close { margin-left: auto; background: none; border: none; font-size: 20px; cursor: pointer; color: #999; }
            .pizza-checkout-upsell-items { display: flex; flex-direction: column; gap: 8px; }
            .pizza-checkout-upsell-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #fff; border-radius: 8px; }
            .pizza-item-name { flex: 1; font-size: 14px; }
            .pizza-item-price { color: #ff6b35; font-weight: bold; }
            .pizza-quick-add { width: 28px; height: 28px; border: none; background: #ff6b35; color: #fff; border-radius: 50%; font-size: 18px; cursor: pointer; }
            .pizza-quick-add:hover { background: #e55a28; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.pizza-quick-add').on('click', function() {
                var $btn = $(this);
                var sideId = $btn.data('side-id');
                
                $btn.prop('disabled', true).text('...');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'pizza_add_side_to_cart',
                    nonce: '<?php echo wp_create_nonce('pizza_builder_nonce'); ?>',
                    side_id: sideId
                }, function(response) {
                    if (response.success) {
                        $btn.text('✓').css('background', '#28a745');
                        $('body').trigger('update_checkout');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Add side product to cart
     */
    public function ajax_add_side_to_cart() {
        check_ajax_referer('pizza_builder_nonce', 'nonce');

        $side_id = isset($_POST['side_id']) ? absint($_POST['side_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

        if (!$side_id) {
            wp_send_json_error(array('message' => __('Invalid product.', 'pizza-ordering')));
        }

        $side = get_post($side_id);
        if (!$side || $side->post_type !== 'pizza_side') {
            wp_send_json_error(array('message' => __('Product not found.', 'pizza-ordering')));
        }

        $price = floatval(get_post_meta($side_id, '_side_price', true));
        $name = $side->post_title;

        // Add to cart as custom item
        $cart_item_data = array(
            'pizza_side' => array(
                'id'    => $side_id,
                'name'  => $name,
                'price' => $price,
            ),
        );

        // We need a dummy product - use any simple product or create virtual one
        // For now, add as custom fee in session
        $sides_in_cart = WC()->session->get('pizza_sides', array());
        $sides_in_cart[] = array(
            'id'       => $side_id,
            'name'     => $name,
            'price'    => $price,
            'quantity' => $quantity,
        );
        WC()->session->set('pizza_sides', $sides_in_cart);

        // Add as fee
        add_action('woocommerce_cart_calculate_fees', function() use ($name, $price) {
            WC()->cart->add_fee($name, $price);
        });

        wp_send_json_success(array(
            'message'    => sprintf(__('%s added to cart!', 'pizza-ordering'), $name),
            'cart_count' => WC()->cart->get_cart_contents_count() + 1,
        ));
    }

    /**
     * AJAX: Add combo to cart
     */
    public function ajax_add_combo_to_cart() {
        check_ajax_referer('pizza_builder_nonce', 'nonce');

        $combo_id = isset($_POST['combo_id']) ? absint($_POST['combo_id']) : 0;

        if (!$combo_id) {
            wp_send_json_error(array('message' => __('Invalid combo.', 'pizza-ordering')));
        }

        $combo = get_post($combo_id);
        if (!$combo || $combo->post_type !== 'pizza_combo') {
            wp_send_json_error(array('message' => __('Combo not found.', 'pizza-ordering')));
        }

        // Store combo in session - user will need to select pizzas
        WC()->session->set('active_combo', array(
            'id'         => $combo_id,
            'name'       => $combo->post_title,
            'price'      => floatval(get_post_meta($combo_id, '_combo_sale_price', true)),
            'pizzas'     => absint(get_post_meta($combo_id, '_combo_pizzas', true)),
            'sides'      => absint(get_post_meta($combo_id, '_combo_sides', true)),
            'drinks'     => absint(get_post_meta($combo_id, '_combo_drinks', true)),
            'selected'   => array(
                'pizzas' => array(),
                'sides'  => array(),
                'drinks' => array(),
            ),
        ));

        wp_send_json_success(array(
            'message'  => __('Select your pizzas for this combo!', 'pizza-ordering'),
            'redirect' => wc_get_page_permalink('shop'),
        ));
    }
}
