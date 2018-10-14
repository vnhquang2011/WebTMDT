<?php if(!defined('WPINC')) die; ?>

<div style="display: none!important;" data-autocomplete-templates>

    <!-- Autocomplete list item template -->
    <a class="pc-autocomplete pc-autocomplete--item" href="#" data-autocomplete-template="item">
        <div class="pc-autocomplete__product">
            <!-- Photo  -->
            <div class="pc-autocomplete__product-photo" style="display: none;" data-autocomplete-product-photo>
                <img class="pc-autocomplete__img" src="#" alt="No photo" data-autocomplete-product-img></span>
            </div>

            <div class="pc-autocomplete__product-info">
                <!-- Title -->
                <div class="pc-autocomplete__product-title" data-autocomplete-product-name></div>
                <!-- Price -->
                <div class="pc-autocomplete__product-price">
                    <div class="product-price product-price--sm product-price--bold" data-autocomplete-product-price>
                    </div>
                </div>
            </div>
        </div>
    </a>

    <!-- Autocomplete Show all result item template -->
    <div class="pc-autocomplete pc-autocomplete--item pc-autocomplete__message pc-autocomplete__message--show-all" data-autocomplete-template="allResult">
        <a href="#woocommerce-product-search-field" data-autocomplete-show-all-result>
            <?php esc_html_e('All search results', 'premmerce-search'); ?>
        </a>
    </div>

</div>