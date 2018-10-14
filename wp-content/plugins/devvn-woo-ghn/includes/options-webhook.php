<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<h2><?php _e('Cài đặt đường dẫn (Webhook)', 'devvn-ghn'); ?></h2>
<?php
$token_key = ghn_class()->get_options('token_key');
if(!$token_key){
    printf(__('Bạn đang ở chế độ TEST nên không xem được phần này. Hãy điền Token Key <a href="%s">tại đây</a> để sử dụng chức năng này.','devvn-ghn') , admin_url('admin.php?page=devvn-woo-ghn&tab=general'));
}else{
    if(!is_ssl()){
        _e('Bắt buộc website phải có SSL (https://) để có thể đăng ký webhook trên GHN', 'devvn-ghn');
    }else{
        ?>
        <form method="post" action="options.php" novalidate="novalidate" class="devvn_options_style devvn_input_full ghn_webhook_url">
            <?php
            $webhook_field = ghn_api()->_ghn_webhook;
            $webhook_field_group = ghn_api()->_ghn_webhook_group;

            settings_fields( $webhook_field_group );
            $options = wp_parse_args(get_option($webhook_field), ghn_api()->_defaultWebhookOptions);
            $webhook_url = $options['webhook_url'];
            $webhook_hash = $options['webhook_hash'];
            $register = true;
            if(!$webhook_url || !$webhook_hash){
                $webhook_hash = wp_generate_password( 24, false );
                $webhook_url = ghn_api()->_ghn_webhook_action . $webhook_hash;
                $register = false;
            }
            if(!$register){
                _e('<p class="error-message">Webhook URL chưa được đăng ký. Hãy ấn vào button đăng ký bên dưới để đăng ký Webhook URL.</p>', 'devvn-ghn');
            }
            ?>
            <div class="webhook_mess"></div>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="license_key"><?php _e('Đường dẫn cập nhật trạng thái tự động (Webhook URL)','devvn-ghn')?></label></th>
                    <td>
                        <input type="text" readonly name="<?php echo $webhook_field?>[webhook_url]" data-webhookaction="<?php echo esc_url(ghn_api()->_ghn_webhook_action);?>" value="<?php echo esc_url($webhook_url);?>" id="webhook_url"/> <br>
                        <input type="hidden" readonly name="<?php echo $webhook_field?>[webhook_hash]" value="<?php echo $webhook_hash;?>" id="webhook_hash"/>
                        <a href="javascript:void(0)" class="dhn_change_webhook_url"><?php _e('Làm mới Webhook URL', 'devvn-ghn');?></a>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php do_settings_sections($webhook_field_group, 'default'); ?>
            <p>
                <input type="button" name="webhook_submit" id="webhook_submit" data-nonce="<?php echo wp_create_nonce('webhook_nonce');?>" class="button button-primary ghn_register_webhook" value="<?php _e('Đăng ký và lưu Webhook URL', 'devvn-ghn');?>">
            </p>
            <?php submit_button();?>
        </form>
        <?php
    }
}
