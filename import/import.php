<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
  Plugin Name: Import Zoopla UK Property Listings
  Plugin URI: http://www.alanwheeler.co.uk
  Description: Import listings from Zoopla.
  Author: Alan Wheeler
  Version: 1.00
  Author URI: http://www.alanwheeler.co.uk
  Text Domain: import-listings-zoopla
  Domain Path: lang
*/
include_once( 'do_import.php' );

class Properties{

	protected static $_instance = null;

	public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	public function __construct() {
		add_action('init', array( $this, 'properties_post_type'), 0 );
		add_action('admin_menu', array( $this, 'add_sub_menu') );
		add_action('admin_init', array( $this, 'register_settings') );
		add_action('admin_init', array( $this, 'admin_init') );
		add_action('save_post', array( $this, 'save_details') );
		add_action('admin_enqueue_scripts', array( $this, 'import_enqueue_css' ) );
		add_action('admin_enqueue_scripts', array( $this, 'import_enqueue_script') );
		add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
	}

	function properties_post_type() {

		$labels = array( 
	  		'name'               => __( 'Properties', 'text_domain' ),
			'singular_name'      => __( 'Property', 'text_domain' ),
			'add_new'            => _x( 'Add New', 'text_domain' ),
			'add_new_item'       => __( 'Add New Property', 'text_domain}' ),
			'edit_item'          => __( 'Edit Property', 'text_domain' ),
			'new_item'           => __( 'New Property', 'text_domain' ),
			'view_item'          => __( 'View Property', 'text_domain' ),
			'search_items'       => __( 'Search Properties', 'text_domain' ),
			'not_found'          => __( 'No Properties found', 'text_domain' ),
			'not_found_in_trash' => __( 'No Properties found in Trash', 'text_domain' ),
			'menu_name'          => __( 'Properties', 'text_domain' ),
	    );
	 
	    $args = array( 
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'description',
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			//'menu_icon'         => '',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post', 
			'supports'            => array( 
										'title', 'editor', 'author', 'thumbnail', 'excerpt'
									),
	    );
		register_post_type( 'property', $args );

	}

	 
	function admin_init(){
		add_meta_box('property-id', 'Property ID', array( $this, 'property_id'), 'property', 'normal', 'high');
		add_meta_box('property-url', 'Property Url', array( $this, 'property_url'), 'property', 'normal', 'high');
		add_meta_box('property-price', 'Property Price £', array( $this, 'property_price'), 'property', 'normal', 'high');
		add_meta_box('property-agent', 'Marketed by', array( $this, 'property_agent'), 'property', 'normal', 'high');
		add_meta_box('property-agent-phone', 'Agent Tel. No.', array( $this, 'property_agent_phone'), 'property', 'normal', 'high');
		add_meta_box('property-agent-logo', 'Agent logo Url.', array( $this, 'property_agent_logo'), 'property', 'normal', 'high');
		
	}

	function add_sub_menu() {
		add_submenu_page( 'edit.php?post_type=property', 'Import Settings', 'Import Settings', 'manage_options', 'import-options', array( $this, 'import_options'), 6 ); 
	}

	function import_enqueue_css() {
		wp_enqueue_style( 'import_enqueue_css', plugin_dir_url( __FILE__ ) . 'css/import_css.css' );
	}
	function import_enqueue_script() {   
	    wp_enqueue_script( 'import_enqueue_script', plugin_dir_url( __FILE__ ) . 'js/import_js.js' );
	    wp_localize_script( 'import_enqueue_script', 'api_key', get_option('import_option_api_key') );
	}
	
	function register_settings(){
		$args = array(
            'type' => 'string',
            'default' => NULL,
            );
    	register_setting( 'import_options_group', 'import_option_api_key', $args );
    	$args = array(
            'type' => 'string',
            'default' => NULL,
            );
    	register_setting( 'import_options_group', 'import_option_postcode', $args );
    	$args = array(
            'type' => 'string',
            'default' => NULL,
            );
    	register_setting( 'import_options_group', 'import_option_number', $args );
    	$args = array(
            'type' => 'string',
            'default' => NULL,
            );
    	register_setting( 'import_options_group', 'import_option_sale_type', $args );
    	$args = array(
            'type' => 'string',
            'default' => NULL,
            );
    	register_setting( 'import_options_group', 'import_option_radius', $args );

	}

