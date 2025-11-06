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
    }

    private function init_admin() {
        // load admin assets manager
        add_action( 'admin_enqueue_scripts', array( 'DevLPLessonMeta\\Admin\\Assets', 'enqueue' ) );

        // meta box manager
        add_action( 'add_meta_boxes', array( 'DevLPLessonMeta\\Admin\\MetaBox', 'add_meta_boxes' ) );
        add_action( 'save_post', array( 'DevLPLessonMeta\\Admin\\MetaBox', 'save_post' ), 10, 2 );
    }
}
