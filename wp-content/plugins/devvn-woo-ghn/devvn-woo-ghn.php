<?php
/*
 * Plugin Name: Woocommerce - Giao Hàng Nhanh (GHN)
 * Plugin URI: https://levantoan.com/san-pham/plugin-ket-noi-giao-hang-nhanh-voi-woocommerce/
 * Version: 1.0.5
 * Description: Tính phí vận chuyển, đăng đơn, kiểm tra tình trạng đơn hàng với giao hàng nhanh (GHN)
 * Author: GHN - Dev by Le Van Toan
 * Author URI: http://levantoan.com
 * Text Domain: devvn-ghn
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.5
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

register_activation_hook(   __FILE__, array( 'DevVN_Woo_GHN_Class', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'DevVN_Woo_GHN_Class', 'on_deactivation' ) );
register_uninstall_hook(    __FILE__, array( 'DevVN_Woo_GHN_Class', 'on_uninstall' ) );

load_textdomain('devvn-ghn', dirname(__FILE__) . '/languages/devvn-ghn-' . get_locale() . '.mo');

class DevVN_Woo_GHN_Class
{
    protected static $instance;

	protected $_version = '1.0.5';
	public $_optionName = 'devvn_woo_district';
	public $_optionGroup = 'devvn-district-options-group';
	public $_defaultOptions = array(
	    'active_village'	            =>	'',
        'required_village'	            =>	'',
        'to_vnd'	                    =>	'',
        'remove_methob_title'	        =>	'',
        'freeship_remove_other_methob'  =>  '',
        'active_vnd2usd'                =>  0,
        'vnd_usd_rate'                  =>  '22745',
        'vnd2usd_currency'              =>  'USD',

        'active_orderstyle'             =>  '',
        'alepay_support'                =>  '',
        'enable_postcode'               =>  '',

        'token_key'                     =>  '',
        'ghn_ghichu'                    =>  'CHOXEMHANGKHONGTHU',
        'ghn_aff_id'                    =>  '',

        'list_hubs'                     => array()
	);

    public $_license_field = 'devvn_ghn_license';
    public $_license_field_group = 'devvn_ghn_license_group';
    public $_defaultLicenseOptions = array(
        'license_key'   =>	'',
    );

	public $_weight_option = 'kilogram';
	public $tinh_thanhpho = array();

    public static function init(){
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

	public function __construct(){

        $this->define_constants();

        $this->set_weight_option();

        include 'cities/tinh_thanhpho.php';

        $this->tinh_thanhpho = $tinh_thanhpho;

    	add_filter( 'woocommerce_checkout_fields' , array($this, 'custom_override_checkout_fields'), 99999 );
    	add_filter( 'woocommerce_states', array($this, 'vietnam_cities_woocommerce'), 9999 );

    	add_action( 'wp_enqueue_scripts', array($this, 'devvn_enqueue_UseAjaxInWp') );
    	add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

    	add_action( 'wp_ajax_load_diagioihanhchinh', array($this, 'load_diagioihanhchinh_func') );
		add_action( 'wp_ajax_nopriv_load_diagioihanhchinh', array($this, 'load_diagioihanhchinh_func') );

		add_filter('woocommerce_localisation_address_formats', array($this, 'devvn_woocommerce_localisation_address_formats') );
		add_filter('woocommerce_order_formatted_billing_address', array($this, 'devvn_woocommerce_order_formatted_billing_address'), 10, 2);

		add_action( 'woocommerce_admin_order_data_after_shipping_address', array($this, 'devvn_after_shipping_address'), 10, 1 );
		add_filter('woocommerce_order_formatted_shipping_address', array($this, 'devvn_woocommerce_order_formatted_shipping_address'), 10, 2);

		add_filter('woocommerce_order_details_after_customer_details', array($this, 'devvn_woocommerce_order_details_after_customer_details'), 10);

		//my account
		add_filter('woocommerce_my_account_my_address_formatted_address',array($this, 'devvn_woocommerce_my_account_my_address_formatted_address'),10,3);

		//More action
        add_filter( 'default_checkout_billing_country', array($this, 'devvn_change_default_checkout_country'), 999 );
        add_filter( 'default_checkout_shipping_country', array($this, 'devvn_change_default_checkout_country'), 999 );

		//Options
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_mysettings') );
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ) );

		add_option( $this->_optionName, $this->_defaultOptions );

        include_once( 'includes/apps.php' );

        if($this->get_options('active_orderstyle')) {
            include_once('includes/class-order-style.php');
        }

        add_filter( 'woocommerce_default_address_fields' , array( $this, 'devvn_custom_override_default_address_fields'), 99999 );
        add_filter('woocommerce_get_country_locale', array($this, 'devvn_woocommerce_get_country_locale'), 99);

        //admin order address, form billing
        add_filter('woocommerce_admin_billing_fields', array($this, 'devvn_woocommerce_admin_billing_fields'), 99);
        add_filter('woocommerce_admin_shipping_fields', array($this, 'devvn_woocommerce_admin_shipping_fields'), 99);

        add_filter('woocommerce_form_field_select', array($this, 'devvn_woocommerce_form_field_select'), 10, 4);

        add_filter('woocommerce_shipping_calculator_enable_postcode','__return_false');

        add_filter('woocommerce_get_order_address', array($this, 'devvn_woocommerce_get_order_address'), 99, 2);  //API V1
        add_filter('woocommerce_rest_prepare_shop_order_object', array($this, 'devvn_woocommerce_rest_prepare_shop_order_object'), 99, 3);//API V2

        add_filter('admin_body_class', array($this, 'devvn_admin_body_class'));

        /*add_action( 'admin_notices', array($this, 'admin_notices') );
        if( is_admin() ) {
            add_action('in_plugin_update_message-' . DEVVN_GHN_BASENAME, array($this,'devvn_modify_plugin_update_message'), 10, 2 );
        }
        include 'includes/updates.php';*/
        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

    }

    public function define_constants(){
        if (!defined('DEVVN_GHN_VERSION_NUM'))
            define('DEVVN_GHN_VERSION_NUM', $this->_version);
        if (!defined('DEVVN_GHN_URL'))
            define('DEVVN_GHN_URL', plugin_dir_url(__FILE__));
        if (!defined('DEVVN_GHN_BASENAME'))
            define('DEVVN_GHN_BASENAME', plugin_basename(__FILE__));
        if (!defined('DEVVN_GHN_PLUGIN_DIR'))
            define('DEVVN_GHN_PLUGIN_DIR', plugin_dir_path(__FILE__));
    }

    function set_weight_option(){
	    $wc_weight = get_option( 'woocommerce_weight_unit' );
	    if($wc_weight == 'g')
	        $this->_weight_option = 'gram';
    }

    public static function on_activation(){
        if ( ! current_user_can( 'activate_plugins' ) )
            return false;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "activate-plugin_{$plugin}" );

    }

    public static function on_deactivation(){
        if ( ! current_user_can( 'activate_plugins' ) )
            return false;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );

    }

    public static function on_uninstall(){
        if ( ! current_user_can( 'activate_plugins' ) )
            return false;
    }

	function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Cài đặt GHN','devvn-ghn'),
            __('Cài đặt GHN','devvn-ghn'),
            'manage_woocommerce',
            'devvn-woo-ghn',
            array(
                $this,
                'devvn_district_setting'
            )
        );
	}

	function register_mysettings() {
		register_setting( $this->_optionGroup, $this->_optionName );
		register_setting( $this->_license_field_group, $this->_license_field );
	}

	function  devvn_district_setting() {
        wp_enqueue_media();
        include 'includes/options-page.php';
	}

	function vietnam_cities_woocommerce( $states ) {
        if(!is_array($this->tinh_thanhpho) || empty($this->tinh_thanhpho)){
            include 'cities/tinh_thanhpho.php';
            $this->tinh_thanhpho = $tinh_thanhpho;
        }
	  	$states['VN'] = $this->tinh_thanhpho;
	  	return $states;
	}

    function custom_override_checkout_fields( $fields ) {
        if(!$this->get_options('alepay_support')) {
            //Billing
            $fields['billing']['billing_last_name'] = array(
                'label' => __('Họ tên', 'devvn-ghn'),
                'placeholder' => _x('Nhập họ tên của bạn', 'placeholder', 'devvn-ghn'),
                'required' => true,
                'class' => array('form-row-wide'),
                'clear' => true,
                'priority' => 10
            );
        }
        if(isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['class'] = array('form-row-first');
        }
        if(isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['class'] = array('form-row-last');
        }
        $fields['billing']['billing_state'] = array(
            'label'			=> __('Khu vực', 'devvn-ghn'),
            'required' 		=> true,
            'type'			=> 'select',
            'class'    		=> array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
            'placeholder'	=> _x('Chọn khu vực', 'placeholder', 'devvn-ghn'),
            'options'   	=> array( '' => __( 'Chọn khu vực', 'devvn-ghn' ) ) + $this->tinh_thanhpho,
            'priority'  =>  30
        );
        if(!$this->get_options()) {
            $fields['billing']['billing_city'] = array(
                'label' => __('Xã/Phường/thị trấn', 'devvn-ghn'),
                'required' => true,
                'type' => 'select',
                'class' => array('form-row-wide', 'address-field', 'update_totals_on_change'),
                'placeholder' => _x('Chọn xã/Phường/thị trấn', 'placeholder', 'devvn-ghn'),
                'options' => array(
                    '' => ''
                ),
                'priority' => 40
            );
            if ($this->get_options('required_village')) {
                $fields['billing']['billing_city']['required'] = false;
            }
        }
        $fields['billing']['billing_address_1']['placeholder'] = _x('Ví dụ: số 20 Ngõ 90', 'placeholder', 'devvn-ghn');
        $fields['billing']['billing_address_1']['class'] = array('form-row-wide');

        $fields['billing']['billing_address_1']['priority']  = 60;
        if(isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['priority'] = 20;
        }
        if(isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['priority'] = 21;
        }
        if(!$this->get_options('alepay_support')) {
            unset($fields['billing']['billing_country']);
            unset($fields['billing']['billing_first_name']);
        }
        unset($fields['billing']['billing_company']);

        //Shipping
        if(!$this->get_options('alepay_support')) {
            $fields['shipping']['shipping_last_name'] = array(
                'label' => __('Họ tên', 'devvn-ghn'),
                'placeholder' => _x('Họ tên của bạn', 'placeholder', 'devvn-ghn'),
                'required' => true,
                'class' => array('form-row-first'),
                'clear' => true,
                'priority' => 10
            );
        }
        $fields['shipping']['shipping_phone'] = array(
            'label' => __('Số điện thoại', 'devvn-ghn'),
            'placeholder' => _x('Số điện thoại', 'placeholder', 'devvn-ghn'),
            'required' => false,
            'class' => array('form-row-last'),
            'clear' => true,
            'priority'  =>  20
        );
        if($this->get_options('alepay_support')) {
            $fields['shipping']['shipping_phone']['class'] = array('form-row-wide');
        }
        $fields['shipping']['shipping_state'] = array(
            'label'		=> __('Khu vực', 'devvn-ghn'),
            'required' 	=> true,
            'type'		=>	'select',
            'class'    	=> array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
            'placeholder'	=>	_x('Chọn khu vực', 'placeholder', 'devvn-ghn'),
            'options'   => array( '' => __( 'Chọn khu vực', 'devvn-ghn' ) ) + $this->tinh_thanhpho,
            'priority'  =>  30
        );
        if(!$this->get_options()) {
            $fields['shipping']['shipping_city'] = array(
                'label' => __('Xã/Phường/thị trấn', 'devvn-ghn'),
                'required' => true,
                'type' => 'select',
                'class' => array('form-row-wide', 'address-field', 'update_totals_on_change'),
                'placeholder' => _x('Chọn xã/Phường/thị trấn', 'placeholder', 'devvn-ghn'),
                'options' => array(
                    '' => '',
                ),
                'priority' => 40
            );
            if ($this->get_options('required_village')) {
                $fields['shipping']['shipping_city']['required'] = false;
            }
        }

        $fields['shipping']['shipping_address_1']['placeholder'] = _x('Ví dụ: số 20 Ngõ 90', 'placeholder', 'devvn-ghn');
        $fields['shipping']['shipping_address_1']['class'] = array('form-row-wide');
        $fields['shipping']['shipping_address_1']['priority'] = 60;
        if(!$this->get_options('alepay_support')) {
            unset($fields['shipping']['shipping_country']);
            unset($fields['shipping']['shipping_first_name']);
        }
        unset($fields['shipping']['shipping_company']);

        uasort( $fields['billing'], array( $this, 'sort_fields_by_order' ) );
        uasort( $fields['shipping'], array( $this, 'sort_fields_by_order' ) );

        return $fields;
    }

    function sort_fields_by_order($a, $b){
        if(!isset($b['priority']) || !isset($a['priority']) || $a['priority'] == $b['priority']){
            return 0;
        }
        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }

	function search_in_array($array, $key, $value)
	{
	    $results = array();

	    if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }elseif(isset($array[$key]) && is_serialized($array[$key]) && in_array($value,maybe_unserialize($array[$key]))){
                $results[] = $array;
            }
	        foreach ($array as $subarray) {
	            $results = array_merge($results, $this->search_in_array($subarray, $key, $value));
	        }
	    }

	    return $results;
	}

	function search_in_array_value($array = array(), $value = ''){
        $results = array();
        if(is_array($array) && !empty($array)) {
            foreach ($array as $k=>$subarray) {
                if(in_array($value, $subarray)){
                    $results[] = $k;
                }
            }
        }
        return $results;
    }

	function devvn_enqueue_UseAjaxInWp() {
		if(is_checkout() || is_page(get_option( 'woocommerce_edit_address_page_id' ))){
            wp_enqueue_style( 'ghn_styles', plugins_url( '/assets/css/devvn_dwas_style.css', __FILE__ ), array(), $this->_version, 'all' );
			wp_enqueue_script( 'devvn_tinhthanhpho', plugins_url('assets/js/devvn_tinhthanh.js', __FILE__), array('jquery','select2'), $this->_version, true);
			$php_array = array(
				'admin_ajax'		=>	admin_url( 'admin-ajax.php'),
				'home_url'			=>	home_url(),
                'formatNoMatches'   =>  __('No value', 'devvn-ghn')
			);
			wp_localize_script( 'devvn_tinhthanhpho', 'ghn_array', $php_array );
		}
	}

	function load_diagioihanhchinh_func() {
		$matp = isset($_POST['matp']) ? sanitize_text_field($_POST['matp']) : '';
		if($matp){
			$result = $this->get_list_wards($matp);
			wp_send_json_success($result);
		}
		wp_send_json_error();
		die();
	}
	function devvn_get_name_location($arg = array(), $id = '', $key = ''){
		if(is_array($arg) && !empty($arg)){
			$nameQuan = $this->search_in_array($arg,$key,$id);
			$nameQuan = isset($nameQuan[0]['name'])?$nameQuan[0]['name']:'';
			return $nameQuan;
		}
		return false;
	}

	function get_name_city($id = ''){
		if(!is_array($this->tinh_thanhpho) || empty($this->tinh_thanhpho)){
			include 'cities/tinh_thanhpho.php';
            $this->tinh_thanhpho = $tinh_thanhpho;
		}
		$id_tinh = sanitize_text_field($id);
		$tinh_thanhpho = (isset($this->tinh_thanhpho[$id_tinh]))?$this->tinh_thanhpho[$id_tinh]:'';
		return $tinh_thanhpho;
	}

	function get_name_ward($id = ''){
		include 'cities/xa_phuong_thitran.php';
        $id_ward = sprintf("%05d", intval($id));
		if(is_array($wards) && !empty($wards)){
			$nameWard = $this->search_in_array($wards,'WardCode',$id_ward);
            $nameWard = isset($nameWard[0]['WardName'])?$nameWard[0]['WardName']:'';
			return $nameWard;
		}
		return false;
	}

	function devvn_woocommerce_localisation_address_formats($arg){
		unset($arg['default']);
		unset($arg['VN']);
		$arg['default'] = "{name}\n{company}\n{address_1}\n{city}\n{state}\n{country}";
		$arg['VN'] = "{name}\n{company}\n{address_1}\n{city}\n{state}\n{country}";
		return $arg;
	}

	function devvn_woocommerce_order_formatted_billing_address($eArg,$eThis){

        if($this->devvn_check_woo_version()){
            $orderID = $eThis->get_id();
        }else {
            $orderID = $eThis->id;
        }

		$nameTinh = $this->get_name_city(get_post_meta( $orderID, '_billing_state', true ));
		$nameQuan = $this->get_name_ward(get_post_meta( $orderID, '_billing_city', true ));

		unset($eArg['state']);
		unset($eArg['city']);

		$eArg['state'] = $nameTinh;
		$eArg['city'] = $nameQuan;

		return $eArg;
	}

	function devvn_woocommerce_order_formatted_shipping_address($eArg,$eThis){

        if($this->devvn_check_woo_version()){
            $orderID = $eThis->get_id();
        }else {
            $orderID = $eThis->id;
        }

		$nameTinh = $this->get_name_city(get_post_meta( $orderID, '_shipping_state', true ));
		$nameQuan = $this->get_name_ward(get_post_meta( $orderID, '_shipping_city', true ));

		unset($eArg['state']);
		unset($eArg['city']);

		$eArg['state'] = $nameTinh;
		$eArg['city'] = $nameQuan;

		return $eArg;
	}

	function devvn_woocommerce_my_account_my_address_formatted_address($args, $customer_id, $name){

		$nameTinh = $this->get_name_city(get_user_meta( $customer_id, $name.'_state', true ));
		$nameQuan = $this->get_name_ward(get_user_meta( $customer_id, $name.'_city', true ));

		unset($args['city']);
		unset($args['state']);

		$args['state'] = $nameTinh;
		$args['city'] = $nameQuan;

		return $args;
	}

	function get_district_id_from_string($string = ''){
	    $arg_id = explode('_', $string);
	    return (isset($arg_id[1])) ? intval($arg_id[1]) : false;
    }

	function get_state_id_from_string($string = ''){
	    $arg_id = explode('_', $string);
	    return (isset($arg_id[0])) ? intval($arg_id[0]) : false;
    }

	function get_list_wards($matp = ''){
		if(!$matp) return false;
		include 'cities/xa_phuong_thitran.php';
		$matp = $this->get_district_id_from_string(sanitize_text_field($matp));
		$result = $this->search_in_array($wards,'DistrictID',$matp);
		return $result;
	}

    function get_states($stateid = ''){
        include 'cities/states.php';
        if($stateid) {
            $result = isset($states[$stateid]) ? $states[$stateid] : '';
        }else{
            $result = $states;
        }
        return $result;
    }

    function get_list_wards_select($matp = ''){
        $ward_select  = array();
        $ward_select_array = $this->get_list_wards($matp);
        if($ward_select_array && is_array($ward_select_array)){
            foreach ($ward_select_array as $ward){
                $ward_select[$ward['WardCode']] = $ward['WardName'];
            }
        }
        return $ward_select;
    }

	function devvn_after_shipping_address($order){
	    if($this->devvn_check_woo_version()){
            $orderID = $order->get_id();
        }else {
            $orderID = $order->id;
        }
	    echo '<div class="devvn_clear"><strong>'.__('Phone number of the recipient', 'devvn-ghn').':</strong> <br>' . get_post_meta( $orderID, '_shipping_phone', true ) . '</div>';
	}

	function devvn_woocommerce_order_details_after_customer_details($order){
		ob_start();
        if($this->devvn_check_woo_version()){
            $orderID = $order->get_id();
        }else {
            $orderID = $order->id;
        }
        $sdtnguoinhan = get_post_meta( $orderID, '_shipping_phone', true );
		if ( $sdtnguoinhan ) : ?>
			<tr>
				<th><?php _e( 'Shipping Phone:', 'devvn-ghn' ); ?></th>
				<td><?php echo esc_html( $sdtnguoinhan ); ?></td>
			</tr>
		<?php endif;
		echo ob_get_clean();
	}

	public function get_options($option = 'active_village'){
		$flra_options = wp_parse_args(get_option($this->_optionName),$this->_defaultOptions);
		return isset($flra_options[$option]) ? $flra_options[$option] : false;
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'select2', plugins_url( '/assets/css/select2.css', __FILE__ ), array(), $this->_version, 'all' );
		wp_enqueue_style( 'woocommerce_district_shipping_styles', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), $this->_version, 'all' );
        wp_enqueue_script( 'bpopup', plugins_url( '/assets/js/jquery.bpopup.min.js', __FILE__ ), array( 'jquery'), $this->_version, true );
        wp_enqueue_script( 'accounting', plugins_url( '/assets/js/accounting.min.js', __FILE__ ), array( 'jquery'), $this->_version, true );
        wp_enqueue_script( 'woocommerce_district_admin_order', plugins_url( '/assets/js/admin-district-admin-order.js', __FILE__ ), array( 'jquery', 'select2', 'accounting'), $this->_version, true );
        wp_localize_script( 'woocommerce_district_admin_order', 'admin_ghn_array', array(
            'ajaxurl'   =>  admin_url('admin-ajax.php'),
            'formatNoMatches'   =>  __('No value', 'devvn-ghn')
        ) );
	}

    public function devvn_check_woo_version($version = '3.0.0'){
        if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, $version, '>=' ) ) {
            return true;
        }
        return false;
    }
    function devvn_change_default_checkout_country() {
        return 'VN';
    }

    function devvn_woocommerce_get_country_locale($args){
        $args['VN'] = array(
            'state' => array(
                'label'        => __('Province/City', 'devvn-ghn'),
                'priority'     => 41,
            ),
            'address_1' => array(
                'priority'     => 44,
            ),
        );
        if(!$this->get_options()) {
            $args['VN']['city'] = array(
                'hidden'   => false,
                'priority'     => 42,
            );
        }
        unset($args['VN']['address_2']);
        return $args;
    }

    function devvn_custom_override_default_address_fields( $address_fields ) {
        if(!$this->get_options('alepay_support')) {
            unset($address_fields['first_name']);
            $address_fields['last_name'] = array(
                'label' => __('Họ tên', 'devvn-ghn'),
                'placeholder' => _x('Nhập họ tên của bạn', 'placeholder', 'devvn-ghn'),
                'required' => true,
                'class' => array('form-row-wide'),
                'clear' => true
            );
        }
        if(!$this->get_options('enable_postcode')) {
            unset($address_fields['postcode']);
        }
        if(!$this->get_options()) {
            $address_fields['city'] = array(
                'label' => __('Xã/Phường/thị trấn', 'devvn-ghn'),
                'type' => 'select',
                'class' => array('form-row-wide'),
                'placeholder' => _x('Chọn xã/Phường/thị trấn', 'placeholder', 'devvn-ghn'),
                'options' => array(
                    '' => ''
                ),
            );
        }else{
            unset($address_fields['city']);
        }
        $address_fields['address_1']['class'] = array('form-row-wide');
        unset($address_fields['address_2']);
        return $address_fields;
    }
    function devvn_woocommerce_admin_billing_fields($billing_fields){
        global $thepostid, $post;
        $thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
        $city = get_post_meta( $thepostid, '_billing_state', true );
        $district = get_post_meta( $thepostid, '_billing_city', true );
        $billing_fields = array(
            'first_name' => array(
                'label' => __( 'First name', 'woocommerce' ),
                'show'  => false,
            ),
            'last_name' => array(
                'label' => __( 'Last name', 'woocommerce' ),
                'show'  => false,
            ),
            'company' => array(
                'label' => __( 'Company', 'woocommerce' ),
                'show'  => false,
            ),
            'country' => array(
                'label'   => __( 'Country', 'woocommerce' ),
                'show'    => false,
                'class'   => 'js_field-country select short',
                'type'    => 'select',
                'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries(),
            ),
            'state' => array(
                'label' => __( 'Tỉnh/thành phố', 'devvn-ghn' ),
                'class'   => 'js_field-state select short',
                'show'  => false,
            ),
            'city' => array(
                'label' => __( 'Xã phường', 'devvn-ghn' ),
                'class'   => 'js_field-city select short',
                'type'      =>  'select',
                'show'  => false,
                'options' => array( '' => __( 'Chọn xã/phường&hellip;', 'devvn-ghn' ) ) + $this->get_list_wards_select($city),
            ),
            'address_1' => array(
                'label' => __( 'Address line 1', 'woocommerce' ),
                'show'  => false,
            ),
            'email' => array(
                'label' => __( 'Email address', 'woocommerce' ),
            ),
            'phone' => array(
                'label' => __( 'Phone', 'woocommerce' ),
            )
        );
        unset($billing_fields['address_2']);
        return $billing_fields;
    }
    function devvn_woocommerce_admin_shipping_fields($shipping_fields){
        global $thepostid, $post;
        $thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
        $city = get_post_meta( $thepostid, '_shipping_state', true );
        $district = get_post_meta( $thepostid, '_shipping_city', true );
        $billing_fields = array(
            'first_name' => array(
                'label' => __( 'First name', 'woocommerce' ),
                'show'  => false,
            ),
            'last_name' => array(
                'label' => __( 'Last name', 'woocommerce' ),
                'show'  => false,
            ),
            'company' => array(
                'label' => __( 'Company', 'woocommerce' ),
                'show'  => false,
            ),
            'country' => array(
                'label'   => __( 'Country', 'woocommerce' ),
                'show'    => false,
                'type'    => 'select',
                'class'   => 'js_field-country select short',
                'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_shipping_countries(),
            ),
            'state' => array(
                'label' => __( 'Tỉnh/thành phố', 'devvn-ghn' ),
                'class'   => 'js_field-state select short',
                'show'  => false,
            ),
            'city' => array(
                'label' => __( 'Quận/huyện', 'devvn-ghn' ),
                'class'   => 'js_field-city select short',
                'type'      =>  'select',
                'show'  => false,
                'options' => array( '' => __( 'Chọn quận/huyện&hellip;', 'devvn-ghn' ) ) + $this->get_list_wards_select($city),
            ),
            'address_1' => array(
                'label' => __( 'Address line 1', 'woocommerce' ),
                'show'  => false,
            ),
        );
        unset($billing_fields['address_2']);
        return $billing_fields;
    }

    function get_cart_contents_weight( $package = array() ) {
        $weight = 0;
        if(isset($package['contents']) && !empty($package['contents'])) {
            foreach ($package['contents'] as $cart_item_key => $values) {
                $weight += (float)$values['data']->get_weight() * $values['quantity'];
            }
            $weight = $this->convert_weight_to_gram($weight);
        }
        return apply_filters( 'wc_devvn_cart_contents_weight', $weight );
    }

    function convert_weight_to_gram( $weight ) {
        switch(get_option( 'woocommerce_weight_unit' )){
            case 'kg':
                $weight = $weight * 1000;
                break;
            case 'lbs':
                $weight = $weight * 453.59237;
                break;
            case 'oz':
                $weight = $weight * 28.34952;
                break;
        }
        return $weight; //return gram
    }

    function get_cart_dimension_package( $package = array(), $dimension_text = 'height' ) {
        $dimension = 0;
        if(isset($package['contents']) && !empty($package['contents'])) {
            foreach ($package['contents'] as $cart_item_key => $values) {
                if($dimension_text == 'width'){
                    $value = (float)$values['data']->get_width();
                }elseif($dimension_text == 'length'){
                    $value = (float)$values['data']->get_length();
                }else{
                    $value = (float)$values['data']->get_height();
                }
                $dimension += $value * $values['quantity'];
            }
            $dimension = $this->convert_dimension_to_cm($dimension);
        }
        return apply_filters( 'wc_devvn_cart_contents_dimension', $dimension, $dimension_text );
    }

    function convert_dimension_to_cm( $dimension ) {
        switch(get_option( 'woocommerce_dimension_unit' )){
            case 'm':
                $dimension = $dimension * 100;
                break;
            case 'mm':
                $dimension = $dimension * 0.1;
                break;
            case 'in':
                $dimension = $dimension * 2.54;
            case 'yd':
                $dimension = $dimension * 91.44;
                break;
        }
        return $dimension; //return cm
    }

    public static function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=devvn-woo-ghn' ) . '" title="' . esc_attr( __( 'Settings', 'devvn-ghn' ) ) . '">' . __( 'Settings', 'devvn-ghn' ) . '</a>',
        );

        return array_merge( $action_links, $links );
    }

    function order_action($label = '', $order = ''){
        if(!$label) return false;
        $orderid = ($order) ? $order->get_id() : '';
        $action = '<p class="form-field form-field-wide">
                        <a href="javascript:void(0)" class="button button-primary check_status_ghn" data-label="'.esc_attr($label).'" data-nonce="'.wp_create_nonce('check_status_ghn').'" data-orderid="'.$orderid.'">' . __('Check đơn hàng', 'devvn-ghn') . '</a>
                        <a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=print_order_ghn&order=' . esc_attr($label)), 'print_order', 'nonce') . '" target="_blank" class="button button-primary">' . __('In hóa đơn GHN', 'devvn-ghn') . '</a>
                        <a href="' . admin_url('admin-ajax.php?action=print_order&order_id=' . esc_attr($orderid)) . '" target="_blank" class="button button-primary">' . __('In hóa đơn theo mẫu riêng', 'devvn-ghn') . '</a>
                    </p>';
        return $action;

    }
    
    function order_get_total($order){
        $order_sub_total = $order->get_subtotal();
        $order_discount_total = $order->get_discount_total();
        if($order_discount_total){
            return $order_sub_total - $order_discount_total;
        }else{
            return $order_sub_total;
        }
    }

    function get_customer_address_shipping($order){
        if(!$order) return false;
        $customer_address = array();

        $billing_phone = wc_clean($order->get_billing_phone());
        $billing_ward = $order->get_billing_city();
        $billing_district = $this->get_district_id_from_string($order->get_billing_state());
        $billing_province = $this->get_state_id_from_string($order->get_billing_state());
        $billing_address = $order->get_billing_address_1();
        $billing_fullname = $order->get_formatted_billing_full_name();
        $shipping_phone = wc_clean(get_post_meta($order->get_id(), '_shipping_phone', true));
        if(!$shipping_phone) $shipping_phone = $billing_phone;

        if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $order->get_formatted_shipping_address() ) :
            $customer_address['name'] = $order->get_formatted_shipping_full_name();
            $customer_address['address'] = $order->get_shipping_address_1();
            $customer_address['province'] = $this->get_state_id_from_string($order->get_shipping_state());
            $customer_address['disrict'] = $this->get_district_id_from_string($order->get_shipping_state());
            $customer_address['ward'] = $order->get_shipping_city();
            $customer_address['phone'] = $shipping_phone;
        else:
            $customer_address['name'] = $billing_fullname;
            $customer_address['address'] = $billing_address;
            $customer_address['province'] = $billing_province;
            $customer_address['disrict'] = $billing_district;
            $customer_address['ward'] = $billing_ward;
            $customer_address['phone'] = $billing_phone;
        endif;
        return $customer_address;
    }

    function get_order_weight($order = '', $field = 'weight'){
        if(!$order) return false;
        $all_weight = 0;
        $all_width = 0;
        $all_height = 0;
        $all_length = 0;
        $product_list = $this->get_product_args($order);
        if($product_list && !is_wp_error($product_list) && !empty($product_list)):
            foreach($product_list as $product):
                $all_weight += (float) ($product['quantity'] * $product['weight']);
                $all_width += (float) ($product['quantity'] * $product['width']);
                $all_height += (float) ($product['quantity'] * $product['height']);
                $all_length += (float) ($product['quantity'] * $product['length']);
            endforeach;
        endif;
        if($field == 'length') return $all_length;
        if($field == 'width') return $all_width;
        if($field == 'height') return $all_height;
        return $all_weight;
    }

    function get_product_args($orderThis){
        $products = array();
        $order_items = $orderThis->get_items();
        $variations = array();
        if($order_items && !empty($order_items)) {
            $key = 0;
            foreach ($order_items as $item) {
                $product = $item->get_product();
                $subtitle = array();
                if(is_array($item->get_meta_data())){
                    foreach ( $item->get_meta_data() as $meta ) {
                        if ( taxonomy_is_product_attribute( $meta->key ) ) {
                            $term = get_term_by( 'slug', $meta->value, $meta->key );
                            $variations[ $meta->key ] = $term ? $term->name : $meta->value;
                        } elseif ( meta_is_product_attribute( $meta->key, $meta->value, $item['product_id'] ) ) {
                            $variations[ $meta->key ] = $meta->value;
                        }
                    }
                    if($variations && is_array($variations)){
                        foreach ($variations as $k=>$v) {
                            $subtitle[] = wc_attribute_label($k, $product).'-'.$v;
                        }
                    }
                }

                if($subtitle) {
                    $name_prod = sanitize_text_field($item['name']) . ' | ' . implode(" | ", $subtitle);
                }else{
                    $name_prod = sanitize_text_field($item['name']);
                }

                $products[$key]['name'] = $name_prod;
                $products[$key]['weight'] = (float) $product->get_weight();
                $products[$key]['width'] = (float) $product->get_width();
                $products[$key]['height'] = (float) $product->get_height();
                $products[$key]['length'] = (float) $product->get_length();
                $products[$key]['price'] = (float) $orderThis->get_item_subtotal($item, false, true);
                $products[$key]['quantity'] = (float) $item->get_quantity();

                $key++;
            }
        }
        return $products;
    }

    function devvn_woocommerce_form_field_select($field, $key, $args, $value){
        if(in_array($key, array('billing_city','shipping_city'))) {
            if(in_array($key, array('billing_city','shipping_city'))) {
                if(!is_checkout() && is_user_logged_in()){
                    if('billing_city' === $key) {
                        $state = wc_get_post_data_by_key('billing_state', get_user_meta(get_current_user_id(), 'billing_state', true));
                    }else{
                        $state = wc_get_post_data_by_key('shipping_state', get_user_meta(get_current_user_id(), 'shipping_state', true));
                    }
                }else {
                    $state = WC()->checkout->get_value('billing_city' === $key ? 'billing_state' : 'shipping_state');
                }
                $city = array('' => ($args['placeholder']) ? $args['placeholder'] : __('Choose an option', 'woocommerce')) + $this->get_list_wards_select($state);
                $args['options'] = $city;
            }

            if ($args['required']) {
                $args['class'][] = 'validate-required';
                $required = ' <abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>';
            } else {
                $required = '';
            }

            if (is_string($args['label_class'])) {
                $args['label_class'] = array($args['label_class']);
            }

            // Custom attribute handling.
            $custom_attributes = array();
            $args['custom_attributes'] = array_filter((array)$args['custom_attributes'], 'strlen');

            if ($args['maxlength']) {
                $args['custom_attributes']['maxlength'] = absint($args['maxlength']);
            }

            if (!empty($args['autocomplete'])) {
                $args['custom_attributes']['autocomplete'] = $args['autocomplete'];
            }

            if (true === $args['autofocus']) {
                $args['custom_attributes']['autofocus'] = 'autofocus';
            }

            if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
                foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
                }
            }

            if (!empty($args['validate'])) {
                foreach ($args['validate'] as $validate) {
                    $args['class'][] = 'validate-' . $validate;
                }
            }

            $label_id = $args['id'];
            $sort = $args['priority'] ? $args['priority'] : '';
            $field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr($sort) . '">%3$s</p>';

            $options = $field = '';

            if (!empty($args['options'])) {
                foreach ($args['options'] as $option_key => $option_text) {
                    if ('' === $option_key) {
                        // If we have a blank option, select2 needs a placeholder.
                        if (empty($args['placeholder'])) {
                            $args['placeholder'] = $option_text ? $option_text : __('Choose an option', 'woocommerce');
                        }
                        $custom_attributes[] = 'data-allow_clear="true"';
                    }
                    $options .= '<option value="' . esc_attr($option_key) . '" ' . selected($value, $option_key, false) . '>' . esc_attr($option_text) . '</option>';
                }

                $field .= '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" class="select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . ' data-placeholder="' . esc_attr($args['placeholder']) . '">
                    ' . $options . '
                </select>';
            }

            if (!empty($field)) {
                $field_html = '';

                if ($args['label'] && 'checkbox' != $args['type']) {
                    $field_html .= '<label for="' . esc_attr($label_id) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
                }

                $field_html .= $field;

                if ($args['description']) {
                    $field_html .= '<span class="description">' . esc_html($args['description']) . '</span>';
                }

                $container_class = esc_attr(implode(' ', $args['class']));
                $container_id = esc_attr($args['id']) . '_field';
                $field = sprintf($field_container, $container_class, $container_id, $field_html);
            }
            return $field;
        }
        return $field;
    }

    function devvn_admin_body_class($classs){
        $classs .= ' ghn_wrap_' . $this->myAffID() . ' ';
        return $classs;
    }

    function devvn_woocommerce_get_order_address($value, $type){
        if($type == 'billing' || $type == 'shipping'){
            if(isset($value['state']) && $value['state']){
                $state = $value['state'];
                $value['state'] = $this->get_name_city($state);
            }
            if(isset($value['city']) && $value['city']){
                $city = $value['city'];
                $value['city'] = $this->get_name_ward($city);
            }
        }
        return $value;
    }

    function myAffID(){
        return ($this->get_options('ghn_aff_id')) ? intval($this->get_options('ghn_aff_id')) : 252905; //145362 252905
    }

    function devvn_woocommerce_rest_prepare_shop_order_object($response, $order, $request){
        if( empty( $response->data ) ) {
            return $response;
        }

        $fields = array(
            'billing',
            'shipping'
        );

        foreach($fields as $field){
            if(isset($response->data[$field]['state']) && $response->data[$field]['state']){
                $state = $response->data[$field]['state'];
                $response->data[$field]['state'] = $this->get_name_city($state);
            }

            if(isset($response->data[$field]['city']) && $response->data[$field]['city']){
                $city = $response->data[$field]['city'];
                $response->data[$field]['city'] = $this->get_name_ward($city);
            }

        }

        return $response;
    }

    function admin_notices(){
        $class = 'notice notice-error';
        $token = $this->get_options('token_key');
        $license_options = wp_parse_args(get_option($this->_license_field), $this->_defaultLicenseOptions);
        if(!$token) {
            printf(__('<div class="%1$s"><p>Hãy điền  <strong>Token Key</strong> của GHN để plugin hoạt động chính xác. Nếu không điền Token Key mặc định sẽ sử dụng TokenTest của GHN. <a href="%2$s">Thêm tại đây</a></p></div>', 'devvn-ghn'), esc_attr($class), esc_url(admin_url('admin.php?page=devvn-woo-ghn')));
        }
        if(!$license_options['license_key']) {
            printf('<div class="%1$s"><p>Hãy điền <strong>License Key</strong> để tự động cập nhật khi có phiên bản mới. <a href="%2$s">Thêm tại đây</a></p></div>', esc_attr($class), esc_url(admin_url('admin.php?page=devvn-woo-ghn&tab=license')));
        }
    }

    function devvn_modify_plugin_update_message( $plugin_data, $response ) {
        $license_options = wp_parse_args(get_option($this->_license_field), $this->_defaultLicenseOptions);
        $license_key = isset($license_options['license_key']) ? sanitize_text_field($license_options['license_key']) : '';
        if( $license_key && isset($plugin_data['package']) && $plugin_data['package']) return;
        $PluginURI = isset($plugin_data['PluginURI']) ? $plugin_data['PluginURI'] : '';
        echo '<br />' . sprintf( __('<strong>Mua bản quyền để được tự động update. <a href="%s" target="_blank">Xem thêm thông tin mua bản quyền</a></strong>', 'devvn-ghn'), $PluginURI);
    }

    public function admin_footer_text( $text ) {
        $current_screen = get_current_screen();
        if ( isset( $current_screen->base ) && $current_screen->base == 'woocommerce_page_devvn-woo-ghn' ) {
            $text = sprintf( __( 'Phát triển bởi %sLê Văn Toản%s.', 'devvn-ghn' ), '<a href="https://levantoan.com" target="_blank"><strong>', '</strong></a>' );
        }
        return $text;
    }

}

