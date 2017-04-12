<?php
namespace Dynamic_Resizer;

/**
 * Adds the needed actions for the whole plugin.
 *
 * @since 0.1
 */
class Dynamic_Resizer {
	/**
	 * Creates an instance of the core class.
	 *
	 * @since 0.1
	 */
	public static function init() {
		static $did;

		if( is_null( $did ) ) {
			new self();
			$did = true;
		}
	}

	/**
	 * Instantiates the class and adds the main needed listeners/hooks.
	 *
	 * @since 0.1
	 */
	protected function __construct() {
		spl_autoload_register( array( $this, 'autoload' ) );
		add_filter( 'intermediate_image_sizes', array( $this, 'fake_sizes' ) );
		add_filter( 'image_downsize', array( $this, 'downsize' ), 10, 3 );
	}

	/**
	 * Attempts to load a class from the plugin.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the needed class.
	 */
	public function autoload( $class_name ) {
		$namespace = 'Dynamic_Resizer\\';

		if( 0 !== strpos( $class_name, $namespace ) ) {
			return;
		}

		$class_name     = str_replace( $namespace, '', $class_name );
		$class_subpath  = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
		$class_subpath .= '.php';
		$class_path     = DYNAMIC_RESIZER_DIR . 'classes' . DIRECTORY_SEPARATOR . $class_subpath;

		if( file_exists( $class_path ) ) {
			include_once( $class_path );
		}
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
		if( $downsize ) {
			# Size already generated
			return $downsize;
		} else {
			try {
				$image = new Image( $id, $size );
				return $image->resize();
			} catch( \Exception $e ) {
				return false;
			}
		}
	}
}