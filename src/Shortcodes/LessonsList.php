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

    public static function render_OLD( $atts ) {

        global $wpdb;

        // Table name
        $table_name = $wpdb->prefix . 'learnpress_courses';

        // 1️⃣ Get table structure
        $table_structure = $wpdb->get_results( "DESCRIBE $table_name" );
        echo '<h2>Table Structure</h2>';
        echo '<pre>';
        print_r( $table_structure );
        echo '</pre>';

        // 2️⃣ Get all data from table
        $table_data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
        echo '<h2>All Table Data</h2>';
        echo '<pre>';
        print_r( $table_data );
        echo '</pre>';

        die;    
        $atts = shortcode_atts( [
            'limit' => 10,
            'order' => 'DESC',
        ], $atts, 'lp_lessons_list' );

        // -----------------------
        // Fetch Lessons
        // -----------------------
        $lesson_query = new \WP_Query( [
            'post_type'      => Keys::LESSON_POST_TYPE,
            'posts_per_page' => intval( $atts['limit'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
            'post_status'    => 'publish',
        ] );

        echo '<h2>Lesson Posts</h2>';
        if ( $lesson_query->have_posts() ) {
            foreach ( $lesson_query->posts as $lesson ) {
                // Get all meta fields dynamically
                $lesson_meta = get_post_meta( $lesson->ID );
                
                // Optional: convert arrays to single values
                $lesson_meta_single = [];
                foreach ( $lesson_meta as $key => $values ) {
                    $lesson_meta_single[ $key ] = maybe_unserialize( $values[0] );
                }

                $data = [
                    'post' => $lesson,
                    'meta' => $lesson_meta_single,
                ];

                echo '<pre>';
                print_r( $data );
                echo '</pre>';
            }
        } else {
            echo '<p>No lessons found.</p>';
        }

        // -----------------------
        // Fetch Courses
        // -----------------------
        $course_query = new \WP_Query( [
            'post_type'      => Keys::COURSE_POST_TYPE,
            'posts_per_page' => intval( $atts['limit'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
            'post_status'    => 'publish',
        ] );

        echo '<h2>Course Posts</h2>';
        if ( $course_query->have_posts() ) {
            foreach ( $course_query->posts as $course ) {
                // Get all meta fields dynamically
                $course_meta = get_post_meta( $course->ID );

                $course_meta_single = [];
                foreach ( $course_meta as $key => $values ) {
                    $course_meta_single[ $key ] = maybe_unserialize( $values[0] );
                }

                $data = [
                    'post' => $course,
                    'meta' => $course_meta_single,
                ];

                echo '<pre>';
                print_r( $data );
                echo '</pre>';
            }
        } else {
            echo '<p>No courses found.</p>';
        }

        wp_reset_postdata();

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

    public static function render_old2( $atts ) {
        global $wpdb;

        $atts = shortcode_atts([
            'limit' => 10,
            'order' => 'DESC',
        ], $atts, 'lp_lessons_list');

        $table_name = $wpdb->prefix . 'learnpress_courses';

        // 1️⃣ Get courses from table
        $courses = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY ID {$atts['order']} LIMIT " . intval($atts['limit']),
            ARRAY_A
        );

        if (empty($courses)) {
            return '<p>No courses found in table.</p>';
        }

        $sections = [];

        // 2️⃣ Loop through courses and parse sections
        foreach ($courses as $course) {
            $course_json = $course['json'] ?? '';
            if (!$course_json) continue;

            $course_data = json_decode($course_json, true);
            if (empty($course_data['sections_items'])) continue;

            foreach ($course_data['sections_items'] as $section) {
                $section_name = $section['section_name'] ?? $section['title'] ?? 'Unnamed Section';

                if (!isset($sections[$section_name])) {
                    $sections[$section_name] = [
                        'section_title' => $section_name,
                        'lessons' => [],
                    ];
                }

                foreach ($section['items'] as $item) {
                    if ($item['item_type'] === 'lp_lesson') {
                        $lesson_id = $item['item_id'];
                        $lesson_post = get_post($lesson_id);
                        $lesson_meta = get_post_meta($lesson_id);

                        $sections[$section_name]['lessons'][] = [
                            'post' => $lesson_post,
                            'meta' => $lesson_meta,
                            'course_title' => $course['post_title'], // optional
                        ];
                    }
                }
            }
        }

        // 3️⃣ Render accordion
        ob_start();
        echo '<div class="lp-course-sections-accordion">';

        foreach ($sections as $section) {
            echo '<div class="lp-section">';
            echo '<h3 class="lp-section-title">' . esc_html($section['section_title']) . '</h3>';
            echo '<div class="lp-section-lessons">';

            foreach ($section['lessons'] as $lesson) {
                $lesson_title = $lesson['post']->post_title ?? 'No Title';
                $lesson_price = $lesson['meta']['_lp_lesson_price'][0] ?? '';
                $lesson_date  = $lesson['meta']['_lp_lesson_date'][0] ?? '';
                $lesson_start = $lesson['meta']['_lp_lesson_start_time'][0] ?? '';
                $lesson_end   = $lesson['meta']['_lp_lesson_end_time'][0] ?? '';
                $course_title = $lesson['course_title'];

                echo '<div class="lp-lesson-item">';
                echo '<strong>' . esc_html($lesson_title) . '</strong>';
                echo ' (' . esc_html($course_title) . ')<br>';
                echo 'Price: ' . esc_html($lesson_price) . ' | ';
                echo 'Date: ' . esc_html($lesson_date) . ' | ';
                echo 'Start: ' . esc_html($lesson_start) . ' | ';
                echo 'End: ' . esc_html($lesson_end);
                echo '</div>';
            }

            echo '</div>'; // .lp-section-lessons
            echo '</div>'; // .lp-section
        }

        echo '</div>'; // .lp-course-sections-accordion

        return ob_get_clean();
    }

    
    public static function render( $atts ) {
        global $wpdb;

        $atts = shortcode_atts([
            'limit' => 10,
            'order' => 'DESC',
        ], $atts, 'lp_lessons_list');

        $table_name = $wpdb->prefix . 'learnpress_courses';

        // 1️⃣ Get courses from table
        $courses = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY ID {$atts['order']} LIMIT " . intval($atts['limit']),
            ARRAY_A
        );

        if (empty($courses)) {
            return '<p>No courses found in table.</p>';
        }

        $sections = [];

        // 2️⃣ Loop through courses and parse sections
        foreach ($courses as $course) {
            $course_json = $course['json'] ?? '';
            if (!$course_json) continue;

            $course_data = json_decode($course_json, true);
            if (empty($course_data['sections_items'])) continue;

            foreach ($course_data['sections_items'] as $section) {
                $section_name = $section['section_name'] ?? $section['title'] ?? 'Unnamed Section';

                if (!isset($sections[$section_name])) {
                    $sections[$section_name] = [
                        'section_title' => $section_name,
                        'lessons' => [],
                    ];
                }

                foreach ($section['items'] as $item) {
                    if ($item['item_type'] === 'lp_lesson') {
                        $lesson_id = $item['item_id'];
                        $lesson_post = get_post($lesson_id);
                        $lesson_meta = get_post_meta($lesson_id);

                        $sections[$section_name]['lessons'][] = [
                            'post' => $lesson_post,
                            'meta' => $lesson_meta,
                            'course_title' => $course['post_title'], // optional
                        ];
                    }
                }
            }
        }

        // 3️⃣ Render Bootstrap Accordion
        ob_start();

        $accordion_id = 'lpCourseAccordion_' . rand(1000, 9999);
        echo '<div class="accordion" id="' . esc_attr($accordion_id) . '">';

        $i = 0;
        foreach ($sections as $section_name => $section) {
            $collapse_id = $accordion_id . '_collapse_' . $i;
            $heading_id  = $accordion_id . '_heading_' . $i;
            ?>

            <div class="accordion-item">
                <h2 class="accordion-header" id="<?php echo esc_attr($heading_id); ?>">
                    <button class="accordion-button <?php echo ($i > 0 ? 'collapsed' : ''); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr($collapse_id); ?>" aria-expanded="<?php echo ($i === 0 ? 'true' : 'false'); ?>" aria-controls="<?php echo esc_attr($collapse_id); ?>">
                        <?php echo esc_html($section['section_title']); ?>
                    </button>
                </h2>
                <div id="<?php echo esc_attr($collapse_id); ?>" class="accordion-collapse collapse <?php echo ($i === 0 ? 'show' : ''); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>" data-bs-parent="#<?php echo esc_attr($accordion_id); ?>">
                    <div class="accordion-body">
                        <?php foreach ($section['lessons'] as $lesson): 
                            $lesson_title = $lesson['post']->post_title ?? 'No Title';
                            $lesson_price = $lesson['meta']['_lp_lesson_price'][0] ?? '';
                            $lesson_date  = $lesson['meta']['_lp_lesson_date'][0] ?? '';
                            $lesson_start = $lesson['meta']['_lp_lesson_start_time'][0] ?? '';
                            $lesson_end   = $lesson['meta']['_lp_lesson_end_time'][0] ?? '';
                            $course_title = $lesson['course_title'];
                        ?>
                            <div class="lp-lesson-item mb-2">
                                <strong><?php echo esc_html($lesson_title); ?></strong>
                                (<?php echo esc_html($course_title); ?>)<br>
                                Price: <?php echo esc_html($lesson_price); ?> |
                                Date: <?php echo esc_html($lesson_date); ?> |
                                Start: <?php echo esc_html($lesson_start); ?> |
                                End: <?php echo esc_html($lesson_end); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php
            $i++;
        }

        echo '</div>'; // end accordion

        return ob_get_clean();
    }


}

// Register shortcode
add_action( 'init', [ __NAMESPACE__ . '\\LessonsList', 'register' ] );
