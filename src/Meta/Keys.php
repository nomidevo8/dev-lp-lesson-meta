<?php
namespace DevLPLessonMeta\Meta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Keys {
    const PRICE = '_lp_lesson_price';
    const DATE  = '_lp_lesson_date';
    const START_TIME = '_lp_lesson_start_time';
    const END_TIME   = '_lp_lesson_end_time';
    const SLOTS   = '_lp_lesson_slots';
    const SYNC_COURSE   = '_lp_lesson_sync_course';
    const TEACHER   = '_lp_lesson_teacher';
    const LOCATION   = '_lp_lesson_location';

    // Nonce
    const NONCE_ACTION = 'dev_lp_lesson_manager_save';
    const NONCE_NAME   = 'dev_lp_lesson_manager_nonce';

    // Post type
    const LESSON_POST_TYPE = 'lp_lesson';

    const COURSE_POST_TYPE = 'lp_course';
}
