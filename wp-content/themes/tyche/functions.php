<?php
/**
 * Tyche functions and definitions.
 *
 * @link    https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Tyche
 */

/**
 * Start Tyche theme framework
 */
require_once 'inc/class-tyche-autoloader.php';
$tyche = new Tyche();

/**
 * @snippet       Add "So Luong" Label in front of Them vao gio
 * @original-sourcecode    https://businessbloomer.com/?p=21986
 * @author        Rodolfo Melogli-Quang Truong
 */
 
add_action( 'woocommerce_before_add_to_cart_quantity', 'bbloomer_echo_qty_front_add_cart' );
 
function bbloomer_echo_qty_front_add_cart() {
 echo '<div class="qty">Số lượng: </div>'; 
}