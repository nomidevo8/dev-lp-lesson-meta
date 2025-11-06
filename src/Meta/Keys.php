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

    // Nonce
    const NONCE_ACTION = 'dev_lp_lesson_manager_save';
    const NONCE_NAME   = 'dev_lp_lesson_manager_nonce';

    // Post type
    const POST_TYPE = 'lp_lesson';
}
