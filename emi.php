<?php
/**
 * Plugin Name: test Real Estate MLS RETS Import
 * Description: None
 * Author: test
 * Author URI: http://test.net/
 * Version: 1.0.0
 * License: GPL2
 */

//If WP Core exists
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

//Start Session
if ( ! session_id() ) {
    session_start();
}

//Paths && Urls
define( 'EMI_TEMPLATES_PATH',      plugin_dir_path(__FILE__) . 'templates/' );
define( 'EMI_ASSETS_URL',          plugin_dir_url(__FILE__) . 'assets/' );
define( 'EMI_IMAGES_URL',          EMI_ASSETS_URL . 'img/' );
define( 'EMI_JS_URL',              EMI_ASSETS_URL . 'js/' );
define( 'EMI_CSS_URL',             EMI_ASSETS_URL . 'css/' );
define( 'EMI_INCLUDES_PATH',       plugin_dir_path(__FILE__) . 'includes/' );

//Message codes
define( 'EMI_ERROR_TYPE',   'error' );
define( 'EMI_SUCCESS_TYPE', 'updated' );
define( 'EMI_WARNING_TYPE', 'warning' );

//Includes
require_once( EMI_INCLUDES_PATH . 'phrets.class.php' );
require_once( EMI_INCLUDES_PATH . 'settings.php' );
require_once( EMI_INCLUDES_PATH . 'phrets.php' );
require_once( EMI_INCLUDES_PATH . 'fields.php' );
require_once( EMI_INCLUDES_PATH . 'ajax.php' );
require_once( EMI_INCLUDES_PATH . 'filters.php' );
require_once( EMI_INCLUDES_PATH . 'helper.php' );

/**
 * Initialize some settings for test
 * Import Plugin
 *
 * @return void
 * @version 1.0
 */
function emi_init() {

    //Create a post type for schedules
    register_post_type( 'emi-schedule', array(
        'public' => false,
        'label' => __( 'Schedule', 'es-plugin' )
    ) );

    //Do Schedules if exists
    emi_schedule();
}
add_action( 'init', 'emi_init' );

/**
 * Register Admin Pages for this plugin
 *
 * @return void
 * @version 1.0.0
 */
function emi_register_admin_pages() {
    if ( function_exists( 'es_plugin_version' ) ) {
        add_submenu_page(
                'es_dashboard',
                __( "MLS RETS Import", "es-plugin" ),
                __( "Import MLS RETS", "es-plugin" ),
                'manage_options',
                'emi-import-mls',
                'emi_import_page'
        );
    }
}
add_action( 'admin_menu', 'emi_register_admin_pages', 11 );

/**
 * Render MLS Import Main Page
 */
function emi_import_page() {
    load_template( EMI_TEMPLATES_PATH . 'main-template.php', true );
}

/**
 * Enqueue Javascript scripts in Admin panel
 *
 * @return void
 * @version 1.0.0
 */
function emi_enqueue_admin_scripts() {

    wp_register_script( 'emi-admin-core', EMI_JS_URL . 'emi-admin.core.js' );
    wp_enqueue_script( 'emi-admin-core' );

    wp_register_style( 'emi-admin-style', EMI_CSS_URL . 'emi-admin.css' );
    wp_enqueue_style( 'emi-admin-style' );

    //jQuery UI For Tabs
    wp_enqueue_script( 'jquery-ui-tabs' );

    //jQuery UI Datepicker
    wp_enqueue_script('jquery-ui-datepicker');

    //Equeue style for Jquery UI
    wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

    //jQuery UI For Sortable lists
    wp_enqueue_script( 'jquery-ui-sortable' );
}
add_action( 'admin_enqueue_scripts', 'emi_enqueue_admin_scripts' );

/**
 * Render Single Status Message
 *
 * @param $type
 * @param $message
 * @param $class
 * @return string
 */
function emi_message( $type, $message, $class = '' ) {
    ob_start(); ?>
        <div class="<?php echo $type; ?> <?php echo $class; ?>"><p><b><?php _e( 'test', 'es-plugin' ); ?>: </b><?php echo $message; ?></p></div>
    <?php return ob_get_clean();
}

/**
 * Install Plugin Options on Plugin Activation
 *
 * @version 1.0
 * @return void
 */
function emi_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'emi_classes_active_fields';
    $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                system_name tinytext NOT NULL,
                long_name tinytext NOT NULL,
                resource tinytext NOT NULL,
                associate tinytext NOT NULL,
                class tinytext NOT NULL,
                object_type tinytext NOT NULL,
                UNIQUE KEY id (id)
            );";

    $wpdb->query( $sql );

    $table_name = $wpdb->prefix . 'emi_filter_fields';
    $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                system_name tinytext NOT NULL,
                label tinytext NOT NULL,
                lookup_name tinytext NOT NULL,
                resource tinytext NOT NULL,
                class tinytext NOT NULL,
                UNIQUE KEY id (id)
            );";

    $wpdb->query( $sql );
}
register_activation_hook( __FILE__, 'emi_install' );

/**
 * Return Action Message
 *
 * @version 1.0
 * @return string
 */
function emi_get_action_message() {
    if ( isset( $_GET['emi-message-code'] ) ) {
        if ( $_GET['emi-message-code'] >= 0 ) {
            return emi_message( EMI_SUCCESS_TYPE, emi_get_message_by_code( $_GET['emi-message-code'] ) );
        } else {
            return emi_message( EMI_ERROR_TYPE, emi_get_message_by_code( $_GET['emi-message-code'] ) );
        }
    }
}

/**
 * Return test logo URL
 *
 * @return bool|string
 */
function emi_get_test_logo_url() {
    $path = plugins_url() .'/test/admin_template/images/';

    if (  @getimagesize( $path . 'test_pro.png' ) ) {
        return $path . 'test_pro.png';
    } else if ( @getimagesize( $path . 'test_simple.png' ) ) {
        return $path . 'test_simple.png';
    } else {
        return false;
    }
}

/**
 * Return All Action Messages
 *
 * @version 1.0
 * @return mixed|void
 */
function emi_get_action_messages() {
    return apply_filters( 'emi_get_action_messages', array(
         1 => __( 'All properties have imported.', 'es-plugin' ),
         2 => __( 'Agent Resource has saved.', 'es-plugin' ),
        -1 => __( 'Failed import some property (ies).', 'es-plugin' ),
        -2 => __( 'You Need to Select Properties for Import / Overwrite.', 'es-plugin' ),
        -3 => __( 'Agent Resource Field is empty.', 'es-plugin' ),
    ) );
}

/**
 * Return message by code
 *
 * @version 1.0
 * @return string|null
 * @param $code
 */
function emi_get_message_by_code( $code ) {
    $messages = emi_get_action_messages();

    if ( isset( $messages[ $code ] ) ) {
        return $messages[ $code ];
    } else {
        return null;
    }
}

/**
 * Return Class of Active Link
 *
 * @param $a
 * @param $b
 * @param bool|false $isEmpty
 * @return string
 */
function emi_active_link( $a, $b ) {
    return $a == $b ? 'class="active"' : '';
}
