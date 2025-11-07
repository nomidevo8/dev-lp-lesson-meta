<?php
namespace DevLPLessonMeta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {
    /** Singleton instance */
    private static $instance = null;

    /** Plugin constants */
    public $dir;
    public $url;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->setup();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function setup() {
        $this->dir = plugin_dir_path( __FILE__ );
        $this->url = plugin_dir_url( dirname( __FILE__, 2 ) );

        // Register post meta on init
        add_action( 'init', array( $this, 'register_meta' ) );

        // Admin only classes
        if ( is_admin() ) {
            $this->init_admin();
        }

        // Initialize shortcodes
        $this->init_shortcodes();
        $this->init_woocommerce();
        // Initialize admin columns
        $this->init_admin_columns();
    }

    public function register_meta() {
        $args = array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'auth_callback'=> function() {
                return current_user_can( 'edit_posts' );
            },
        );

        register_post_meta( 'lp_lesson', Meta\Keys::PRICE, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::DATE, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::START_TIME, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::END_TIME, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::SLOTS, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::SYNC_COURSE, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::TEACHER, $args );
        register_post_meta( 'lp_lesson', Meta\Keys::LOCATION, $args );
    }

    private function init_admin() {
        // load admin assets manager
        add_action( 'admin_enqueue_scripts', array( 'DevLPLessonMeta\\Admin\\Assets', 'enqueue' ) );

        // meta box manager
        add_action( 'add_meta_boxes', array( 'DevLPLessonMeta\\Admin\\MetaBox', 'add_meta_boxes' ) );
        add_action( 'save_post', array( 'DevLPLessonMeta\\Admin\\MetaBox', 'save_post' ), 10, 2 );
    }

    private function init_shortcodes() {
        // Check if the shortcode class exists, then register it
        if ( class_exists( '\\DevLPLessonMeta\\Shortcodes\\LessonsList' ) ) {
            \DevLPLessonMeta\Shortcodes\LessonsList::register();
        }
    }

    private function init_woocommerce(){
        if ( class_exists( '\\DevLPLessonMeta\\WooCommerce\\CheckoutHandler' ) ) {
            \DevLPLessonMeta\WooCommerce\CheckoutHandler::register();
        }
    }

    private function init_admin_columns() {
        if ( class_exists( '\\DevLPLessonMeta\\Admin\\LessonsColumns' ) ) {
            \DevLPLessonMeta\Admin\LessonsColumns::init();
        }
    }
}
