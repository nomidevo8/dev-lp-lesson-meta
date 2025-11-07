<?php
namespace DevLPLessonMeta\WooCommerce;

use DevLPLessonMeta\Meta\Keys;

if (!defined('ABSPATH')) {
    exit;
}

class CheckoutHandler {

    /**
     * Register hooks
     */
    public static function register() {
        add_action('wp_ajax_dev_lp_create_lesson_cart', [CheckoutHandler::class, 'ajax_create_lesson_cart']);
        add_action('wp_ajax_nopriv_dev_lp_create_lesson_cart', [CheckoutHandler::class, 'ajax_create_lesson_cart']);

        // Make our virtual product always purchasable site-wide
        add_filter('woocommerce_is_purchasable', [CheckoutHandler::class, 'force_purchasable'], 10, 2);

        // Show lesson meta in cart and checkout
        add_filter('woocommerce_get_item_data', [CheckoutHandler::class, 'display_lesson_meta_in_cart'], 10, 2);

        // Show lesson name instead of generic product title
        add_filter('woocommerce_cart_item_name', [CheckoutHandler::class, 'custom_cart_item_name'], 10, 3);

        // Ensure dynamic prices are applied correctly
        add_action('woocommerce_before_calculate_totals', [CheckoutHandler::class, 'apply_dynamic_prices'], 20);

        // Customize checkout fields
        add_filter('woocommerce_checkout_fields', [CheckoutHandler::class, 'dev_woocommerce_checkout_fields'], 999);
        add_action('woocommerce_checkout_create_order_line_item', [CheckoutHandler::class, 'add_lesson_id_to_order_item'], 10, 4);


        // Reduce lesson slots after successful order
        add_action('woocommerce_thankyou', [CheckoutHandler::class, 'reduce_lesson_slots']);

        // Reduce slots for cash on delivery immediately
        add_action('woocommerce_checkout_order_processed', [CheckoutHandler::class, 'reduce_slots_cod']);

        // Reduce slots for all other payments when order is completed
        add_action('woocommerce_order_status_completed', [CheckoutHandler::class, 'reduce_lesson_slots']);
    }

    /**
     * Make our "Lesson Booking" product always purchasable
     */
    public static function force_purchasable($purchasable, $product) {
        $stored_id = (int) get_option('dev_lp_virtual_product_id');
        if ($stored_id && $product->get_id() === $stored_id) {
            return true;
        }
        return $purchasable;
    }

    /**
     * AJAX handler to create cart from selected lessons
     */
    public static function ajax_create_lesson_cart() {
        check_ajax_referer('dev_lp_nonce', 'nonce');

        if (!function_exists('WC')) {
            wp_send_json_error('WooCommerce not available.');
        }

        $lesson_ids = isset($_POST['lesson_ids']) ? array_map('intval', (array) $_POST['lesson_ids']) : [];
        if (empty($lesson_ids)) {
            wp_send_json_error('No lessons selected.');
        }

        // Empty existing cart
        WC()->cart->empty_cart();

        // Retrieve or create the virtual product
        $product_id = self::get_virtual_product_id();
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_purchasable()) {
            wp_send_json_error("Product (ID {$product_id}) not purchasable or invalid.");
        }

        foreach ($lesson_ids as $lesson_id) {
            $title     = get_the_title($lesson_id);
            $price     = (float) get_post_meta($lesson_id, Keys::PRICE, true);
            $date_raw  = get_post_meta($lesson_id, Keys::DATE, true);
            $start_raw = get_post_meta($lesson_id, Keys::START_TIME, true);
            $end_raw   = get_post_meta($lesson_id, Keys::END_TIME, true);
            $teacher   = get_post_meta($lesson_id, Keys::TEACHER, true);
            $location  = get_post_meta($lesson_id, Keys::LOCATION, true);

            $date_formatted = $date_raw ? date('l, F j, Y', strtotime($date_raw)) : '';

            $format_time = function($time_str) {
                $timestamp = strtotime($time_str);
                return $timestamp ? date('g:i A', $timestamp) : $time_str;
            };

            $start_formatted = $start_raw ? $format_time($start_raw) : '';
            $end_formatted   = $end_raw ? $format_time($end_raw) : '';

            $cart_item_data = [
                'lesson_id'       => $lesson_id,
                'lesson_title'    => $title,
                'lesson_date'     => $date_formatted,
                'lesson_start'    => $start_formatted,
                'lesson_end'      => $end_formatted,
                'lesson_teacher'  => $teacher,
                'lesson_location' => $location,
            ];

            WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
        }

