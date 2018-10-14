<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<tr>
    <td><?php _e('Chọn cửa hàng/kho','devvn-ghn');?></td>
    <td>
        <select name="ghn_creatorder_hub" id="ghn_creatorder_hub">
            <option class=""><?php _e('Chọn cửa hàng/kho','devvn-ghn');?></option>
            <?php
            $all_hubs = $this->getHubs();
            if(!empty($all_hubs) && is_array($all_hubs)){
                foreach($all_hubs as $hub){
                    $hubID = isset($hub['HubID']) ? $hub['HubID'] : '';
                    $Address = isset($hub['Address']) ? $hub['Address'] : '';
                    $ContactName = isset($hub['ContactName']) ? $hub['ContactName'] : '';
                    ?>
                    <option value="<?php echo $hubID;?>" <?php selected($hubID, $HubID_Order)?>><?php echo '#'.$hubID . ' - '. $ContactName .' - ' . $Address;?></option>
                    <?php
                }
            }
            ?>
        </select><br>
        <small>Phần này là tự động. Trong trường hợp thay đổi chi nhanh có thể sẽ làm phí vận chuyển thay đổi</small>
    </td>
</tr>
<tr>
    <td colspan="2">
        <div class="devvn_option_2col ghn_order_customerinfor">
            <div class="devvn_option_col ghn_more_wrap">
                <div class="devvn_option_col_title">
                    <strong>Thông tin khách hàng</strong>
                    <small>Ẩn bớt</small>
                </div>
                <div class="ghn_customer_infor ghn_more_content">
                    <div class="ghn_customer_row">
                        <div class="ghn_customer_col">
                            <?php _e('Họ và tên', 'devvn-ghn');?>
                        </div>
                        <div class="ghn_customer_col">
                            <?php echo $name;?>
                        </div>
                    </div>
                    <div class="ghn_customer_row">
                        <div class="ghn_customer_col">
                            <?php _e('Số điện thoại', 'devvn-ghn');?>
                        </div>
                        <div class="ghn_customer_col">
                            <?php echo $phone;?>
                        </div>
                    </div>
                    <div class="ghn_customer_row">
                        <div class="ghn_customer_col">
                            <?php _e('Địa chỉ', 'devvn-ghn');?>
                        </div>
                        <div class="ghn_customer_col">
                            <?php echo $address;?>
                        </div>
                    </div>
                    <div class="ghn_customer_row">
                        <div class="ghn_customer_col">
                            <?php _e('Phường/Xã', 'devvn-ghn');?>
                        </div>
                        <div class="ghn_customer_col">
                            <?php echo ghn_class()->get_name_ward($ward);?>
                        </div>
                    </div>
                    <div class="ghn_customer_row">
                        <div class="ghn_customer_col">
                            <?php _e('Khu vực', 'devvn-ghn');?>
                        </div>
                        <div class="ghn_customer_col">
                            <?php echo ghn_class()->get_name_city($province.'_'.$disrict);?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="devvn_option_col ghn_more_wrap">
                <div class="devvn_option_col_title">
                    <strong>Thông tin sản phẩm</strong>
                    <small>Ẩn bớt</small>
                </div>
                <div class="ghn_more_content">
                    <table class="prod_table">
                        <thead>
                        <tr>
                            <th>Tên sp</th>
                            <th>Weight</th>
                            <th>SL</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $content_order = '';
                        if($product_list && !is_wp_error($product_list) && !empty($product_list)):
                            foreach($product_list as $product):
                                $content_order .= $product['name'] .' x '. $product['quantity'] . ' | ';
                                ?>
                                <tr>
                                    <td><?php echo $product['name']?></td>
                                    <td><?php echo $product['weight']?></td>
                                    <td><?php echo $product['quantity']?></td>
                                </tr>
                            <?php endforeach;?>
                        <?php endif;?>
                        </tbody>
                    </table>
                    <textarea name="ghn_contentOrder" id="ghn_contentOrder"><?php echo esc_textarea($content_order);?></textarea>
                </div>
            </div>
        </div>
    </td>
