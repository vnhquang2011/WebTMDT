<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'WebTMDT');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'mOw!9HIL1G1L~*8<dJ]H8dL!bC0$%n[CQ:^/k{.AjAq0G}Y4;u,mOKxrT@V#3=u#');
define('SECURE_AUTH_KEY',  'Q}/N9m/DPmE`.-JH%H(hL%2Dw$`x16B h`}&lQ6OT6aXdEKp{!r*R{@>0cwR|; G');
define('LOGGED_IN_KEY',    'C&MH>b3])p0B)AUTBB-[e&EP=~+$I`1f!~HCg^(H<l%q|p>!@#6I0a`%{c62<7vi');
define('NONCE_KEY',        'nNP?}cp!qd!$iI<,h^&aD_uhz|K%HM6[x[JQzv8&lUyvIEihWy7qIPKWr9qzhSzO');
define('AUTH_SALT',        '$A8{C0>9muv!|beoc40fNQ2ay_dvD2mAI=L(P?wp/T380=M^8/I{Ta@=pRL$h*oG');
define('SECURE_AUTH_SALT', 'kOp`sh,hqLYr_,Yji2i6kg!OAM2>AkuA%%j?  8qTLl}~Aqz,`c78/|xU|$*G/#_');
define('LOGGED_IN_SALT',   'I>E+)nD]C1]^!$RUTjC(P_tYIKwv<^!)>5-aEZUI7;NEt(y^W%tv_NuqsPv|_ze0');
define('NONCE_SALT',       'lQpQu]FskJUX$/[ Y[>MH6}Yh!?7$/e[U1VBFTdF8$S;!1J!-9(ikf!9!G)M .#X');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
