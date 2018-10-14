<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<form method="post" action="options.php" novalidate="novalidate" class="devvn_options_style">
    <?php
    $license_field = $this->_license_field;
    $license_field_group = $this->_license_field_group;

    settings_fields( $license_field_group );
    $license_options = wp_parse_args(get_option($license_field), $this->_defaultLicenseOptions);
    ?>
    <h2><?php _e('License', 'devvn-ghn'); ?></h2>
    <p>License để update tự động khi có bản cập nhật. <?php if(!$license_options['license_key']):?><span style="color: red;">Liên hệ <a href="https://www.facebook.com/levantoan.wp" target="_blank">tại đây</a> để nhận license ngay.</span><?php endif;?></p>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="license_key"><?php _e('License Key','devvn-ghn')?><span class="devvn_require">*</span></label></th>
                <td>
                    <input type="text" name="<?php echo $license_field?>[license_key]" value="<?php echo $license_options['license_key'];?>" id="license_key"/> <br>
                </td>
            </tr>
        </tbody>
    </table>
    <?php do_settings_sections($license_field_group, 'default'); ?>
    <?php submit_button();?>
</form>