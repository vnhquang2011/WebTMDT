<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$token_key = ghn_class()->get_options('token_key');
if(!$token_key){
    printf(__('Bạn đang ở chế độ TEST nên không xem được phần này. Hãy điền Token Key <a href="%s">tại đây</a> để sử dụng chức năng này.','devvn-ghn') , admin_url('admin.php?page=devvn-woo-ghn&tab=general'));
}else{
?>
<a href="#" class="button button-primary devvn_ghn_addhub"><?php _e('Thêm kho', 'devvn-ghn'); ?></a>
<?php
$all_hubs = ghn_api()->getHubs();
if($all_hubs && !empty($all_hubs)):
wp_nonce_field('action_nonce_update', 'nonce_update');
?>
<div class="devvn_option_2col devvn_options_style">
    <?php foreach($all_hubs as $hub):
    $HubID = isset($hub['HubID']) ? $hub['HubID'] : '';
    $Address = isset($hub['Address']) ? $hub['Address'] : '';
    $ContactName = isset($hub['ContactName']) ? $hub['ContactName'] : '';
    $ContactPhone = isset($hub['ContactPhone']) ? $hub['ContactPhone'] : '';
    $ProvinceID = isset($hub['ProvinceID']) ? $hub['ProvinceID'] : '';
    $DistrictID = isset($hub['DistrictID']) ? $hub['DistrictID'] : '';
    $Email = isset($hub['Email']) ? $hub['Email'] : '';
    $IsMain = isset($hub['IsMain']) ? $hub['IsMain'] : '';
    ?>
    <div class="devvn_option_col">
        <div class="devvn_option_box">
            <table class="devvn_hubs_table widefat" cellspacing="0">
                <thead>
                <tr>
                    <th colspan="2">
                        <h2><?php _e('Kho', 'devvn-ghn'); ?> #<?php echo $HubID;?></h2>
                        <a href="#" class="khuvuc_banhang" data-hubid="<?php echo $HubID;?>" title="<?php _e('Chọn tỉnh/quận huyện mà cửa hàng/kho này sẽ giao hàng.', 'devvn-ghn'); ?>"><?php _e('Khu vực bán hàng', 'devvn-ghn'); ?></a>
                    </th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Mã kho', 'devvn-ghn'); ?></td>
                        <td>
                            <?php echo $HubID;?>
                            <input class="hub_HubID" data-name="HubID" type="hidden" value="<?php echo $HubID;?>">
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Tên người liên hệ', 'devvn-ghn'); ?></td>
                        <td><input type="text" class="hub_ContactName" data-name="ContactName" name="ContactName_<?php echo $HubID;?>" value="<?php echo $ContactName;?>"></td>
                    </tr>
                    <tr>
                        <td><?php _e('Số điện thoại liên hệ', 'devvn-ghn'); ?></td>
                        <td><input type="text" class="hub_ContactPhone" data-name="ContactPhone" name="ContactPhone_<?php echo $HubID;?>" value="<?php echo $ContactPhone;?>"></td>
                    </tr>
                    <tr>
                        <td><?php _e('Địa chỉ', 'devvn-ghn'); ?></td>
                        <td><input type="text" class="hub_Address" data-name="Address" name="Address_<?php echo $HubID;?>" value="<?php echo $Address;?>"></td>
                    </tr>
                    <tr>
                        <td><?php _e('Email', 'devvn-ghn'); ?></td>
                        <td><input type="text" class="hub_Email" data-name="Email" name="Email_<?php echo $HubID;?>" value="<?php echo $Email;?>"></td>
                    </tr>
                    <tr>
                        <td><?php _e('Khu vực', 'devvn-ghn'); ?></td>
                        <td>
                            <select name="DistrictID_<?php echo $HubID;?>" class="hub_DistrictID" data-name="DistrictID" >
                                <option value=""><?php _e('Chọn khu vực','devvn-ghn')?></option>
                                <?php if(ghn_class()->tinh_thanhpho && is_array(ghn_class()->tinh_thanhpho)):?>
                                    <?php foreach(ghn_class()->tinh_thanhpho as $k=>$v):
                                        $district = ghn_class()->get_district_id_from_string($k);
                                        ?>
                                        <option value="<?php echo $k;?>" <?php echo selected($k,$ProvinceID.'_'.$DistrictID)?> data-district="<?php echo $district;?>"><?php echo $v;?></option>
                                    <?php endforeach;?>
                                <?php endif;?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Kho chính', 'devvn-ghn'); ?></td>
                        <td><label><input type="checkbox" class="hub_IsMain" data-name="IsMain" name="ismain_<?php echo $HubID;?>" value="1" <?php checked(true, $IsMain)?>> Đặt làm kho chính</label></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text_alignright">
                            <a href="#" class="button button-primary devvn_ghn_updatehubs"><?php _e('Cập nhật thông tin', 'devvn-ghn'); ?></a>
                            <span class="spinner"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endforeach;?>
</div>
<?php
$hub_district = get_option(ghn_api()->_allhubs);
foreach($all_hubs as $hub):
    $HubID = isset($hub['HubID']) ? $hub['HubID'] : '';
    $this_hub_district = isset($hub_district[$HubID]) ? $hub_district[$HubID] : array();
    ?>
    <div id="hub_district_<?php echo $HubID;?>" class="ghn_popup_style">
        <div class="khuvu_banhang_wrap devvn_options_style">
            <div class="devvn_option_box">
                <table class="devvn_hubs_table widefat" cellspacing="0">
                    <thead>
                    <tr>
                        <th colspan="2">
                            <h2><?php printf(__('Khu vực bán hàng của kho #%s', 'devvn-ghn'), $HubID); ?></h2>
                            <input class="search_city" placeholder="<?php _e('Tìm nhanh theo tên', 'devvn-ghn'); ?>">
                            <a href="#" class="devvn_float_right devvn_checkbox_all"><?php _e('Chọn/Bỏ toàn bộ', 'devvn-ghn');?></a>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="ghn_all_state_checkbox">
                                <?php
                                $states = ghn_class()->get_states();
                                asort($states);
                                foreach($states as $k=>$v){?>
                                    <div class="ghn_all_state_checkbox_item">
                                    <label><input type="checkbox" name="hubs_district_<?php echo $HubID;?>" value="<?php echo $k;?>" <?php echo (in_array($k, $this_hub_district)) ? 'checked="checked"' : '';?>> <?php echo $v;?></label>
                                    </div>
                                <?php }?>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="2" class="text_alignright">
                            <div class="ghn_msg"></div>
                            <a href="#" class="button button-primary devvn_float_right devvn_ghn_addhubdistrict" data-hubid="<?php echo $HubID;?>"><?php _e('Lưu', 'devvn-ghn'); ?></a>
                            <a href="#" class="button close_popup devvn_float_right"><?php _e('Đóng', 'devvn-ghn'); ?></a>
                            <span class="spinner"></span>
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endforeach;?>
<?php else:?>
    <div class="nonce_hubs"><?php _e('Chưa có kho hàng nào', 'devvn-ghn'); ?></div>
<?php endif;?>
<div class="clear"></div>
<div class="add_hub_popup devvn_options_style">
    <div class="devvn_option_box">
        <table class="devvn_hubs_table widefat" cellspacing="0">
            <thead>
            <tr>
                <th colspan="2"><h2><?php _e('Thêm kho hàng/cửa hàng mới', 'devvn-ghn'); ?></h2></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?php _e('Tên người liên hệ', 'devvn-ghn'); ?></td>
                <td><input type="text" class="hub_ContactName" data-name="ContactName" name="ContactName" value=""></td>
            </tr>
            <tr>
                <td><?php _e('Số điện thoại liên hệ', 'devvn-ghn'); ?></td>
                <td><input type="text" class="hub_ContactPhone" data-name="ContactPhone" name="ContactPhone" value=""></td>
            </tr>
            <tr>
                <td><?php _e('Địa chỉ', 'devvn-ghn'); ?></td>
                <td><input type="text" class="hub_Address" data-name="Address" name="Address" value=""></td>
            </tr>
            <tr>
                <td><?php _e('Email', 'devvn-ghn'); ?></td>
                <td><input type="text" class="hub_Email" data-name="Email" name="Email" value=""></td>
            </tr>
            <tr>
                <td><?php _e('Khu vực', 'devvn-ghn'); ?></td>
                <td>
                    <select name="DistrictID" class="hub_DistrictID" data-name="DistrictID" >
                        <option value=""><?php _e('Chọn khu vực','devvn-ghn')?></option>
                        <?php if(ghn_class()->tinh_thanhpho && is_array(ghn_class()->tinh_thanhpho)):?>
                            <?php foreach(ghn_class()->tinh_thanhpho as $k=>$v):
                                $district = ghn_class()->get_district_id_from_string($k);
                                ?>
                                <option value="<?php echo $k;?>" data-district="<?php echo $district;?>"><?php echo $v;?></option>
                            <?php endforeach;?>
                        <?php endif;?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php _e('Kho chính', 'devvn-ghn'); ?></td>
                <td><label><input type="checkbox" class="hub_IsMain" data-name="IsMain" name="ismain" value="1"> Đặt làm kho chính</label></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="2" class="text_alignright">
                    <div class="ghn_msg"></div>
                    <?php wp_nonce_field('action_nonce_add', 'nonce_add');?>
                    <a href="#" class="button button-primary  devvn_ghn_addhubs"><?php _e('Thêm', 'devvn-ghn'); ?></a>
                    <a href="#" class="button close_popup devvn_float_right"><?php _e('Hủy', 'devvn-ghn'); ?></a>
                    <span class="spinner"></span>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php }?>