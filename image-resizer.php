<?php
/**
 * Plugin name: Image Resizer
 * Author: Radoslav Georgiev
 * License: GPL v2
 * Description: Generates image sizes only when needed, instead of the 404 page.
 */
class Image_Resizer {
	public function __construct() {
		add_filter( 'intermediate_image_sizes', array( $this, 'fake_sizes' ) );
		add_filter( 'image_downsize', array( $this, 'downsize' ), 10, 3 );
	}

	/**
	 * Lie to WordPress that there are no image sizes.
	 * 
	 * @since 0.1
	 * 
	 * @param mixed[] $sizes The sizes that are not needed.
	 * @return mixed[] An empty array.
	 */
	public function fake_sizes( $sizes ) {
		if( ! function_exists( 'get_current_screen' ) )
			return $sizes;

		$screen = get_current_screen();

		# ToDo: Check other upload scenarios

		if( 'async-upload' != $screen->id ) {
			return $sizes;
		}

		return array();
	}

	/**
	 * Handles image downsizing.
	 * 
	 * @since 0.1
	 * 
	 * @param bool         $downsize Whether to short-circuit the image downsize. Default false.
	 * @param int          $id       Attachment ID for image.
	 * @param array|string $size     Either an array of dimensions or a string.
	 * @return mixed
	 */
	public function downsize( $downsize, $id, $size ) {
		$img_url    = wp_get_attachment_url( $id );
		$meta       = wp_get_attachment_metadata( $id );
		$uploads    = wp_upload_dir();
		$upload_dir = $uploads[ 'basedir' ];
		$upload_url = $uploads[ 'baseurl' ];

		# Defaults
		$width    = $height = 0;
		$crop     = false;
		$the_size = false;

		if( is_string( $size ) ) {
			# Look for a specific size
			$sizes    = array();
			$raw      = get_intermediate_image_sizes();

			foreach( $raw as $s ) {
				if( ! isset( $GLOBALS['_wp_additional_image_sizes' ][ $s ] ) )
					continue;

				$sizes[ $s ] = $GLOBALS['_wp_additional_image_sizes' ][ $s ];
			}

			if( isset( $sizes[ $size ] ) ) {
				$the_size        = $sizes[ $size ];
				$is_intermediate = true;
			} elseif( 'thumbnail' == $size ) {
				$the_size = array(
					'width'  => intval( get_option( 'thumbnail_size_w' ) ),
					'height' => intval( get_option( 'thumbnail_size_h' ) ),
					'crop'   => intval( get_option( 'thumbnail_crop' ) )
				);

				$is_intermediate = false;
			}
		} else if( is_array( $size ) ) {
			# Hard-coded size
			$the_size        = $size;
			$is_intermediate = false;
		} else {
			# Nothing to do
			return $downsize;
		}

		# Use the size
		if( isset( $the_size[ 'width' ] ) )  $width  = $the_size[ 'width' ];
		if( isset( $the_size[ 'height' ] ) ) $height = $the_size[ 'height' ];
		if( isset( $the_size[ 'crop' ] ) )   $crop   = $the_size[ 'crop' ];

		# If there is no size, don't continue
		if( ! $width && ! $height ) {
			return $downsize;
		}

		# Calculate the size that would be needed
		$dims = image_resize_dimensions( $meta[ 'width' ], $meta[ 'height' ], $width, $height, $crop );

		# Shortcuts to expected widths and sizes
		$w = $dims[ 4 ];
		$h = $dims[ 5 ];

		# If the image is not resized, go on
		if( $meta[ 'width' ] == $w && $meta[ 'height' ] == $h ) {
			return $downsize;
		}

		# Check for the appropriate file
		$path = $upload_dir . '/' . $meta[ 'file' ];
		$file = preg_replace( "~^(.+)\.(jpeg|jpg|png)$~i", "$1-$w-$h.$2", $path );

		if( ! file_exists( $file ) ) {
			$editor = wp_get_image_editor( $path );

			if( $editor->resize( $w, $h, $crop ) ) {
				$editor->save( $file );
				$img_url  = str_replace( $upload_dir, $upload_url, $file );
				$filetype = wp_check_filetype( basename( $file ), null );

				# Update the image's metadata
				if( is_string( $size ) ) {
					# Check if there is a size to overwrite
					if( isset( $meta[ 'sizes' ][ $size ] ) ) {
						$dir = dirname( $path );
						$old = $dir . '/' . $meta[ 'sizes' ][ $size ][ 'file' ];

						unlink( $old );
					}

					# Save the new size
					$meta[ 'sizes' ][ $size ] = array(
						'file'      => basename( $file ),
						'width'     => $w,
						'height'    => $h,
						'mime-type' => $filetype[ 'type' ],
					);
				}

				wp_update_attachment_metadata( $id, $meta );

				return array( $img_url, $w, $h, $is_intermediate );
			}
		}

		return $downsize;
	}
}

new Image_Resizer();

add_action( 'after_setup_theme', function(){
	add_image_size( 'post-thumbnail', 303, 302, true );
}, 1000);