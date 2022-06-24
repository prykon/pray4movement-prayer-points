<?php
/**
 * Plugin Name: Disciple.Tools - Pray4Movement Prayer Points
 * Plugin URI: https://github.com/DiscipleTools/pray4movement-prayer-points
 * Description: Disciple.Tools - Pray4Movement Prayer Points is intended to help developers and integrator jumpstart their extension of the Disciple.Tools system.
 * Text Domain: pray4movement-prayer-points
 * Domain Path: /languages
 * Version:  0.1
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/pray4movement-prayer-points
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Refactoring (renaming) this plugin as your own:
 * 1. @todo Rename the `pray4movement-prayer-points.php file.
 * 2. @todo Refactor all occurrences of the name Pray4Movement_Prayer_Points, pray4movement_prayer_points, pray4movement-prayer-points, prayer_point, and "Pray4Movement Prayer Points"
 * 3. @todo Update the README.md and LICENSE
 * 4. @todo Update the default.pot file if you intend to make your plugin multilingual. Use a tool like POEdit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `Pray4Movement_Prayer_Points` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function pray4movement_prayer_points() {
    $pray4movement_prayer_points_required_dt_theme_version = '1.19';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( "Disciple_Tools" );
    if ( $is_theme_dt && version_compare( $version, $pray4movement_prayer_points_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'pray4movement_prayer_points_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return Pray4Movement_Prayer_Points::instance();

}
add_action( 'after_setup_theme', 'pray4movement_prayer_points', 20 );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class Pray4Movement_Prayer_Points {

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $is_rest = dt_is_rest();
        require_once( 'rest-api/rest-api.php' );


        if ( is_admin() ) {
            require_once( 'admin/admin-menu-and-tabs.php' );
        }

        $this->i18n();

        if ( is_admin() ) {
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }

    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.
            // @todo add other links here
        }

        return $links_array;
    }

    public static function activation() {
        self::create_prayer_points_table_if_not_exist();
        self::create_prayer_points_library_table_if_not_exist();
        self::create_prayer_points_meta_table_if_not_exist();
    }

    private static function create_prayer_points_table_if_not_exist() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $test = $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dt_prayer_points` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `library_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
                `title` LONGTEXT COLLATE utf8mb4_unicode_520_ci NOT NULL,
                `content` LONGTEXT COLLATE utf8mb4_unicode_520_ci NOT NULL,
                `reference` VARCHAR(100) COLLATE utf8mb4_unicode_520_ci NULL,
                `book` VARCHAR(50) COLLATE utf8mb4_unicode_520_ci NULL,
                `verse` VARCHAR(50) COLLATE utf8mb4_unicode_520_ci NULL,
                `hash` VARCHAR(65) DEFAULT NULL,
                `status` VARCHAR(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'unpublished',
                PRIMARY KEY (`id`)
            ) $charset_collate;" //@phpcs:ignore
        );
        if ( !$test ) {
            throw new Exception( 'Could not create table dt_prayer_points' );
        }
    }

    private static function create_prayer_points_library_table_if_not_exist() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $test = $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dt_prayer_points_lib` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `key` VARCHAR(255) NOT NULL,
                `name` VARCHAR(191) NOT NULL,
                `description` LONGTEXT DEFAULT NULL,
                `icon` LONGTEXT COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
                `status` VARCHAR(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'unpublished',
                `last_updated` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (`id`)
            ) $charset_collate;" //@phpcs:ignore
        );
        if ( !$test ) {
            throw new Exception( 'Could not create table dt_prayer_points_lib' );
        }
    }

    private static function create_prayer_points_meta_table_if_not_exist() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $test = $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dt_prayer_points_meta` (
                `meta_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `prayer_id` BIGINT(20) UNSIGNED DEFAULT NULL,
                `meta_key` varchar(255) DEFAULT NULL,
                `meta_value` LONGTEXT,
                PRIMARY KEY (`meta_id`)
            ) $charset_collate;" //@phpcs:ignore
        );
        if ( !$test ) {
            throw new Exception( 'Could not create table dt_prayer_points_meta' );
        }
    }

    public static function deactivation() {
        // add functions here that need to happen on deactivation
        delete_option( 'dismissed-pray4movement-prayer-points' );
    }

    public function i18n() {
        $domain = 'pray4movement-prayer-points';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    public function __toString() {
        return 'pray4movement-prayer-points';
    }

    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "pray4movement_prayer_points::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


register_activation_hook( __FILE__, [ 'Pray4Movement_Prayer_Points', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'Pray4Movement_Prayer_Points', 'deactivation' ] );


if ( ! function_exists( 'pray4movement_prayer_points_hook_admin_notice' ) ) {
    function pray4movement_prayer_points_hook_admin_notice() {
        global $pray4movement_prayer_points_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple.Tools - Pray4Movement Prayer Points' plugin requires 'Disciple.Tools' theme to work. Please activate 'Disciple.Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === "disciple-tools-theme" ){
            $message .= ' ' . sprintf( esc_html( 'Current Disciple.Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $pray4movement_prayer_points_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-pray4movement-prayer-points', false ) ) { ?>
            <div class="notice notice-error notice-pray4movement-prayer-points is-dismissible" data-notice="pray4movement-prayer-points">
                <p><?php echo esc_html( $message );?></p>
            </div>
            <script>
                jQuery(function($) {
                    $( document ).on( 'click', '.notice-pray4movement-prayer-points .notice-dismiss', function () {
                        $.ajax( ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'pray4movement-prayer-points',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( "dt_hook_ajax_notice_handler" ) ){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}
require_once( 'functions/shortcodes.php' );

/**
 * Plugin Releases and updates
 * @todo Uncomment and change the url if you want to support remote plugin updating with new versions of your plugin
 * To remove: delete the section of code below and delete the file called version-control.json in the plugin root
 *
 * This section runs the remote plugin updating service, so you can issue distributed updates to your plugin
 *
 * @note See the instructions for version updating to understand the steps involved.
 * @link https://github.com/DiscipleTools/pray4movement-prayer-points/wiki/Configuring-Remote-Updating-System
 *
 * @todo Enable this section with your own hosted file
 * @todo An example of this file can be found in (version-control.json)
 * @todo Github is a good option for delivering static json.
 */
/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
//add_action( 'plugins_loaded', function (){
//    if ( is_admin() && !( is_multisite() && class_exists( "DT_Multisite" ) ) || wp_doing_cron() ){
//        // Check for plugin updates
//        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
//            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' )){
//                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
//            }
//        }
//        if ( class_exists( 'Puc_v4_Factory' ) ){
//            Puc_v4_Factory::buildUpdateChecker(
//                'https://raw.githubusercontent.com/DiscipleTools/pray4movement-prayer-points/master/version-control.json',
//                __FILE__,
//                'pray4movement-prayer-points'
//            );
//
//        }
//    }
//} );
