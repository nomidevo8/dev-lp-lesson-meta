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
