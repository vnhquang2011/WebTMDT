<?php
if ( class_exists( 'WC_Shipping_Method' ) ) {
    class SVW_Shipping_Method_Ghtk extends WC_Shipping_Method {
        /**
         * Constructor for your shipping class
         *
         * @access public
         *
         * @return void
         */
        public function __construct() {
            $this->id                 = 'svw_shipping_ghtk';
            $this->method_title       = esc_html__( 'Giao Hàng Tiết Kiệm', 'svw' );
            $this->method_description = esc_html__( 'Kích hoạt tính năng ship hàng qua GHTK', 'svw' );
            $this->enabled            = $this->get_option( 'enabled' );
            $this->title              = $this->get_option( 'title' );
            $this->sender_city        = $this->get_option( 'sender_city' );
            $this->sender_district    = $this->get_option( 'sender_district' );
            $this->sender_ward        = $this->get_option( 'sender_ward' );
            $this->sender_token       = $this->get_option( 'sender_token' );

            $this->init();
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

        /**
         * Checking is gateway enabled or not
         *
         * @return boolean [description]
         */
        public function is_method_enabled() {
            return $this->enabled == 'yes';
        }

        public function get_sender_city() {
            return $this->sender_city;
        }

        public function get_sender_district() {
            return $this->sender_district;
        }

        public function get_sender_ward() {
            return $this->sender_ward;
        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => esc_html__( 'Kích hoạt ship qua GHN', 'svw' ),
                    'type'    => 'checkbox',
                    'label'   => esc_html__( 'Kích hoạt', 'svw' ),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => esc_html__( 'Tiêu đề', 'svw' ),
                    'type'        => 'text',
                    'description' => esc_html__( 'Tiêu đề hiển thị khi khách hàng thanh toán.', 'svw' ),
                    'default'     => esc_html__( 'GHTK', 'svw' ),
                    'desc_tip'    => true,
                ),
                'sender_name' => array(
                    'title'       => esc_html__( 'Tên người gửi hàng', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_address' => array(
                    'title'       => esc_html__( 'Địa chỉ', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_phone' => array(
                    'title'       => esc_html__( 'Số điện thoại người gửi hàng', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_city' => array(
                    'title'       => esc_html__( 'Tỉnh/ Thành Phố', 'svw' ),
                    'type'        => 'select',
                    'options'     => SVW_Ultility::get_cities_array(),
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_district' => array(
                    'title'       => esc_html__( 'Quận/Huyện', 'svw' ),
                    'type'        => 'select',
                    'description' => '',
                    'options'     => SVW_Ultility::get_districts_array_by_city_id( $this->get_sender_city() ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_ward' => array(
                    'title'       => esc_html__( 'Xã/ Phường', 'svw' ),
                    'type'        => 'select',
                    'description' => '',
                    'options'     => SVW_Ultility::get_wards_array_by_district_id( $this->get_sender_district() ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'sender_token' => array(
                    'title'       => esc_html__( 'Token Giao Hàng Tiết Kiệm', 'svw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * calculate_shipping function.
         *
         * @access public
         *
         * @param mixed $package
         *
         * @return void
         */
        public function calculate_shipping( $package = array() ) {

            $products       = $package['contents'];
            $FromDistrictID = $this->sender_district;
            $FromCityID     = $this->sender_city;
            $ToDistrictID   = $package['destination']['district'];
            $ToCityID       = $package['destination']['city'];
            $amount         = 0.0;

            if ( ! $this->is_method_enabled() ) {
                return;
            }

            if ( $products ) {
                $amount = $this->calculate_shipping_fee( $products, $FromCityID, $FromDistrictID, $ToDistrictID, $ToCityID );
            }
            if ( $amount ) {
                $rate = array(
                    'id'    => $this->id,
                    'label' => $this->title,
                    'cost'  => $amount
                );

                // Register the rate
                $this->add_rate( $rate );
            }

        }

        /**
         * Check if shipping for this product is enabled
         *
         * @param integet $product_id
         *
         * @return boolean
         */
        public static function is_product_disable_shipping( $product_id ) {
            $enabled = get_post_meta( $product_id, '_disable_shipping', true );

            if ( $enabled == 'yes' ) {
                return true;
            }

            return false;
        }

        /**
         * Check if seller has any shipping enable product in this order
         *
         * @since  2.4.11
         *
         * @param  array $products
         *
         * @return boolean
         */
        public function has_shipping_enabled_product( $products ) {

            foreach ( $products as $product ) {
                if ( !self::is_product_disable_shipping( $product['product_id'] ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Calculate shipping per seller
         *
         * @param  array $products
         * @param  array $destination
         *
         * @return float
         */
        public function calculate_shipping_fee( $products, $FromCityID, $FromDistrictID, $ToDistrictID, $ToCityID ) {
            $total_weight   = 0;
            $product_weight = 0;
            $amount         = 0;

            foreach ( $products as $product ) {
                $product_data = wc_get_product( $product['product_id'] )->get_data() ;
                $weight       = $product_data['weight'];
                if ( $product['quantity'] > 1 && $weight > 0 ) {
                    $product_weight = $weight * $product['quantity'];
                } else {
                    $product_weight = $weight;
                }
                $total_weight = $total_weight + $product_weight;
            }

            $pick_province = $FromCityID;
            $province      = $ToCityID;
            $pick_district = $FromDistrictID;
            $district      = $ToDistrictID;

            $service = array (
                "pick_province" => SVW_Ultility::convert_id_to_name_city( $pick_province ),
                "pick_district" => SVW_Ultility::convert_id_to_name_district( $pick_province, $pick_district ),
                "province"      => SVW_Ultility::convert_id_to_name_city( $province ),
                "district"      => SVW_Ultility::convert_id_to_name_district( $province, $district ),
                "weight"        => $total_weight*1000
            );

            $response_service = wp_remote_post( SVW_API_GHTK_URL."/services/shipment/fee?".http_build_query( $service ), array(
                'method' => 'POST',
                'headers' => array( 'Token' => $this->sender_token ),
                )
            );


            if ( is_wp_error( $response_service ) ) {
                $error_message = $response_service->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                $data = json_decode( $response_service['body'] );
                if ( isset( $data->fee->delivery ) ) {
                    $amount = $data->fee->fee;
                }
            }

            return $amount;
        }
    }
}