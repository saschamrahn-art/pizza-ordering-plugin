/**
 * Pizza Builder - Modern Design JavaScript
 *
 * @package Pizza_Ordering
 * @version 2.1.0
 */

(function($) {
    'use strict';

    var PizzaBuilder = {
        
        // Configuration
        config: {
            productId: 0,
            currency: 'kr.',
            defaultToppingIds: []
        },

        // Current selection state
        selection: {
            sizeId: 0,
            sizeName: '',
            sizePrice: 0,
            baseId: 0,
            baseName: '',
            basePrice: 0,
            sauceId: 0,
            sauceName: '',
            saucePrice: 0,
            includedToppings: [],
            removedToppings: [],
            addedToppings: [],
            sides: [],
            combos: [],
            quantity: 1
        },

        // Initialization flag
        initialized: false,

        /**
         * Initialize
         */
        init: function() {
            if (this.initialized) return;

            var $form = $('[id^="pizza-builder-form-"]');
            if ($form.length === 0) {
                console.log('Pizza Builder: No form found');
                return;
            }

            this.config.productId = $form.data('product-id');
            
            // Get default topping IDs from hidden field
            var defaultToppingsStr = $form.find('input[name="default_toppings"]').val();
            if (defaultToppingsStr) {
                this.config.defaultToppingIds = defaultToppingsStr.split(',').map(function(id) {
                    return parseInt(id, 10);
                }).filter(function(id) {
                    return !isNaN(id) && id > 0;
                });
            }

            this.bindEvents();
            this.initializeSelections();
            this.updateSummary();
            
            this.initialized = true;
            console.log('Pizza Builder initialized for product:', this.config.productId);
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            var self = this;

            // Size selection
            $(document).on('change', '.pizza-size-card input[type="radio"]', function() {
                self.handleSizeChange($(this));
            });

            // Base selection
            $(document).on('change', '.pizza-pill input[name="pizza_base"]', function() {
                self.handleBaseChange($(this));
            });

            // Sauce selection
            $(document).on('change', '.pizza-pill input[name="pizza_sauce"]', function() {
                self.handleSauceChange($(this));
            });

            // Included topping toggle
            $(document).on('change', '.pizza-included-toggle', function() {
                self.handleIncludedToggle($(this));
            });

            // Extra topping checkbox
            $(document).on('change', '.pizza-extra-checkbox', function() {
                self.handleExtraTopping($(this));
            });

            // Side item checkbox
            $(document).on('change', '.pizza-side-checkbox', function() {
                self.handleSideToggle($(this));
            });

            // Combo checkbox
            $(document).on('change', '.pizza-combo-checkbox', function() {
                self.handleComboToggle($(this));
            });

            // Category filter buttons
            $(document).on('click', '.pizza-cat-btn', function(e) {
                e.preventDefault();
                self.handleCategoryFilter($(this));
            });

            // Quantity buttons
            $(document).on('click', '.pizza-qty-minus', function(e) {
                e.preventDefault();
                self.updateQuantity(-1);
            });

            $(document).on('click', '.pizza-qty-plus', function(e) {
                e.preventDefault();
                self.updateQuantity(1);
            });

            // Add to cart button
            $(document).on('click', '.pizza-add-to-cart-btn', function(e) {
                e.preventDefault();
                self.addToCart($(this));
            });

            // Update selected class on labels
            $(document).on('change', '.pizza-size-card input, .pizza-pill input', function() {
                var $parent = $(this).closest('.pizza-sizes-grid, .pizza-pills');
                $parent.find('.selected').removeClass('selected');
                $(this).closest('.pizza-size-card, .pizza-pill').addClass('selected');
            });

            // Extra item change handler - update selected class
            $(document).on('change', '.pizza-extra-checkbox', function() {
                var $item = $(this).closest('.pizza-extra-item');
                if ($(this).is(':checked')) {
                    $item.addClass('selected');
                } else {
                    $item.removeClass('selected');
                }
            });

            // Side item change handler - update selected class
            $(document).on('change', '.pizza-side-checkbox', function() {
                var $item = $(this).closest('.pizza-side-item');
                if ($(this).is(':checked')) {
                    $item.addClass('selected');
                } else {
                    $item.removeClass('selected');
                }
            });

            // Combo change handler - update selected class
            $(document).on('change', '.pizza-combo-checkbox', function() {
                var $item = $(this).closest('.pizza-combo-card');
                if ($(this).is(':checked')) {
                    $item.addClass('selected');
                } else {
                    $item.removeClass('selected');
                }
            });
        },

        /**
         * Initialize selections from pre-selected inputs
         */
        initializeSelections: function() {
            var self = this;

            // Size
            var $selectedSize = $('.pizza-size-card input:checked');
            if ($selectedSize.length) {
                this.selection.sizeId = parseInt($selectedSize.val(), 10);
                this.selection.sizeName = $selectedSize.data('name') || $selectedSize.closest('.pizza-size-card').find('.pizza-size-name').text();
                this.selection.sizePrice = parseFloat($selectedSize.data('price')) || 0;
            }

            // Base
            var $selectedBase = $('input[name="pizza_base"]:checked');
            if ($selectedBase.length) {
                this.selection.baseId = parseInt($selectedBase.val(), 10);
                this.selection.baseName = $selectedBase.data('name') || $selectedBase.closest('.pizza-pill').find('.pizza-pill-name').text();
                this.selection.basePrice = parseFloat($selectedBase.data('price')) || 0;
            }

            // Sauce
            var $selectedSauce = $('input[name="pizza_sauce"]:checked');
            if ($selectedSauce.length) {
                this.selection.sauceId = parseInt($selectedSauce.val(), 10);
                this.selection.sauceName = $selectedSauce.data('name') || $selectedSauce.closest('.pizza-pill').find('.pizza-pill-name').text();
                this.selection.saucePrice = parseFloat($selectedSauce.data('price')) || 0;
            }

            // Included toppings (default toppings that are ON)
            $('.pizza-included-toggle:checked').each(function() {
                var $toggle = $(this);
                self.selection.includedToppings.push({
                    id: parseInt($toggle.val(), 10),
                    name: $toggle.data('name'),
                    price: 0
                });
            });
        },

        /**
         * Handle size change
         */
        handleSizeChange: function($input) {
            this.selection.sizeId = parseInt($input.val(), 10);
            this.selection.sizeName = $input.data('name') || $input.closest('.pizza-size-card').find('.pizza-size-name').text();
            this.selection.sizePrice = parseFloat($input.data('price')) || 0;
            
            // Update selected class
            $('.pizza-sizes-grid .pizza-size-card').removeClass('selected');
            $input.closest('.pizza-size-card').addClass('selected');
            
            // Update step indicator
            this.updateStepIndicator(1);
            this.updateSummary();
        },

        /**
         * Handle base change
         */
        handleBaseChange: function($input) {
            this.selection.baseId = parseInt($input.val(), 10);
            this.selection.baseName = $input.data('name') || $input.closest('.pizza-pill').find('.pizza-pill-name').text();
            this.selection.basePrice = parseFloat($input.data('price')) || 0;
            
            this.updateStepIndicator(2);
            this.updateSummary();
        },

        /**
         * Handle sauce change
         */
        handleSauceChange: function($input) {
            this.selection.sauceId = parseInt($input.val(), 10);
            this.selection.sauceName = $input.data('name') || $input.closest('.pizza-pill').find('.pizza-pill-name').text();
            this.selection.saucePrice = parseFloat($input.data('price')) || 0;
            
            this.updateStepIndicator(3);
            this.updateSummary();
        },

        /**
         * Handle included topping toggle (ON/OFF)
         */
        handleIncludedToggle: function($toggle) {
            var id = parseInt($toggle.val(), 10);
            var name = $toggle.data('name');
            var $item = $toggle.closest('.pizza-toggle-item');
            var $status = $item.find('.pizza-toggle-status');

            if ($toggle.is(':checked')) {
                // Topping is ON - add to included, remove from removed
                $item.removeClass('is-off');
                $status.removeClass('pizza-status-off').addClass('pizza-status-on').text(pizzaBuilder.i18n.included || 'Inkluderet');
                
                // Add to included
                if (!this.findTopping(this.selection.includedToppings, id)) {
                    this.selection.includedToppings.push({ id: id, name: name, price: 0 });
                }
                // Remove from removed
                this.selection.removedToppings = this.selection.removedToppings.filter(function(t) {
                    return t.id !== id;
                });
            } else {
                // Topping is OFF - add to removed, remove from included
                $item.addClass('is-off');
                $status.removeClass('pizza-status-on').addClass('pizza-status-off').text(pizzaBuilder.i18n.removed || 'Fjernet');
                
                // Add to removed
                if (!this.findTopping(this.selection.removedToppings, id)) {
                    this.selection.removedToppings.push({ id: id, name: name });
                }
                // Remove from included
                this.selection.includedToppings = this.selection.includedToppings.filter(function(t) {
                    return t.id !== id;
                });
            }

            this.updateStepIndicator(4);
            this.updateSummary();
        },

        /**
         * Handle extra topping checkbox
         */
        handleExtraTopping: function($checkbox) {
            var id = parseInt($checkbox.val(), 10);
            var name = $checkbox.data('name');
            var price = parseFloat($checkbox.data('price')) || 0;
            var $item = $checkbox.closest('.pizza-extra-item');

            if ($checkbox.is(':checked')) {
                $item.addClass('selected');
                if (!this.findTopping(this.selection.addedToppings, id)) {
                    this.selection.addedToppings.push({ id: id, name: name, price: price });
                }
            } else {
                $item.removeClass('selected');
                this.selection.addedToppings = this.selection.addedToppings.filter(function(t) {
                    return t.id !== id;
                });
            }

            this.updateStepIndicator(4);
            this.updateSummary();
        },

        /**
         * Handle side toggle
         */
        handleSideToggle: function($checkbox) {
            var id = parseInt($checkbox.val(), 10);
            var name = $checkbox.data('name');
            var price = parseFloat($checkbox.data('price')) || 0;
            var $item = $checkbox.closest('.pizza-side-item');

            if ($checkbox.is(':checked')) {
                $item.addClass('selected');
                if (!this.findInArray(this.selection.sides, id)) {
                    this.selection.sides.push({ id: id, name: name, price: price });
                }
            } else {
                $item.removeClass('selected');
                this.selection.sides = this.selection.sides.filter(function(s) {
                    return s.id !== id;
                });
            }

            this.updateSummary();
        },

        /**
         * Handle combo toggle
         */
        handleComboToggle: function($checkbox) {
            var id = parseInt($checkbox.val(), 10);
            var name = $checkbox.data('name');
            var price = parseFloat($checkbox.data('price')) || 0;
            var $item = $checkbox.closest('.pizza-combo-card');

            if ($checkbox.is(':checked')) {
                $item.addClass('selected');
                if (!this.findInArray(this.selection.combos, id)) {
                    this.selection.combos.push({ id: id, name: name, price: price });
                }
            } else {
                $item.removeClass('selected');
                this.selection.combos = this.selection.combos.filter(function(c) {
                    return c.id !== id;
                });
            }

            this.updateSummary();
        },

        /**
         * Find item in array by ID
         */
        findInArray: function(arr, id) {
            for (var i = 0; i < arr.length; i++) {
                if (arr[i].id === id) return arr[i];
            }
            return null;
        },

        /**
         * Handle category filter
         */
        handleCategoryFilter: function($btn) {
            var category = $btn.data('category');
            
            $('.pizza-cat-btn').removeClass('active');
            $btn.addClass('active');

            if (category === 'all') {
                $('.pizza-extra-item').removeClass('hidden');
            } else {
                $('.pizza-extra-item').each(function() {
                    var itemCat = $(this).data('category');
                    if (itemCat === category) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            }
        },

        /**
         * Update quantity
         */
        updateQuantity: function(change) {
            var newQty = this.selection.quantity + change;
            if (newQty < 1) newQty = 1;
            if (newQty > 10) newQty = 10;
            
            this.selection.quantity = newQty;
            $('.pizza-qty-value').text(newQty);
            $('.pizza-quantity-input').val(newQty);
            
            this.updateSummary();
        },

        /**
         * Update step indicator
         */
        updateStepIndicator: function(step) {
            $('.pizza-step').each(function() {
                var stepNum = parseInt($(this).data('step'), 10);
                if (stepNum <= step) {
                    $(this).addClass('active');
                }
            });
        },

        /**
         * Calculate total price
         */
        calculateTotal: function() {
            var total = 0;

            // Size price
            total += this.selection.sizePrice;

            // Base extra price
            total += this.selection.basePrice;

            // Sauce extra price
            total += this.selection.saucePrice;

            // Added toppings (extra cost)
            for (var i = 0; i < this.selection.addedToppings.length; i++) {
                total += this.selection.addedToppings[i].price;
            }

            // Multiply pizza by quantity
            total *= this.selection.quantity;

            // Add sides (not multiplied by pizza quantity)
            for (var j = 0; j < this.selection.sides.length; j++) {
                total += this.selection.sides[j].price;
            }

            // Add combos (not multiplied by pizza quantity)
            for (var k = 0; k < this.selection.combos.length; k++) {
                total += this.selection.combos[k].price;
            }

            return total;
        },

        /**
         * Format price
         */
        formatPrice: function(price) {
            return price.toFixed(2).replace('.', ',') + ' ' + (pizzaBuilder.currency || 'kr.');
        },

        /**
         * Update order summary
         */
        updateSummary: function() {
            var self = this;

            // Size row
            if (this.selection.sizeName) {
                $('.pizza-summary-size .pizza-summary-label').text(this.selection.sizeName);
                $('.pizza-summary-size .pizza-summary-value').text(this.formatPrice(this.selection.sizePrice));
            }

            // Base row
            if (this.selection.baseName) {
                $('.pizza-summary-base .pizza-summary-label').text(this.selection.baseName);
                $('.pizza-summary-base .pizza-summary-value').text(this.selection.basePrice > 0 ? '+' + this.formatPrice(this.selection.basePrice) : '—');
            }

            // Sauce row
            if (this.selection.sauceName) {
                $('.pizza-summary-sauce .pizza-summary-label').text(this.selection.sauceName);
                $('.pizza-summary-sauce .pizza-summary-value').text(this.selection.saucePrice > 0 ? '+' + this.formatPrice(this.selection.saucePrice) : '—');
            }

            // Included toppings tags
            var $includedSection = $('.pizza-summary-included-section');
            var $includedTags = $('.pizza-included-tags');
            if (this.selection.includedToppings.length > 0) {
                $includedSection.show();
                $includedTags.empty();
                this.selection.includedToppings.forEach(function(t) {
                    $includedTags.append('<span class="pizza-summary-tag included">' + t.name + '</span>');
                });
            } else {
                $includedSection.hide();
            }

            // Removed toppings tags
            var $removedSection = $('.pizza-summary-removed-section');
            var $removedTags = $('.pizza-removed-tags');
            if (this.selection.removedToppings.length > 0) {
                $removedSection.show();
                $removedTags.empty();
                this.selection.removedToppings.forEach(function(t) {
                    $removedTags.append('<span class="pizza-summary-tag removed">' + t.name + '</span>');
                });
            } else {
                $removedSection.hide();
            }

            // Added toppings list
            var $addedSection = $('.pizza-summary-added-section');
            var $addedList = $('.pizza-summary-added-list');
            if (this.selection.addedToppings.length > 0) {
                $addedSection.show();
                $addedList.empty();
                this.selection.addedToppings.forEach(function(t) {
                    $addedList.append(
                        '<div class="pizza-summary-row">' +
                        '<span class="pizza-summary-label">' + t.name + '</span>' +
                        '<span class="pizza-summary-value">+' + self.formatPrice(t.price) + '</span>' +
                        '</div>'
                    );
                });
            } else {
                $addedSection.hide();
            }

            // Sides list
            var $sidesSection = $('.pizza-summary-sides-section');
            var $sidesList = $('.pizza-summary-sides-list');
            if (this.selection.sides.length > 0) {
                if ($sidesSection.length === 0) {
                    // Create section if it doesn't exist
                    var sidesHtml = '<div class="pizza-summary-section pizza-summary-sides-section">' +
                        '<div class="pizza-summary-section-title">🍽️ Tilbehør</div>' +
                        '<div class="pizza-summary-sides-list"></div>' +
                        '</div>';
                    $('.pizza-summary-added-section').after(sidesHtml);
                    $sidesSection = $('.pizza-summary-sides-section');
                    $sidesList = $('.pizza-summary-sides-list');
                }
                $sidesSection.show();
                $sidesList.empty();
                this.selection.sides.forEach(function(s) {
                    $sidesList.append(
                        '<div class="pizza-summary-row">' +
                        '<span class="pizza-summary-label">' + s.name + '</span>' +
                        '<span class="pizza-summary-value">+' + self.formatPrice(s.price) + '</span>' +
                        '</div>'
                    );
                });
            } else if ($sidesSection.length > 0) {
                $sidesSection.hide();
            }

            // Combos list
            var $combosSection = $('.pizza-summary-combos-section');
            var $combosList = $('.pizza-summary-combos-list');
            if (this.selection.combos.length > 0) {
                if ($combosSection.length === 0) {
                    // Create section if it doesn't exist
                    var combosHtml = '<div class="pizza-summary-section pizza-summary-combos-section">' +
                        '<div class="pizza-summary-section-title">🔥 Tilbud</div>' +
                        '<div class="pizza-summary-combos-list"></div>' +
                        '</div>';
                    var $afterEl = $('.pizza-summary-sides-section').length > 0 ? 
                        $('.pizza-summary-sides-section') : $('.pizza-summary-added-section');
                    $afterEl.after(combosHtml);
                    $combosSection = $('.pizza-summary-combos-section');
                    $combosList = $('.pizza-summary-combos-list');
                }
                $combosSection.show();
                $combosList.empty();
                this.selection.combos.forEach(function(c) {
                    $combosList.append(
                        '<div class="pizza-summary-row">' +
                        '<span class="pizza-summary-label">' + c.name + '</span>' +
                        '<span class="pizza-summary-value">+' + self.formatPrice(c.price) + '</span>' +
                        '</div>'
                    );
                });
            } else if ($combosSection.length > 0) {
                $combosSection.hide();
            }

            // Total price
            var total = this.calculateTotal();
            $('.pizza-total-price').text(this.formatPrice(total));
        },

        /**
         * Find topping in array
         */
        findTopping: function(arr, id) {
            for (var i = 0; i < arr.length; i++) {
                if (arr[i].id === id) return arr[i];
            }
            return null;
        },

        /**
         * Add to cart
         */
        addToCart: function($btn) {
            var self = this;

            if (!this.selection.sizeId) {
                alert(pizzaBuilder.i18n.selectSize || 'Vælg venligst en størrelse');
                return;
            }

            // Disable button
            var originalText = $btn.find('.pizza-btn-text').text();
            $btn.prop('disabled', true);
            $btn.find('.pizza-btn-text').text(pizzaBuilder.i18n.adding || 'Tilføjer...');

            // Prepare data
            var data = {
                action: 'pizza_add_to_cart',
                nonce: pizzaBuilder.nonce,
                product_id: this.config.productId,
                quantity: this.selection.quantity,
                size_id: this.selection.sizeId,
                base_id: this.selection.baseId,
                sauce_id: this.selection.sauceId,
                included_topping_ids: this.selection.includedToppings.map(function(t) { return t.id; }),
                removed_topping_ids: this.selection.removedToppings.map(function(t) { return t.id; }),
                added_topping_ids: this.selection.addedToppings.map(function(t) { return t.id; }),
                extra_portion_ids: [],
                side_ids: this.selection.sides.map(function(s) { return s.id; }),
                combo_ids: this.selection.combos.map(function(c) { return c.id; })
            };

            $.ajax({
                url: pizzaBuilder.ajax_url || pizzaBuilder.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $btn.hide();
                        $('.pizza-success-message').show();

                        // Update cart count if available
                        if (response.data && response.data.cart_count) {
                            $('.cart-contents-count, .cart-count').text(response.data.cart_count);
                        }

                        // Trigger WooCommerce event
                        $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
                    } else {
                        alert(response.data.message || 'Der opstod en fejl');
                        $btn.prop('disabled', false);
                        $btn.find('.pizza-btn-text').text(originalText);
                    }
                },
                error: function() {
                    alert(pizzaBuilder.i18n.error || 'Der opstod en fejl. Prøv igen.');
                    $btn.prop('disabled', false);
                    $btn.find('.pizza-btn-text').text(originalText);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PizzaBuilder.init();
    });

    // Also initialize after AJAX complete (for dynamic loading)
    $(document).ajaxComplete(function() {
        if (!PizzaBuilder.initialized) {
            PizzaBuilder.init();
        }
    });

})(jQuery);
