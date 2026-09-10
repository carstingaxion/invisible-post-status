<?php
/**
 * Plugin Name:       Invisible Post Status
 * Plugin URI:        https://github.com/carstingaxion/invisible-post-status
 * Description:       Adds a custom post status "invisible" to WordPress, allowing posts to be hidden from public listings while still being accessible via direct URL.
 * Version:           0.1.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Carsten Bach
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       invisible-post-status
 * Domain Path:       /languages
 *
 * @package InvisiblePostStatus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'INVISIBLE_POST_STATUS_VERSION' ) ) {
	define( 'INVISIBLE_POST_STATUS_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
}

if ( ! defined( 'INVISIBLE_POST_STATUS_PLUGIN_FILE' ) ) {
	define( 'INVISIBLE_POST_STATUS_PLUGIN_FILE', __FILE__ );
}

if ( ! function_exists( 'invisible_post_status_autoloader' ) ) {
	/**
	 * Autoload class files from includes/classes directory.
	 *
	 * Maps class names to file paths using WordPress naming conventions:
	 * - Class: Invisible_Post_Status_Post_Type_Support
	 * - File:  includes/classes/class-post-type-support.php
	 *
	 * @since 0.3.0
	 * @param string $class_name Fully qualified class name to autoload.
	 * @return void
	 */
	function invisible_post_status_autoloader( string $class_name ): void {
		// Only autoload classes with our prefix.
		if ( 0 !== strpos( $class_name, 'Invisible_Post_Status_' ) ) {
			return;
		}

		// Convert class name to file name.
		// Example: Invisible_Post_Status_Post_Type_Support -> post-type-support.
		$file_name = str_replace( 'Invisible_Post_Status_', '', $class_name );
		$file_name = strtolower( str_replace( '_', '-', $file_name ) );
		$file_path = plugin_dir_path( INVISIBLE_POST_STATUS_PLUGIN_FILE ) . 'includes/classes/class-' . $file_name . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
	spl_autoload_register( 'invisible_post_status_autoloader' );
}

if ( ! function_exists( 'invisible_post_status_load_textdomain' ) ) {
	/**
	 * Load the plugin text domain for translations.
	 *
	 * Loads translation files from the languages/ directory within the plugin.
	 * WordPress will also check WP_LANG_DIR/plugins/ for override translations.
	 *
	 * @since 0.3.0
	 * @return void
	 */
	function invisible_post_status_load_textdomain(): void {
		load_plugin_textdomain(
			'invisible-post-status',
			false,
			dirname( plugin_basename( INVISIBLE_POST_STATUS_PLUGIN_FILE ) ) . '/languages'
		);
	}
	add_action( 'init', 'invisible_post_status_load_textdomain' );
}

if ( ! function_exists( 'invisible_post_status_init' ) ) {
	/**
	 * Initialize the plugin.
	 *
	 * Bootstraps all singleton class instances in the correct order.
	 * Each class registers its own WordPress hooks internally.
	 *
	 * Load order:
	 * 1. Setup
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function invisible_post_status_init(): void {
		Invisible_Post_Status_Setup::get_instance();
	}
	add_action( 'plugins_loaded', 'invisible_post_status_init' );
}
