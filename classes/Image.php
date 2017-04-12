<?php
namespace Dynamic_Resizer;

/**
 * Handles singular image resizing.
 *
 * @since 0.1
 */
class Image {
	/**
	 * Holds the ID of the image in the database.
	 *
	 * @since 0.1
	 * @var int
	 */
	protected $id;

	/**
	 * Contains the image size, which is needed/requested.
	 *
	 * @since 0.1
	 * @var mixed
	 */
	protected $size;

	/**
	 * Constructs the resizable image.
	 *
	 * @since 0.1
	 *
	 * @param int          $id   The ID of the image.
	 * @param array|string $size Either an array of dimensions or a string.
	 */
	public function __construct( $id, $size ) {
		$this->id   = $id;
		$this->size = Size::get( $size );
	}

	/**
	 * Resizes the image.
	 *
	 * @since 3.0
	 *
	 * @return mixed Either an array with image attributes or false in case of errors.
	 */
	public function resize() {
		# Don't resize non-intermediate sizes
		if( ! $this->size->intermediate ) {
			return false;
		}

		$meta       = wp_get_attachment_metadata( $this->id );
		$uploads    = wp_upload_dir();
		$upload_dir = $uploads[ 'basedir' ];
		$upload_url = $uploads[ 'baseurl' ];

		# Calculate the size that would be needed
		$dims = image_resize_dimensions(
			// Original dimentions
			$meta[ 'width' ],
			$meta[ 'height' ],

			// Needed dimentions and crop
			$this->size->width, $this->size->height, $this->size->crop
		);

		# Shortcuts to expected widths and sizes
		$w = $dims[ 4 ];
		$h = $dims[ 5 ];

		# If the image does not need to be resized, continue.
		if( $meta[ 'width' ] == $w && $meta[ 'height' ] == $h ) {
			return false;
		}

		# Check for the appropriate file
		$path = $upload_dir . '/' . $meta[ 'file' ];

		# Missing originals cannot be resized
		if( ! file_exists( $path ) ) {
			throw new \Exception( "Original file does not exist." );
		}

		# Create an editor and look for the resized name
		$editor  = wp_get_image_editor( $path );
		$file    = $editor->generate_filename( $w . 'x' . $h );
		$img_url = str_replace( $upload_dir, $upload_url, $file );

		# If the file already exists, use it
		if( file_exists( $file ) ) {
			# Make sure the size is saved for next time
			$this->save_size( $file, $w, $h );
			return array( $img_url, $w, $h, $this->size->intermediate );
		}

		# Try resizing
		if( ! $editor->resize( $w, $h, $this->size->crop ) ) {
			throw new \Exception( 'Editor could not resize the file.' );
		}

		# Save the file
		$editor->save( $file );

		# Check if there is a size to overwrite. If we got there, something changed
		if( isset( $meta[ 'sizes' ][ $this->size->intermediate ] ) ) {
			$file_used = false;
			$old_path  = $meta[ 'sizes' ][ $this->size->intermediate ][ 'file' ];

			foreach( $meta[ 'sizes' ] as $size_name => $size ) {
				if( $old_path == $size[ 'file' ] && $size_name != $this->size->intermediate ) {
					$file_used = true;
				}
			}

			# Only if the old image file is not used, delete it
			if( ! $file_used ) {
				unlink( dirname( $path ) . '/' . $old_path );
			}
		}

		# Save the new size
		$this->save_size( $file, $w, $h );

		return array( $img_url, $w, $h, $this->size->intermediate );
	}

	/**
	 * Saves the current size.
	 *
	 * @since 0.1
	 *
	 * @param string $file   The file that corresponds to the name.
	 * @param int    $width  The width of the file/image.
	 * @param int    $height The height of the file/image.
	 */
	protected function save_size( $file, $width, $height ) {
		$meta     = wp_get_attachment_metadata( $this->id );
		$filetype = wp_check_filetype( basename( $file ), null );

		$meta[ 'sizes' ][ $this->size->intermediate ] = array(
			'file'      => basename( $file ),
			'width'     => $width,
			'height'    => $height,
			'mime-type' => $filetype[ 'type' ]
		);

		// $meta = apply_filters( 'wp_generate_attachment_metadata', $meta, $this->id );

		wp_update_attachment_metadata( $this->id, $meta );
	}
}
