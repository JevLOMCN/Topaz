<?php

namespace jakobo\HOTP;

/**
 * HOTP Class
 * Based on the work of OAuth, and the sample implementation of HMAC OTP
 * http://tools.ietf.org/html/draft-mraihi-oath-hmac-otp-04#appendix-D
 * @author Jakob Heuser (firstname)@felocity.com
 * @copyright 2011-2020
 * @license BSD-3-Clause
 * @version 1.0
 */
class HOTP {
    /**
     * Generate a HOTP key based on a counter value (event based HOTP)
     * @param string $key the key to use for hashing
     * @param int $counter the number of attempts represented in this hashing
     * @return HOTPResult a HOTP Result which can be truncated or output
     */
    public static function generateByCounter( string $key, int $counter ): HOTPResult {
        // the counter value can be more than one byte long,
        // so we need to pack it down properly.
        $cur_counter = [ 0, 0, 0, 0, 0, 0, 0, 0 ];
        for ( $i = 7; $i >= 0; $i-- ) {
            $cur_counter[$i] = pack( 'C*', $counter );
            $counter = $counter >> 8;
        }

        $bin_counter = implode( $cur_counter );

        // Pad to 8 chars
        if ( strlen( $bin_counter ) < 8 ) {
            $bin_counter = str_repeat(
                chr(0 ),
                8 - strlen( $bin_counter )
            ) . $bin_counter;
        }

        // HMAC
        $hash = hash_hmac( 'sha1', $bin_counter, $key );

        return new HOTPResult( $hash );
    }

    /**
     * Generate a HOTP key based on a timestamp and window size
     * @param string $key the key to use for hashing
     * @param int $window the size of the window a key is valid for in seconds
     * @param int|false $timestamp a timestamp to calculate for, defaults to time()
     * @return HOTPResult a HOTP Result which can be truncated or output
     */
    public static function generateByTime( string $key, int $window, $timestamp = false ): HOTPResult {
        if ( !$timestamp && $timestamp !== 0 ) {
            // @codeCoverageIgnoreStart
            $timestamp = self::getTime();
            // @codeCoverageIgnoreEnd
        }

        $counter = intval( $timestamp / $window) ;

        return self::generateByCounter( $key, $counter );
    }

    /**
     * Generate a HOTP key collection based on a timestamp and window size
     * all keys that could exist between a start and end time will be included
     * in the returned array
     * @param string $key the key to use for hashing
     * @param int $window the size of the window a key is valid for in seconds
     * @param int $min the minimum window to accept before $timestamp
     * @param int $max the maximum window to accept after $timestamp
     * @param int|false $timestamp a timestamp to calculate for, defaults to time()
     * @return array of HOTPResult
     */
    public static function generateByTimeWindow( string $key, int $window, int $min = -1, int $max = 1, $timestamp = false ): array {
        if ( !$timestamp && $timestamp !== 0 ) {
            // @codeCoverageIgnoreStart
            $timestamp = self::getTime();
            // @codeCoverageIgnoreEnd
        }

        $counter = intval( $timestamp / $window );
        $window = range( $min, $max );

        $out = [];
        foreach ( $window as $value ) {
            $shift_counter = $counter + $value;
            $out[$shift_counter] = self::generateByCounter( $key, $shift_counter );
        }

        return $out;
    }

    /**
     * Gets the current time
     * Ensures we are operating in UTC for the entire framework
     * Restores the timezone on exit.
     * @return int the current time
     * @codeCoverageIgnore
     */
    public static function getTime(): int {
        // PHP's time is always UTC
        return time();
    }
}
