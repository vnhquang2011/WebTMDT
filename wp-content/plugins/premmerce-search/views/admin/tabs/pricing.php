<?php

if ( ! defined('WPINC')) {
    die;
}

if (function_exists('premmerce_ps_fs')) {
    premmerce_ps_fs()->_pricing_page_render();
}
