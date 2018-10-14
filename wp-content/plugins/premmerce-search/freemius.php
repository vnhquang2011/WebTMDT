<?php

// Create a helper function for easy SDK access.
function premmerce_ps_fs()
{
    global  $premmerce_ps_fs ;
    
    if ( !isset( $premmerce_ps_fs ) ) {
        // Include Freemius SDK.
        require_once dirname( __FILE__ ) . '/freemius/start.php';
        $premmerce_ps_fs = fs_dynamic_init( array(
            'id'             => '1520',
            'slug'           => 'premmerce-search',
            'type'           => 'plugin',
            'public_key'     => 'pk_d9c7db3dca9bf62e9b60d2a2ee8f8',
            'is_premium'     => false,
            'has_addons'     => false,
            'has_paid_plans' => true,
            'trial'          => array(
            'days'               => 7,
            'is_require_payment' => true,
        ),
            'menu'           => array(
            'slug'    => 'premmerce-search-admin',
            'support' => false,
            'contact' => false,
            'account' => false,
            'parent'  => array(
            'slug' => 'premmerce',
        ),
        ),
            'is_live'        => true,
        ) );
    }
    
    return $premmerce_ps_fs;
}

// Init Freemius.
premmerce_ps_fs();
// Signal that SDK was initiated.
do_action( 'premmerce_ps_fs_loaded' );