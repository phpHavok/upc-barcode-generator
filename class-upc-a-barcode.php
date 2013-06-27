<?php

namespace sunsoft;

/**
 * @version 1.0.1
 * @todo More/better documentation.
 */
class UPC_A_Barcode {
	/**
	 * The UPC of the barcode. Stored internally as an array
	 * of digits.
	 *
	 * @since 1.0
	 */
	private $upc;
	
	/**
	 * A conversion table for the left bits of the UPC.
	 *
	 * @since 1.0
	 */
	private static $upc_left_bits_table = array(
		0 => array( 0, 0, 0, 1, 1, 0, 1 ),
		1 => array( 0, 0, 1, 1, 0, 0, 1 ),
		2 => array( 0, 0, 1, 0, 0, 1, 1 ),
		3 => array( 0, 1, 1, 1, 1, 0, 1 ),
		4 => array( 0, 1, 0, 0, 0, 1, 1 ),
		5 => array( 0, 1, 1, 0, 0, 0, 1 ),
		6 => array( 0, 1, 0, 1, 1, 1, 1 ),
		7 => array( 0, 1, 1, 1, 0, 1, 1 ),
		8 => array( 0, 1, 1, 0, 1, 1, 1 ),
		9 => array( 0, 0, 0, 1, 0, 1, 1 )
	);
	
	/**
	 * A conversion table for the right bits of the UPC.
	 *
	 * @since 1.0
	 */
	private static $upc_right_bits_table = array(
		0 => array( 1, 1, 1, 0, 0, 1, 0 ),
		1 => array( 1, 1, 0, 0, 1, 1, 0 ),
		2 => array( 1, 1, 0, 1, 1, 0, 0 ),
		3 => array( 1, 0, 0, 0, 0, 1, 0 ),
		4 => array( 1, 0, 1, 1, 1, 0, 0 ),
		5 => array( 1, 0, 0, 1, 1, 1, 0 ),
		6 => array( 1, 0, 1, 0, 0, 0, 0 ),
		7 => array( 1, 0, 0, 0, 1, 0, 0 ),
		8 => array( 1, 0, 0, 1, 0, 0, 0 ),
		9 => array( 1, 1, 1, 0, 1, 0, 0 )
	);
	
	/**
	 * Construct a new object.
	 *
	 * @since 1.0
	 */
	public function __construct( $upc ) {
		$this->set_upc( $upc );
	}
	
	/**
	 * Set the UPC.
	 *
	 * @since 1.0
	 */
	public function set_upc( $upc ) {
		$upc = (string) $upc;
		
		if ( 1 !== preg_match( '/\d{12}/', $upc ) )
			throw new \Exception( 'Invalid UPC given: ' . $upc );
		
		$this->upc = array_map( 'intval', str_split( $upc ) );
	}
	
	/**
	 * Return the UPC as a string.
	 *
	 * @since 1.0
	 */
	public function get_upc() {
		return implode( '', array_map( 'strval', $this->upc ) );
	}
	
	/**
	 * Convert a left digit into its UPC bit pattern.
	 *
	 * @since 1.0
	 */
	protected function upc_left_bits_for_digit( $digit ) {
		return self::$upc_left_bits_table[ $digit ];
	}
	
	/**
	 * Convert a right digit into its UPC bit pattern.
	 *
	 * @since 1.0
	 */
	protected function upc_right_bits_for_digit( $digit ) {
		return self::$upc_right_bits_table[ $digit ];
	}
	
	/**
	 * Create the barcode from the UPC, and return its image data.
	 *
	 * @since 1.0
	 */
	public function get_barcode_symbol( $base_width = 1 ) {
		$bits = array();
		
		// Left margin
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		
		// Left-hand guard
		$bits[] = 1;
		$bits[] = 0;
		$bits[] = 1;
		
		// Left-side digits
		$upc_digits_left = array_slice( $this->upc, 0, 6 );
		
		foreach ( $upc_digits_left as $digit )
			$bits = array_merge( $bits, $this->upc_left_bits_for_digit( $digit ) );
		
		// Center guard
		$bits[] = 0;
		$bits[] = 1;
		$bits[] = 0;
		$bits[] = 1;
		$bits[] = 0;
		
		// Right-side digits
		$upc_digits_right = array_slice( $this->upc, 6 );
		
		foreach ( $upc_digits_right as $digit )
			$bits = array_merge( $bits, $this->upc_right_bits_for_digit( $digit ) );
		
		// Right-hand guard
		$bits[] = 1;
		$bits[] = 0;
		$bits[] = 1;
		
		// Right margin
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		$bits[] = 0;
		
		// Calculate the barcode image size.
		$barcode_image_width = count( $bits ) * $base_width;
		$barcode_image_height = $barcode_image_width;
		
		// Generate the image buffer.
		$barcode_image = imagecreatetruecolor( $barcode_image_width, $barcode_image_height );
		
		// Draw the background.
		$background_color = imagecolorallocate( $barcode_image, 0xFF, 0xFF, 0xFF );
		imagefill( $barcode_image, 0, 0, $background_color );
		imagecolordeallocate( $barcode_image, $background_color );
		
		// Draw the barcode.
		$foreground_color = imagecolorallocate( $barcode_image, 0x00, 0x00, 0x00 );
		
		imagesetthickness( $barcode_image, $base_width );
		
		$x = 0;
		
		foreach ( $bits as $bit ) {
			if ( 1 === $bit )
				imageline( $barcode_image, $x, 0, $x, $barcode_image_height, $foreground_color );
			
			$x += $base_width;
		}
		
		imagecolordeallocate( $barcode_image, $foreground_color );
		
		// Generate the PNG image data.
		ob_start();
		imagepng( $barcode_image );
		imagedestroy( $barcode_image );
		
		// Return the PNG image data.
		return ob_get_clean();
	}
}

?>