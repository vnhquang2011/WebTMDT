<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
 * Author Name: Le Van Toan
 * Author URI: https://levantoan.com
 */
function ghn_shipping_method_init() {
    if ( ! class_exists( 'WC_GHN_Shipping_Method' ) ) {
        class WC_GHN_Shipping_Method extends WC_Shipping_Method {
            public $ghn_mess = '';
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct() {

                $this->id                 = 'ghn_shipping_method';
                $this->method_title       = __( 'Giao hàng nhanh (GHN)' );
                $this->method_description = __( 'Tính phí vận chuyển và đồng bộ đơn hàng với giao hàng nhanh (GHN)' );

                $this->init();

                $this->enabled            = $this->settings['enabled'];
                $this->title              = $this->settings['title'];

            }

            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings API
                $this->init_form_fields();
                $this->init_settings();

                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title'     => __( 'Kích hoạt', 'devvn-ghn' ),
                        'type'      => 'checkbox',
                        'label'     => __( 'Kích hoạt tính phí vận chuyển bằng GHN', 'devvn-ghn' ),
                        'default'   => 'yes',
                    ),
                    'title' => array(
                        'title' => __( 'Tiêu đề', 'devvn-ghn' ),
                        'type' => 'text',
                        'description' => __( 'Mô tả cho phương thức vận chuyển', 'devvn-ghn' ),
                        'default' => __( 'Vận chuyển qua GHN', 'devvn-ghn' )
                    ),
                );
            } // End init_form_fields()

            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping( $package = array() ) {

                $rates = ghn_api()->findAvailableServices($package);

                if($rates && !empty($rates)) {
                    $HubID = end($rates);
                    $HubID = isset($HubID['HubID']) ? $HubID['HubID'] : '';
                    foreach($rates as $methob) {
                        $ServiceID =  isset($methob['ServiceID']) ? intval($methob['ServiceID']) : '';
                        if($ServiceID) {
                            $rate = array(
                                'id' => $this->id . '_' . $ServiceID,
                                'label' => isset($methob['Name']) ? esc_attr($methob['Name']) : '',
                                'cost' => isset($methob['ServiceFee']) ? (float)$methob['ServiceFee'] : 0,
                                'calc_tax' => 'per_item',
                                'meta_data' => array(
                                    'ExpectedDeliveryTime' => isset($methob['ExpectedDeliveryTime']) ? date('d/m/Y', strtotime($methob['ExpectedDeliveryTime'])) : '',
                                    'HubID' => $HubID,
                                    'ServiceID' => $ServiceID,
                                )
                            );
                            $this->add_rate($rate);
                        }
                    }
                }
            }

            function devvn_no_shipping_cart(){
                return $this->ghn_mess;
            }
        }
    }
}

add_action( 'woocommerce_shipping_init', 'ghn_shipping_method_init' );

