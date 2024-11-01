<?php
/** wp-save-custom-header.php
 *
 * Plugin Name:	WP Save Custom Header
 * Plugin URI:	http://www.obenlands.de/en/portfolio/wp-save-custom-header/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-save-custom-header
 * Description:	Makes it possible to save uploaded custom headers and make them part of the default selection.
 * Version:		1.6
 * Author:		Konstantin Obenland
 * Author URI:	http://www.obenlands.de/en/?utm_source=wordpress&utm_medium=plugin&utm_campaign=wp-save-custom-header
 * Text Domain:	wp-save-custom-header
 * Domain Path:	/lang
 * License:		GPLv2
 */


if( ! class_exists('Obenland_Wp_Plugins') ) {
	require_once('obenland-wp-plugins.php');
}


register_activation_hook(__FILE__, array(
	'Obenland_Wp_Save_Custom_Header',
	'activation'
));


class Obenland_Wp_Save_Custom_Header extends Obenland_Wp_Plugins {

	///////////////////////////////////////////////////////////////////////////
	// PROPERTIES, PROTECTED
	///////////////////////////////////////////////////////////////////////////

	/**
	 * The plugins' text domain
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	protected
	 * @static
	 *
	 * @var		string
	 */
	protected static $plugin_textdomain	=	'wp-save-custom-header';


	/**
	 * The relative path to the header image folder.
	 *
	 * The path should look like this:
	 * 'wp-content/themes/{theme_name}/images/headers[/{blog_id}]'
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	protected
	 *
	 * @var		string
	 */
	protected $image_path;
	
	
	/**
	 * The folder within the template directory where the headers sit
	 *
	 * @author	Konstantin Obenland
	 * @since	1.5 - 23.04.2011
	 * @access	protected
	 *
	 * @var		string
	 */
	protected $image_folder;
	

	///////////////////////////////////////////////////////////////////////////
	// METHODS, PUBLIC
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Constructor
	 *
	 * Set image path to the template's images folder
	 * Add all needed filters to correct the image upload and path/url
	 * manipulation
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 * @global	$wpdb
	 *
	 * @return	Obenland_Wp_Save_Custom_Header
	 */
	public function __construct(){

		parent::__construct( array(
			'textdomain'		=>	self::$plugin_textdomain,
			'plugin_name'		=>	plugin_basename(__FILE__),
			'donate_link_id'	=>	'UUFKGWPK469GW'
		));
		
		$this->image_folder	=	get_option( 'wp-header-upload-folder', 'images/headers' );
		
		$this->image_path	=	'wp-content/themes/'
							.	trailingslashit( get_option('template') )
							.	$this->image_folder;

		if ( is_multisite() ) {
			global $wpdb;
			$this->image_path =  trailingslashit( $this->image_path ) . $wpdb->blogid;
		}
		
		load_plugin_textdomain($this->textdomain , false, $this->textdomain . '/lang');

		if ( is_admin() AND isset($_GET['page']) AND 'custom-header' == $_GET['page'] ) {

			add_filter( 'clean_url', array(
				&$this,
				'option_siteurl'
			));

			add_filter( 'admin_init', array(
				&$this,
				'setup_default_headers'
			));
			
			add_filter( 'wp_create_file_in_uploads', array(
				&$this,
				'wp_create_file_in_uploads'
			), 10, 2);


			add_filter( 'upload_dir', array(
				&$this,
				'upload_dir'
			));

			add_filter( 'option_upload_path', array(
				&$this,
				'option_upload_path'
			));

			add_filter( 'wp_update_attachment_metadata', array(
				&$this,
				'update_default_headers'
			));
		}
			 
		add_filter( 'admin_notices', array(
			&$this,
			'admin_notices'
		));
		
		add_filter( 'intermediate_image_sizes', array(
			&$this,
			'intermediate_image_sizes'
		));

		add_filter( 'wp_get_attachment_url', array(
			&$this,
			'set_header_image_url'
		), 10, 2);

		add_filter( 'get_attached_file', array(
			&$this,
			'set_header_image_path'
		), 10, 2);
		add_filter( 'load_image_to_edit_filesystempath', array(
			&$this,
			'set_header_image_path'
		), 10, 2);

		add_filter( '_wp_relative_upload_path', array(
			&$this,
			'wp_relative_upload_path'
		));

		add_action( 'delete_attachment', array(
			&$this,
			'delete_attachment_files'
		));

		add_filter( 'wp_save_image_file', array(
			&$this,
			'wp_save_image_file'
		), 10, 5);
		
		add_action( 'admin_init', array(
			&$this,
			'add_settings_field'
		));
	}


