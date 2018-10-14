<?php

namespace Premmerce\Search\Frontend;

use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Search\SearchPlugin ;
use  WP_Query ;
use  WP_REST_Request ;
use  WP_REST_Response ;
use  WP_REST_Server ;
class RestController
{
    /**
     * @var string
     */
    private  $searchPath ;
    /**
     * @var string
     */
    private  $namespace = 'premmerce-search/v1' ;
    /**
     * @var string
     */
    private  $route = '/search' ;
    /**
     * @var FileManager
     */
    private  $fileManager ;
    /**
     * @var int
     */
    private  $maxResultsNum = 6 ;
    /**
     * @var int
     */
    private  $minToSearch = 3 ;
    /**
     * RestController constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct( FileManager $fileManager )
    {
        $this->fileManager = $fileManager;
        $this->searchPath = $this->namespace . $this->route;
        #/premmerce_clear
        add_action( 'rest_api_init', function () {
            register_rest_route( $this->namespace, $this->route, array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'search' ),
            ) );
        } );
        add_action( 'wp_enqueue_scripts', function () use( $fileManager ) {
            wp_enqueue_script( 'premmerce_search', $fileManager->locateAsset( 'frontend/js/autocomplete.js' ), array( 'jquery', 'jquery-ui-autocomplete' ) );
            wp_enqueue_style( 'premmerce_search_css', $fileManager->locateAsset( 'frontend/css/autocomplete.css' ) );
            wp_localize_script( 'premmerce_search', 'premmerceSearch', array(
                'url'            => esc_url_raw( apply_filters( 'wpml_permalink', rest_url( $this->searchPath ) ) ),
                'minLength'      => $this->minToSearch,
                'showAllMessage' => __( 'All search results', 'premmerce-search' ),
                'nonce'          => wp_create_nonce( 'wp_rest' ),
            ) );
        } );
    }
    
    /**
     * Returns json items by term
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function search( WP_REST_Request $request )
    {
        $term = mb_strtolower( $request->get_param( 'term' ) );
        $suggestions = array();
        $productVisibilityTerms = wc_get_product_visibility_term_ids();
        $productVisibilityNotIn[] = $productVisibilityTerms['exclude-from-search'];
        // Hide out of stock products.
        if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
            $productVisibilityNotIn[] = $productVisibilityTerms['outofstock'];
        }
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => $this->maxResultsNum,
            's'              => $term,
            'tax_query'      => array( array(
            'taxonomy' => 'product_visibility',
            'field'    => 'term_taxonomy_id',
            'terms'    => $productVisibilityNotIn,
            'operator' => 'NOT IN',
        ) ),
        );
        $loop = new WP_Query( $args );
        while ( $loop->have_posts() ) {
            $loop->the_post();
            $product = wc_get_product( get_the_ID() );
            $suggestion = array();
            $suggestion['label'] = get_the_title();
            $suggestion['link'] = get_permalink();
            $suggestion['image'] = get_the_post_thumbnail_url();
            $suggestion['price'] = $product->get_price_html();
            $suggestions[] = $suggestion;
        }
        return new WP_REST_Response( $suggestions, 200 );
    }

}