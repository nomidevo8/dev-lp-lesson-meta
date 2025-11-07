<?php
namespace DevLPLessonMeta\Admin;

use DevLPLessonMeta\Meta\Keys;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LessonsColumns {

    public static function init() {
        add_filter(
            'manage_' . Keys::LESSON_POST_TYPE . '_posts_columns',
            [ __CLASS__, 'add_columns' ],
            20 // Run after other plugins/themes
        );

        add_action(
            'manage_' . Keys::LESSON_POST_TYPE . '_posts_custom_column',
            [ __CLASS__, 'render_columns' ],
            10,
            2
        );
    }

    // Add new columns
    public static function add_columns($columns) {
        $columns_new = [
            'cb' => $columns['cb'], 
            'title' => $columns['title'], 
            'lesson_datetime' => __('Date/Time', 'dev-lp-lesson-meta'),
            'lesson_slots' => __('Available Slots', 'dev-lp-lesson-meta'),
            'lesson_course' => __('Assigned Course', 'dev-lp-lesson-meta'),
            'lesson_teacher' => __('Teacher', 'dev-lp-lesson-meta'),
            'lesson_price' => __('Price', 'dev-lp-lesson-meta'),
            'lesson_location' => __('Location', 'dev-lp-lesson-meta'),
        ];

        return $columns_new;
    }

    // Render content for custom columns
    public static function render_columns($column, $post_id) {
        switch ($column) {
            case 'lesson_datetime':
                $date  = get_post_meta($post_id, Keys::DATE, true);
                $start = get_post_meta($post_id, Keys::START_TIME, true);
                $end   = get_post_meta($post_id, Keys::END_TIME, true);

                if ($date) {
                    $formatted_date = date_i18n('M j, Y', strtotime($date));
                    $formatted_start = $start ? date_i18n('g:i A', strtotime($start)) : '';
                    $formatted_end   = $end ? date_i18n('g:i A', strtotime($end)) : '';
                    echo esc_html($formatted_date);
                    if ($formatted_start || $formatted_end) {
                        echo '<br><small class="text-muted">' . esc_html(trim($formatted_start . ($formatted_end ? ' – ' . $formatted_end : ''))) . '</small>';
                    }
                } else {
                    echo '-';
                }
                break;

            case 'lesson_slots':
                $slots = get_post_meta($post_id, Keys::SLOTS, true);
                echo $slots ?: '-';
                break;

            case 'lesson_course':
                $course_id = get_post_meta($post_id, Keys::SYNC_COURSE, true);
                if ($course_id) {
                    $course = get_post($course_id);
                    if ($course) {
                        echo esc_html($course->post_title);
                    } else {
                        echo '-';
                    }
                } else {
                    echo '-';
                }
                break;

            case 'lesson_teacher':
                $teacher = get_post_meta($post_id, Keys::TEACHER, true);
                echo $teacher ?: '-';
                break;

            case 'lesson_price':
                $price = get_post_meta($post_id, Keys::PRICE, true);
                if ($price) {
                    $currency = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
                    echo esc_html($currency . ' ' . $price);
                } else {
                    echo '-';
                }
                break;

            case 'lesson_location':
                $location = get_post_meta($post_id, Keys::LOCATION, true);
                echo $location ?: '-';
                break;
        }
    }
}

// Initialize columns
add_action('init', [ __NAMESPACE__ . '\\LessonsColumns', 'init' ]);