function ghn_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
    if ( ! empty( $args ) && is_array( $args ) ) {
        extract( $args ); // @codingStandardsIgnoreLine
    }

    $located = ghn_locate_template( $template_name, $template_path, $default_path );

    if ( ! file_exists( $located ) ) {
        /* translators: %s template */
        ghn_doing_it_wrong( __FUNCTION__, sprintf( __( '%s không tồn tại.', 'devvn-ghn' ), '<code>' . $located . '</code>' ), '2.1' );
        return;
    }

    // Allow 3rd party plugin filter template file from their plugin.
    $located = apply_filters( 'ghn_get_template', $located, $template_name, $args, $template_path, $default_path );

    do_action( 'ghn_before_template_part', $template_name, $template_path, $located, $args );

    include $located;

    do_action( 'ghn_after_template_part', $template_name, $template_path, $located, $args );
}

function ghn_locate_template( $template_name, $template_path = '', $default_path = '' ) {
    if ( ! $template_path ) {
        $template_path = apply_filters( 'ghn_template_path', 'devvn-ghn/' );
    }

    if ( ! $default_path ) {
        $default_path =  untrailingslashit( plugin_dir_path( __FILE__ )) . '/templates/';
    }

    // Look within passed path within the theme - this is priority.
    $template = locate_template(
        array(
            trailingslashit( $template_path ) . $template_name,
            $template_name,
        )
    );

    if ( ! $template ) {
        $template = $default_path . $template_name;
    }

    // Return what we found.
    return apply_filters( 'ghn_locate_template', $template, $template_name, $template_path );
}

