<?php
/**
 * UBC H5P Addon - Kaltura Integration
 *
 * @package     UBC H5P
 * @author      Kelvin Xu
 * @copyright   2021 University of British Columbia
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: UBC H5P Addon - Kaltura Integration
 * Plugin URI:  https://ubc.ca/
 * Description: Provides UI for users to pull video from UBC Kaltura and embed into h5p contents.
 * Version:     1.0.7
 * Author:      Kelvin Xu
 * Text Domain: ubc-h5p-addon-kaltura-integration
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

namespace UBC\H5P\KalturaIntegration;

define( 'H5P_KALTURA_INTEGRATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'H5P_KALTURA_INTEGRATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin initialization
 *
 * @return void
 */
function init() {
	require_once 'includes/class-kalturaintegration.php';
}

add_action( 'admin_init', __NAMESPACE__ . '\\init' );
