<?php
namespace Dynamic_Resizer;

/**
 * Works with image sizes.
 *
 * @since 0.1
 */
class Size {
	/**
	 * Holds the width of the size.
	 *
	 * @since 0.1
	 * @var int
	 */
	protected $width = 0;

	/**
	 * Holds the height of the size.
	 *
	 * @since 0.1
	 * @var int
	 */
	protected $height = 0;

	/**
	 * Indicates if the image must be cropped.
	 *
	 * @since 0.1
	 * @var bool
	 */
	protected $crop = false;

	/**
	 * Indicates if the image size is intermediate.
	 *
	 * @since 0.1
	 * @var mixed
	 */
	protected $intermediate = false;

	/**
	 * Initializes the size.
	 *
	 * @since 0.1
	 *
	 * @param mixed $size Either a string name or an array of dimentions.
	 */
	protected function __construct( $size ) {
		$details      = false;
		$intermediate = false;
		$sizes        = $this->get_intermediate_sizes();

		if( is_string( $size ) && isset( $sizes[ $size ] ) ) {
			$this->intermediate = $size;
			$details            = $sizes[ $size ];
		} elseif( is_array( $size ) && ( isset( $size[ 'width' ] ) || isset( $size[ 'height' ] ) ) ) {
			$details = $size;
		} elseif( is_array( $size ) && isset( $size[ 0 ] ) ) {
			$details = array(
				'width'  => $size[ 0 ],
				'height' => isset( $size[ 1 ] ) ? $size[ 1 ] : 0,
				'crop'   => isset( $size[ 2 ] ) ? true : false
			);
		} else {
			throw new \Exception( "The requested size could not be located." );
		}

		if( isset( $details[ 'width' ] ) )
			$this->width  = $details[ 'width' ];

		if( isset( $details[ 'height' ] ) )
			$this->height = $details[ 'height' ];

		if( isset( $details[ 'crop' ] ) )
			$this->crop   = $details[ 'crop' ];
	}

	/**
	 * Returns all intermediate sizes (incl. thumbnail).
	 *
	 * @since 0.1
	 *
	 * @return mixed[]
	 */
	protected function get_intermediate_sizes() {
		static $sizes;

		if( ! is_null( $sizes ) ) {
			return $sizes;
		}

		foreach( get_intermediate_image_sizes() as $s ) {
			if( isset( $GLOBALS['_wp_additional_image_sizes' ][ $s ] ) ) {
				$sizes[ $s ] = $GLOBALS['_wp_additional_image_sizes' ][ $s ];
			}
		}

		$sizes[ 'thumbnail' ] = array(
			'width'  => intval( get_option( 'thumbnail_size_w' ) ),
			'height' => intval( get_option( 'thumbnail_size_h' ) ),
			'crop'   => intval( get_option( 'thumbnail_crop' ) )
		);

		$sizes[ 'medium' ] = array(
			'width'  => intval( get_option( 'medium_size_w' ) ),
			'height' => intval( get_option( 'medium_size_h' ) ),
			'crop'   => intval( get_option( 'medium_crop' ) )
		);

		$sizes[ 'large' ] = array(
			'width'  => intval( get_option( 'large_size_w' ) ),
			'height' => intval( get_option( 'large_size_h' ) ),
			'crop'   => intval( get_option( 'large_crop' ) )
		);

		return $sizes;
	}

	/**
	 * Returns a size based on dimentions or size.
	 *
	 * @since 0.1
	 *
	 * @param mixed $size Either a string name or an array of dimentions.
	 * @return Image_Size
	 */
	public static function get( $size ) {
		static $generated;

		if( is_array( $size ) ) {
			$codename = implode( ',', $size );
		} else {
			$codename = $size;
		}

		if( is_null( $generated ) ) {
			$generated = array();
		}

		if( isset( $generated[ $codename ] ) ) {
			return $generated[ $codename ];
		} else {
			return $generated[ $codename ] = new self( $size );
		}
	}

	/**
	 * Returns the properties of the size.
	 *
	 * @since 0.1
	 *
	 * @param string $name The name of the needed property.
	 * @return mixed
	 */
	public function __get( $name ) {
		return property_exists( $this, $name )
			? $this->$name
			: false;
	}
}
