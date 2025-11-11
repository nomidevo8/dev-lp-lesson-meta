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
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function render() {
        ob_start();

        // Get all courses
        $courses = get_posts([
            'post_type'      => Keys::COURSE_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($courses)) {
            echo '<div class="alert alert-info">' . esc_html__('No courses found.', 'dev-lp-lesson-meta') . '</div>';
            return ob_get_clean();
        }
        ?>
        <div class="accordion" id="lpCoursesAccordion" style="width:100%;max-width:100%;">
            <?php foreach ($courses as $index => $course) :
                $course_id = $course->ID;

                // Get lessons for this course
                $lessons = get_posts([
                    'post_type'      => Keys::LESSON_POST_TYPE,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_key'       => Keys::SYNC_COURSE,
                    'meta_value'     => $course_id,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]);

                $collapse_id = 'collapse-' . $course_id;
                ?>
                <div class="accordion-item mb-3 border-0 shadow-sm">
                    <h2 class="accordion-header" id="heading-<?php echo esc_attr($course_id); ?>">
                        <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?php echo esc_attr($collapse_id); ?>"
                            aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr($collapse_id); ?>">
                            <?php echo esc_html($course->post_title); ?>
                        </button>
                    </h2>
                    <div id="<?php echo esc_attr($collapse_id); ?>"
                        class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                        aria-labelledby="heading-<?php echo esc_attr($course_id); ?>"
                        data-bs-parent="#lpCoursesAccordion">
                        <div class="accordion-body p-0">

                            <?php if (empty($lessons)) : ?>
                                <p class="text-muted"><?php esc_html_e('No lessons assigned to this course.', 'dev-lp-lesson-meta'); ?></p>
                            <?php else : ?>
                                <div class="table-responsive w-100">
                                    <table class="table table-bordered table-striped align-middle mb-0 w-100">
                                        <thead class="table-primary text-center">
                                            <tr>
                                                <th><?php esc_html_e('Date/Time', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Lesson Name', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Lesson Type', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Teacher', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Location', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Price', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Available Slots', 'dev-lp-lesson-meta'); ?></th>
                                                <th><?php esc_html_e('Action', 'dev-lp-lesson-meta'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lessons as $lesson) :
                                                $lesson_id = $lesson->ID;
                                                $price     = get_post_meta($lesson_id, Keys::PRICE, true);
                                                $date      = get_post_meta($lesson_id, Keys::DATE, true);
                                                $start     = get_post_meta($lesson_id, Keys::START_TIME, true);
                                                $end       = get_post_meta($lesson_id, Keys::END_TIME, true);
                                                $slots     = get_post_meta($lesson_id, Keys::SLOTS, true);
                                                $teacher   = get_post_meta($lesson_id, Keys::TEACHER, true);
                                                $location  = get_post_meta($lesson_id, Keys::LOCATION, true);
                                                // Combine date and start time into one proper ISO-like string
                                                $datetime_str = '';
                                                if ( $date && $start ) {
                                                    $datetime_str = date( 'Y-m-d H:i', strtotime( $date . ' ' . $start ) );
                                                } elseif ( $date ) {
                                                    $datetime_str = date( 'Y-m-d', strtotime( $date ) );
                                                }
                                                ?>
                                                <tr class="text-center lesson-row <?php echo $slots <= 0 ? 'lesson-full' : ''; ?>"
                                                    data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>"
                                                    data-date="<?php echo esc_attr( $datetime_str ); ?>"
                                                    data-price="<?php echo esc_attr( $price ); ?>"
                                                    data-title="<?php echo esc_attr( $lesson->post_title ); ?>"
                                                   data-course="<?php echo esc_attr(            $course->post_title ); ?>"
                                                    data-slots="<?php echo esc_attr( $slots ); ?>">

                                                    <td>
                                                        <?php
                                                        $formatted_date = $date ? date_i18n( 'M j, Y', strtotime( $date ) ) : '';
                                                        $formatted_start = $start ? date_i18n( 'g:i A', strtotime( $start ) ) : '';
                                                        $formatted_end   = $end ? date_i18n( 'g:i A', strtotime( $end ) ) : '';
                                                        echo esc_html( $formatted_date );
                                                        if ( $formatted_start || $formatted_end ) {
                                                            echo '<br><small class="text-muted">' . esc_html( trim( $formatted_start . ( $formatted_end ? ' – ' . $formatted_end : '' ) ) ) . '</small>';
                                                        }
                                                        ?>
                                                    </td>

                                                    <td><?php echo esc_html($lesson->post_title); ?></td>
                                                    <td><?php echo esc_html('M') . $index + 1; ?></td>
                                                    <td><?php echo esc_html($teacher); ?></td>
                                                    <td><?php echo esc_html($location); ?></td>
                                                    <td>
                                                        <?php
                                                        if ( $price ) {
                                                            $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
                                                            $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol( $currency ) : '';
                                                            echo esc_html( $currency_symbol . ' ' . $price );
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>

                                                    <td><?php echo esc_html($slots ?: '-'); ?></td>

                                                    <td>
                                                        <?php if ( $slots > 0 ) : ?>
                                                            <a class="btn btn-primary btn-sm rounded-pill">
                                                                <?php esc_html_e('Book Now', 'dev-lp-lesson-meta'); ?>
                                                            </a>
                                                        <?php else : ?>
                                                            <button class="btn btn-secondary btn-sm rounded-pill" disabled>
                                                                <?php esc_html_e('Full', 'dev-lp-lesson-meta'); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }   

    public static function enqueue_assets() {
        // Enqueue Bootstrap CSS and JS
        wp_enqueue_style(
            'lessons-list-responsive',
            plugin_dir_url( dirname( __DIR__ ) ) . 'src/Shortcodes/assets/css/lessons-list.css',
            [],
            '1.0.0343433344'
        );

        wp_enqueue_script(
            'lessons-list',
            plugin_dir_url( dirname( __DIR__ ) ) . 'src/Shortcodes/assets/js/lessons-list.js',
            [],
            '5.3.2343343345',
            true
        );

        
        wp_localize_script('lessons-list', 'devLesson', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dev_lp_nonce'),
        ]);

    }
}

// Register shortcode
add_action( 'init', [ __NAMESPACE__ . '\\LessonsList', 'register' ] );
