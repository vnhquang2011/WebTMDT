<?php

if ( ! defined('WPINC')) {
    die;
}

if (function_exists('premmerce_ps_fs') && premmerce_ps_fs()->is_registered()) {
    premmerce_ps_fs()->add_filter('hide_account_tabs', '__return_true');
    premmerce_ps_fs()->_account_page_load();
    premmerce_ps_fs()->_account_page_render();
}
