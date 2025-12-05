# 🍕 Pizza Ordering Plugin for WooCommerce

A complete pizza ordering system for WooCommerce with visual pizza builder, customizable toppings, sides, combo deals, and kitchen dashboard.

**Version:** 2.5.0  
**Developer:** Sascha Marc Rahn  
**Requires:** WordPress 5.8+, WooCommerce 6.0+  
**Language:** Danish UI, English code

---

## ✨ Features

### 🍕 Pizza Builder
- Modern dark hero design with product info
- Size selection with cards (diameter, slices, serves info)
- Base selection with emoji/image support
- Sauce selection with emoji/image support
- **ON/OFF toggle system** for included toppings
- Extra toppings selection with pricing
- Real-time order summary sidebar
- Responsive design for all devices

### 🍽️ Sides & Extras
- Sides categorized by type (Drinks, Salads, Bread, etc.)
- Automatic emoji icons based on category
- Image or emoji support for each item
- Integrated directly in pizza builder

### 🔥 Combo Deals
- Create combo offers with discounts
- Show savings to customers
- Valid period support
- *(v2.6: Product selector coming soon)*

### 👨‍🍳 Kitchen Dashboard
- Real-time order management
- Order status updates
- Print functionality
- Auto-refresh option

### 🚗 Delivery & Pickup
- Delivery zone management by postcode
- Delivery fee calculation
- Pickup option
- Time slot selection

---

## 📁 File Structure

```
pizza-ordering/
├── pizza-ordering.php              # Main plugin file
├── includes/
│   ├── class-pizza-post-types.php  # Custom post types
│   ├── class-pizza-product-type.php # WC_Product_Pizza
│   ├── class-pizza-ajax.php        # AJAX handlers
│   ├── class-pizza-cart.php        # Cart modifications
│   └── class-wc-product-pizza.php  # Product type registration
├── admin/
│   ├── class-pizza-admin.php       # Admin settings
│   ├── class-pizza-kitchen.php     # Kitchen dashboard
│   ├── css/admin-style.css
│   └── js/admin-script.js
├── public/
│   ├── class-pizza-frontend.php    # Pizza builder rendering
│   ├── css/pizza-builder.css       # Frontend styles
│   ├── js/pizza-builder.js         # Frontend interactivity
│   └── templates/
└── languages/
```

---

## 🚀 Installation

1. Download the `pizza-ordering` folder
2. Upload to `/wp-content/plugins/`
3. Activate the plugin in WordPress Admin
4. Go to **Pizza Ordering** menu to configure

---

## ⚙️ Configuration

### 1. Create Sizes
**Pizza → Sizes → Add New**
- Name: Small, Medium, Large, Family
- Base price, diameter, slices, serves

### 2. Create Bases
**Pizza → Bases → Add New**
- Name: Classic, Thin, Thick, Gluten-free
- Extra price (0 for default)
- Emoji or image

### 3. Create Sauces
**Pizza → Sauces → Add New**
- Name: Tomato, Garlic, BBQ, Pesto
- Extra price (0 for default)
- Emoji or image

### 4. Create Toppings
**Pizza → Toppings → Add New**
- Name, prices per size
- Emoji or image
- Allergen info

### 5. Create Side Categories
**Pizza → Side Categories**
- Drinks, Salads, Bread, Dips, etc.

### 6. Create Sides
**Pizza → Sides → Add New**
- Name, price, description
- Assign to category
- Upload image

### 7. Create Pizza Products
**Products → Add New → Product Type: Pizza**
- Set as preset pizza
- Select default toppings
- Allow customization

---

## 📋 Shortcodes

```php
[pizza_menu]           // Display pizza menu grid
[pizza_sides]          // Display sides menu
[pizza_combos]         // Display combo deals
```

---

## 🎨 Design System

CSS Variables used:
```css
--pizza-primary: #4CAF50    /* Green */
--pizza-secondary: #ff5722  /* Orange */
--pizza-dark: #1a1a2e       /* Dark blue */
--pizza-white: #ffffff
--pizza-border: #e0e0e0
```

---

## 📝 Version History

| Version | Changes |
|---------|---------|
| 2.5.0 | Sides & Combos integrated in pizza builder |
| 2.4.0 | Image upload + emoji selector in admin |
| 2.3.0 | Danish text, fixed extras clicking, clean product page |
| 2.2.0 | Modern mockup design implemented |
| 2.0.0 | ON/OFF toggle system for included toppings |
| 1.0.0 | Initial release |

---

## 🔜 Roadmap

- [ ] **v2.6:** Improved combo system with product selector
- [ ] Allergen display on toppings
- [ ] Half & Half pizza option
- [ ] Customer favorites / reorder
- [ ] Upsell popup after add to cart

---

## 🛠️ For Developers

### Key Hooks

```php
// Modify pizza price calculation
add_filter('pizza_calculate_price', 'my_custom_pricing', 10, 2);

// After pizza added to cart
add_action('pizza_added_to_cart', 'my_after_add', 10, 2);
```

### JavaScript Events

```javascript
// Pizza builder initialized
$(document).on('pizza_builder_init', function(e, productId) {});

// Selection changed
$(document).on('pizza_selection_changed', function(e, selection) {});
```

---

## 📄 License

GPL v2 or later

---

## 🤝 Support

For issues and feature requests, please use the GitHub Issues tab.

---

Made with ❤️ for pizza lovers
