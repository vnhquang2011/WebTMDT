<?php use Premmerce\Search\SearchPlugin;

if(!defined('WPINC')) die; ?>

<div class="form-wrap">
    <form id="premmerce_search_options_form" method="post" action="options.php">
	    <?php
	        settings_fields( 'premmerce_search_options' );
	        do_settings_sections( $pageSlug );
	    ?>
    </form>
</div>

<form method="post">

    <?php submit_button(__('Update indexes', 'premmerce-search'), 'secondary', SearchPlugin::DOMAIN . '-update-indexes', true, ['value' => '1'] ); ?>

</form>
<p><?php _e("We recommend that you update the indexes after the products have been changed", SearchPlugin::DOMAIN) ?></p>


<?php echo $submitButton;