<?php
if (!defined('ABSPATH')) exit;

/** Szybki helper bool z opcją domyślną */
function xyz_boolopt($key, $default = 1){
    return (int) get_option($key, $default) === 1;
}

/** ENABLE = rejestrujemy integrację (kod, widgety, bloki) */
function xyz_can_enable_gutenberg(){ return xyz_boolopt('xyz_enable_gutenberg', 1); }
function xyz_can_enable_elementor(){ return xyz_boolopt('xyz_enable_elementor', 1) && did_action('elementor/loaded'); }
function xyz_can_enable_wpbakery(){ return xyz_boolopt('xyz_enable_wpbakery', 1) && (function_exists('vc_map') || defined('WPB_VC_VERSION')); }
function xyz_can_enable_shortcode(){ return xyz_boolopt('xyz_enable_shortcode', 1); }

/** SHOW = render na froncie (gdy treść już istnieje) */
function xyz_can_show_gutenberg(){ return xyz_boolopt('xyz_show_gutenberg', 1); }
function xyz_can_show_elementor(){ return xyz_boolopt('xyz_show_elementor', 1); }
function xyz_can_show_wpbakery(){ return xyz_boolopt('xyz_show_wpbakery', 1); } // UWAŻAJ na nazwę opcji (bez spacji!)
function xyz_can_show_shortcode(){ return xyz_boolopt('xyz_show_shortcode', 1); }

/** Czy wolno wykonać do_shortcode() teraz (globalny „bezpiecznik”) */
function xyz_allow_shortcodes_now(){
    return xyz_can_enable_shortcode() && xyz_can_show_shortcode();
}

// helper (np. w includes/helpers.php)
function xyz_client_ip(): string {
    $h = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($h) {
        // bierz pierwszy niepusty, obetnij spacje
        foreach (explode(',', $h) as $part) {
            $ip = trim($part);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}