</tr>
<tr>
    <td colspan="2">
        <div class="devvn_option_2col ghn_order_customerinfor">
            <div class="devvn_option_col">
                <strong><?php _e('Gói hàng', 'devvn-ghn');?></strong>
                <table class="goihang_table">
                    <tbody>
                    <tr>
                        <td><?php _e('Mã đơn hệ thống', 'devvn-ghn')?></td>
                        <td>
                            <input type="text" name="ghn_ExternalCode" id="ghn_ExternalCode" value=""/>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Giá trị gói hàng', 'devvn-ghn')?></td>
                        <td>
                            <input type="number" name="ghn_InsuranceFee" id="ghn_InsuranceFee" value="<?php echo ghn_class()->order_get_total($order)?>"/> <?php echo get_woocommerce_currency_symbol();?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Khối lượng', 'devvn-ghn')?></td>
                        <td>
                            <?php $all_weight = ghn_class()->convert_weight_to_gram(ghn_class()->get_order_weight($order));?>
                            <input type="text" name="ghn_order_weight" id="ghn_order_weight" value="<?php echo $all_weight;?>"> gram
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Kích thước (cm)', 'devvn-ghn')?></td>
                        <td class="input_inline">
                            <?php $all_width = ghn_class()->convert_dimension_to_cm(ghn_class()->get_order_weight($order, 'width'));?>
                            <?php $all_height = ghn_class()->convert_dimension_to_cm(ghn_class()->get_order_weight($order, 'height'));?>
                            <?php $all_length = ghn_class()->convert_dimension_to_cm(ghn_class()->get_order_weight($order, 'length'));?>
                            <input type="text" name="ghn_order_length" id="ghn_order_length" value="<?php echo $all_length;?>"> dài
                            <input type="text" name="ghn_order_width" id="ghn_order_width" value="<?php echo $all_width;?>"> rộng
                            <input type="text" name="ghn_order_height" id="ghn_order_height" value="<?php echo $all_height;?>"> cao
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Ghi chú bắt buộc', 'devvn-ghn')?> <span class="required">*</span></td>
                        <td>
                            <?php
                            $ghn_ghichu = ghn_class()->get_options('ghn_ghichu');
                            ?>
                            <select name="ghn_ghichu_required" id="ghn_ghichu_required">
                                <option value="CHOXEMHANGKHONGTHU" <?php selected('CHOXEMHANGKHONGTHU',$ghn_ghichu);?>><?php _e('Cho xem hàng, không cho thử','devvn-ghn');?></option>
                                <option value="CHOTHUHANG" <?php selected('CHOTHUHANG',$ghn_ghichu);?>><?php _e('Cho thử hàng','devvn-ghn');?></option>
                                <option value="KHONGCHOXEMHANG" <?php selected('KHONGCHOXEMHANG',$ghn_ghichu);?>><?php _e('Không cho thử hàng','devvn-ghn');?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Ghi chú', 'devvn-ghn')?></td>
                        <td><textarea name="ghn_ghichu" id="ghn_ghichu"></textarea></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="devvn_option_col">
                <strong>Gói cước</strong>
                <?php
                $rates = ghn_api()->order_findAvailableServices($order);
                ?>
                <div class="ghn_all_goicuoc">
                    <?php
                    if($rates && !empty($rates)) {
                        foreach ($rates as $methob) {
                            $ServiceID = isset($methob['ServiceID']) ? intval($methob['ServiceID']) : '';
                            $ExpectedDeliveryTime = isset($methob['ExpectedDeliveryTime']) ? date('d/m/Y', strtotime($methob['ExpectedDeliveryTime'])) : '';
                            $Name = isset($methob['Name']) ? esc_attr($methob['Name']) : '';
                            $ServiceFee = isset($methob['ServiceFee']) ? $methob['ServiceFee'] : '';
                            $Extras = isset($methob['Extras']) ? $methob['Extras'] : array();
                            $Extras_all = array();
                            //remove gui hang tai diem
                            foreach ($Extras as $this_extras){
                                if($this_extras['ServiceID'] != '53337'){
                                    $Extras_all[] = $this_extras;
                                }
                            }
                            if($ServiceID) {
                                ?>
                                <div class="ghn_all_goicuoc_list" data-extras="<?php echo esc_attr(json_encode($Extras_all));?>">
                                    <div class="ghn_all_goicuoc_col">
                                        <label><input type="radio" name="ghn_services" data-fee="<?php echo $ServiceFee;?>" value="<?php echo $ServiceID; ?>" <?php checked($ServiceID, $method_id)?>> <?php echo $Name . ' - ' . wc_price($ServiceFee);?></label>
                                    </div>
                                    <div class="ghn_all_goicuoc_col"><?php _e('Dự kiến giao', 'devvn-ghn')?> <?php echo $ExpectedDeliveryTime; ?></div>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
                <div class="ghn_all_phuphi">
                    <strong>Phụ phí</strong>
                    <div class="ghn_all_phuphi_list"></div>
                </div>
                <div class="ghn_nguoithanhtoan">
                    <?php _e('Người thanh toán:','devvn-ghn');?>
                    <label><input type="radio" name="ghn_PaymentTypeID" class="ghn_PaymentTypeID"  value="1" checked="checked"> <?php _e('Người gửi','devvn-ghn');?></label>
                    <label><input type="radio" name="ghn_PaymentTypeID" class="ghn_PaymentTypeID" value="2"> <?php _e('Người nhận','devvn-ghn');?></label>
                </div>
                <div class="ghn_tienthuho">
                    <?php _e('Tiền thu hộ (COD):','devvn-ghn');?>
                    <input type="number" name="ghn_tienthuho" id="ghn_tienthuho" data-total="<?php echo $order->get_total();?>" data-subtotal="<?php echo ghn_class()->order_get_total($order);?>" value="<?php echo $order->get_total()?>"/> <?php echo get_woocommerce_currency_symbol();?>
                </div>
                <div class="ghn_makhuyenmai">
                    <?php _e('Mã khuyến mại:','devvn-ghn');?>
                    <input type="text" name="ghn_CouponCode" id="ghn_CouponCode"  value="">
                </div>
                <div class="ghn_nguoithanhtoan">
                    <?php _e('Gửi hàng tại điểm giao dịch (-2.000đ):','devvn-ghn');?>
                    <label><input type="radio" name="ghn_isPickAtStation" class="ghn_isPickAtStation"  value="1"> <?php _e('Có','devvn-ghn');?></label>
                    <label><input type="radio" name="ghn_isPickAtStation" class="ghn_isPickAtStation" value="2" checked="checked"> <?php _e('Không','devvn-ghn');?></label>
                </div>
            </div>
        </div>
    </td>
</tr>
<tr>
    <td colspan="2" class="total_order_api">
    </td>
</tr>