/**
 * Pizza Ordering Admin Scripts
 *
 * @package Pizza_Ordering
 */

(function($) {
    'use strict';

    /**
     * Kitchen Dashboard Handler
     */
    var PizzaKitchen = {
        autoRefreshInterval: null,
        lastOrderCount: 0,

        /**
         * Initialize
         */
        init: function() {
            if ($('.pizza-kitchen-wrap').length === 0) {
                return;
            }

            this.bindEvents();
            this.startAutoRefresh();
            this.updateTime();
            
            // Update time every minute
            setInterval(this.updateTime.bind(this), 60000);

            console.log('[Pizza Kitchen] Dashboard initialized');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Manual refresh
            $('#pizza-kitchen-refresh').on('click', function() {
                self.refreshOrders();
            });

            // Auto-refresh toggle
            $('#pizza-auto-refresh').on('change', function() {
                if ($(this).is(':checked')) {
                    self.startAutoRefresh();
                } else {
                    self.stopAutoRefresh();
                }
            });

            // Status change buttons
            $(document).on('click', '.pizza-status-btn', function() {
                var $card = $(this).closest('.pizza-order-card');
                var orderId = $card.data('order-id');
                var newStatus = $(this).data('next-status');
                
                self.updateOrderStatus(orderId, newStatus, $card);
            });
        },

        /**
         * Update current time display
         */
        updateTime: function() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            $('.pizza-kitchen-time').text(hours + ':' + minutes);
        },

        /**
         * Start auto-refresh
         */
        startAutoRefresh: function() {
            var self = this;
            var rate = (typeof pizzaKitchen !== 'undefined') ? pizzaKitchen.refreshRate : 30000;
            
            this.autoRefreshInterval = setInterval(function() {
                self.refreshOrders();
            }, rate);

            console.log('[Pizza Kitchen] Auto-refresh started');
        },

        /**
         * Stop auto-refresh
         */
        stopAutoRefresh: function() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
            console.log('[Pizza Kitchen] Auto-refresh stopped');
        },

        /**
         * Refresh orders
         */
        refreshOrders: function() {
            var self = this;
            var $button = $('#pizza-kitchen-refresh');

            $button.find('.dashicons').addClass('spin');

            $.ajax({
                url: pizzaKitchen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pizza_get_kitchen_orders',
                    nonce: pizzaKitchen.nonce,
                    statuses: ['received', 'preparing', 'ready']
                },
                success: function(response) {
                    if (response.success) {
                        self.updateOrderDisplay(response.data.orders);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Pizza Kitchen] Refresh failed:', error);
                },
                complete: function() {
                    $button.find('.dashicons').removeClass('spin');
                }
            });
        },

        /**
         * Update order display
         *
         * @param {Array} orders Order data
         */
        updateOrderDisplay: function(orders) {
            var receivedCount = 0;
            var preparingCount = 0;
            var readyCount = 0;

            orders.forEach(function(order) {
                switch (order.status) {
                    case 'received':
                        receivedCount++;
                        break;
                    case 'preparing':
                        preparingCount++;
                        break;
                    case 'ready':
                        readyCount++;
                        break;
                }
            });

            // Update counts
            $('[data-status="received"] .pizza-order-count').text(receivedCount);
            $('[data-status="preparing"] .pizza-order-count').text(preparingCount);
            $('[data-status="ready"] .pizza-order-count').text(readyCount);

            // Check for new orders
            var totalOrders = receivedCount + preparingCount + readyCount;
            if (totalOrders > this.lastOrderCount && this.lastOrderCount > 0) {
                this.playNewOrderSound();
            }
            this.lastOrderCount = totalOrders;
        },

        /**
         * Update order status
         *
         * @param {number} orderId   Order ID
         * @param {string} newStatus New status
         * @param {jQuery} $card     Order card element
         */
        updateOrderStatus: function(orderId, newStatus, $card) {
            var self = this;
            var $button = $card.find('.pizza-status-btn');

            $button.prop('disabled', true).text('...');

            $.ajax({
                url: pizzaKitchen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pizza_update_order_status',
                    nonce: pizzaKitchen.nonce,
                    order_id: orderId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Animate card to new column
                        self.moveCardToColumn($card, newStatus);
                    } else {
                        alert(response.data.message || 'Error updating status');
                        $button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Pizza Kitchen] Status update failed:', error);
                    alert('Error updating status. Please try again.');
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Move card to new column
         *
         * @param {jQuery} $card     Order card element
         * @param {string} newStatus New status
         */
        moveCardToColumn: function($card, newStatus) {
            var $newColumn = $('#orders-' + newStatus.replace('_', '-'));
            
            // If status is completed, remove card
            if (newStatus === 'delivered' || newStatus === 'picked_up' || newStatus === 'out_for_delivery') {
                $card.slideUp(300, function() {
                    $(this).remove();
                });
                return;
            }

            // Animate card movement
            $card.css('opacity', '0.5');
            
            setTimeout(function() {
                $card.detach();
                $card.attr('data-status', newStatus);
                $card.prependTo($newColumn);
                $card.css('opacity', '1');
                
                // Update counts
                $('.pizza-kitchen-column').each(function() {
                    var status = $(this).data('status');
                    var count = $(this).find('.pizza-order-card').length;
                    $(this).find('.pizza-order-count').text(count);
                });
            }, 300);

            // Refresh to get updated buttons
            setTimeout(function() {
                location.reload();
            }, 500);
        },

        /**
         * Play new order sound
         */
        playNewOrderSound: function() {
            if (!$('#pizza-sound-alerts').is(':checked')) {
                return;
            }

            var audio = document.getElementById('pizza-new-order-sound');
            if (audio) {
                audio.play().catch(function(e) {
                    console.log('[Pizza Kitchen] Sound playback failed:', e);
                });
            }
        },

        /**
         * Print order
         */
        printOrder: function(orderId) {
            var $card = $('.pizza-order-card[data-order-id="' + orderId + '"]');
            if ($card.length === 0) {
                return;
            }

            var printContent = this.generatePrintContent($card);
            var printWindow = window.open('', '_blank', 'width=400,height=600');
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };
        },

        /**
         * Generate print content for order
         */
        generatePrintContent: function($card) {
            var orderNumber = $card.find('.pizza-order-number').text();
            var orderTime = $card.find('.pizza-order-time').text();
            var orderType = $card.find('.pizza-type-delivery, .pizza-type-pickup').text();
            var customer = $card.find('.pizza-order-customer').html();
            var items = [];
            
            $card.find('.pizza-item').each(function() {
                var $item = $(this);
                items.push({
                    name: $item.find('.pizza-item-name').text(),
                    details: $item.find('.pizza-item-details').text(),
                    instructions: $item.find('.pizza-instructions').text()
                });
            });

            var notes = $card.find('.pizza-order-notes').text();

            var html = '<!DOCTYPE html><html><head>';
            html += '<meta charset="UTF-8">';
            html += '<title>Order ' + orderNumber + '</title>';
            html += '<style>';
            html += 'body { font-family: monospace; padding: 10px; max-width: 300px; margin: 0 auto; }';
            html += '.header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }';
            html += '.order-number { font-size: 24px; font-weight: bold; }';
            html += '.order-type { font-size: 18px; padding: 5px 10px; background: #000; color: #fff; display: inline-block; margin: 10px 0; }';
            html += '.customer { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #000; }';
            html += '.item { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dotted #ccc; }';
            html += '.item-name { font-weight: bold; font-size: 16px; }';
            html += '.item-details { margin-left: 10px; font-size: 12px; }';
            html += '.instructions { background: #ffff99; padding: 5px; margin-top: 5px; font-style: italic; }';
            html += '.notes { background: #ffff99; padding: 10px; margin-top: 10px; }';
            html += '.footer { text-align: center; margin-top: 20px; font-size: 10px; }';
            html += '</style>';
            html += '</head><body>';
            
            html += '<div class="header">';
            html += '<div class="order-number">' + orderNumber + '</div>';
            html += '<div>' + orderTime + '</div>';
            html += '<div class="order-type">' + orderType + '</div>';
            html += '</div>';
            
            html += '<div class="customer">' + customer + '</div>';
            
            html += '<div class="items">';
            for (var i = 0; i < items.length; i++) {
                html += '<div class="item">';
                html += '<div class="item-name">' + items[i].name + '</div>';
                if (items[i].details) {
                    html += '<div class="item-details">' + items[i].details + '</div>';
                }
                if (items[i].instructions) {
                    html += '<div class="instructions">' + items[i].instructions + '</div>';
                }
                html += '</div>';
            }
            html += '</div>';
            
            if (notes) {
                html += '<div class="notes"><strong>NOTES:</strong> ' + notes + '</div>';
            }
            
            html += '<div class="footer">Printed: ' + new Date().toLocaleString() + '</div>';
            html += '</body></html>';
            
            return html;
        }
    };

    /**
     * Spinning animation
     */
    $('<style>')
        .prop('type', 'text/css')
        .html('.dashicons.spin { animation: dashicons-spin 1s linear infinite; } @keyframes dashicons-spin { 100% { transform: rotate(360deg); } }')
        .appendTo('head');

    /**
     * Print button handler
     */
    $(document).on('click', '.pizza-print-btn', function() {
        var orderId = $(this).closest('.pizza-order-card').data('order-id');
        PizzaKitchen.printOrder(orderId);
    });

    /**
     * Media Uploader for Pizza Components (toppings, sauces, bases)
     */
    var PizzaMediaUploader = {
        frame: null,
        
        init: function() {
            var self = this;
            
            // Upload button click
            $(document).on('click', '.pizza-upload-image', function(e) {
                e.preventDefault();
                var targetId = $(this).data('target');
                self.openUploader(targetId, $(this));
            });
            
            // Remove button click
            $(document).on('click', '.pizza-remove-image', function(e) {
                e.preventDefault();
                var targetId = $(this).data('target');
                self.removeImage(targetId, $(this));
            });
        },
        
        openUploader: function(targetId, $button) {
            var self = this;
            
            // If frame exists, reopen it
            if (this.frame) {
                this.frame.open();
                return;
            }
            
            // Create media frame
            this.frame = wp.media({
                title: 'Vælg billede',
                button: {
                    text: 'Brug dette billede'
                },
                multiple: false
            });
            
            // When image selected
            this.frame.on('select', function() {
                var attachment = self.frame.state().get('selection').first().toJSON();
                
                // Update hidden field
                $('#' + targetId).val(attachment.id);
                
                // Update preview
                var $preview = $button.closest('.pizza-media-upload').find('.pizza-image-preview');
                $preview.html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 100px; height: auto; border-radius: 8px;">');
                
                // Update button text
                $button.text('Skift billede');
                
                // Add remove button if not exists
                if ($button.siblings('.pizza-remove-image').length === 0) {
                    $button.after('<button type="button" class="button pizza-remove-image" data-target="' + targetId + '">Fjern billede</button>');
                }
            });
            
            this.frame.open();
        },
        
        removeImage: function(targetId, $button) {
            // Clear hidden field
            $('#' + targetId).val('');
            
            // Clear preview
            $button.closest('.pizza-media-upload').find('.pizza-image-preview').html('');
            
            // Update upload button text
            $button.siblings('.pizza-upload-image').text('Upload billede');
            
            // Remove the remove button
            $button.remove();
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        PizzaKitchen.init();
        PizzaMediaUploader.init();
    });

})(jQuery);
