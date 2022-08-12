<?php
/**
 * Kaltura Integration class.
 *
 * @since 1.0.0
 * @package ubc-h5p-kaltura-integration
 */

namespace UBC\H5P\KalturaIntegration;

/**
 * Class to initiate Kaltura Integration functionalities
 */
class KalturaIntegration {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'load-h5p-content_page_h5p_new', array( $this, 'enqueue_add_new_content_script' ), 10 );
		add_action( 'wp_ajax_ubc_h5p_kaltura_verify_source', array( $this, 'kaltura_verify_source' ) );
		//add_action( 'h5p_additional_embed_head_tags', array( $this, 'kaltura_embed_styles' ) );
		//add_filter( 'print_h5p_content', array( $this, 'kaltura_shortcode_styles' ), 10, 2 );
	}

	/**
	 * Load assets for h5p new content page.
	 *
	 * @return void
	 */
	public function enqueue_add_new_content_script() {
		if ( ! ( isset( $_GET['page'] ) && 'h5p_new' === $_GET['page'] ) ) {
			return;
		}

		wp_enqueue_script(
			'ubc-h5p-kaltura-integration-js',
			H5P_KALTURA_INTEGRATION_PLUGIN_URL . 'assets/dist/js/app.js',
			array(),
			filemtime( H5P_KALTURA_INTEGRATION_PLUGIN_DIR . 'assets/dist/js/app.js' ),
			true
		);

		wp_localize_script(
			'ubc-h5p-kaltura-integration-js',
			'ubc_h5p_kaltura_integration_admin',
			array(
				'security_nonce'          => wp_create_nonce( 'security' ),
				'plugin_url'              => H5P_KALTURA_INTEGRATION_PLUGIN_URL,
				'kaltura_instruction_url' => defined( 'UBC_H5P_KALTURA_INSTRUCTION_URL' ) ? UBC_H5P_KALTURA_INSTRUCTION_URL : '/getting-started-with-h5p/finding-your-ubc-kaltura-video-id/',
				'iframe_css_file_version' => filemtime( H5P_KALTURA_INTEGRATION_PLUGIN_DIR . 'assets/dist/css/app.css' ),
				'kaltura_service_url' => KALTURA_SERVICE_URL,
				'kaltura_partner_id' => KALTURA_PARTNER_ID,
			)
		);
	}//end enqueue_add_new_content_script()

	/**
	 * Ajax handler to verify if the source video is available.
	 *
	 * @return void
	 */
	public function kaltura_verify_source() {
		check_ajax_referer( 'security', 'nonce' );

		$video_url = isset( $_POST['video_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_url'] ) ) : null;
		$response  = wp_remote_head( $video_url );

		if ( isset( $response['response'] ) && isset( $response['response']['code'] ) && ( 200 === $response['response']['code'] || 302 === $response['response']['code'] ) ) {
			wp_send_json(
				array(
					'valid'   => true,
					'message' => __( "Media ID Valid. The source URL has been generated above. Press 'Insert' to use this Kaltura media.", 'ubc-h5p-addon-kaltura-integration' ),
				)
			);
		} else {
			wp_send_json(
				array(
					'valid'   => false,
					'message' => __( 'Error. Media ID Invalid. Please see how to find the ID for your media uploaded to Kaltura.', 'ubc-h5p-addon-kaltura-integration' ),
				)
			);
		}

	}//end kaltura_verify_source()

	/**
	 * Print style for presentation content type in embed mode.
	 */
	public function kaltura_embed_styles() {
		echo '<style>
			.h5p-element{
				min-height: 100px;
			}
		</style>';
	}

	/**
	 * Embed script for shortcode.
	 *
	 * @param string $h5p_content_wrapper The HTML string of the content wrapper.
	 * @param array  $content Array contains all the content information.
	 *
	 * @return string
	 */
	public function kaltura_shortcode_styles( $h5p_content_wrapper, $content ) {
		wp_enqueue_script(
			'ubc-h5p-kaltura-integration-presentation-js',
			H5P_KALTURA_INTEGRATION_PLUGIN_URL . 'assets/dist/js/shortcode.js',
			array(),
			filemtime( H5P_KALTURA_INTEGRATION_PLUGIN_DIR . 'assets/dist/js/shortcode.js' ),
			true
		);

		return $h5p_content_wrapper;
	}
}

new KalturaIntegration();