	/**
	 * Checks if the current theme supports custom header functionality and bails
	 * if it doesn't. The plugin will stay deactivated.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 * @static
	 *
	 * @param	bool	$network_wide
	 *
	 * @return	void
	 */
	public static function activation( $network_wide ) {
		load_plugin_textdomain(self::$plugin_textdomain , false, self::$plugin_textdomain . '/lang');

		if ( version_compare(get_bloginfo('version'), '3.0', '<') ) {
			_e( 'This plugin requires WordPress version 3.0 or later.', self::$plugin_textdomain );
			exit;
		}
		 
		if ( ! current_theme_supports('custom-header') ) {
			_e( 'Your current theme does not support Custom Headers', self::$plugin_textdomain );
			exit;
		}
		 
		if ( version_compare(get_bloginfo('version'), '3.1', '>=') AND ! current_theme_supports('custom-header-uploads') ) {
			_e( 'Your current theme does not support Custom Header Uploads', self::$plugin_textdomain );
			exit;
		}

		if ( version_compare(get_bloginfo('version'), '3.2', '>=') ) {
			_e( 'This Plugin was deprecated with WordPress 3.2 because its functionality was integrated into core.', self::$plugin_textdomain );
			exit;
		}
		 
		$option_suffix	=	array(
			'_size_w'	=>	230,
			'_size_h'	=>	48,
			'_crop'		=>	true
		);
		 
		if ( $network_wide ) {

			global $wpdb;
			
			// I hate hate SQL statements in plugins!
			$blogs = $wpdb->get_col( $wpdb->prepare("
				SELECT blog_id
				FROM {$wpdb->blogs}
				WHERE site_id = %d
				AND spam = '0'
				AND deleted = '0'
				AND archived = '0'
			", $wpdb->siteid) );

			foreach ( $blogs as $blog_id ) {

				foreach( $option_suffix as $suffix => $val ) {
					update_blog_option( $blog_id, 'header-thumbnail' . $suffix, $val );
				}
			}
		} else {
			foreach ( $option_suffix as $suffix => $val ) {
				update_option( 'header-thumbnail' . $suffix, $val );
			}
		}


	}


	/**
	 * Registers the uploaded header, so it is displayed with all the others
	 * since the menu screen doesn't reload all the way
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0.1 - 19.02.2011
	 * @access	public
	 *
	 * @param	array	$data
	 *
	 * @return	void
	 */
	public function update_default_headers( $attachment_meta_data ) {

		if ( isset($_REQUEST['step']) AND '3' == $_REQUEST['step'] ) {
			$attachment_meta_data['post_title']	=	$attachment_meta_data['file'];
			$this->register_default_headers( array($attachment_meta_data) );
		}
		return $attachment_meta_data;
	}


	/**
	 * Adds the image size for header thumbnails and registers all uploaded
	 * header images as defaults
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0.1 - 19.02.2011
	 * @access	public
	 *
	 * @return	void
	 */
	public function setup_default_headers() {
		// Add header thumbnail size
		add_image_size( 'header-thumbnail', 230, 48 );

		// Get all uploaded header images
		$posts = get_posts(array(
			'numberposts'	=>	-1,
			'post_type'		=>	'attachment',
			'meta_key'		=>	'_header_image',
			'order'			=>	'ASC',
		));
		$headers = array();

		foreach ( $posts as $post ) {
			$meta	=	get_post_meta( $post->ID, '_wp_attachment_metadata' );
			$meta	=	( 1 == count($meta) ) ? $meta[0] : $meta;

			if( ! empty($meta) AND is_array($meta) ) {
				$meta['post_title']	=	$post->post_title;
				$headers[]			=	$meta;
			}
		}

		if ( ! empty($headers) ) {
			$this->register_default_headers( $headers );
		}

		if ( is_dir(ABSPATH . $this->image_path) AND
			! is_writable(ABSPATH . $this->image_path) ) {

			add_settings_error(
				$this->textdomain,
				'not-writable',
				sprintf(
					__('Please make the <code>%s</code> folder of your theme writable!', $this->textdomain),
					'/' . $this->image_folder
				)
			);
		}
	}


	/**
	 * Sets up the header array from post_meta data
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 * @global	$wpdb
	 *
	 * @param	array	$posts
	 *
	 * @return	void
	 */
	public function register_default_headers( $posts ) {

		$bid = '';
		if ( is_multisite() ) {
			global $wpdb;
			$bid = trailingslashit($wpdb->blogid);
		}
		$placeholder = '%s/' . trailingslashit($this->image_folder) . $bid;
		
		foreach ( $posts as $file_name ) {

			$pics[$file_name['file']] = array(

				// %s is a placeholder for the theme template directory URI
				'url'			=>	$placeholder . $file_name['file'],
				'thumbnail_url'	=>	$placeholder . $file_name['sizes']['header-thumbnail']['file'],
				'description'	=>	$file_name['post_title']
			);
		}

		// Register the existing (uploaded) header images
		register_default_headers( $pics );
	}


	/**
	 * Sets the post meta key to identify the attachment as a header
	 *
	 * This function is called when a new header is uploaded. Since every
	 * uploaded picture is saved as an attachment, we need to be able to
	 * distinguish the header as such, therefore adding an identifier.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 *
	 * @param	string	$image
	 * @param	int		$id
	 *
	 * @return	string
	 */
	public function wp_create_file_in_uploads( $image, $id ) {
		update_post_meta( $id, '_header_image', true );
		return $image;
	}


	/**
	 * Removes the subfolders previously set by WP
	 *
	 * This function is called when a new header is uploaded. Uploads usually
	 * are stored in the 'wp-contents/uploads' folder but the Header API
	 * expects all images in a theme subfolder, so the path needs to be
	 * adjusted.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 *
	 * @param	array	$args
	 *
	 * @return	array
	 */
	public function upload_dir( $args ) {
		$args['path']	=	ABSPATH . $this->image_path;
		$args['url']	=	home_url( $this->image_path );
		$args['subdir']	=	'';

		return $args;
	}


	/**
	 * Corrects the path for header uploads
	 *
	 * Here the correct path for header uploads gets set. This is necessary since
	 * uploads usually are stored in the 'wp-contents/uploads' folder
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 *
	 * @param	string	$value
	 *
	 * @return	string
	 */
	public function option_upload_path( $value ) {
		return $this->image_path;
	}


	/**
	 * Returns only the thumbnail size
	 *
	 * This disables the saving of the image in other (unnecessary) sizes, like
	 * 'medium' or 'small'
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 *
	 * @param	array	$sizes
	 *
	 * @return	array
	 */
	public function intermediate_image_sizes( $sizes ) {

		if (	(defined('DOING_AJAX') AND
				true === DOING_AJAX)
			OR
				(is_admin() AND
				isset($_GET['page']) AND
				'custom-header' == $_GET['page'])
		) {
			$sizes[] = 'header-thumbnail';
		}
		return $sizes;
	}


	/**
	 * Corrects the schema
	 *
	 * @author	Konstantin Obenland
	 * @since	1.0 - 03.02.2011
	 * @access	public
	 *
	 * @param	array	$array
	 *
	 * @return	array
	 */
	public function option_siteurl( $array ) {
		if ( stristr($array, $this->image_path) ) {
			return str_replace( 'https', 'http', $array );
		}

		return $array;
	}



	/**
	 * Displays all error messages
	 *
	 * Helper function for displaying errors and notices on the admin screen.
	 * Should probably be moved to Obenland_Wp_Plugins class.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	public
	 *
	 * @return	string
	 */
	public function admin_notices() {

		if ( version_compare(get_bloginfo('version'), '3.2', '>=') AND
			 current_theme_supports( 'custom-header-uploads' ) ) {
			
			 $this->remove_me();
		}

		settings_errors( $this->textdomain );
	}


	/**
	 * Checks to see whether an attachment is a header image and fixes the URL to
	 * the image based on that information
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	public
	 *
	 * @param	string	$url
	 * @param	int		$post_ID
	 *
	 * @return	string
	 */
	public function set_header_image_url( $url, $post_ID ) {

		if ( '1' == get_post_meta( $post_ID, '_header_image', true) ) {
			$upload_dir = wp_upload_dir();

			if ( false === $upload_dir['error'] ) {
				$url	=	str_replace( $upload_dir['baseurl'], home_url($this->image_path), $url );
			}
			else {
				$path	=	parse_url($url);
				$file	=	get_post_meta( $post_ID, '_wp_attached_file', true);
					
				if ( basename($path['path']) != $file ) {
					$url	=	home_url( trailingslashit($this->image_path) . $file );
				}
					
			}
		}
		return $url;
	}


	/**
	 * Checks to see whether an attachment is a header image and fixes the path
	 * to the image based on that information
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	public
	 *
	 * @param	string	$url
	 * @param	int		$post_ID
	 *
	 * @return	string
	 */
	public function set_header_image_path( $path, $post_ID ) {
		 
		if ( '1' == get_post_meta($post_ID, '_header_image', true) ) {
			$upload_dir = wp_upload_dir();

			$path	=	( false === $upload_dir['error'] )
					?	str_replace( $upload_dir['basedir'], ABSPATH . $this->image_path, $path )
					:	path_join( ABSPATH . $this->image_path, $path );
		}
		return $path;
	}


	/**
	 * Returns the relative path to the upload image
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	public
	 *
	 * @param	string	$path
	 *
	 * @return	string
	 */
	public function wp_relative_upload_path( $path ) {
		 
		if ( 0 === strpos($path, ABSPATH . $this->image_path) ) {
			$path = ltrim( str_replace(ABSPATH . $this->image_path, '', $path), '/' );
		}
		return $path;
	}


	/**
	 * Deletes all files associated with an Attachment ID
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	public
	 *
	 * @param	int		$post_ID
	 *
	 * @return	void
	 */
	public function delete_attachment_files( $post_ID ) {
		 
		if ( ! '1' == get_post_meta( $post_ID, '_header_image', true) ) {
			return;
		}
		 
		$files			=	array();
		$backup_sizes	=	get_post_meta( $post_ID, '_wp_attachment_backup_sizes', true );
		$meta_data		=	wp_get_attachment_metadata( $post_ID );
		$files[]		=	$meta_data['file'];

		if ( is_array($backup_sizes) ) {
			foreach ( $backup_sizes as $file ) {
				$files[]	=	$file['file'];
			}
		}
		 
		foreach ( $meta_data['sizes'] as $size) {
			$files[]	=	$size['file'];
		}

		foreach ( $files as $file ) {
			@ unlink( path_join(ABSPATH . $this->image_path, $file) );
		}
	}


	/**
	 * Resets the header image to the edited image
	 *
	 * When a header image is edited in the Media Library the image will get
	 * automatically updated to the current version.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.2 - 18.03.2011
	 * @access	public
	 *
	 * @param	null	$null
	 * @param	string	$filename
	 * @param	string	$image
	 * @param	string	$mime_type
	 * @param	int		$post_ID
	 *
	 * @return	null
	 */
	public function wp_save_image_file( $null, $filename, $image, $mime_type, $post_ID ) {

		if ( '1' == get_post_meta($post_ID, '_header_image', true) AND
			get_theme_mod('header_image') == wp_get_attachment_url($post_ID) ) {
				
			$url = str_replace( ABSPATH . $this->image_path, home_url($this->image_path), $filename );
			set_theme_mod( 'header_image', $url );
		}
		return $null;
	}
	
	
	/**
	 * Registers the setting and the settings field if it does not already
	 * exist
	 *
	 * @author	Konstantin Obenland
	 * @since	1.5 - 23.04.2011
	 * @access	public
	 * @global	$wp_settings_fields
	 *
	 * return	void
	 */
	public function add_settings_field() {
		global $wp_settings_fields;
		
		if ( ! isset($wp_settings_fields['media']['uploads']['wp-header-upload-folder']) ) {
			
			register_setting(
				'media',									// Option group
				'wp-header-upload-folder',					// Option name
				array(&$this, 'settings_field_validate')	// Sanitation callback
			);
			
			$title = __('Store header images in this template folder', $this->textdomain);
			
			add_settings_field(
				'wp-header-upload-folder',					// Id
				$title,										// Title
				array(&$this, 'settings_field_callback'),	// Callback
				'media',									// Page
				'uploads',									// Section
				array(										// Args
					'label_for'	=>	'wp-header-upload-folder'
				)
			);
		}
	}
	
	
	/**
	 * Displays the settings field HTML
	 *
	 * @author	Konstantin Obenland
	 * @since	1.5 - 23.04.2011
	 * @access	public
	 *
	 * return	void
	 */
	public function settings_field_callback() {
	?>
		<input name="wp-header-upload-folder" type="text" id="wp-header-upload-folder" value="<?php echo esc_attr( $this->image_folder ); ?>" class="regular-text code" />
		<span class="description"><?php _e( 'Default is <code>images/headers</code>', $this->textdomain ) ; ?></span>
	<?php
	}
	
	
	/**
	 * Sanitizes the settings field input
	 *
	 * To make the input usable across plugins the folder path will be saved
	 * without prepended or trailing slashes
	 *
	 * @author	Konstantin Obenland
	 * @since	1.5 - 23.04.2011
	 * @access	public
	 *
	 * @param	string	$input
	 *
	 * @return	string	The sanitized folder name
	 */
	public function settings_field_validate( $input ) {
		$input = trim( $input, '/' );
		
		if ( empty($input) ){
			add_settings_error(
				'wp-header-upload-folder',
				'empty-value',
				__('Header images should not be stored in the root directory of your theme. Please specify a folder and try again.', $this->textdomain)
			);
			return $this->image_folder;
		}
		
		if ( ! is_dir(trailingslashit(TEMPLATEPATH) . $input) ) {
			add_settings_error(
				'wp-header-upload-folder',
				'no-dir',
				__('The specified folder does not exist! Please create the folder, make it writable and try again.', $this->textdomain)
			);
			return $this->image_folder;
		}
		
		if ( ! is_writable(trailingslashit(TEMPLATEPATH) . $input) ) {
			add_settings_error(
				'wp-header-upload-folder',
				'not-writable',
				__('The specified folder is not writable! Please make it writable and try again.', $this->textdomain)
			);
			return $this->image_folder;
		}
		
		return $input;
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	// METHODS, PUBLIC
	///////////////////////////////////////////////////////////////////////////
	
	/**
	 * Makes all saved headers available for core functionality and deactivates
	 * the plugin.
	 *
	 * @author	Konstantin Obenland
	 * @since	1.6 - 02.07.2011
	 * @access	private
	 *
	 * @return	void
	 */
	private function remove_me() {
		
		$upload = wp_upload_dir();
		
		if ( $upload['path'] == ABSPATH . $this->image_path ) {
			return;
		}
		
		$posts = get_posts(array(
			'numberposts'	=>	-1,
			'post_type'		=>	'attachment',
			'meta_key'		=>	'_header_image',
			'order'			=>	'ASC',
		));
		
		
		foreach ( $posts as $post ) {
			
			wp_update_post( $post->ID, '_wp_attachment_is_custom_header', get_option('stylesheet' ) );
			delete_post_meta( $post->ID, '_header_image' );
			
			$meta	=	get_post_meta( $post->ID, '_wp_attachment_metadata' );
			$meta	=	( 1 == count($meta) ) ? $meta[0] : $meta;
							
			// Update the URL to the pics
			$post->guid = trailingslashit($upload['baseurl']) . $meta['file'];
			@ wp_update_post( $post );
			
			$files[]		=	$meta['file'];
			 
			foreach ( $meta['sizes'] as $size) {
				$files[]	=	$size['file'];
			}
	
			foreach ( $files as $file ) {
				
				//Copy the pics and delete the old ones
				@ copy( path_join(ABSPATH . $this->image_path, $file), path_join($upload['basedir'], $file) );
				@ unlink( path_join(ABSPATH . $this->image_path, $file) );
			}
		}
		
		remove_theme_mod( 'header_image' );
		
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( $this->plugin_name );
		
		add_settings_error(
			$this->textdomain,
			'plugin-deactivated',
			__('WP Save Custom Header was deactivated. It\'s functionality was integrated into core in WordPress 3.2.', $this->textdomain),
			'updated'
		);
	}
} // End of class Obenland_Wp_Save_Custom_Header


new Obenland_Wp_Save_Custom_Header;


/* End of file wp-save-custom-header.php */
/* Location: ./wp-content/plugins/wp-save-custom-header/wp-save-custom-header.php */