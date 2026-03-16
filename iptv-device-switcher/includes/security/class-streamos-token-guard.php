<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * File: iptv-device-switcher/includes/security/class-streamos-token-guard.php
 * Path: /iptv-device-switcher/includes/security/class-streamos-token-guard.php
 */
class IPTV_Device_Switcher_Includes_Security_Class_StreamOS_Token_Guard {

    private static function key(string $token) : string {
        // Shorten and normalize to keep transient keys safe.
        return 'streamos_tok_' . substr(sha1($token), 0, 32);
    }

    /**
     * Marks token as used (single-use). Returns false if token was already used.
     */
    public static function validate_and_mark(string $token, int $ttl = 900) : bool {
        $token = (string)$token;
        if ($token === '') return false;

        $key = self::key($token);
        if (get_transient($key)) {
            return false;
        }
        set_transient($key, 1, max(60, (int)$ttl));
        return true;
    }
}
