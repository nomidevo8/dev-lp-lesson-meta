<?php
namespace DevLPLessonMeta\Admin;

use DevLPLessonMeta\Meta\Keys;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Assets {
    public static function enqueue( $hook ) {
        global $post;

        // Only on lp_lesson edit screens
        if ( ! isset( $post ) || $post->post_type !== Keys::POST_TYPE ) {
            return;
        }

        // Admin CSS for spacing (tiny)
        wp_register_style( 'dev_lp_lesson_manager_admin', plugins_url( '../assets/admin.css', __FILE__ ) );
        wp_enqueue_style( 'dev_lp_lesson_manager_admin' );
    }
}
