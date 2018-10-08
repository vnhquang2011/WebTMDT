<?php
if (!defined('ABSPATH'))
    die('No direct access allowed');
//for woof_meta_get_keys method
//another way is stupid copy/paste!
class WOOF_PDS_CPT extends WC_Product_Data_Store_CPT {

    public function get_internal_meta_keys() {
        return $this->internal_meta_keys;
    }

}

 