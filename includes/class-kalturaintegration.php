<?php
/**
 * Kaltura Integration class.
 *
 * @since 1.0.0
 * @package ubc-h5p-kaltura-integration
 */

namespace UBC\H5P\KalturaIntegration;

use Kaltura\Client\Client;
use Kaltura\Client\Configuration;
use Kaltura\Client\Enum\MediaType;
use Kaltura\Client\Enum\SessionType;
use Kaltura\Client\Type\MediaEntry;
use Kaltura\Client\Type\UploadedFileTokenResource;
use Kaltura\Client\Type\UploadToken;
use Kaltura\Client\Type\Category;
use Kaltura\Client\Type\CategoryFilter;

/**
 * Class to initiate Kaltura Integration functionalities
 */
class KalturaIntegration {
	private $kaltura_admin_secret;
	private $kaltura_partner_id;
	private $kaltura_service_url;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'load-h5p-content_page_h5p_new', array( $this, 'enqueue_add_new_content_script' ), 10 );
		add_action( 'wp_ajax_ubc_h5p_kaltura_verify_source', array( $this, 'kaltura_verify_source' ) );
		add_action( 'wp_ajax_ubc_h5p_kaltura_upload_video', array( $this, 'kaltura_upload_video' ) );

		$this->_set_kaltura_config_vars();
	}

	private function _set_kaltura_config_vars() {
		if (
			!empty(get_option('kaltura_admin_secret')) &&
			!empty(get_option('kaltura_partner_id')) &&
			!empty(get_option('kaltura_service_url')))
		{
			$this->kaltura_admin_secret = get_option('kaltura_admin_secret');
			$this->kaltura_partner_id = get_option('kaltura_partner_id');
			$this->kaltura_service_url = get_option('kaltura_service_url');
		} elseif (
			(defined('KALTURA_ADMIN_SECRET') && !empty(KALTURA_ADMIN_SECRET)) &&
			(defined('KALTURA_PARTNER_ID') && !empty(KALTURA_PARTNER_ID)) &&
			(defined('KALTURA_SERVICE_URL') && !empty(KALTURA_SERVICE_URL))
		) {
			$this->kaltura_admin_secret = KALTURA_ADMIN_SECRET;
			$this->kaltura_partner_id = KALTURA_PARTNER_ID;
			$this->kaltura_service_url = KALTURA_SERVICE_URL;
		}
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
				'kaltura_service_url' => $this->kaltura_service_url,
				'kaltura_partner_id' => $this->kaltura_partner_id,
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
	 * Create Kaltura Client and start Kaltura Session.
	 * 
	 * @return Client
	 */
	public function get_kaltura_client($type = SessionType::USER) {
		$user = wp_get_current_user()->ID;
    $kconf = new Configuration($this->kaltura_partner_id);
    $kconf->setServiceUrl($this->kaltura_service_url);
    $kclient = new Client($kconf);
    $ksession = $kclient->session->start($this->kaltura_admin_secret, $user, $type, $this->kaltura_partner_id);
    $kclient->setKs($ksession);

    return $kclient;
	}

	/**
	 * Ajax handler to upload a video to Kaltura and return its entry ID.
	 * 
	 * @return void
	 */
	public function kaltura_upload_video() {
		check_ajax_referer( 'security', 'nonce' );

		$video_file_path = $_FILES['video_file']['tmp_name'];
    $video_file_name = $_FILES['video_file']['name'];

		try {
			$kclient = $this->get_kaltura_client();

			// 1. Create upload token
			$upload_token = new UploadToken();
			$token = $kclient->uploadToken->add($upload_token);
	
			// 2. Upload the file data
			$upload = $kclient->uploadToken->upload($token->id, $video_file_path);
	
			// 3. Create Kaltura Media Entry and add categories
			$media_entry = new MediaEntry();
			$media_entry->name = $video_file_name;
			$media_entry->mediaType = MediaType::VIDEO;
			$media_entry->categoriesIds = $this->_create_category_hierarchy($kclient);
			$entry = $kclient->media->add($media_entry);
	
			// 4. Attach the uploaded video to the Media Entry
			$resource = new UploadedFileTokenResource();
			$resource->token = $token->id;
			$response = $kclient->media->addContent($entry->id, $resource);
	
			wp_send_json(
				array(
					'kalturaId' => $response->id,
					'message' => __( "Successfully uploaded video to Kaltura. The Kaltura ID and source URL have been generated. Press 'Insert' to use this Kaltura media.", 'ubc-h5p-addon-kaltura-integration' ),
				)
			);
		} catch (\Throwable $e) {
			wp_send_json(
				array(
					'kalturaId'=> null,
					'message' => __( "An error occurred. Please check your Kaltura credentials and video file and try again.", 'ubc-h5p-addon-kaltura-integration' ),
				)
			);
		}
	}

	/**
	 * Create the hierarchy of categories 'Tapestry>{site URL}>{date}>H5P', to place an uploaded video under.
	 * 
	 * @return string	Comma-separated list of category IDs in the chain.
	 */
	private function _create_category_hierarchy($kclient)
	{
		$parent_category_name = 'Tapestry';
		$filter = new CategoryFilter();
		$filter->fullNameStartsWith = $parent_category_name;
		$categories = $kclient->category->listAction($filter, null);

		$k_admin_client = null;

		// 'Tapestry'
		$parent_category = $this->_get_or_create_category($parent_category_name, null, $categories, $k_admin_client);

		// 'Tapestry>{site URL}'
		$site_url = get_bloginfo('url');
		$site_category = $this->_get_or_create_category($site_url, $parent_category, $categories, $k_admin_client);

		// 'Tapestry>{site URL}>{date}'
		$date = date('Y/m/d');
		$date_category = $this->_get_or_create_category($date, $site_category, $categories, $k_admin_client);
		
		// 'Tapestry>{site URL}>{date}>H5P'
		$h5p_category = $this->_get_or_create_category('H5P', $date_category, $categories, $k_admin_client);

		return $parent_category->id.','.$site_category->id.','.$date_category->id.','.$h5p_category->id;
	}
	
	/**
	 * Find or create the category with name as a child of a parent category (or at the root, if no parent given).
	 * 
	 * @return Category
	 */
	private function _get_or_create_category($category_name, $parent_category, $categories, &$k_admin_client)
	{
			$category_full_name = $parent_category ? $parent_category->fullName.'>'.$category_name : $category_name;
			$category_index = array_search($category_full_name, array_column($categories->objects, 'fullName'));
			$category = (false !== $category_index ? $categories->objects[$category_index] : null);

			if (null === $category) {
					$created_category = new Category();

					if ($parent_category) {
							$created_category->parentId = $parent_category->id;
					}
					$created_category->name = $category_name;
					$k_admin_client = $k_admin_client ?? $this->get_kaltura_client(SessionType::ADMIN);  // Reuse Kaltura session if possible
					$category = $k_admin_client->category->add($created_category);
			}

			return $category;
	}

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
