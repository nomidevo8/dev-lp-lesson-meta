<?php
namespace DevLPLessonMeta\Shortcodes;

use WP_Query;
use DevLPLessonMeta\Meta\Keys;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LessonsList {

    public static function register() {
        add_shortcode( 'lp_lessons_list', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( [
            'limit' => 10,
            'order' => 'DESC',
        ], $atts, 'lp_lessons_list' );

        $query = new WP_Query( [
            'post_type'      => Keys::POST_TYPE,
            'posts_per_page' => intval( $atts['limit'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
            'post_status'    => 'publish',
        ] );

        if ( ! $query->have_posts() ) {
            return '<p>No lessons found.</p>';
        }

        
        // Get all posts returned by the query
        $all_posts = $query->posts;

        // Print all data
        echo '<pre>';
        print_r( $all_posts );
        echo '</pre>';
        die;
        
        ob_start();
        echo '<div class="lp-lessons-list">';
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_data = get_post();

            // Get all post meta for this post
            $all_meta = get_post_meta( $post_data->ID );

            // Combine post data and meta
            $lesson_data = [
                'post' => $post_data,
                'meta' => $all_meta,
            ];

            // Print it nicely
            echo '<pre>';
            print_r( $lesson_data );
            echo '</pre>';
            // $price = get_post_meta( get_the_ID(), Keys::PRICE, true );
            // $date  = get_post_meta( get_the_ID(), Keys::DATE, true );
            // $start = get_post_meta( get_the_ID(), Keys::START_TIME, true );
            // $end   = get_post_meta( get_the_ID(), Keys::END_TIME, true );

            // echo '<div class="lesson-item">';
            // echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
            // echo '<ul>';
            // echo '<li><strong>Price:</strong> ' . esc_html( $price ) . '</li>';
            // echo '<li><strong>Date:</strong> ' . esc_html( $date ) . '</li>';
            // echo '<li><strong>Start:</strong> ' . esc_html( $start ) . '</li>';
            // echo '<li><strong>End:</strong> ' . esc_html( $end ) . '</li>';
            // echo '</ul>';
            // echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }
}

// Register shortcode
add_action( 'init', [ __NAMESPACE__ . '\\LessonsList', 'register' ] );
