<?php
/*
Plugin Name: FacetWP - Dropdown Images for WooCommerce Categories
Description: 
Version: 1.0.0
*/

defined( 'ABSPATH' ) or exit;

class FacetWP_Dropdown_Images {


	public $facet_names = array();

	function __construct() {

		define( 'FWPDI_VERSION', '1.0.0' );
		define( 'FWPDI_DIR', dirname( __FILE__ ) );
		define( 'FWPDI_URL', plugins_url( '', __FILE__ ) );
		define( 'FWPDI_BASENAME', plugin_basename( __FILE__ ) );

		add_action( 'init', array( $this, 'init' ), 12 );
	}


	function init() {
		if ( ! function_exists( 'FWP' ) ) {
			return;
		}

		$this->find_facets();

		// register assets
		wp_register_script( 'fwpdi-front', FWPDI_URL . '/assets/js/msdropdown/jquery.dd.min.js', array( 'jquery' ), FWPDI_VERSION, true );
		wp_register_style( 'fwpdi-front', FWPDI_URL . '/assets/css/msdropdown/dd.css', array(), FWPDI_VERSION );

		// wp hooks
		add_action( 'wp_footer', array( $this, 'render_assets' ) );
		add_action( 'wp_footer', array( $this, 'add_inline' ) );

		add_filter( 'facetwp_facet_html', array( $this, 'filter_html' ), 10, 2 );
	}

	/**
	 * create an array of product category sources
	 */
	function find_facets() {
	    $facets = FWP()->helper->get_facets();
	    foreach ( $facets AS $facet ) {
	        if ( isset( $facet['source'] ) && 'tax/product_cat' == $facet['source'] && isset( $facet['type'] )&& 'dropdown' == $facet['type'] ) {
	            $this->facet_names[] = $facet['name'];
            }
        }
    }

	function render_assets() {
		wp_enqueue_style( 'fwpdi-front' );
		wp_enqueue_script( 'fwpdi-front' );
	}

	/**
	 * add inline css and js to footer
     *
     * TODO: figure out a way to target only select box for a product_cat dropdown
	 */
	function add_inline() {
	    $facet_classes = array();
		foreach ( $this->facet_names AS $facet ) {
			$facet_classes[] = 'body .facetwp-facet-' . $facet . ' select';
		}
	    if ( !empty( $facet_classes ) ) : ?>
        <style>
            .dd img { width: 50px; display: inline-block; }
            .dd { width: 100% !important; }
        </style>
		<script>
            (function($) {
                $(document).on('facetwp-loaded', function() {
                    $("<?php echo implode( ',', $facet_classes ); ?>").msDropDown();
                });
            })(jQuery);
		</script>
        <?php endif;
	}

	/**
     * filter html output to add data-image to select options
     *
	 * @param $output
	 * @param $params
	 *
	 * @return mixed
	 */
	function filter_html( $output, $params ) {
		if ( 'dropdown' == $params['facet']['type'] && "tax/product_cat" == $params['facet']['source'] ) {

			$terms = get_terms( 'product_cat', array(
				'hide_empty' => false,
			) );

			$search = array();
			$replace = array();

			foreach ( $terms AS $term ) {
				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$image = wp_get_attachment_image_url( $thumbnail_id );
				if ( $image ) {
					$search[] = 'value="' . $term->slug . '"';
					$replace[] = 'value="' . $term->slug . '" data-image="' . $image . '"';
				}
			}

			$output = str_replace( $search, $replace, $output );
		}
		return $output;
	}
}

new FacetWP_Dropdown_Images();
