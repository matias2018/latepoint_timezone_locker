<?php
/**
 * Plugin Name:       Latepoint Timezone Locker
 * Plugin URI:        https://pedromatias.dev/ltl
 * Description:       Forces Latepoint booking forms to use the WordPress timezone setting instead of the user's browser timezone.
 * Version:           1.2.0
 * Author:            Pedro Matias
 * Author URI:        https://pedromatias.dev
 * Contributors:      Pedro Matias
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       latepoint-timezone-locker
 * Domain Path:       /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define the known *prefix* of the cookie name
define( 'LTL_LATEPOINT_TIMEZONE_COOKIE_PREFIX', 'latepoint_selected_timezone_' );

/**
 * Check if Latepoint plugin is active.
 */
function ltl_is_latepoint_active() {
	return defined('LATEPOINT_VERSION');
}

// --- Core Logic ---

/**
 * Force disable Latepoint's timezone selection/info features via settings filter.
 */
function ltl_filter_latepoint_settings( $value, $option_name ) {
    // Modify main Latepoint settings array
    if ( $option_name === 'latepoint_settings' && is_array( $value ) ) {
        $value['steps_show_timezone_selector'] = 'off';
        $value['steps_show_timezone_info']     = 'off';
        if ( defined('LTL_DEBUG') && LTL_DEBUG ) {
            error_log('[LTL DEBUG] Filtering latepoint_settings: Forced timezone features OFF.');
        }
    } elseif ($option_name === 'steps_show_timezone_selector' || $option_name === 'steps_show_timezone_info'){
		if ( defined('LTL_DEBUG') && LTL_DEBUG ) {
            error_log('[LTL DEBUG] Filtering direct setting get (' . $option_name . '): Forced OFF.');
        }
		return 'off';
	}
    return $value;
}
add_filter( 'pre_option_latepoint_settings', 'ltl_filter_latepoint_settings', 10, 2 );
add_filter( 'pre_option_steps_show_timezone_selector', function(){ return 'off'; }, 10 );
add_filter( 'pre_option_steps_show_timezone_info', function(){ return 'off'; }, 10 );
// Opt filter for OsSettingsHelper::is_on (if needed and filter exists)
/*
add_filter('latepoint_is_setting_on', function($is_on, $setting_key) {
    if ($setting_key === 'steps_show_timezone_selector' || $setting_key === 'steps_show_timezone_info') {
        if ( defined('LTL_DEBUG') && LTL_DEBUG ) { error_log('[LTL DEBUG] Filtering latepoint_is_setting_on (' . $setting_key . '): Forced OFF.'); }
        return false;
    }
    return $is_on;
}, 10, 2);
*/


/**
 * Force WP Timezone via the session timezone filter.
 */
function ltl_force_wp_timezone_via_session_filter( $timezone_name ) {
    if ( ltl_is_latepoint_active() ) {
        $wp_timezone_string = wp_timezone_string();
        if ( ! empty( $wp_timezone_string ) ) {
            if ( defined('LTL_DEBUG') && LTL_DEBUG && $timezone_name !== $wp_timezone_string ) { error_log('[LTL DEBUG] Filter latepoint_timezone_name_from_session: Overriding "' . $timezone_name . '" with WP Setting: "' . $wp_timezone_string . '"'); }
            return $wp_timezone_string;
        } else {
            if ( class_exists('OsTimeHelper') && method_exists('OsTimeHelper', 'get_wp_timezone_name') ) {
                $calculated_wp_tz_name = OsTimeHelper::get_wp_timezone_name();
                if ( defined('LTL_DEBUG') && LTL_DEBUG && $timezone_name !== $calculated_wp_tz_name ) { error_log('[LTL DEBUG] Filter latepoint_timezone_name_from_session: WP Setting empty. Overriding "' . $timezone_name . '" with OsTimeHelper fallback: "' . $calculated_wp_tz_name . '"'); }
                return $calculated_wp_tz_name;
            } else {
                $wp_offset = get_option( 'gmt_offset' );
                $tz_name = timezone_name_from_abbr( '', $wp_offset * 3600, false );
                if ($tz_name === false) {
                    $hours   = (int) $wp_offset;
                    $minutes = abs( ( $wp_offset - (int) $wp_offset ) * 60 );
                    $tz_name = sprintf( '%+03d:%02d', $hours, $minutes );
                }
                if ( defined('LTL_DEBUG') && LTL_DEBUG && $timezone_name !== $tz_name ) { error_log('[LTL DEBUG] Filter latepoint_timezone_name_from_session: WP Setting empty. Overriding "' . $timezone_name . '" with manual offset fallback: "' . $tz_name . '"'); }
                return $tz_name;
            }
        }
    }
    return $timezone_name;
}
add_filter( 'latepoint_timezone_name_from_session', 'ltl_force_wp_timezone_via_session_filter', 99 );


/**
 * === Updated Cookie Clearing Logic ===
 *
 * Clear the Latepoint timezone cookie(s) which have dynamic names.
 * Iterates through cookies and clears any matching the known prefix.
 */
