<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class Import {

	protected static $_instance = null;

	protected $import_option_api_key;

	protected $import_option_postcode;

	protected $import_option_radius;

	protected $import_option_number;

	protected $import_option_sale_type;

	protected $properties;

	public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	public function __construct() {
		add_action('wp_ajax_handle_ajax', array( $this, 'handle_ajax' ) );

		
	}

	function handle_ajax() {

		if ( !function_exists('media_handle_upload') ) {
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');
		}

		$count = 0;

		$this->properties = array();

		global $wpdb;

		$this->import_option_api_key = get_option( 'import_option_api_key' );
		$this->import_option_postcode = get_option( 'import_option_postcode' );
		$this->import_option_radius = get_option( 'import_option_radius' );
		$this->import_option_number = get_option( 'import_option_number' );
		$this->import_option_sale_type = get_option( 'import_option_sale_type' );

		$xml = simplexml_load_file('http://api.zoopla.co.uk/api/v1/property_listings.xml?postcode='. $this->import_option_postcode 
			. '&page_size=' . $this->import_option_number 
			. '&radius=' . $this->import_option_radius 
			. '&listing_status=' . $this->import_option_sale_type 
			. '&api_key=' . $this->import_option_api_key 
			);


		if ($xml !== FALSE) {

			foreach ($xml->listing as $property) {

				$this->properties[] = $property;
		                  
		    } 

		}

		foreach ( $this->properties as $property ) {

			// has the property already been imported
			$args = array(
	            'post_type' => 'property',
	            'posts_per_page' => 1,
	            'post_status' => 'any',
	            'meta_query' => array(
	            	array(
		            	'key' => 'property_id',
		            	'value' => (string)$property->listing_id
		            )
	            )
	        );

			$property_query = new WP_Query($args);

			if ( !$property_query->have_posts() ) {

		        $postdata = array(
						'post_excerpt'   => (string)$property->short_description,
						'post_content' 	 => $property->description,
						'post_title'     => wp_strip_all_tags( (string)$property->displayable_address ),
						'post_status'    => 'publish',
						'post_type'      => 'property',
						'comment_status' => 'closed',
					);

				$post_id = wp_insert_post( $postdata, true );

				update_post_meta( $post_id, 'property_id', (string)$property->listing_id );
				update_post_meta( $post_id, 'property_url', (string)$property->details_url );
				update_post_meta( $post_id, 'property_price', (string)$property->price );
				update_post_meta( $post_id, 'property_agent', (string)$property->agent_name );
				update_post_meta( $post_id, 'property_agent_phone', (string)$property->agent_phone );
				update_post_meta( $post_id, 'property_agent_logo', (string)$property->agent_logo );

				// handle the image
				$url = (string)$property->image_url;
				$description = '';
				$filename = basename( $url );

				$tmp = download_url( $url );
			    $file_array = array(
			        'name' => basename( $url ),
			        'tmp_name' => $tmp
			    );

			    $id = media_handle_sideload( $file_array, $post_id, $description, array('post_title' => $filename) );

			    set_post_thumbnail($post_id, $id);

			    echo 'Imported property: ' . $property->listing_id . '<br />';

			}else{
				echo $property->listing_id . ' This property has already been imported<br />';
			}


		}







		//var_dump($this->properties);
		wp_die();
	}
}-

$import = Import::instance();

/*
update_post_meta($post->ID, 'property_id', sanitize_text_field( $_POST['property_id']) );
update_post_meta($post->ID, 'property_url', sanitize_text_field( $_POST['property_url']) );
update_post_meta($post->ID, 'property_price', sanitize_text_field( $_POST['property_price']) );
update_post_meta($post->ID, 'property_agent', sanitize_text_field( $_POST['property_agent']) );
update_post_meta($post->ID, 'property_agent_phone', sanitize_text_field( $_POST['property_agent_phone']) );
*/