	function import_options() {
		$properties_count = wp_count_posts( 'property' );
		?>
		<p><strong>You currently have <?php echo $properties_count->publish; ?> Property Listings.</strong></p>
		<div class="wrap" style="padding:5px 10px;background:#444;margin-left:0;">
			<p style="margin:0;color:#fff;"><strong>Zoopla Property Import Options</strong></p>
		</div>
		<form id="import_settings_form" method="post" action="options.php">
            <?php settings_fields('import_options_group'); ?>
            <table class="form-table">
            	<tr valign="top"><th scope="row">Zoopla Api Key: <span class="dashicons dashicons-editor-help"></span></th>
                    <td>You can get your Zoopla Api Key <a href="https://developer.zoopla.co.uk/" target="_blank"><strong>here</strong></a></td>
                </tr>
                <tr valign="top"><th scope="row">Enter Api Key:</th>
                    <td><input type="text" id="import_option_api_key" name="import_option_api_key" value="<?php echo get_option('import_option_api_key'); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top"><th scope="row">Postcode:</th>
                    <td><input type="text" name="import_option_postcode" value="<?php echo get_option('import_option_postcode'); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top"><th scope="row">Postcode Radius:</th>
                    <td>
	                    <select name="import_option_radius">
						    <option value="5" <?php selected( get_option('import_option_radius'), 5 ); ?>>5 Miles</option>
						    <option value="10" <?php selected( get_option('import_option_radius'), 10 ); ?>>10 Miles</option>
						    <option value="20" <?php selected( get_option('import_option_radius'), 20 ); ?>>20 Miles</option>
						    <option value="30" <?php selected( get_option('import_option_radius'), 30 ); ?>>30 Miles</option>
						    <option value="40" <?php selected( get_option('import_option_radius'), 40 ); ?>>40 Miles</option>
						</select>
					</td>
                </tr>
                <tr valign="top"><th scope="row">No. of properties to import:</th>
                    <td>
	                    <select name="import_option_number">
						    <option value="10" <?php selected( get_option('import_option_number'), 10 ); ?>>10</option>
						    <option value="20" <?php selected( get_option('import_option_number'), 20 ); ?>>20</option>
						    <option value="30" <?php selected( get_option('import_option_number'), 30 ); ?>>30</option>
						    <option value="40" <?php selected( get_option('import_option_number'), 40 ); ?>>40</option>
						    <option value="50" <?php selected( get_option('import_option_number'), 50 ); ?>>50</option>
						    <option value="60" <?php selected( get_option('import_option_number'), 60 ); ?>>60</option>
						    <option value="70" <?php selected( get_option('import_option_number'), 70 ); ?>>70</option>
						    <option value="80" <?php selected( get_option('import_option_number'), 80 ); ?>>80</option>
						    <option value="90" <?php selected( get_option('import_option_number'), 90 ); ?>>90</option>
						    <option value="100" <?php selected( get_option('import_option_number'), 100 ); ?>>100</option>
						</select>
					</td>
                </tr>
                <tr valign="top"><th scope="row">Property Marketing Type:</th>
                    <td>
	                    <select name="import_option_sale_type">
						    <option value="sale" <?php selected( get_option('import_option_sale_type'), 'sale' ); ?>>For Sale</option>
						    <option value="rent" <?php selected( get_option('import_option_sale_type'), 'rent' ); ?>>To Let</option>
						</select>
					</td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" id="save_changes" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
        <div class="wrap" style="padding:5px 10px;background:#444;margin-left:0;">
			<p style="margin:0;color:#fff;"><strong>Zoopla Property Import</strong></p>
		</div>
		<p class="submit">
            <button id="run_import" class="button-primary" /><?php _e('Run Import') ?></button>	
        </p>
        <div id="response" class="response wrap"></div>
		<?php
	}

	function property_id(){
		global $post;

		$custom_post = get_post_custom($post->ID);
		$property_id = $custom_post['property_id'][0];
		?>
		<input name="property_id" value="<?php echo $property_id; ?>" class="regular-text" />
		<?php
	}

	function property_url(){
		global $post;

		$custom_post = get_post_custom($post->ID);
		$property_url = $custom_post['property_url'][0];
		?>
		<input name="property_url" value="<?php echo $property_url; ?>" class="regular-text code" />
		<?php
	}

	function property_price(){
		global $post;

		$custom_post = get_post_custom($post->ID);
		$property_price = $custom_post['property_price'][0];
		?>
		<input name="property_price" value="<?php echo $property_price; ?>" class="regular-text" />
		<?php
	}

	function property_agent(){
		global $post;

		$custom_post = get_post_custom($post->ID);
		$property_agent = $custom_post['property_agent'][0];
		?>
		<input name="property_agent" value="<?php echo $property_agent; ?>" class="regular-text" />
		<?php
	}

	function property_agent_phone(){
		global $post;

		$custom_post = get_post_custom($post->ID);
		$property_agent_phone = $custom_post['property_agent_phone'][0];
		?>
		<input name="property_agent_phone" value="<?php echo $property_agent_phone; ?>" class="regular-text" />
		<?php
	}

	function property_agent_logo(){
		global $post;

		$custom_post = get_post_custom($post->ID);
		$property_agent_logo = $custom_post['property_agent_logo'][0];
		?>
		<input name="property_agent_logo" value="<?php echo $property_agent_logo; ?>" class="regular-text" />
		<?php
	}

	function save_details(){
		global $post;

		update_post_meta($post->ID, 'property_id', sanitize_text_field( $_POST['property_id']) );
		update_post_meta($post->ID, 'property_url', sanitize_text_field( $_POST['property_url']) );
		update_post_meta($post->ID, 'property_price', sanitize_text_field( $_POST['property_price']) );
		update_post_meta($post->ID, 'property_agent', sanitize_text_field( $_POST['property_agent']) );
		update_post_meta($post->ID, 'property_agent_phone', sanitize_text_field( $_POST['property_agent_phone']) );
		update_post_meta($post->ID, 'property_agent_logo', sanitize_text_field( $_POST['property_agent_logo']) );
	}


	function action_links( $links ) {
        return array_merge( array(
            '<a href="' . admin_url( 'edit.php?post_type=property&page=import-options' ) . '">' . __( 'Import Options', 'properties' ) . '</a>',
            '<a href="' . esc_url( apply_filters( 'properties_url', 'http://www.alanwheeler.co.uk/', 'properties' ) ) . '" target="_blank">' . __( 'Website', 'properties' ) . '</a>',
        ), $links );
    }


}	

$property_import = Properties::instance();