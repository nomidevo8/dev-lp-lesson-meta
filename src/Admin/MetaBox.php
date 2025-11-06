<?php
namespace DevLPLessonMeta\Admin;

use DevLPLessonMeta\Meta\Keys;
use DevLPLessonMeta\Helpers\Validator;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MetaBox {

    public static function add_meta_boxes() {
        add_meta_box(
            'dev_lp_lesson_manager_meta',
            __( 'Lesson Details', 'dev-lp-lesson-meta' ),
            array( __CLASS__, 'render' ),
            Keys::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render( \WP_Post $post ) {
        $price = get_post_meta( $post->ID, Keys::PRICE, true );
        $date  = get_post_meta( $post->ID, Keys::DATE, true );
        $st    = get_post_meta( $post->ID, Keys::START_TIME, true );
        $et    = get_post_meta( $post->ID, Keys::END_TIME, true );

        wp_nonce_field( Keys::NONCE_ACTION, Keys::NONCE_NAME );
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="lp_lesson_price"><?php esc_html_e( 'Price', 'dev-lp-lesson-meta' ); ?></label></th>
                    <td>
                        <input type="number" step="0.01" min="0" name="lp_lesson_price" id="lp_lesson_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Leave empty for free lessons.', 'dev-lp-lesson-meta' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="lp_lesson_date"><?php esc_html_e( 'Date', 'dev-lp-lesson-meta' ); ?></label></th>
                    <td>
                        <input type="date" name="lp_lesson_date" id="lp_lesson_date" value="<?php echo esc_attr( $date ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'YYYY-MM-DD', 'dev-lp-lesson-meta' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="lp_lesson_start_time"><?php esc_html_e( 'Start Time', 'dev-lp-lesson-meta' ); ?></label></th>
                    <td>
                        <input type="time" name="lp_lesson_start_time" id="lp_lesson_start_time" value="<?php echo esc_attr( $st ); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr>
                    <th><label for="lp_lesson_end_time"><?php esc_html_e( 'End Time', 'dev-lp-lesson-meta' ); ?></label></th>
                    <td>
                        <input type="time" name="lp_lesson_end_time" id="lp_lesson_end_time" value="<?php echo esc_attr( $et ); ?>" class="regular-text" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function save_post( $post_id, $post ) {
        // Only handle our post type
        if ( empty( $post ) || $post->post_type !== Keys::POST_TYPE ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST[ Keys::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ Keys::NONCE_NAME ] ) ), Keys::NONCE_ACTION ) ) {
            return;
        }

        // Autosave / revision
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Price
        if ( isset( $_POST['lp_lesson_price'] ) ) {
            $raw = trim( wp_unslash( $_POST['lp_lesson_price'] ) );
            $price = $raw === '' ? '' : floatval( str_replace( ',', '.', $raw ) );
            if ( $price === '' ) {
                delete_post_meta( $post_id, Keys::PRICE );
            } else {
                update_post_meta( $post_id, Keys::PRICE, $price );
            }
        }

        // Date
        if ( isset( $_POST['lp_lesson_date'] ) ) {
            $date = sanitize_text_field( wp_unslash( $_POST['lp_lesson_date'] ) );
            if ( Validator::is_valid_date( $date ) ) {
                update_post_meta( $post_id, Keys::DATE, $date );
            } else {
                delete_post_meta( $post_id, Keys::DATE );
            }
        }

        // Start time
        if ( isset( $_POST['lp_lesson_start_time'] ) ) {
            $st = sanitize_text_field( wp_unslash( $_POST['lp_lesson_start_time'] ) );
            if ( Validator::is_valid_time( $st ) ) {
                update_post_meta( $post_id, Keys::START_TIME, $st );
            } else {
                delete_post_meta( $post_id, Keys::START_TIME );
            }
        }

        // End time
        if ( isset( $_POST['lp_lesson_end_time'] ) ) {
            $et = sanitize_text_field( wp_unslash( $_POST['lp_lesson_end_time'] ) );
            if ( Validator::is_valid_time( $et ) ) {
                update_post_meta( $post_id, Keys::END_TIME, $et );
            } else {
                delete_post_meta( $post_id, Keys::END_TIME );
            }
        }
    }
}