        wp_send_json_success(['checkout_url' => wc_get_checkout_url()]);
    }

    /**
     * Retrieve or create a virtual product used for lessons
     */
    private static function get_virtual_product_id() {
        $option_key = 'dev_lp_virtual_product_id';
        $product_id = (int) get_option($option_key);

        if ($product_id && get_post_status($product_id) === 'publish') {
            return $product_id;
        }

        $product = new \WC_Product_Simple();
        $product->set_name('Course Booking');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_regular_price(1);
        $product->save();

        $product_id = $product->get_id();
        update_option($option_key, $product_id);

        return $product_id;
    }

    /**
     * Display lesson meta in cart and checkout
     */
    public static function display_lesson_meta_in_cart($item_data, $cart_item) {
        if (!empty($cart_item['lesson_id'])) {
            if (!empty($cart_item['lesson_title'])) {
                $item_data[] = ['key' => __('Lesson', 'dev-lp-lesson-meta'), 'value' => esc_html($cart_item['lesson_title'])];
            }
            if (!empty($cart_item['lesson_date'])) {
                $item_data[] = ['key' => __('Date', 'dev-lp-lesson-meta'), 'value' => esc_html($cart_item['lesson_date'])];
            }
            if (!empty($cart_item['lesson_start']) && !empty($cart_item['lesson_end'])) {
                $item_data[] = ['key' => __('Time', 'dev-lp-lesson-meta'), 'value' => esc_html("{$cart_item['lesson_start']} - {$cart_item['lesson_end']}")];
            }
            if (!empty($cart_item['lesson_teacher'])) {
                $item_data[] = ['key' => __('Teacher', 'dev-lp-lesson-meta'), 'value' => esc_html($cart_item['lesson_teacher'])];
            }
            if (!empty($cart_item['lesson_location'])) {
                $item_data[] = ['key' => __('Location', 'dev-lp-lesson-meta'), 'value' => esc_html($cart_item['lesson_location'])];
            }
        }
        return $item_data;
    }

    /**
     * Apply correct lesson price to cart items
     */
    public static function apply_dynamic_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['lesson_id'])) {
                $price = (float) get_post_meta($cart_item['lesson_id'], Keys::PRICE, true);
                if ($price > 0) {
                    $cart_item['data']->set_price($price);
                }
            }
        }
    }

    /**
     * Customize cart item name with lesson title
     */
    public static function custom_cart_item_name($product_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['lesson_title'])) {
            $date = isset($cart_item['lesson_date']) ? ' (' . esc_html($cart_item['lesson_date']) . ')' : '';
            return esc_html($cart_item['lesson_title'] . $date);
        }
        return $product_name;
    }

    /**
     * Customize WooCommerce checkout fields
     */
    public static function dev_woocommerce_checkout_fields($fields) {
        $keep = ['billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone'];
        foreach ($fields['billing'] as $key => $field) {
            if (!in_array($key, $keep)) unset($fields['billing'][$key]);
        }
        $fields['shipping'] = [];
        if (isset($fields['order']['order_comments'])) unset($fields['order']['order_comments']);
        return $fields;
    }

    public static function add_lesson_id_to_order_item($item, $cart_item_key, $values, $order) {
        if (!empty($values['lesson_id'])) {
            $item->add_meta_data('lesson_id', $values['lesson_id'], true);
        }
        if (!empty($values['lesson_title'])) {
            $item->add_meta_data('lesson_title', $values['lesson_title'], true);
        }
    }

    /**
     * Reduce slots immediately for COD orders
     */
    public static function reduce_slots_cod($order_id, $posted_data, $order) {
        if (!$order) return;

        if ($order->get_payment_method() === 'cod') {
            // Call your existing reduce_lesson_slots logic
            self::reduce_lesson_slots($order_id);
        }
    }


    /**
     * Reduce lesson slots after successful order
     */
    public static function reduce_lesson_slots($order_id) {
        error_log("This is triggered");
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if slots were already reduced
        if ($order->get_meta('_slots_reduced')) {
            return;
        }


        foreach ($order->get_items() as $item) {
            $lesson_id = $item->get_meta('lesson_id');
            $quantity  = $item->get_quantity();

            if ($lesson_id) {
                $slots = (int) get_post_meta($lesson_id, Keys::SLOTS, true);
                $slots_before = $slots;
                $slots -= $quantity;
                update_post_meta($lesson_id, Keys::SLOTS, max(0, $slots));

                // Optionally mark full
                if ($slots <= 0) {
                    update_post_meta($lesson_id, '_is_full', 'yes');
                }

            }
        }

        // Mark order as slots reduced
        $order->update_meta_data('_slots_reduced', 'yes');
        $order->save();
    }


}
