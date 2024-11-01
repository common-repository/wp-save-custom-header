<?php
//Don't uninstall unless you absolutely want to!
if ( ! defined( 'WP_UNINSTALL_PLUGIN' )){
	wp_die('WP_UNINSTALL_PLUGIN undefined.');
}


/**
 * Deletes all files associated with an Attachment ID
 * 
 * @author	Konstantin Obenland
 * @since	1.2 - 18.03.2011
 * 
 * @param	int		$post_ID
 * 
 * @return	void
 */
function ko_wp_sch_delete_attachment( $post_ID ) {
	
	$path	=	ABSPATH
			.	'wp-content/themes/'
			.	get_option( 'template' )
			.	'/images/headers';
			
	if ( is_multisite() ) {
		global $wpdb;
		
		$dir = trailingslashit( $path ) . $wpdb->blogid;
		
		if ( ! $dh = @opendir($dir) ) {
			return;
		}
		
		while ( false !== ($obj = readdir($dh)) ) {
			if ( $obj == '.' OR $obj == '..') {
				continue;
			}
			@unlink( $dir . '/' . $obj );
		}
		
		closedir( $dh );
		@rmdir ( $dir );
		
	} else {
		
		$files			=	array();
		$backup_sizes	=	get_post_meta(
			$post_ID,
			'_wp_attachment_backup_sizes',
			true
		);
		$meta_data		=	wp_get_attachment_metadata( $post_ID );
		$files[]		=	$meta_data['file'];

		if ( is_array($backup_sizes) ) {
			foreach ($backup_sizes as $file) {
				$files[]	=	$file['file'];
			}
		}
	
		foreach ( $meta_data['sizes'] as $size) {
			$files[]	=	$size['file'];
		}
	
		foreach ( $files as $file ) {
			@ unlink( path_join($path, $file) );
		}
	}
}

/**
 * Deletes all files and options
 * 
 * @author	Konstantin Obenland
 * @since	1.3 - 07.04.2011
 * 
 * @return	void
 */
function ko_wp_sch_uninstall() {
	
	$header_images = get_posts(array(
		'numberposts'	=>	-1,
		'post_type'		=>	'attachment',
		'meta_key'		=>	'_header_image'
	));
	
	// Delete all images
	foreach($header_images as $header_image) {
		ko_wp_sch_delete_attachment( $header_image->ID );	//Delete Files
		wp_delete_attachment( $header_image->ID, true );	//Delete Data
	}
	
	// Delete all options
	$keys = array('_size_w', '_size_h', '_crop');
	
	foreach( $keys as $key ) {
		delete_option( 'header-thumbnail'.$key );
	}
	
	// Delete option only if no other plugin needs it
	if( ! is_plugin_active('wp-display-header/wp-display-header.php') ) {
		delete_option( 'wp-header-upload-folder' );
	}
	
	// Don't leave without resetting the default header image
	remove_theme_mod( 'header_image' );
}

if( is_multisite() ) {
  
	global $wpdb;
  
	$blogs = $wpdb->get_col( $wpdb->prepare("
		SELECT blog_id
		FROM {$wpdb->blogs}
		WHERE site_id = %d
		AND spam = '0'
		AND deleted = '0'
		AND archived = '0'
	", $wpdb->siteid) );

	foreach ( $blogs as $blog_id ) {
		
		switch_to_blog($blog_id);
		
		ko_wp_sch_uninstall();
	}
	
	restore_current_blog();
	
} else {
	ko_wp_sch_uninstall();
}


/* Goodbye! Thank you for having me! */


/* End of file uninstall.php */
/* Location: ./wp-content/plugins/wp-save-custom-header/uninstall.php */