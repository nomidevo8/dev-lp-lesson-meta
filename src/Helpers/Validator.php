<?php
namespace DevLPLessonMeta\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Validator {
    public static function is_valid_date( $date ) {
        if ( empty( $date ) ) {
            return false;
        }
        $parts = explode( '-', $date );
        if ( count( $parts ) !== 3 ) {
            return false;
        }
        $y = intval( $parts[0] );
        $m = intval( $parts[1] );
        $d = intval( $parts[2] );
        return checkdate( $m, $d, $y );
    }

    public static function is_valid_time( $time ) {
        if ( empty( $time ) ) {
            return false;
        }
        return (bool) preg_match( '/^([01]?\\d|2[0-3]):[0-5]\\d$/', $time );
    }
}