function add_ghn_shipping_method( $methods ) {
    $methods['ghn_shipping_method'] = 'WC_GHN_Shipping_Method';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_ghn_shipping_method' );

class DevVN_GHN_API{

    protected static $_instance = null;
    private $token = '';
    private $url_remote = '';

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public $_allhubs = 'ghn_allhubs';
    public $_allhubs_group = 'ghn_allhubs_option';
    public $_defaultHubsOptions = array();

    public $_ghn_webhook = 'ghn_webhook';
    public $_ghn_webhook_group = 'ghn_webhook_option';
    public $_defaultWebhookOptions = array(
        'webhook_url'   =>  '',
        'webhook_hash'  =>  ''
    );
    public $_ghn_webhook_action = '';

    public function __construct() {
        $this->token = ghn_class()->get_options('token_key');
        if(!$this->token){
            $this->url_remote = 'http://api.serverapi.host/api/v1/apiv3/';
            $this->token = 'TokenTest';
        }else{
            $this->url_remote = 'https://console.ghn.vn/api/v1/apiv3/';
        }

        $this->_ghn_webhook_action = esc_url(admin_url('admin-ajax.php?action=devvn_ghn_webhook&hash='));

        add_action( 'add_meta_boxes', array($this, 'ghn_order_action') );
        add_action( 'save_post', array($this, 'ghn_save_meta_box'), 10, 2 );
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'ghn_update_order_html') );

        add_action( 'wp_ajax_update_hubs', array($this, 'devvn_update_hubs') );
        add_action( 'wp_ajax_add_hubs', array($this, 'devvn_add_hubs') );
        add_action( 'wp_ajax_add_hubdistrict', array($this, 'devvn_add_hubdistrict') );
        add_action( 'wp_ajax_ghn_change_hub', array($this, 'devvn_ghn_change_hub') );
        add_action( 'wp_ajax_ghn_creat_order', array($this, 'devvn_ghn_creat_order') );
        add_action( 'wp_ajax_ghn_update_order', array($this, 'devvn_ghn_update_order') );
        add_action( 'wp_ajax_ghn_tracking_order', array($this, 'devvn_ghn_tracking_order') );
        add_action( 'wp_ajax_ghn_cancel_order', array($this, 'devvn_ghn_cancel_order') );
        add_action( 'wp_ajax_ghn_set_webhook', array($this, 'devvn_ghn_set_webhook') );
        add_action( 'wp_ajax_nopriv_devvn_ghn_webhook', array($this, 'devvn_ghn_webhook_func') );
        add_action( 'wp_ajax_ghn_calculatefee', array($this, 'ghn_calculatefee_func') );
        add_action( 'wp_ajax_ghn_creat_order_ajax', array($this, 'ghn_creat_order_ajax_func') );

        add_action( 'admin_init', array( $this, 'register_mysettings') );
        add_option( $this->_allhubs, $this->_defaultHubsOptions );
        add_option( $this->_ghn_webhook, $this->_defaultWebhookOptions );

    }

    function register_mysettings(){
        register_setting( $this->_allhubs_group, $this->_allhubs );
        register_setting( $this->_ghn_webhook_group, $this->_ghn_webhook );
    }

    function delete_cache(){
        delete_transient($this->token . '_allhubs');
    }

    function get_hubs_near($city_customer_id = '', $field = 'DistrictID'){
        if(!$city_customer_id) return false;
        $all_hub_district = get_option(ghn_api()->_allhubs);
        $main_hub = $this->get_main_hubs();
        $hub_near = ghn_class()->search_in_array_value($all_hub_district, $city_customer_id);

        if(in_array($main_hub, $hub_near)) {
            $hub_near = $main_hub;
        }elseif(!empty($hub_near)) {
            $hub_near = $hub_near[0];
        }else{
            $hub_near = $main_hub;
        }

        $allHubs = $this->getHubs();
        $hub_near = ghn_class()->search_in_array($allHubs, 'HubID', $hub_near);
        if($field == 'all') {
            $hub_near = isset($hub_near[0]) ? $hub_near[0] : '';
        }else{
            $hub_near = isset($hub_near[0]) ? $hub_near[0][$field] : '';
        }
        return $hub_near;
    }

    function get_main_hubs($field = 'HubID'){
        $allHubs = $this->getHubs();
        $mainHub = ghn_class()->search_in_array($allHubs, 'IsMain', 1);
        if(isset($mainHub[0])) {
            if ($field == 'all') {
                return $mainHub[0];
            } else {
                return $mainHub[0][$field];
            }
        }else{
            return false;
        }
    }

    function get_hub_by_id($hubID = '', $field = 'DistrictID'){
        $allHubs = $this->getHubs();
        $mainHub = ghn_class()->search_in_array($allHubs, 'HubID', $hubID);
        if(isset($mainHub[0])) {
            if ($field == 'all') {
                return $mainHub[0];
            } else {
                return $mainHub[0][$field];
            }
        }else{
            return false;
        }
    }

    function get_cURL($args = array()){

        if(empty($args)) return false;

        $data = isset($args['data']) ? $args['data'] : '';
        $action = isset($args['action']) ? $args['action'] : 'CalculateFee';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url_remote . $action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => is_ssl(),
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response,true);

        return $result;
    }

    public function get_CalculateFee($args = array())
    {
        if(empty($args)) return false;

        $args = wp_parse_args($args, array(
            'Weight' => 0,
            'Length' => 0,
            'Width' => 0,
            'Height' => 0,
            'FromDistrictID' => '',
            'ToDistrictID' => '',
            'ServiceID' => '',
            'OrderCosts' => array(),
            'CouponCode' => '',
            'InsuranceFee' => '',
        ));

        $data = array(
            "token"	=> $this->token,
            "Weight" => (float) $args['Weight'],
            "Length" => (float) $args['Length'],
            "Width" => (float) $args['Width'],
            "Height" => (float) $args['Height'],
            "FromDistrictID" => (float) $args['FromDistrictID'],
            "ToDistrictID" => (float) $args['ToDistrictID'],

            "ServiceID" => $args['ServiceID'],
            "OrderCosts" =>  $args['OrderCosts'],

            "CouponCode" => sanitize_text_field($args['CouponCode']),
            "InsuranceFee" => (float) $args['InsuranceFee']
        );
        $args = array(
            'data'  =>  $data,
            'action'    =>  'CalculateFee'

        );
        $result = $this->get_cURL($args);

        if($result && is_array($result) && !empty($result)){
            if(isset($result['code']) && $result['code'] == 1){
                $data = isset($result['data']) ? $result['data'] : '';
                return $data;
            }else{
                $msg = isset($result['msg']) ? $result['msg'] : '';
                $data['ErrorMessage'] = $msg;
                return $data;
            }
        }
        return false;
    }

    function ghn_calculatefee_func(){

        /*if ( !wp_verify_nonce( $_REQUEST['nonce'], "ghn_action_nonce_action")) {
            wp_send_json_error('Check nonce failed!');
        }*/
        $hubID = isset($_POST['hubID']) ? intval($_POST['hubID']) : 0;
        $orderid = isset($_POST['orderid']) ? intval($_POST['orderid']) : 0;
        $weight = isset($_POST['weight']) ? (float) $_POST['weight'] : 0;
        $length = isset($_POST['length']) ? (float) $_POST['length'] : 0;
        $width = isset($_POST['width']) ? (float) $_POST['width'] : 0;
        $height = isset($_POST['height']) ? (float) $_POST['height'] : 0;
        $CouponCode = isset($_POST['couponcode']) ? sanitize_text_field($_POST['couponcode']) : '';
        $ServiceID = isset($_POST['serviceID']) ? intval($_POST['serviceID']) : '';
        $OrderCosts = isset($_POST['orderCosts']) ? (array) $_POST['orderCosts'] : '';
        $InsuranceFee = isset($_POST['insuranceFee']) ? (float) $_POST['insuranceFee'] : '';
        if($hubID) {
            $FromDistrictID = $this->get_hub_by_id($hubID);
        }

        $order = wc_get_order($orderid);
        $ToDistrictID = '';
        if(!is_wp_error($order)) {
            $customer_infor = ghn_class()->get_customer_address_shipping($order);
            $ToDistrictID = $customer_infor['disrict'];
        }

        if(!$FromDistrictID || !$ToDistrictID || !$ServiceID) wp_send_json_error('Kiểm tra lại dữ liệu gửi vào');

        $OrderCosts_args = array();

        if($OrderCosts && !empty($OrderCosts)){
            foreach($OrderCosts as $OrderCosts_item){
                $OrderCosts_args[]['ServiceID'] = $OrderCosts_item;
            }
        }

        $args = array(
            'Weight' => $weight,
            'Length' => $length,
            'Width' => $width,
            'Height' => $height,
            'FromDistrictID' => $FromDistrictID,
            'ToDistrictID' => $ToDistrictID,
            'ServiceID' => $ServiceID,
            'OrderCosts' => $OrderCosts_args,
            'CouponCode' => $CouponCode,
            'InsuranceFee' => $InsuranceFee,
        );

        $result = $this->get_CalculateFee($args);
        $result_args = array();
        if($result) {
            $result_args['result_html'] = $this->total_order_html($result);
            if(isset($result['ErrorMessage']) && !$result['ErrorMessage']){
                $result_args['allow_creat_order'] =  true;
                wp_send_json_success($result_args);
            }else{
                $result_args['allow_creat_order'] =  false;
                wp_send_json_success($result_args);
            }
        }
        wp_send_json_error();
        die();
    }

    public function findAvailableServices($package = array(), $hubid = '')
    {
        $ToDistrictID = isset($package['destination']['state']) ? ghn_class()->get_district_id_from_string($package['destination']['state']) : '';
        $state = isset($package['destination']['state']) ? ghn_class()->get_state_id_from_string($package['destination']['state']) : '';
        if($hubid){
            $DistrictID = $this->get_hub_by_id($hubid);
        }
        $data = array(
            "token"	=> $this->token,
            "Weight" => (float) ghn_class()->get_cart_contents_weight($package),
            "Length" => (float) ghn_class()->get_cart_dimension_package($package, 'length'),
            "Width" => (float) ghn_class()->get_cart_dimension_package($package, 'width'),
            "Height" => (float) ghn_class()->get_cart_dimension_package($package),
            "FromDistrictID" => isset($DistrictID) ? (float) $DistrictID : $this->get_hubs_near($state),
            "ToDistrictID" => $ToDistrictID,
            "CouponCode" => "",
            "InsuranceFee" => isset($package['cart_subtotal']) ? $package['cart_subtotal'] : '',
        );
        $args = array(
            'data'  =>  $data,
            'action'    =>  'FindAvailableServices'

        );
        $result = $this->get_cURL($args);

        if($result && is_array($result) && !empty($result)){
            if(isset($result['code']) && $result['code'] == 1){
                $data = isset($result['data']) ? $result['data'] : '';
                if($data) {
                    $data[]['HubID'] = ($hubid) ? $hubid : $this->get_hubs_near($state, 'HubID');
                }
                return $data;
            }else{
                return false;
            }
        }
        return false;
    }
    public function order_findAvailableServices($order = '', $hubid = '', $args = array())
    {
        if(!$order) return false;

        $args = wp_parse_args($args, array(
            'weight' => 0,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'CouponCode'    =>  '',
            'InsuranceFee'    =>  ''
        ));

        $customer_infor = ghn_class()->get_customer_address_shipping($order);
        $ToDistrictID = $customer_infor['disrict'];
        $state = $customer_infor['province'];
        if($hubid){
            $DistrictID = $this->get_hub_by_id($hubid);
        }
        $data = array(
            "token"	=> $this->token,
            "Weight" =>  (float) ghn_class()->convert_weight_to_gram($args['weight']),
            "Length" => (float) ghn_class()->convert_dimension_to_cm($args['length']),
            "Width" => (float) ghn_class()->convert_dimension_to_cm($args['width']),
            "Height" => (float) ghn_class()->convert_dimension_to_cm($args['height']),
            "FromDistrictID" => isset($DistrictID) ? (float) $DistrictID : $this->get_hubs_near($state),
            "ToDistrictID" => $ToDistrictID,
            "CouponCode" => $args['CouponCode'],
            "InsuranceFee" => (float) $args['InsuranceFee']
        );
        $args = array(
            'data'  =>  $data,
            'action'    =>  'FindAvailableServices'

        );
        $result = $this->get_cURL($args);

        if($result && is_array($result) && !empty($result)){
            if(isset($result['code']) && $result['code'] == 1){
                $data = isset($result['data']) ? $result['data'] : '';
                if($data) {
                    $data[]['HubID'] = ($hubid) ? $hubid : $this->get_hubs_near($state, 'HubID');
                }
                return $data;
            }else{
                return false;
            }
        }
        return false;
    }
    public function getHubs()
    {
        if ( false === ( $allhubs = get_transient( $this->token . '_allhubs' ) ) ) {
            $args = array(
                'data' => array(
                    "token" => $this->token
                ),
                'action' => 'GetHubs'

            );
            $result = $this->get_cURL($args);

            if ($result && is_array($result) && !empty($result)) {
                if (isset($result['code']) && $result['code'] == 1) {
                    $data = isset($result['data']) ? devvn_sort_desc_array($result['data'], 'IsMain') : '';
                    set_transient($this->token . '_allhubs', $data);
                    return $data;
                }
            }
            $this->delete_cache();
            return false;
        }else{
            return $allhubs;
        }
    }
    public function updateHubs($data = array())
    {
        if(!is_array($data) || empty($data)) return false;
        $args = array(
            'data'  =>  array(
                "token"	=> $this->token,
                "Latitude" => 0,
                "Longitude" => 0,
                "IsMain"    =>  false
            ),
            'action'    =>  'UpdateHubs'
        );
        foreach($data as $k=>$v){
            if($k == 'HubID'){
                $args['data'][$k] = (int) $v;
            }elseif($k == 'IsMain'){
                $args['data']['IsMain'] = ($v == 1) ? true : false;
            }elseif( $k == 'DistrictID') {
                $args['data'][$k] = ghn_class()->get_district_id_from_string($v);
            }else{
                $args['data'][$k] = $v;
            }
        }
        $result = $this->get_cURL($args);

        if($result && is_array($result) && !empty($result)){
            if(isset($result['code']) && $result['code'] == 1){
                $this->delete_cache();
                return true;
            }else{
                return (isset($result['msg'])) ? $result['msg'] : false;
            }
        }
        return false;
    }

    public function addHubs($data = array())
    {
        if(!is_array($data) || empty($data)) return false;
        $args = array(
            'data'  =>  array(
                "token"	=> $this->token,
                "Latitude" => 0,
                "Longitude" => 0,
                "IsMain"    =>  false
            ),
            'action'    =>  'AddHubs'
        );
        foreach($data as $k=>$v){
            if($k == 'IsMain'){
                $args['data']['IsMain'] = ($v == 1) ? true : false;
            }elseif( $k == 'DistrictID') {
                $args['data'][$k] = ghn_class()->get_district_id_from_string($v);
            }else{
                $args['data'][$k] = $v;
            }
        }
        $result = $this->get_cURL($args);

        if($result && is_array($result) && !empty($result)){
            if(isset($result['code']) && $result['code'] == 1){
                $this->delete_cache();
                return true;
            }else{
                return (isset($result['msg'])) ? $result['msg'] : false;
            }
        }
        return false;
    }

    function devvn_update_hubs(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "action_nonce_update")) {
            wp_send_json_error();
            die();
        }

        $data = isset($_POST['data']) ? $_POST['data'] : array();
        if(true === ($results = $this->updateHubs($data))){
            wp_send_json_success();
        }else{
            wp_send_json_error($results);
        }
        die();
    }

    function devvn_add_hubs(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "action_nonce_add")) {
            wp_send_json_error();
            die();
        }
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        if(true === ($results = $this->addHubs($data))){
            wp_send_json_success(__('Thêm cửa hàng/kho thành công! Đang làm mới...'));
        }else{
            wp_send_json_error($results);
        }
        die();
    }

    function devvn_add_hubdistrict(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "action_nonce_update")) {
            wp_send_json_error('Check nonce failed');
            die();
        }
        $hubid = isset($_POST['hubid']) ? $_POST['hubid'] : 0;
        $districtID = isset($_POST['districtID']) ? $_POST['districtID'] : array();

        if($hubid){
            $old_hub_district = get_option(ghn_api()->_allhubs);
            $old_hub_district[$hubid] = $districtID;
            if(update_option( $this->_allhubs, $old_hub_district)){
                wp_send_json_success('Update thành công');
            }else{
                wp_send_json_error('Không có gì thay đổi hoặc có lỗi khi update!');
            }
        }
        wp_send_json_error('Không tồn tại HubID');
        die();
    }

    public function createOrder($data = array())
    {
        if(!is_array($data) || empty($data)) return false;
        $args = array(
            'data'  =>  array(
                "token"	=> $this->token,
                "PaymentTypeID" => (isset($data['PaymentTypeID']) && $data['PaymentTypeID']) ? intval($data['PaymentTypeID']) : 1,
                "FromDistrictID" => (isset($data['FromDistrictID']) && $data['FromDistrictID']) ? intval($data['FromDistrictID']) : 0,
                "FromWardCode" => (isset($data['FromWardCode']) && $data['FromWardCode']) ? sanitize_text_field($data['FromWardCode']) : "",
                "ToDistrictID" => (isset($data['ToDistrictID']) && $data['ToDistrictID']) ? intval($data['ToDistrictID']) : 0,
                "ToWardCode" => isset($data['ToWardCode']) ? sanitize_text_field($data['ToWardCode']) : "",
                "Note" => isset($data['Note']) ? sanitize_textarea_field($data['Note']) : "",
                "SealCode" => isset($data['SealCode']) ? sanitize_text_field($data['SealCode']) : "",
                "ExternalCode" => isset($data['ExternalCode']) ? sanitize_text_field($data['ExternalCode']) : "",

                "ClientContactName" => isset($data['ClientContactName']) ? sanitize_text_field($data['ClientContactName']) : "",
                "ClientContactPhone" => isset($data['ClientContactPhone']) ? sanitize_text_field($data['ClientContactPhone']) : "",
                "ClientAddress" => isset($data['ClientAddress']) ? sanitize_text_field($data['ClientAddress']) : "",
                "ClientHubID" => (isset($data['ClientHubID']) && $data['ClientHubID']) ? intval($data['ClientHubID']) : 0,

                "CustomerName" => isset($data['CustomerName']) ? sanitize_text_field($data['CustomerName']) : "",
                "CustomerPhone" => isset($data['CustomerPhone']) ? sanitize_text_field($data['CustomerPhone']) : "",
                "ShippingAddress" => isset($data['ShippingAddress']) ? sanitize_text_field($data['ShippingAddress']) : "",

                "CoDAmount" => isset($data['CoDAmount']) ? (float) $data['CoDAmount'] : 0,
                "NoteCode" => (isset($data['NoteCode']) && $data['NoteCode']) ? sanitize_text_field($data['NoteCode']) : apply_filters('devvn_notecode_default', 'KHONGCHOXEMHANG'),

                "InsuranceFee" => isset($data['InsuranceFee']) ? (float) $data['InsuranceFee'] : 0,

                "ServiceID" => isset($data['ServiceID']) ? (int) $data['ServiceID'] : 0,

                "ToLatitude" => isset($data['ToLatitude']) ? (float) $data['ToLatitude'] : 0,
                "ToLongitude" => isset($data['ToLongitude']) ? (float) $data['ToLongitude'] : 0,
                "FromLat" => isset($data['FromLat']) ? (float) $data['FromLat'] : 0,
                "FromLng" => isset($data['FromLng']) ? (float) $data['FromLng'] : 0,

                "Content" => isset($data['Content']) ? sanitize_text_field($data['Content']) : "",
                "CouponCode" => isset($data['CouponCode']) ? sanitize_text_field($data['CouponCode']) : "",

                "Weight" => isset($data['Weight']) && $data['Weight'] ? (float) $data['Weight'] : 0,
                "Length" => isset($data['Length']) && $data['Length'] ? (float) $data['Length'] : 1,
                "Width" => isset($data['Width']) && $data['Width'] ? (float) $data['Width'] : 1,
                "Height" => isset($data['Height']) && $data['Height'] ? (float) $data['Height'] : 1,

                "ShippingOrderCosts" => isset($data['ShippingOrderCosts']) ? $data['ShippingOrderCosts'] : array(),

                "CheckMainBankAccount" => false,
                "ReturnContactName" => "",
                "ReturnContactPhone" => "",
                "ReturnAddress" => "",
                "ReturnDistrictCode" => "",
                "ExternalReturnCode" => "",
                "IsCreditCreate" => false,
                "AffiliateID"  =>  ghn_class()->myAffID()
            ),
            'action'    =>  'CreateOrder'
        );

        $result = $this->get_cURL($args);

        return $result;
    }

    public function updateOrder($data = array())
    {
        if(!is_array($data) || empty($data)) return false;
        $args = array(
            'data'  =>  array(
                "token"	=> $this->token,

                "ShippingOrderID" => (isset($data['ShippingOrderID']) && $data['ShippingOrderID']) ? intval($data['ShippingOrderID']) : 0,
                "OrderCode" => (isset($data['OrderCode']) && $data['OrderCode']) ? sanitize_text_field($data['OrderCode']) : '',

                "PaymentTypeID" => (isset($data['PaymentTypeID']) && $data['PaymentTypeID']) ? intval($data['PaymentTypeID']) : 1,
                "FromDistrictID" => (isset($data['FromDistrictID']) && $data['FromDistrictID']) ? intval($data['FromDistrictID']) : 0,
                "FromWardCode" => (isset($data['FromWardCode']) && $data['FromWardCode']) ? sanitize_text_field($data['FromWardCode']) : "",
                "ToDistrictID" => (isset($data['ToDistrictID']) && $data['ToDistrictID']) ? intval($data['ToDistrictID']) : 0,
                "ToWardCode" => isset($data['ToWardCode']) ? sanitize_text_field($data['ToWardCode']) : "",
                "Note" => isset($data['Note']) ? sanitize_textarea_field($data['Note']) : "",
                "SealCode" => isset($data['SealCode']) ? sanitize_text_field($data['SealCode']) : "",
                "ExternalCode" => isset($data['ExternalCode']) ? sanitize_text_field($data['ExternalCode']) : "",

                "ClientContactName" => isset($data['ClientContactName']) ? sanitize_text_field($data['ClientContactName']) : "",
                "ClientContactPhone" => isset($data['ClientContactPhone']) ? sanitize_text_field($data['ClientContactPhone']) : "",
                "ClientAddress" => isset($data['ClientAddress']) ? sanitize_text_field($data['ClientAddress']) : "",
                "ClientHubID" => (isset($data['ClientHubID']) && $data['ClientHubID']) ? intval($data['ClientHubID']) : 0,

                "CustomerName" => isset($data['CustomerName']) ? sanitize_text_field($data['CustomerName']) : "",
                "CustomerPhone" => isset($data['CustomerPhone']) ? sanitize_text_field($data['CustomerPhone']) : "",
                "ShippingAddress" => isset($data['ShippingAddress']) ? sanitize_text_field($data['ShippingAddress']) : "",

                "CoDAmount" => isset($data['CoDAmount']) ? (float) $data['CoDAmount'] : 0,
                "NoteCode" => (isset($data['NoteCode']) && $data['NoteCode']) ? sanitize_text_field($data['NoteCode']) : apply_filters('devvn_notecode_default', 'KHONGCHOXEMHANG'),

                "InsuranceFee" => isset($data['InsuranceFee']) ? (float) $data['InsuranceFee'] : 0,

                "ServiceID" => isset($data['ServiceID']) ? (int) $data['ServiceID'] : 0,

                "ToLatitude" => isset($data['ToLatitude']) ? (float) $data['ToLatitude'] : 0,
                "ToLongitude" => isset($data['ToLongitude']) ? (float) $data['ToLongitude'] : 0,
                "FromLat" => isset($data['FromLat']) ? (float) $data['FromLat'] : 0,
                "FromLng" => isset($data['FromLng']) ? (float) $data['FromLng'] : 0,

                "Content" => isset($data['Content']) ? sanitize_text_field($data['Content']) : "",
                "CouponCode" => isset($data['CouponCode']) ? sanitize_text_field($data['CouponCode']) : "",

                "Weight" => isset($data['Weight']) && $data['Weight'] ? (float) $data['Weight'] : 0,
                "Length" => isset($data['Length']) && $data['Length'] ? (float) $data['Length'] : 1,
                "Width" => isset($data['Width']) && $data['Width'] ? (float) $data['Width'] : 1,
                "Height" => isset($data['Height']) && $data['Height'] ? (float) $data['Height'] : 1,

                "OrderCosts" => isset($data['ShippingOrderCosts']) ? $data['ShippingOrderCosts'] : array(),

                "CheckMainBankAccount" => false,
                "ReturnContactName" => "",
                "ReturnContactPhone" => "",
                "ReturnAddress" => "",
                "ReturnDistrictCode" => "",
                "ExternalReturnCode" => "",
                "IsCreditCreate" => false,
                "AffiliateID"  =>  ghn_class()->myAffID()
            ),
            'action'    =>  'UpdateOrder'
        );

        $result = $this->get_cURL($args);

        return $result;
    }

    function ghn_order_action(){
        add_meta_box(
            'ghn-action-id',
            __( 'Giao Hàng NHANH (GHN)', 'devvn-ghn' ),
            array($this, 'ghn_order_action_callback'),
            'shop_order',
            'side',
            'high'
        );
    }

    function ghn_order_action_callback($post){
        wp_nonce_field( 'ghn_action_nonce_action', 'ghn_action_nonce' );
        $ghn_order_fullinfor = get_post_meta($post->ID, '_ghn_order_fullinfor', true);
        $ghn_ordercode = get_post_meta($post->ID, '_ghn_ordercode', true);
        $ghn_order_status = get_post_meta($post->ID, '_ghn_order_status', true);
        $ghn_order_submited = get_post_meta($post->ID, '_ghn_order_submited', true);
        ?>
        <?php if($ghn_ordercode && $ghn_order_status != 'Cancel'):?>
            <div  class="ghn_ordercode_html_wrap_<?php echo $post->ID;?>"><?php echo $this->ordercode_html($ghn_ordercode);?></div>
            <p class="ajax_status_tracking">
                <?php if($ghn_order_status):?>
                    <?php printf(__('<strong>Trạng thái:</strong> %s', 'devvn-ghn'), $this->get_status_text($ghn_order_status));?>
                <?php endif;?>
            </p>
            <p><a href="#" class="button button-primary ghn_update_order" data-ordercode="<?php echo $ghn_ordercode;?>"><?php _e('Chỉnh sửa đơn hàng', 'devvn-ghn')?></a></p>
            <p><a href="#" class="button button-primary ghn_tracking_order" data-ordercode="<?php echo $ghn_ordercode;?>"><?php _e('Kiểm tra đơn hàng', 'devvn-ghn')?></a></p>
            <p><a href="#" class="button button-link-delete ghn_cancel_order" data-ordercode="<?php echo $ghn_ordercode;?>"><?php _e('Hủy đơn hàng', 'devvn-ghn')?></a></p>
        <?php else:?>
            <div  class="ghn_ordercode_html_wrap_<?php echo $post->ID;?>">
            <a href="#" class="button button-primary ghn_creat_order_popup"><?php _e('Tạo vận đơn', 'devvn-ghn')?></a>
            </div>
        <?php endif;?>
        <?php
    }

    function ordercode_html($ghn_ordercode){
        ob_start();
        ?>
        <p><?php printf(__('<strong>Mã vận đơn:</strong> %s', 'devvn-ghn'), $ghn_ordercode);?></p>
        <?php
        return ob_get_clean();
    }

    function devvn_woocommerce_admin_order_data_after_order_details($order){
        $customer_infor = ghn_class()->get_customer_address_shipping($order);
        extract($customer_infor);

        $shipping_methods = $order->get_shipping_methods();
        $HubID_Order = '';
        $method_id = '';
        foreach ( $shipping_methods as $shipping_method ) {
            foreach($shipping_method->get_formatted_meta_data() as $meta_data){
                if($meta_data->key && $meta_data->key == 'HubID' && !$HubID_Order){
                    $HubID_Order = $meta_data->value;
                }
            }
            foreach($shipping_method->get_formatted_meta_data() as $meta_data){
                if($meta_data->key && $meta_data->key == 'ServiceID' && !$method_id){
                    $method_id = $meta_data->value;
                }
            }
        }

        $product_list = ghn_class()->get_product_args($order);
        ?>
        <div class="ghn_popup_style ghn_creat_popup devvn_options_style" id="ajax_wrap_<?php echo $order->get_id();?>">
            <div class="devvn_option_box">
                <table class="devvn_hubs_table widefat" cellspacing="0">
                    <thead>
                    <tr>
                        <th colspan="2"><h2><?php _e('Đăng đơn hàng lên GHN', 'devvn-ghn'); ?></h2></th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php include 'ghn-creat-order-html.php';?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="2" class="text_alignright">
                            <div class="ghn_msg"></div>
                            <a href="#" class="button button-primary devvn_float_right devvn_ghn_creat_order"><?php _e('Tạo đơn hàng', 'devvn-ghn'); ?></a>
                            <a href="#" class="button close_popup devvn_float_right"><?php _e('Hủy tạo', 'devvn-ghn'); ?></a>
                            <span class="spinner"></span>
                        </td>
                    </tr>
                    </tfoot>
                </table>
                <input type="hidden" value="<?php echo $order->get_id()?>" name="order_id" class="order_id"/>
                <input type="hidden" value="0" name="allow_creat_order" class="allow_creat_order"/>
            </div>
        </div>
        <?php
    }

    function total_order_html($args = array()){
        if(!$args || !is_array($args)) return false;
        ob_start();
        ?>
        <div class="devvn_option_1col">
            <table>
                <tbody>
                    <?php if(!$args['ErrorMessage']): ?>
                    <tr class="total_order_api_shipfee">
                        <th>Phí vận chuyển:</th>
                        <td><?php echo wc_price($args['ServiceFee']);?></td>
                    </tr>
                    <?php if(isset($args['OrderCosts']) && !empty($args['OrderCosts'])):
                        foreach($args['OrderCosts'] as $Costs):
                        ?>
                        <tr>
                            <th><?php echo $Costs['Name']?>:</th>
                            <td><?php echo wc_price($Costs['Cost'])?></td>
                        </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                    <tr class="total_order_api_total">
                        <th>Tổng cộng:</th>
                        <td><?php echo ($args['DiscountFee']) ? wc_price($args['DiscountFee']) : wc_price($args['CalculatedFee']);?></td>
                    </tr>
                    <?php else:?>
                        <tr>
                            <td colspan="2"><?php echo $args['ErrorMessage'];?></td>
                        </tr>
                    <?php endif;?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    function ghn_update_order_html($order){
        $ghn_order_submited = get_post_meta($order->get_id() , '_ghn_order_submited', true );
        $ghn_order_fullinfor = get_post_meta($order->get_id() , '_ghn_order_fullinfor', true );
        $OrderID = isset($ghn_order_fullinfor['data']['OrderID']) ? $ghn_order_fullinfor['data']['OrderID'] : 0;
        $OrderCode = isset($ghn_order_fullinfor['data']['OrderCode']) ? $ghn_order_fullinfor['data']['OrderCode'] : '';
        $ghn_order_status = get_post_meta($order->get_id(), '_ghn_order_status', true);

        if(!empty($ghn_order_submited) && $ghn_order_status != 'Cancel') {
            if($OrderID){
                $ghn_order_submited['ShippingOrderID'] = $OrderID;
            }
            if($OrderCode){
                $ghn_order_submited['OrderCode'] = $OrderCode;
            }
            $this->devvn_form_creat_order_html($ghn_order_submited, $order);
        }else{
            $this->devvn_woocommerce_admin_order_data_after_order_details($order);
        }
    }

    function devvn_form_creat_order_html($data = array(), $order){
        if(empty($data)) return false;
        $data = wp_parse_args($data, array(

            'ShippingOrderID'   =>  0,
            'OrderCode' =>  '',

            'PaymentTypeID' => 1,

            "ClientContactName" => "",
            "ClientContactPhone" => "",
            "ClientAddress" => "",
            "ClientHubID" => "",

            "FromDistrictID" => "",

            "ToDistrictID" => 0,
            "ToWardCode" => "",

            "Note" => "",

            "CustomerName" => "",
            "CustomerPhone" => "",
            "ShippingAddress" => "",

            "CoDAmount" => 0,
            "NoteCode" => "",

            "InsuranceFee" => 0,

            "ServiceID" => 0,

            "Content" => "",
            "CouponCode" => "",

            "Weight" => 0,
            "Length" => 0,
            "Width" => 0,
            "Height" => 0,

            "ShippingOrderCosts" => array(),
        ));
        $customer_infor = ghn_class()->get_customer_address_shipping($order);
        extract($customer_infor);

        $product_list = ghn_class()->get_product_args($order);
        ?>
        <div class="ghn_popup_style ghn_creat_popup devvn_options_style ghn_update_wrap" id="ajax_wrap_<?php echo $order->get_id();?>">
            <div class="devvn_option_box">
                <table class="devvn_hubs_table widefat" cellspacing="0">
                    <thead>
                    <tr>
                        <th colspan="2"><h2><?php _e('Đăng đơn hàng lên GHN', 'devvn-ghn'); ?></h2></th>
                    </tr>
                    </thead>
                    <tbody>
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
                                        <option value="<?php echo $hubID;?>" <?php selected($hubID, $data['ClientHubID'])?>><?php echo '#'.$hubID . ' - '. $ContactName .' - ' . $Address;?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select><br>
                            <small><?php _e('Phần này là tự động. Trong trường hợp thay đổi chi nhanh có thể sẽ làm phí vận chuyển thay đổi.','devvn-ghn');?></small>
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
                                                <th><?php _e('Tên sp','devvn-ghn');?></th>
                                                <th><?php _e('Weight','devvn-ghn');?></th>
                                                <th><?php _e('SL','devvn-ghn');?></th>
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
                                                <input type="text" name="ghn_ExternalCode" id="ghn_ExternalCode" value="<?php echo isset($data['ExternalCode']) ? $data['ExternalCode'] : '';?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Giá trị gói hàng', 'devvn-ghn')?></td>
                                            <td>
                                                <input type="number" name="ghn_InsuranceFee" id="ghn_InsuranceFee" value="<?php echo $data['InsuranceFee'];?>"/> <?php echo get_woocommerce_currency_symbol();?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Khối lượng', 'devvn-ghn')?></td>
                                            <td>
                                                <input type="text" name="ghn_order_weight" id="ghn_order_weight" value="<?php echo $data['Weight'];?>"> gram
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Kích thước (cm)', 'devvn-ghn')?></td>
                                            <td class="input_inline">
                                                <input type="text" name="ghn_order_length" id="ghn_order_length" value="<?php echo $data['Length'];?>"> <?php _e('dài','devvn-ghn');?>
                                                <input type="text" name="ghn_order_width" id="ghn_order_width" value="<?php echo $data['Width'];?>"> <?php _e('rộng','devvn-ghn');?>
                                                <input type="text" name="ghn_order_height" id="ghn_order_height" value="<?php echo $data['Height'];?>"> <?php _e('cao','devvn-ghn');?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Ghi chú bắt buộc', 'devvn-ghn')?> <span class="required">*</span></td>
                                            <td>
                                                <select name="ghn_ghichu_required" id="ghn_ghichu_required">
                                                    <option value="CHOXEMHANGKHONGTHU" <?php selected('CHOXEMHANGKHONGTHU',$data['NoteCode']);?>><?php _e('Cho xem hàng, không cho thử','devvn-ghn');?></option>
                                                    <option value="CHOTHUHANG" <?php selected('CHOTHUHANG',$data['NoteCode']);?>><?php _e('Cho thử hàng','devvn-ghn');?></option>
                                                    <option value="KHONGCHOXEMHANG" <?php selected('KHONGCHOXEMHANG',$data['NoteCode']);?>><?php _e('Không cho thử hàng','devvn-ghn');?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php _e('Ghi chú', 'devvn-ghn')?></td>
                                            <td><textarea name="ghn_ghichu" id="ghn_ghichu"><?php echo esc_textarea($data['Note'])?></textarea></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="devvn_option_col">
                                    <strong><?php _e('Gói cước','devvn-ghn');?></strong>
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
                                                            <label><input type="radio" name="ghn_services" data-fee="<?php echo $ServiceFee;?>" value="<?php echo $ServiceID; ?>" <?php checked($ServiceID, $data['ServiceID'])?>> <?php echo $Name . ' - ' . wc_price($ServiceFee);?></label>
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
                                        <div class="ghn_all_phuphi_list" data-phuphichoose="<?php echo esc_attr(json_encode($data['ShippingOrderCosts']));?>"></div>
                                    </div>
                                    <div class="ghn_nguoithanhtoan">
                                        <?php _e('Người thanh toán:','devvn-ghn');?>
                                        <label><input type="radio" name="ghn_PaymentTypeID" class="ghn_PaymentTypeID"  value="1" <?php checked(1,$data['PaymentTypeID'])?>> <?php _e('Người gửi','devvn-ghn');?></label>
                                        <label><input type="radio" name="ghn_PaymentTypeID" class="ghn_PaymentTypeID" value="2" <?php checked(2,$data['PaymentTypeID'])?>> <?php _e('Người nhận','devvn-ghn');?></label>
                                    </div>
                                    <div class="ghn_tienthuho">
                                        <?php _e('Tiền thu hộ (COD):','devvn-ghn');?>
                                        <input type="number" name="ghn_tienthuho" id="ghn_tienthuho" data-total="<?php echo $data['InsuranceFee'];?>" data-subtotal="<?php echo $data['InsuranceFee'];?>" data-codamount="<?php echo $data['CoDAmount']?>" value="<?php echo $data['CoDAmount']?>"/> <?php echo get_woocommerce_currency_symbol();?>
                                    </div>
                                    <div class="ghn_makhuyenmai">
                                        <?php _e('Mã khuyến mại:','devvn-ghn');?>
                                        <input type="text" name="ghn_CouponCode" id="ghn_CouponCode"  value="<?php echo $data['CouponCode']?>">
                                    </div>
                                    <?php
                                    $isPickAtStation = 2;
                                    foreach ($data['ShippingOrderCosts'] as $item) {
                                        if($item['ServiceID'] == 53337){
                                            $isPickAtStation = 1;
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="ghn_nguoithanhtoan">
                                        <?php _e('Gửi hàng tại điểm giao dịch (-2.000đ):','devvn-ghn');?>
                                        <label><input type="radio" name="ghn_isPickAtStation" class="ghn_isPickAtStation"  value="1" <?php checked(1, $isPickAtStation)?>> <?php _e('Có','devvn-ghn');?></label>
                                        <label><input type="radio" name="ghn_isPickAtStation" class="ghn_isPickAtStation" value="2" <?php checked(2, $isPickAtStation)?>> <?php _e('Không','devvn-ghn');?></label>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="total_order_api">
                        </td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="2" class="text_alignright">
                            <div class="ghn_msg"></div>
                            <a href="#" class="button button-primary devvn_float_right devvn_ghn_update_order"><?php _e('Cập nhật', 'devvn-ghn'); ?></a>
                            <a href="#" class="button close_popup devvn_float_right"><?php _e('Hủy chỉnh sửa', 'devvn-ghn'); ?></a>
                            <span class="spinner"></span>
                            <input type="hidden" name="ghn_ShippingOrderID" id="ghn_ShippingOrderID" value="<?php echo $data['ShippingOrderID']?>">
                            <input type="hidden" name="ghn_OrderCode" id="ghn_OrderCode" value="<?php echo $data['OrderCode']?>">
                        </td>
                    </tr>
                    </tfoot>
                </table>
                <input type="hidden" value="<?php echo $order->get_id()?>" name="order_id" class="order_id"/>
                <input type="hidden" value="0" name="allow_creat_order" class="allow_creat_order"/>
            </div>
        </div>
        <?php
    }
     function ghn_save_meta_box($post_id, $post){
         $nonce_name   = isset( $_POST['ghn_action_nonce'] ) ? $_POST['ghn_action_nonce'] : '';
         $nonce_action = 'ghn_action_nonce_action';

         // Check if nonce is set.
         if ( ! isset( $nonce_name ) ) {
             return;
         }

         // Check if nonce is valid.
         if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
             return;
         }

         // Check if user has permissions to save data.
         if ( ! current_user_can( 'edit_post', $post_id ) ) {
             return;
         }

         // Check if not an autosave.
         if ( wp_is_post_autosave( $post_id ) ) {
             return;
         }

         // Check if not a revision.
         if ( wp_is_post_revision( $post_id ) ) {
             return;
         }

     }

     function devvn_ghn_change_hub(){
         if ( !wp_verify_nonce( $_REQUEST['nonce'], "ghn_action_nonce_action")) {
             wp_send_json_error('Check nonce failed!');
         }
         $hubid = isset($_POST['hubid']) ? intval($_POST['hubid']) : '';
         $order_id = isset($_POST['order_id']) ? (float) $_POST['order_id'] : '';
         $weight = isset($_POST['weight']) ? (float) $_POST['weight'] : 0;
         $length = isset($_POST['length']) ? (float) $_POST['length'] : 0;
         $width = isset($_POST['width']) ? (float) $_POST['width'] : 0;
         $height = isset($_POST['height']) ? (float) $_POST['height'] : 0;
         $CouponCode = isset($_POST['CouponCode']) ? sanitize_text_field($_POST['CouponCode']) : '';
         $InsuranceFee = isset($_POST['InsuranceFee']) ? floatval($_POST['InsuranceFee']) : '';

         if(!$hubid || !$order_id) wp_send_json_error('Kiểm tra lại dữ liệu gửi vào');

         $args = array(
             'weight' => $weight,
             'length' => $length,
             'width' => $width,
             'height' => $height,
             'CouponCode' => $CouponCode,
             'InsuranceFee' => $InsuranceFee,
         );

         $order = wc_get_order($order_id);
         if($order && !is_wp_error($order)) {
             $rates = ghn_api()->order_findAvailableServices($order, $hubid, $args);
             ob_start();
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
                                 <label><input type="radio" name="ghn_services" data-fee="<?php echo $ServiceFee;?>" value="<?php echo $ServiceID; ?>"> <?php echo $Name . ' - ' . wc_price($ServiceFee);?></label>
                             </div>
                             <div class="ghn_all_goicuoc_col"><?php _e('Dự kiến giao', 'devvn-ghn')?> <?php echo $ExpectedDeliveryTime; ?></div>
                         </div>
                         <?php
                     }
                 }
             }
             wp_send_json_success(ob_get_clean());
         }
         wp_send_json_error('Có lỗi xảy ra');
     }

     function devvn_ghn_creat_order(){
         if ( !wp_verify_nonce( $_REQUEST['nonce'], "ghn_action_nonce_action")) {
             wp_send_json_error('Check nonce failed!');
         }
         $hubID = isset($_POST['hubID']) ? intval($_POST['hubID']) : 0;
         $order_ID = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;
         $order = wc_get_order($order_ID);
         if(!$order_ID || is_wp_error($order)){
             wp_send_json_error('Không tìm thấy Order');
         }
         if(!$hubID){
             wp_send_json_error('Hãy chọn 1 cửa hàng/kho');
         }
         $customer_infor = ghn_class()->get_customer_address_shipping($order);

         $extras_all = array();
         $extras = (isset($_POST['ghn_extras']) && !empty($_POST['ghn_extras'])) ? $_POST['ghn_extras'] : array();
         if(!empty($extras)){
             foreach($extras as $ext){
                 $extras_all[] = array(
                     "ServiceID" =>  intval($ext)
                 );
             }
         }

         $data = array(
             'PaymentTypeID' => isset($_POST['PaymentTypeID']) ? intval($_POST['PaymentTypeID']) : 1,

             "ClientContactName" => $this->get_hub_by_id($hubID, 'ContactName'),
             "ClientContactPhone" => $this->get_hub_by_id($hubID, 'ContactPhone'),
             "ClientAddress" => $this->get_hub_by_id($hubID, 'Address'),
             "ClientHubID" => $hubID,

             "FromDistrictID" => $this->get_hub_by_id($hubID, 'DistrictID'),

             "ToDistrictID" => isset($customer_infor['disrict']) ? intval($customer_infor['disrict']) : 0,
             "ToWardCode" => isset($customer_infor['ward']) ? sanitize_text_field($customer_infor['ward']) : "",

             "Note" => isset($_POST['noteOrder']) ? sanitize_textarea_field($_POST['noteOrder']) : "",

             "CustomerName" => isset($customer_infor['name']) ? sanitize_text_field($customer_infor['name']) : "",
             "CustomerPhone" => isset($customer_infor['phone']) ? sanitize_text_field($customer_infor['phone']) : "",
             "ShippingAddress" => isset($customer_infor['address']) ? sanitize_text_field($customer_infor['address']) : "",

             "CoDAmount" => isset($_POST['CoDAmount']) ? (float) $_POST['CoDAmount'] : 0,
             "NoteCode" => isset($_POST['noteCode']) ? sanitize_text_field($_POST['noteCode']) : '',

             "InsuranceFee" => isset($_POST['InsuranceFee']) ? (float) $_POST['InsuranceFee'] : 0,
             "ExternalCode" => isset($_POST['ExternalCode']) ? sanitize_text_field($_POST['ExternalCode']) : '',

             "ServiceID" => isset($_POST['ghn_services']) ? (int) $_POST['ghn_services'] : 0,

             "Content" => isset($_POST['ghn_contentOrder']) ? sanitize_textarea_field($_POST['ghn_contentOrder']) : "",
             "CouponCode" => isset($_POST['ghn_CouponCode']) ? sanitize_text_field($_POST['ghn_CouponCode']) : "",

             "Weight" => (isset($_POST['ghn_order_weight']) && $_POST['ghn_order_weight']) ? (float) $_POST['ghn_order_weight'] : 0,
             "Length" => isset($_POST['ghn_order_length']) && $_POST['ghn_order_length'] ? (float) $_POST['ghn_order_length'] : 1,
             "Width" => isset($_POST['ghn_order_width']) && $_POST['ghn_order_width'] ? (float) $_POST['ghn_order_width'] : 1,
             "Height" => isset($_POST['ghn_order_height']) && $_POST['ghn_order_height'] ? (float) $_POST['ghn_order_height'] : 1,

             "ShippingOrderCosts" => $extras_all,

         );

         $result = $this->createOrder($data);
         $data_args = isset($result['data']) ? $result['data'] : array();
         $msg = isset($result['msg']) ? $result['msg'] : '';
         if(isset($result['code']) && $result['code'] == 0){
             $data_msg = $msg . '\n';
             foreach($data_args as $k=>$v){
                 $data_msg .= $v . '\n';
             }
             wp_send_json_error($data_msg);
         }elseif(isset($result['code']) && $result['code'] == 1){

             $ghn_ordercode = isset($result['data']['OrderCode']) ? $result['data']['OrderCode'] : '';

             if($ghn_ordercode){
                 update_post_meta( $order_ID , '_ghn_order_fullinfor', $result );
                 update_post_meta( $order_ID , '_ghn_ordercode', $ghn_ordercode );
                 update_post_meta( $order_ID , '_ghn_order_submited', $data );
                 delete_post_meta( $order_ID , '_ghn_order_status');
                 $result = array(
                     'result_html' =>   __('Đăng đơn thành công!...', 'devvn-ghn'),
                     'ghn_ordercode'    =>  $ghn_ordercode,
                     'ordercode_html'   => $this->ordercode_html($ghn_ordercode)
                 );
                 wp_send_json_success($result);
             }
         }
         wp_send_json_error(__('Lỗi không xác định', 'devvn-ghn'));
         die();
     }

     function devvn_ghn_update_order(){
         if ( !wp_verify_nonce( $_REQUEST['nonce'], "ghn_action_nonce_action")) {
             wp_send_json_error('Check nonce failed!');
         }
         $hubID = isset($_POST['hubID']) ? intval($_POST['hubID']) : 0;
         $ghn_ShippingOrderID = isset($_POST['ghn_ShippingOrderID']) ? intval($_POST['ghn_ShippingOrderID']) : 0;
         $ghn_OrderCode = isset($_POST['ghn_OrderCode']) ? sanitize_text_field($_POST['ghn_OrderCode']) : '';
         $order_ID = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;
         $order = wc_get_order($order_ID);
         if(!$order_ID || is_wp_error($order)){
             wp_send_json_error('Không tìm thấy Order');
         }
         if(!$hubID){
             wp_send_json_error('Hãy chọn 1 cửa hàng/kho');
         }
         $customer_infor = ghn_class()->get_customer_address_shipping($order);

         $extras_all = array();
         $extras = (isset($_POST['ghn_extras']) && !empty($_POST['ghn_extras'])) ? $_POST['ghn_extras'] : array();
         if(!empty($extras)){
             foreach($extras as $ext){
                 $extras_all[] = array(
                     "ServiceID" =>  intval($ext)
                 );
             }
         }

         $data = array(
             'ShippingOrderID'  =>  $ghn_ShippingOrderID,
             'OrderCode'  =>  $ghn_OrderCode,

             'PaymentTypeID' => isset($_POST['PaymentTypeID']) ? intval($_POST['PaymentTypeID']) : 1,

             "ClientContactName" => $this->get_hub_by_id($hubID, 'ContactName'),
             "ClientContactPhone" => $this->get_hub_by_id($hubID, 'ContactPhone'),
             "ClientAddress" => $this->get_hub_by_id($hubID, 'Address'),
             "ClientHubID" => $hubID,

             "FromDistrictID" => $this->get_hub_by_id($hubID, 'DistrictID'),

             "ToDistrictID" => isset($customer_infor['disrict']) ? intval($customer_infor['disrict']) : 0,
             "ToWardCode" => isset($customer_infor['ward']) ? sanitize_text_field($customer_infor['ward']) : "",

             "Note" => isset($_POST['noteOrder']) ? sanitize_textarea_field($_POST['noteOrder']) : "",

             "CustomerName" => isset($customer_infor['name']) ? sanitize_text_field($customer_infor['name']) : "",
             "CustomerPhone" => isset($customer_infor['phone']) ? sanitize_text_field($customer_infor['phone']) : "",
             "ShippingAddress" => isset($customer_infor['address']) ? sanitize_text_field($customer_infor['address']) : "",

             "CoDAmount" => isset($_POST['CoDAmount']) ? (float) $_POST['CoDAmount'] : 0,
             "NoteCode" => isset($_POST['noteCode']) ? sanitize_text_field($_POST['noteCode']) : '',

             "InsuranceFee" => isset($_POST['InsuranceFee']) ? (float) $_POST['InsuranceFee'] : 0,

             "ServiceID" => isset($_POST['ghn_services']) ? (int) $_POST['ghn_services'] : 0,

             "Content" => isset($_POST['ghn_contentOrder']) ? sanitize_textarea_field($_POST['ghn_contentOrder']) : "",
             "CouponCode" => isset($_POST['ghn_CouponCode']) ? sanitize_text_field($_POST['ghn_CouponCode']) : "",

             "Weight" => (isset($_POST['ghn_order_weight']) && $_POST['ghn_order_weight']) ? (float) $_POST['ghn_order_weight'] : 0,
             "Length" => isset($_POST['ghn_order_length']) && $_POST['ghn_order_length'] ? (float) $_POST['ghn_order_length'] : 1,
             "Width" => isset($_POST['ghn_order_width']) && $_POST['ghn_order_width'] ? (float) $_POST['ghn_order_width'] : 1,
             "Height" => isset($_POST['ghn_order_height']) && $_POST['ghn_order_height'] ? (float) $_POST['ghn_order_height'] : 1,

             "ShippingOrderCosts" => $extras_all,

         );

         $result = $this->updateOrder($data);
         $data_args = isset($result['data']) ? $result['data'] : array();
         $msg = isset($result['msg']) ? $result['msg'] : '';
         if(isset($result['code']) && $result['code'] == 0){
             $data_msg = $msg . '\n';
             foreach($data_args as $k=>$v){
                 $data_msg .= $v . '\n';
             }
             wp_send_json_error($data_msg);
         }elseif(isset($result['code']) && $result['code'] == 1){
             update_post_meta( $order_ID , '_ghn_order_fullinfor', $result );
             update_post_meta( $order_ID , '_ghn_order_submited', $data );
             $result = array(
                 'result_html' =>   __('Cập nhật thành công!...', 'devvn-ghn'),
                 'ghn_ordercode'    =>  $ghn_OrderCode,
                 'ordercode_html'   => $this->ordercode_html($ghn_OrderCode)
             );
             wp_send_json_success($result);
         }
         wp_send_json_error(__('Lỗi không xác định', 'devvn-ghn'));
         die();
     }
    function devvn_ghn_cancel_order(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "ghn_action_nonce_action")) {
            wp_send_json_error('Check nonce failed!');
        }
        $ordercode = isset($_POST['ordercode']) ? sanitize_text_field($_POST['ordercode']) : '';
        $post_ID = isset($_POST['post_ID']) ? sanitize_text_field($_POST['post_ID']) : '';
        if($ordercode){
            $args = array(
                'data'  =>  array(
                    "token"	=> $this->token,
                    "OrderCode" => $ordercode
                ),
                'action'    =>  'CancelOrder'
            );

            $result = $this->get_cURL($args);
            $msg = isset($result['msg']) ? sanitize_text_field($result['msg']) : '';
            $data_args = isset($result['data']) ? $result['data'] : array();
            if(isset($result['code']) && $result['code'] == 1){
                delete_post_meta($post_ID,'_ghn_ordercode');
                delete_post_meta($post_ID,'_ghn_order_submited');
                delete_post_meta($post_ID,'_ghn_order_fullinfor');
                delete_post_meta($post_ID,'_ghn_order_status');
                wp_send_json_success(__('Đã hủy đơn hàng thành công', 'devvn-ghn'));
            }else{
                $data_msg = $msg . '\n';
                foreach($data_args as $k=>$v){
                    $data_msg .= $v . '\n';
                }
                wp_send_json_error($data_msg);
            }
        }
        die();
    }
    function get_status_text($CurrentStatus){
        $text = __('Không xác định','devvn-ghn');
        switch ($CurrentStatus){
            case 'ReadyToPick':
                $text = __('Đơn hàng mới tạo','devvn-ghn');
                break;
            case 'Picking':
                $text = __('Đang lấy hàng','devvn-ghn');
                break;
            case 'Storing':
                $text = __('Hàng đã được lưu kho','devvn-ghn');
                break;
            case 'Delivering':
                $text = __('Đang giao hàng','devvn-ghn');
                break;
            case 'Delivered':
                $text = __('Giao thành công','devvn-ghn');
                break;
            case 'Return':
                $text = __('Chờ trả hàng','devvn-ghn');
                break;
            case 'Returned':
                $text = __('Trả thành công','devvn-ghn');
                break;
            case 'WaitingToFinish':
                $text = __('Chờ thanh toán/chuyển COD','devvn-ghn');
                break;
            case 'Finish':
                $text = __('Đơn hàng hoàn tất','devvn-ghn');
                break;
            case 'Cancel':
                $text = __('Đã hủy','devvn-ghn');
                break;
            case 'LostOrder':
                $text = __('Hàng thất lạc','devvn-ghn');
                break;
        }
        return $text;
    }
    function devvn_ghn_tracking_order(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "ghn_action_nonce_action")) {
            wp_send_json_error('Check nonce failed!');
        }
        $ordercode = isset($_POST['ordercode']) ? sanitize_text_field($_POST['ordercode']) : '';
        $post_ID = isset($_POST['post_ID']) ? sanitize_text_field($_POST['post_ID']) : '';
        if($ordercode){
            $args = array(
                'data'  =>  array(
                    "token"	=> $this->token,
                    "OrderCode" => $ordercode
                ),
                'action'    =>  'OrderInfo'
            );

            $result = $this->get_cURL($args);
            $msg = isset($result['msg']) ? sanitize_text_field($result['msg']) : '';
            $data_args = isset($result['data']) ? $result['data'] : array();
            if(isset($result['code']) && $result['code'] == 1){
                $CurrentStatus = isset($result['data']['CurrentStatus']) ? $result['data']['CurrentStatus'] : '';
                $name = $this->get_status_text($CurrentStatus);
                update_post_meta($post_ID, '_ghn_order_status', $CurrentStatus);
                $this->change_order_status($post_ID, $CurrentStatus);
                wp_send_json_success(sprintf(__('Trạng thái đơn hàng: %s', 'devvn-ghn'), $name));
            }else{
                $data_msg = $msg . '\n';
                foreach($data_args as $k=>$v){
                    $data_msg .= $v . '\n';
                }
                wp_send_json_error($data_msg);
            }
        }
        wp_send_json_error(__('Lỗi không xác định', 'devvn-ghn'));
        die();
    }
    public function setWebhook($data = array())
    {
        if(!is_array($data) || empty($data)) return false;
        $webhook_url = isset($data['webhook_url']) ? esc_url_raw($data['webhook_url']) : '';
        if($webhook_url) {
            $args = array(
                'data' => array(
                    "token" => $this->token,
                    "TokenClient" => array($this->token),
                    "ConfigCod" => true,
                    "ConfigReturnData" => true,
                    "URLCallback" => $webhook_url,
                    "ConfigField" => array(
                        "CoDAmount" => true,
                        "CurrentWarehouseName" => true,
                        "CustomerID" => true,
                        "CustomerName" => true,
                        "CustomerPhone" => true,
                        "Note" => true,
                        "OrderCode" => true,
                        "ServiceName" => true,
                        "ShippingOrderCosts" => true,
                        "Weight" => true,
                        "ReturnInfo" => true,
                        "ExternalCode" => true
                    ),
                    "ConfigStatus" => array(
                        "Cancel" => true,
                        "Delivered" => true,
                        "Delivering" => true,
                        "Finish" => true,
                        "LostOrder" => true,
                        "Picking" => true,
                        "ReadyToPick" => true,
                        "Return" => true,
                        "Returned" => true,
                        "Storing" => true,
                        "WaitingToFinish" => true
                    )
                ),
                'action' => 'SetConfigClient'
            );
            $result = $this->get_cURL($args);

            return $result;
        }else{
            return false;
        }
    }

    function devvn_ghn_set_webhook(){
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "webhook_nonce")) {
            wp_send_json_error("Check nonce failed!");
        }
        $webhook_url = isset($_POST['webhook_url']) ? esc_url_raw($_POST['webhook_url']) : '';
        if(!$webhook_url) wp_send_json_error(__('Webhook URL không được để trống!', 'devvn-ghn'));
        $data = array(
            'webhook_url'   => $webhook_url
        );
        $result = $this->setWebhook($data);
        $data_args = isset($result['data']) ? $result['data'] : array();
        $msg = isset($result['msg']) ? $result['msg'] : '';
        if(isset($result['code']) && $result['code'] == 0){
            $data_msg = $msg . '\n';
            foreach($data_args as $k=>$v){
                $data_msg .= $v . '\n';
            }
            wp_send_json_error($data_msg);
        }elseif(isset($result['code']) && $result['code'] == 1){
            wp_send_json_success(__('Đăng ký webhook thành công. Đang lưu cài đặt và tải lại trang web ...'));
        }
        wp_send_json_error(__('Lỗi không xác định', 'devvn-ghn'));
        die();
    }
    function change_order_status($orderID, $status = ''){
        if($orderID){
            $order = wc_get_order($orderID);
            if($order && !is_wp_error($order)) {
                switch ($status) {
                    case "Cancel":
                        $order->set_status('cancelled');
                        $order->save();
                        break;
                    case "Finish":
                    case "WaitingToFinish":
                    case "Delivered":
                        $order->set_status('completed');
                        $order->save();
                        break;
                }
            }
        }
    }
    function devvn_ghn_webhook_func(){

        $POST = json_decode(file_get_contents('php://input'), true);

        if (isset($_POST) && empty($_POST)) {
            $_POST = $POST;
        }

        /*$log  = "User: ".$_SERVER['REMOTE_ADDR'].' - '.date("F j, Y, g:i a").PHP_EOL.
            json_encode($_POST).PHP_EOL.
            json_encode($_GET).PHP_EOL.
            "-------------------------".PHP_EOL;

        file_put_contents( dirname(__FILE__) . '/log_start_'.date("j.n.Y").'.txt', $log, FILE_APPEND);*/

        $hash = isset($_GET['hash']) ? sanitize_text_field($_GET['hash']) : '';

        $options = wp_parse_args(get_option($this->_ghn_webhook), $this->_defaultWebhookOptions);
        $webhook_hash = $options['webhook_hash'];

        if($hash && $webhook_hash && $webhook_hash == $hash) {

            $CurrentStatus = isset($_POST['CurrentStatus']) ? sanitize_text_field($_POST['CurrentStatus']) : '';
            $OrderCode = isset($_POST['OrderCode']) ? sanitize_text_field($_POST['OrderCode']) : '';
            $params = array(
                'post_type' => 'shop_order',
                'meta_key' => '_ghn_ordercode',
                'meta_value' => $OrderCode,
                'posts_per_page' => 1,
                'post_status'      => 'any',
            );
            $order = get_posts($params);
            if($order && !is_wp_error($order) && $CurrentStatus){
                $orderID = $order[0]->ID;
                update_post_meta($orderID, '_ghn_order_status', $CurrentStatus);
                $this->change_order_status($orderID, $CurrentStatus);
            }

        }

    }

    function ghn_creat_order_ajax_func(){
        $orderid = isset($_POST['orderid']) ? intval($_POST['orderid']) : '';
        $result = array();
        if($orderid){
            $order = wc_get_order($orderid);
            $customer_infor = ghn_class()->get_customer_address_shipping($order);
            extract($customer_infor);

            $shipping_methods = $order->get_shipping_methods();
            $HubID_Order = '';
            $method_id = '';
            foreach ( $shipping_methods as $shipping_method ) {
                foreach($shipping_method->get_formatted_meta_data() as $meta_data){
                    if($meta_data->key && $meta_data->key == 'HubID' && !$HubID_Order){
                        $HubID_Order = $meta_data->value;
                    }
                }
                foreach($shipping_method->get_formatted_meta_data() as $meta_data){
                    if($meta_data->key && $meta_data->key == 'ServiceID' && !$method_id){
                        $method_id = $meta_data->value;
                    }
                }
            }

            $product_list = ghn_class()->get_product_args($order);
            ob_start();
            include 'ghn-creat-order-html.php';
            $result['result_html'] = ob_get_clean();
            wp_send_json_success($result);
        }
        wp_send_json_error();
        die();
    }

}

function ghn_api(){
    return DevVN_GHN_API::instance();
}
ghn_api();