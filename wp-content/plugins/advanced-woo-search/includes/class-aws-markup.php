<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Markup' ) ) :

    /**
     * Class for plugin search action
     */
    class AWS_Markup {

        /*
         * Generate search box markup
         */
        public function markup() {

            global $wpdb;

            $table_name = $wpdb->prefix . AWS_INDEX_TABLE_NAME;

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
                echo 'Please go to <a href="' . admin_url( 'admin.php?page=aws-options' ) . '">plugins settings page</a> and click on "Reindex table" button.';
                return;
            }


            $placeholder   = AWS_Helpers::translate( 'search_field_text', AWS()->get_settings( 'search_field_text' ) );
            $min_chars     = AWS()->get_settings( 'min_chars' );
            $show_loader   = AWS()->get_settings( 'show_loader' );
            $show_more     = AWS()->get_settings( 'show_more' );
            $show_page     = AWS()->get_settings( 'show_page' );
            $show_clear    = AWS()->get_settings( 'show_clear' );
            $use_analytics = AWS()->get_settings( 'use_analytics' );

            $current_lang = AWS_Helpers::get_lang();

            $url_array = parse_url( home_url() );
            $url_query_parts = array();

            if ( isset( $url_array['query'] ) && $url_array['query'] ) {
                parse_str( $url_array['query'], $url_query_parts );
            }


            $params_string = '';

            $params = array(
                'data-url'           => admin_url('admin-ajax.php'),
                'data-siteurl'       => home_url(),
                'data-lang'          => $current_lang ? $current_lang : '',
                'data-show-loader'   => $show_loader,
                'data-show-more'     => $show_more,
                'data-show-page'     => $show_page,
                'data-show-clear'    => $show_clear,
                'data-use-analytics' => $use_analytics,
                'data-min-chars'     => $min_chars,
            );

            foreach( $params as $key => $value ) {
                $params_string .= $key . '="' . $value . '" ';
            }

            $markup = '';
            $markup .= '<div class="aws-container" ' . $params_string . '>';
            $markup .= '<form class="aws-search-form" action="' . home_url('/') . '" method="get" role="search" >';
            $markup .= '<input  type="text" name="s" value="' . get_search_query() . '" class="aws-search-field" placeholder="' . $placeholder . '" autocomplete="off" />';
            $markup .= '<input type="hidden" name="post_type" value="product">';
            $markup .= '<input type="hidden" name="type_aws" value="true">';

            if ( $current_lang ) {
                $markup .= '<input type="hidden" name="lang" value="' . $current_lang . '">';
            }

            if ( $url_query_parts ) {
                foreach( $url_query_parts as $url_query_key => $url_query_value  ) {
                    $markup .= '<input type="hidden" name="' . $url_query_key . '" value="' . $url_query_value . '">';
                }
            }

            $markup .= '<div class="aws-search-clear">';
                $markup .= '<span aria-label="Clear Search">×</span>';
            $markup .= '</div>';

            $markup .= '</form>';
            $markup .= '</div>';

            return apply_filters( 'aws_searchbox_markup', $markup );

        }

    }

endif;