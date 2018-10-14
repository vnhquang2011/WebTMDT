<?php

namespace Premmerce\Search\Admin;

use  Premmerce\Search\Model\Word ;
use  Premmerce\Search\SearchPlugin ;
use  Premmerce\SDK\V2\FileManager\FileManager ;
use  Premmerce\Search\WordProcessor ;
/**
 * Class Admin
 *
 * @package Premmerce\Search\Admin
 */
class Admin
{
    /**
     * @var FileManager
     */
    private  $fileManager ;
    /**
     * @var string
     */
    private  $settingsPage ;
    /**
     * @var Word
     */
    private  $word ;
    /**
     * @var WordProcessor
     */
    private  $wordProcessor ;
    /**
     * @var string
     */
    private  $submitButton = '' ;
    /**
     * Admin constructor.
     *
     * Register menu items and handlers
     *
     * @param FileManager $fileManager
     * @param Word $word
     * @param WordProcessor $processor
     */
    public function __construct( FileManager $fileManager, Word $word, WordProcessor $processor )
    {
        $this->fileManager = $fileManager;
        $this->word = $word;
        $this->wordProcessor = $processor;
        $this->settingsPage = SearchPlugin::DOMAIN . '-admin';
        add_action( 'admin_init', array( $this, 'registerSettings' ) );
        add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
        #premmerce_clear
        add_action( 'admin_menu', array( $this, 'addFullPack' ), 100 );
        #/premmerce_clear
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
    }
    
    /**
     * Add submenu to premmerce menu page
     */
    public function addMenuPage()
    {
        global  $admin_page_hooks ;
        $premmerceMenuExists = isset( $admin_page_hooks['premmerce'] );
        $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="20" height="16" style="fill:#82878c" viewBox="0 0 20 16"><g id="Rectangle_7"> <path d="M17.8,4l-0.5,1C15.8,7.3,14.4,8,14,8c0,0,0,0,0,0H8h0V4.3C8,4.1,8.1,4,8.3,4H17.8 M4,0H1C0.4,0,0,0.4,0,1c0,0.6,0.4,1,1,1 h1.7C2.9,2,3,2.1,3,2.3V12c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1V1C5,0.4,4.6,0,4,0L4,0z M18,2H7.3C6.6,2,6,2.6,6,3.3V12 c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1v-1.7C8,10.1,8.1,10,8.3,10H14c1.1,0,3.2-1.1,5-4l0.7-1.4C20,4,20,3.2,19.5,2.6 C19.1,2.2,18.6,2,18,2L18,2z M14,11h-4c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1h4c0.6,0,1-0.4,1-1C15,11.4,14.6,11,14,11L14,11z M14,14 c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1C15,14.4,14.6,14,14,14L14,14z M4,14c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1 c0.6,0,1-0.4,1-1C5,14.4,4.6,14,4,14L4,14z"/></g></svg>';
        $svg = 'data:image/svg+xml;base64,' . base64_encode( $svg );
        if ( !$premmerceMenuExists ) {
            add_menu_page(
                'Premmerce',
                'Premmerce',
                'manage_options',
                'premmerce',
                '',
                $svg
            );
        }
        add_submenu_page(
            'premmerce',
            __( 'Search settings', 'premmerce-search' ),
            __( 'Search settings', 'premmerce-search' ),
            'manage_options',
            $this->settingsPage,
            array( $this, 'options' )
        );
        
        if ( !$premmerceMenuExists ) {
            global  $submenu ;
            unset( $submenu['premmerce'][0] );
        }
    
    }
    
    #premmerce_clear
    public function addFullPack()
    {
        global  $submenu ;
        if ( !function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $premmerceInstalled = array_key_exists( 'premmerce-premium/premmerce.php', $plugins ) || array_key_exists( 'premmerce/premmerce.php', $plugins );
        if ( !$premmerceInstalled ) {
            $submenu['premmerce'][999] = array( 'Get premmerce full pack', 'manage_options', admin_url( 'plugin-install.php?tab=plugin-information&plugin=premmerce' ) );
        }
    }
    
    #/premmerce_clear
    /**
     * Options page
     */
    public function options()
    {
        $data = $_POST;
        if ( isset( $data[SearchPlugin::DOMAIN . '-update-indexes'] ) ) {
            $this->update();
        }
        $current = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings' );
        $tabs['settings'] = __( 'Settings', 'premmerce-search' );
        #premmerce_clear
        
        if ( function_exists( 'premmerce_ps_fs' ) ) {
            if ( premmerce_ps_fs()->is_registered() ) {
                $tabs['account'] = __( 'Account', 'premmerce-search' );
            }
            $tabs['contact'] = __( 'Contact Us', 'premmerce-search' );
            //            $tabs['pricing'] = __('Pricing', 'premmerce-search');
        }
        
        #/premmerce_clear
        $this->fileManager->includeTemplate( 'admin/main.php', array(
            'current'      => $current,
            'tabs'         => $tabs,
            'pageSlug'     => $this->settingsPage,
            'submitButton' => $this->submitButton,
        ) );
    }
    
    /**
     * Register plugin settings
     */
    public function registerSettings()
    {
        #/premmerce_clear
    }
    
    /**
     * Register admin styles
     */
    public function enqueueAssets()
    {
        if ( stristr( get_current_screen()->id, $this->settingsPage ) ) {
            wp_enqueue_style( 'premmerce-search-admin', $this->fileManager->locateAsset( 'admin/css/premmerce-search-admin.css' ) );
        }
    }
    
    /**
     * Set settings on plugin activate. Do not change existing options.
     */
    public function setSettings()
    {
        //Set default settings
        $defaultSettings = array(
            SearchPlugin::OPTIONS['minToSearch']   => 3,
            SearchPlugin::OPTIONS['resultNum']     => 6,
            SearchPlugin::OPTIONS['whereToSearch'] => array(
            'sku'     => false,
            'excerpt' => false,
        ),
        );
        foreach ( $defaultSettings as $settingName => $settingValue ) {
            if ( !get_option( $settingName ) ) {
                update_option( $settingName, $settingValue );
            }
        }
    }
    
    /**
     * Sanitize Where to search checkboxes data
     *
     * @param array $rawInput
     *
     * @return array $cleanData
     */
    public function sanitizeWhereToSearch( $rawInput )
    {
        $checkboxes = array( 'excerpt', 'sku' );
        $cleanData = array();
        foreach ( $checkboxes as $checkboxName ) {
            $cleanData[$checkboxName] = isset( $rawInput[$checkboxName] );
        }
        return $cleanData;
    }
    
    /**
     * Update indexes handler
     */
    public function update()
    {
        $words = $this->wordProcessor->prepareIndexes( $this->word->selectProductWords() );
        $this->word->updateIndexes( $words );
    }

}