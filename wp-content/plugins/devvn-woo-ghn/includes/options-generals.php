<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<form method="post" action="options.php" novalidate="novalidate" class="devvn_options_style">
    <?php
    settings_fields( $this->_optionGroup );
    $flra_options = wp_parse_args(get_option($this->_optionName),$this->_defaultOptions);
    ?>
    <h2><?php _e('GHN API', 'devvn-ghn'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="token_key"><?php _e('Token Key','devvn-ghn')?><span class="devvn_require">*</span></label></th>
                <td>
                    <input type="text" name="<?php echo $this->_optionName?>[token_key]" value="<?php echo $flra_options['token_key'];?>" id="token_key"/> <br>
                    <small><?php printf(__('Lấy token key <a href="%s" target="_blank">tại đây</a>','devvn-ghn'), 'https://sso.ghn.vn/ssoLogin?app=apiv3');?></small>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="token_key"><?php _e('Ghi chú bắt buộc','devvn-ghn')?><span class="devvn_require">*</span></label></th>
                <td>
                    <select name="<?php echo $this->_optionName?>[ghn_ghichu]" id="ghn_ghichu">
                        <option value="CHOXEMHANGKHONGTHU" <?php selected('CHOXEMHANGKHONGTHU',$flra_options['ghn_ghichu']);?>><?php _e('Cho xem hàng, không cho thử','devvn-ghn');?></option>
                        <option value="CHOTHUHANG" <?php selected('CHOTHUHANG',$flra_options['ghn_ghichu']);?>><?php _e('Cho thử hàng','devvn-ghn');?></option>
                        <option value="KHONGCHOXEMHANG" <?php selected('KHONGCHOXEMHANG',$flra_options['ghn_ghichu']);?>><?php _e('Không cho thử hàng','devvn-ghn');?></option>
                    </select><br>
                    <small><?php _e('Ghi chú bắt buộc khi đăng đơn lên GHN','devvn-ghn');?></small>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ghn_aff_id"><?php _e('Mã liên kết','devvn-ghn')?></label></th>
                <td>
                    <input type="text" name="<?php echo $this->_optionName?>[ghn_aff_id]" value="<?php echo $flra_options['ghn_aff_id'];?>" id="ghn_aff_id"/> <br>
                    <small><?php _e('Không bắt buộc. Nếu là đối tác hãy liên hệ với GHN để có mã này.','devvn-ghn');?></small>
                </td>
            </tr>
        </tbody>
    </table>
    <h2><?php _e('General', 'devvn-ghn'); ?></h2>
    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row"><label for="activeplugin"><?php _e('Ẩn mục phường/xã','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[active_village]" <?php checked('1',$flra_options['active_village'])?> value="1" /> <?php _e('Ẩn mục phường/xã','devvn-ghn')?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="required_village"><?php _e('KHÔNG bắt buộc nhập phường/xã','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[required_village]" <?php checked('1',$flra_options['required_village'])?> value="1" /> <?php _e('Không bắt buộc','devvn-ghn')?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="to_vnd"><?php _e('Chuyển ₫ sang VNĐ','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[to_vnd]" <?php checked('1',$flra_options['to_vnd'])?> value="1" id="to_vnd"/> <?php _e('Cho phép chuyển sang VNĐ','devvn-ghn')?></label><br>
                <small>Xem thêm <a href="http://levantoan.com/thay-doi-ky-hieu-tien-te-dong-viet-nam-trong-woocommerce-d-sang-vnd/" target="_blank"> cách thiết lập đơn vị tiền tệ ₫ (Việt Nam đồng)</a></small>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="remove_methob_title"><?php _e('Loại bỏ tiêu đề vận chuyển','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[remove_methob_title]" <?php checked('1',$flra_options['remove_methob_title'])?> value="1" id="remove_methob_title"/> <?php _e('Loại bỏ hoàn toàn tiêu đề của phương thức vận chuyển','devvn-ghn')?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="freeship_remove_other_methob"><?php _e('Ẩn phương thức khi có free-shipping','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[freeship_remove_other_methob]" <?php checked('1',$flra_options['freeship_remove_other_methob'])?> value="1" id="freeship_remove_other_methob"/> <?php _e('Ẩn tất cả những phương thức vận chuyển khác khi có miễn phí vận chuyển','devvn-ghn')?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="active_vnd2usd"><?php _e('Kích hoạt chuyển đổi VNĐ sang USD','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[active_vnd2usd]" <?php checked('1',$flra_options['active_vnd2usd'])?> value="1" /> <?php _e('Kích hoạt chuyển đổi VNĐ sang USD để có thể sử dụng paypal','devvn-ghn')?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="vnd_usd_rate"><?php _e('VNĐ quy đổi sang tiền','devvn-ghn')?></label></th>
            <td>
                <select name="<?php echo $this->_optionName?>[vnd2usd_currency]" id="vnd2usd_currency">
                    <?php
                    $paypal_supported_currencies = array(
                        'AUD',
                        'BRL',
                        'CAD',
                        'MXN',
                        'NZD',
                        'HKD',
                        'SGD',
                        'USD',
                        'EUR',
                        'JPY',
                        'TRY',
                        'NOK',
                        'CZK',
                        'DKK',
                        'HUF',
                        'ILS',
                        'MYR',
                        'PHP',
                        'PLN',
                        'SEK',
                        'CHF',
                        'TWD',
                        'THB',
                        'GBP',
                        'RMB',
                        'RUB'
                    );
                    foreach ( $paypal_supported_currencies as $currency ) {
                        if ( strtoupper( $currency ) == $flra_options['vnd2usd_currency'] ) {
                            printf( '<option selected="selected" value="%1$s">%1$s</option>', $currency );
                        } else {
                            printf( '<option value="%1$s">%1$s</option>', $currency );
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="vnd_usd_rate"><?php _e('Số quy đổi','devvn-ghn')?></label></th>
            <td>
                <input type="number" min="0" name="<?php echo $this->_optionName?>[vnd_usd_rate]" value="<?php echo $flra_options['vnd_usd_rate'];?>" id="vnd_usd_rate"/> <br>
                <small><?php _e('Tỷ giá quy đổi từ VNĐ','devvn-ghn')?></small>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="active_orderstyle"><?php _e('Thay đổi giao diện trang đơn hàng','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[active_orderstyle]" <?php checked('1',$flra_options['active_orderstyle'])?> value="1" /> <?php _e('Thay đổi giao diện trang danh sách đơn hàng','devvn-ghn')?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="alepay_support"><?php _e('Alepay','devvn-ghn')?></label></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[alepay_support]" <?php checked('1',$flra_options['alepay_support'])?> value="1" /> <?php _e('Hỗ trợ thanh toán qua Alepay','devvn-ghn')?></label>
                <br><small>Để thanh toán qua Alepay bắt buộc phải có first_name và country. Để tải plugin Alepay hãy đăng ký với Alepay và họ sẽ cung cấp Plugin</small>
            </td>
        </tr>
        <tr>
            <th scope="row"></th>
            <td>
                <label><input type="checkbox" name="<?php echo $this->_optionName?>[enable_postcode]" <?php checked('1',$flra_options['enable_postcode'])?> value="1" /> <?php _e('Hiện trường Postcode','devvn-ghn')?></label>
                <br><small>Nếu sử dụng kiểu thanh toán "Tokenization" của Alepay thì bắt buộc cần Postcode.</small>
            </td>
        </tr>
        <?php do_settings_fields($this->_optionGroup, 'default'); ?>
        </tbody>
    </table>
    <?php do_settings_sections($this->_optionGroup, 'default'); ?>
    <?php submit_button();?>
</form>