function ltl_clear_timezone_cookie() {
    // Run only on frontend for active Latepoint installations
    if ( ! is_admin() && ltl_is_latepoint_active() && ! empty( $_COOKIE ) ) {

        $cookie_prefix = LTL_LATEPOINT_TIMEZONE_COOKIE_PREFIX;
        $prefix_length = strlen( $cookie_prefix );

        foreach ( $_COOKIE as $name => $value ) {
            // Check if the cookie name starts with our defined prefix
            // Use strncmp for compatibility, or str_starts_with() for PHP 8+
            // if (str_starts_with($name, $cookie_prefix)) { // PHP 8+
            if ( strncmp( $name, $cookie_prefix, $prefix_length ) === 0 ) { // Works on older PHP

                if ( defined('LTL_DEBUG') && LTL_DEBUG ) {
                    error_log('[LTL DEBUG] Found dynamic Latepoint timezone cookie: "' . $name . '". Attempting to clear.');
                }

                // Attempt to clear using Latepoint's helper first (preferred)
                if ( class_exists('OsSessionsHelper') && method_exists('OsSessionsHelper', 'unsetcookie') ) {
                    try {
                        OsSessionsHelper::unsetcookie( $name );
                    } catch (Exception $e) {
                        if ( defined('LTL_DEBUG') && LTL_DEBUG ) {
                            error_log('[LTL DEBUG] Error using OsSessionsHelper::unsetcookie for ' . $name . ': ' . $e->getMessage());
                        }
                        // Fallback to standard setcookie if helper fails
                        setcookie( $name, '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '' );
                    }
                } else {
                    // Fallback if helper class/method doesn't exist
                    setcookie( $name, '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '' );
                    if ( defined('LTL_DEBUG') && LTL_DEBUG ) {
                        error_log('[LTL DEBUG] OsSessionsHelper::unsetcookie not found. Using standard setcookie() for: "' . $name . '".');
                    }
                }

                // Unset from the current request's $_COOKIE array as well (optional but good practice)
                unset( $_COOKIE[$name] );

                // Optional: If you are certain only ONE such cookie exists, you could 'break;' here.
                // break;
            }
        }
    }
}
add_action( 'init', 'ltl_clear_timezone_cookie', 5 ); // Run fairly early on init


/**
 * Enqueue Frontend JavaScript Fallback.
 */
function ltl_enqueue_scripts() {
	if ( ! ltl_is_latepoint_active() || is_admin() ) {
		return;
	}
    $wp_timezone_string = ltl_force_wp_timezone_via_session_filter('');
    $latepoint_script_handle = 'latepoint-main-app-frontend'; // <-- *** VERIFY THIS HANDLE ***
	wp_enqueue_script(
		'latepoint-timezone-locker-script',
		plugin_dir_url( __FILE__ ) . 'js/ltl-script.js',
		array( 'jquery', $latepoint_script_handle ),
		'1.2.1', // Version bump
		true
	);
	wp_localize_script(
		'latepoint-timezone-locker-script',
		'ltl_data',
		array(
			'wp_timezone' => $wp_timezone_string,
            'debug_mode'  => ( defined('LTL_DEBUG') && LTL_DEBUG )
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ltl_enqueue_scripts' );


//  Admin Notices and Settings Link 
/**
 * Admin notice if Latepoint is not active.
 */
function ltl_admin_notice_missing_latepoint() {
    $user_id = get_current_user_id();
    if ( get_user_meta( $user_id, 'ltl_dismissed_notice', true ) ) { return; }
	if ( ! ltl_is_latepoint_active() && current_user_can( 'activate_plugins' ) ) {
        $nonce = wp_create_nonce('ltl_dismiss_notice_nonce');
		$message = sprintf( esc_html__( '"%s" requires the "Latepoint" plugin to be installed and active for full functionality.', 'latepoint-timezone-locker' ), '<strong>Latepoint Timezone Locker</strong>' );
        $dismiss_url = add_query_arg(array('ltl_action' => 'dismiss_notice', '_wpnonce' => $nonce));
		printf( '<div class="notice notice-error is-dismissible" data-ltl-dismiss-url="%s"><p>%s</p></div>', esc_url($dismiss_url), $message );
	}
}
add_action( 'admin_notices', 'ltl_admin_notice_missing_latepoint' );
/** Handle Dismissal of Admin Notice */
function ltl_handle_dismiss_notice() {
    if ( isset( $_GET['ltl_action'] ) && $_GET['ltl_action'] === 'dismiss_notice' ) {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ltl_dismiss_notice_nonce' ) ) { wp_die( __( 'Security check failed', 'latepoint-timezone-locker' ) ); }
        $user_id = get_current_user_id();
        if ( $user_id ) {
            update_user_meta( $user_id, 'ltl_dismissed_notice', true );
            wp_safe_redirect( remove_query_arg( array( 'ltl_action', '_wpnonce' ) ) );
            exit;
        }
    }
}
add_action( 'admin_init', 'ltl_handle_dismiss_notice' );
/** Add settings link on plugin page */
function ltl_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php#timezone_string' ) . '">' . __( 'WP Timezone Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
$plugin_basename = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin_basename", 'ltl_add_settings_link' );