function ghn_doing_it_wrong( $function, $message, $version ) {
    // @codingStandardsIgnoreStart
    $message .= ' Backtrace: ' . wp_debug_backtrace_summary();

    if ( is_ajax() ) {
        do_action( 'doing_it_wrong_run', $function, $message, $version );
        error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
    } else {
        _doing_it_wrong( $function, $message, $version );
    }
    // @codingStandardsIgnoreEnd
}
if(!function_exists('devvn_sort_asc_array')) {
    function devvn_sort_asc_array($input = array(), $keysort = 'dk')
    {
        $sort = array();
        if ($input && is_array($input)) {
            foreach ($input as $k => $v) {
                $sort[$keysort][$k] = $v[$keysort];
            }
            array_multisort($sort[$keysort], SORT_ASC, $input);
        }
        return $input;
    }
}
if(!function_exists('devvn_sort_desc_array')) {
    function devvn_sort_desc_array($input = array(), $keysort = 'dk')
    {
        $sort = array();
        if ($input && is_array($input)) {
            foreach ($input as $k => $v) {
                $sort[$keysort][$k] = $v[$keysort];
            }
            array_multisort($sort[$keysort], SORT_DESC, $input);
        }
        return $input;
    }
}
function ghn_class(){
    return DevVN_Woo_GHN_Class::init();
}

ghn_class();
include ('includes/class-ghn-shipping.php');
}//End if active woo
