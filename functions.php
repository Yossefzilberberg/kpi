<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts' );




//*** Computec Scripts ***
//====================

// disable gutenberg  for posts
//========================
add_filter('use_block_editor_for_post', '__return_false', 10);

// disable gutenberg  for post types
//===========================
add_filter('use_block_editor_for_post_type', '__return_false', 10);


/***** VCF Support *****/
//===================
function _enable_vcard_upload( $mime_types ){
  	$mime_types['vcf'] = 'text/vcard';
	$mime_types['vcard'] = 'text/vcard';
  	return $mime_types;
}
add_filter('upload_mimes', '_enable_vcard_upload' );


/***** Shorten Post/Page link  *****/
//============================
add_filter( 'get_shortlink', function ( $shortlink ) {
    return $shortlink;
});


/***** Allow SVG *****/
//===================
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {

  global $wp_version;
  if ( $wp_version !== '4.7.1' ) {
     return $data;
  }

  $filetype = wp_check_filetype( $filename, $mimes );

  return [
      'ext'             => $filetype['ext'],
      'type'            => $filetype['type'],
      'proper_filename' => $data['proper_filename']
  ];

}, 10, 4 );

function cc_mime_types( $mimes ){
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter( 'upload_mimes', 'cc_mime_types' );

function fix_svg() {
  echo '<style type="text/css">
        .attachment-266x266, .thumbnail img {
             width: 100% !important;
             height: auto !important;
        }
        </style>';
}
add_action( 'admin_head', 'fix_svg' );



// Elementor Form Telephone Number Validition
//======================================
function dorzki_validate_phone_elementor( $field, $record, $ajax_handler ) {
	if ( preg_match( '/^((0\d{1,2}\-\d{7})|(0\d{8,9}))$/', $field['value'] ) !== 1 ) {
		$ajax_handler->add_error( $field['id'], esc_html__( 'מספר הטלפון אינו חוקי', 'dorzki' ) );
	}
}
add_action( 'elementor_pro/forms/validation/tel', 'dorzki_validate_phone_elementor', 10, 3 );
	 

// Connect to admin error massage
//===============================
function no_wordpress_errors(){
  return 'הנתונים שהקלדת שגויים, אנא נסו שנית';
}
add_filter( 'login_errors', 'no_wordpress_errors' );


// prevents hackers from getting your username by using ?author=1 at the end of your domain url 
// ==============================================================================
add_action('template_redirect', computec_template_redirect);
function computec_template_redirect() {
    if (is_author()) {
        wp_redirect( home_url() ); exit;
    }
}

// remove wp version number from head and rss
function artisansweb_remove_version() {
    return '';
}
add_filter('the_generator', 'artisansweb_remove_version');
