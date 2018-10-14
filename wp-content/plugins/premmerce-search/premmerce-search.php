<?php

use Premmerce\Search\SearchPlugin;

/**
 * WooCommerce Product Search
 *
 * @package           Premmerce\Search
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Product Search
 * Plugin URI:        https://premmerce.com/woocommerce-product-search/
 * Description:       Premmerce Search makes the WooCommerce product search more flexible and efficient and gives the additional search results due to the spell correction.
 * Version:           2.0.6
 * Author:            premmerce
 * Author URI:        https://premmerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       premmerce-search
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4
 *
 * @fs_premium_only /views/admin/fields
 *
 */

// If this file is called directly, abort.
if ( ! defined('WPINC')) {
    die;
}

if ( ! function_exists('premmerce_ps_fs')) {

    call_user_func(function () {

        require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        #premmerce_clear
        require_once plugin_dir_path(__FILE__) . '/freemius.php';
        #/premmerce_clear
        $main = new SearchPlugin(__FILE__);

        register_activation_hook(__FILE__, [$main, 'activate']);

        register_uninstall_hook(__FILE__, [SearchPlugin::class, 'uninstall']);

        $main->run();
    });
}
