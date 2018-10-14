<?php

namespace Premmerce\Search\Frontend;

use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Search\Model\Word ;
use  Premmerce\Search\SearchPlugin ;
use  Premmerce\Search\WordProcessor ;
use  WP_Query ;
use  wpdb ;
class SearchHandler
{
    /**
     * @var Word
     */
    private  $word ;
    /**
     * @var WordProcessor
     */
    private  $processor ;
    /**
     * @var wpdb
     */
    private  $wpdb ;
    /**
     * @var
     */
    private  $searchWord ;
    /**
     * @var
     */
    private  $fileManager ;
    /**
     * @var array|null
     */
    private  $cachedLikeQueries = null ;
    /**
     * @var array
     */
    private  $whereToSearch = array() ;
    /**
     * @var string
     */
    private  $postsInSkuSearch = '' ;
    /**
     * SearchHandler constructor.
     *
     * @param Word $word
     * @param WordProcessor $processor
     * @param FileManager $fileManager
     */
    public function __construct( Word $word, WordProcessor $processor, FileManager $fileManager )
    {
        global  $wpdb ;
        $this->wpdb = $wpdb;
        $this->word = $word;
        $this->processor = $processor;
        $this->fileManager = $fileManager;
        add_action( 'wp_footer', array( $this, 'renderAutocompleteItem' ) );
        add_action( 'parse_query', function ( WP_Query $query ) {
            if ( !$this->searchWord ) {
                $this->searchWord = esc_sql( mb_strtolower( $query->get( 's' ) ) );
            }
        } );
        add_filter(
            'posts_search',
            array( $this, 'getSkuIds' ),
            10,
            2
        );
        add_filter(
            'posts_search',
            array( $this, 'extendSearch' ),
            10,
            2
        );
        add_filter(
            'posts_fields',
            array( $this, 'extendSearchFields' ),
            10,
            2
        );
        add_filter(
            'posts_search_orderby',
            array( $this, 'extendSearchOrder' ),
            10,
            2
        );
    }
    
    /**
     * Prepare post ids for sku search
     *
     * @param $request
     * @param WP_Query $wpQuery
     */
    public function getSkuIds( $request, WP_Query $wpQuery )
    {
        #/premmerce_clear
    }
    
    /**
     * Render autocomplete item template in footer
     */
    public function renderAutocompleteItem()
    {
        $this->fileManager->includeTemplate( 'frontend/autocomplete-template.php' );
    }
    
    /**
     * @param string $fields
     * @param WP_Query $wpQuery
     *
     * @return string
     */
    public function extendSearchFields( $fields, WP_Query $wpQuery )
    {
        
        if ( $wpQuery->is_search() ) {
            $likes = $this->getLikeQueries();
            $likeExcerpt = $this->getLikeExcerptPart();
            if ( count( $likes ) ) {
                return $fields . ', (' . implode( '+', $likes ) . $likeExcerpt . $this->postsInSkuSearch . ') as relevance';
            }
        }
        
        return $fields;
    }
    
    /**
     * @param string $orderBy
     * @param WP_Query $wpQuery
     *
     * @return string
     */
    public function extendSearchOrder( $orderBy, WP_Query $wpQuery )
    {
        
        if ( $wpQuery->is_search() ) {
            $likes = $this->getLikeQueries();
            if ( count( $likes ) ) {
                return 'relevance DESC';
            }
        }
        
        return $orderBy;
    }
    
    /**
     * Extends default wordpress search
     *
     * @param string $request
     * @param WP_Query $wpQuery
     *
     * @return string
     */
    public function extendSearch( $request, WP_Query $wpQuery )
    {
        
        if ( $wpQuery->is_search() ) {
            $likes = $this->getLikeQueries();
            $likeExcerpt = $this->getLikeExcerptPart();
            $args = '(' . implode( '+', $likes ) . $likeExcerpt . $this->postsInSkuSearch . ')';
            $request = sprintf( 'AND ((%s) >= 1 )', $args );
        }
        
        return $request;
    }
    
    /**
     * Create array of like queries for relevance
     *
     *
     * @return array|null
     */
    private function getLikeQueries()
    {
        
        if ( is_null( $this->cachedLikeQueries ) ) {
            $wordsFromSearch = $this->processor->splitString( $this->searchWord );
            $this->processor->setDictionary( $this->word->getWords() );
            $matchedWords = $this->processor->matchWords( $wordsFromSearch );
            //real search string
            $likes[] = '(' . $this->wpdb->prepare( "{$this->wpdb->posts}.post_title LIKE '%s'", '%' . $this->searchWord . '%' ) . ') * 2';
            //real search words
            foreach ( $wordsFromSearch as $singleSearchWord ) {
                $likes[] = '(' . $this->wpdb->prepare( "{$this->wpdb->posts}.post_title LIKE '%s'", '%' . $singleSearchWord . '%' ) . ')';
            }
            //found in dictionary
            foreach ( $matchedWords as $word ) {
                $likes[] = '(' . $this->wpdb->prepare( "{$this->wpdb->posts}.post_title LIKE '%s'", '%' . $word . '%' ) . ')';
            }
            $this->cachedLikeQueries = $likes;
        }
        
        return $this->cachedLikeQueries;
    }
    
    /**
     *
     * @return string
     *
     */
    private function getLikeExcerptPart()
    {
        $excerptLike = '';
        if ( isset( $this->whereToSearch['excerpt'] ) && $this->whereToSearch['excerpt'] ) {
            $excerptLike = '+(' . $this->wpdb->prepare( " {$this->wpdb->posts}.post_excerpt LIKE '%s'", '%' . $this->searchWord . '%' ) . ')';
        }
        return $excerptLike;
    }
    
    /**
     * Search by product and product variation sku.
     *
     * @return string
     */
    private function getIdsForSkuSearch()
    {
        $postsInQueryPart = '';
        $productsIds = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT p.ID FROM {$this->wpdb->posts} p \n\t\t\t\t\t\tINNER JOIN {$this->wpdb->postmeta} pm ON (pm.post_id = p.ID AND pm.meta_key = '_sku' AND pm.meta_value LIKE %s ) \n\t\t\t\t\t  \tWHERE post_type  = 'product'", '%' . $this->searchWord . '%' ) );
        $productVariationsIds = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT DISTINCT p.post_parent \n\t\t\t\t\t  \tFROM {$this->wpdb->posts} p\n\t\t\t\t\t  \tINNER JOIN {$this->wpdb->postmeta} pm ON (pm.post_id = p.ID AND pm.meta_key = '_sku' AND pm.meta_value LIKE %s ) \n\t\t\t\t\t  \tWHERE post_type = 'product_variation'", '%' . $this->searchWord . '%' ) );
        $foundIds = array_merge( $productsIds, $productVariationsIds );
        
        if ( $foundIds ) {
            $placeholders = array_fill( 0, count( $foundIds ), '%d' );
            $placeholders = implode( ',', $placeholders );
            $postsInQueryPart = $this->wpdb->prepare( " +( {$this->wpdb->posts}.ID IN ({$placeholders}) ) * 3", $foundIds );
        }
        
        return $postsInQueryPart;
    }

}