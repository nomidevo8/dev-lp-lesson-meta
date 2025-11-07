<?php
/**
 * Plugin Name: Dev LP Lesson Meta
 * Description: OOP plugin to add Price, Date, Start Time and End Time meta boxes to LearnPress lessons (post type: lp_lesson). Clean multi-file structure, REST-ready meta and optional admin assets.
 * Version: 1.0.0
 * Author: Serve Tech
 * Text Domain: dev-lp-lesson-meta
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Minimal autoload for src/ classes
spl_autoload_register( function( $class ) {
    $prefix = 'DevLPLessonMeta\\';
    $base_dir = __DIR__ . '/src/';

    // only autoload our namespace
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative = str_replace( $prefix, '', $class );
    $file = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Bootstrap
add_action( 'plugins_loaded', function() {
    DevLPLessonMeta\Plugin::instance();
} );


/**
 * Enqueue Bootstrap 5 for Lessons Accordion
 */
function devlp_enqueue_bootstrap() {
    // Bootstrap CSS
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        [],
        '5.3.2'
    );

    // Bootstrap JS Bundle (includes Popper)
    wp_enqueue_script(
        'bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.2',
        true
    );
    
    // Toaster CSS and Js
    wp_enqueue_style(
        'toastr-css',
        'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
        [],
        '2.1.4'
    );
    wp_enqueue_script(
        'toastr-js',
        'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
        [],
        '2.1.4',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'devlp_enqueue_bootstrap' );
