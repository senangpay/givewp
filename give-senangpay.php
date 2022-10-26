<?php

if (!defined('ABSPATH')) {
    exit;
}

class Give_Senangpay_Gateway
{
    private static $instance;

    const QUERY_VAR = 'senangpay_givewp_return';
    const LISTENER_PASSPHRASE = 'senangpay_givewp_listener_passphrase';

    private function __construct()
    {
        add_action('init', array($this, 'return_listener'));
        add_action('give_gateway_senangpay', array($this, 'process_payment'));
        add_action('give_senangpay_cc_form', array($this, 'give_senangpay_cc_form'));
        add_filter('give_enabled_payment_gateways', array($this, 'give_filter_senangpay_gateway'), 10, 2);
        add_filter('give_payment_confirm_senangpay', array($this, 'give_senangpay_success_page_content'));
    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function give_filter_senangpay_gateway($gateway_list, $form_id)
    {
        if ((false === strpos($_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms'))
            && $form_id
            && !give_is_setting_enabled(give_get_meta($form_id, 'senangpay_customize_senangpay_donations', true, 'global'), array('enabled', 'global'))
        ) {
            unset($gateway_list['senangpay']);
        }
        return $gateway_list;
    }

    private function create_payment($purchase_data)
    {

        $form_id = intval($purchase_data['post_data']['give-form-id']);
        $price_id = isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '';

        // Collect payment data.
        $insert_payment_data = array(
            'price' => $purchase_data['price'],
            'give_form_title' => $purchase_data['post_data']['give-form-title'],
            'give_form_id' => $form_id,
            'give_price_id' => $price_id,
            'date' => $purchase_data['date'],
            'user_email' => $purchase_data['user_email'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency' => give_get_currency($form_id, $purchase_data),
            'user_info' => $purchase_data['user_info'],
            'status' => 'pending',
            'gateway' => 'senangpay',
        );

        /**
         * Filter the payment params.
         *
         * @since 3.0.2
         *
         * @param array $insert_payment_data
         */
        $insert_payment_data = apply_filters('give_create_payment', $insert_payment_data);

        // Record the pending payment.
        return give_insert_payment($insert_payment_data);
    }

    private function get_senangpay($purchase_data)
    {

        $form_id = intval($purchase_data['post_data']['give-form-id']);

        $custom_donation = give_get_meta($form_id, 'senangpay_customize_senangpay_donations', true, 'global');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        if ($status) {
            return array(
                'secret_key' => give_get_meta($form_id, 'senangpay_secret_key', true),
                'merchant_id' => give_get_meta($form_id, 'senangpay_merchant_id', true),
                'description' => give_get_meta($form_id, 'senangpay_description', true, true),
            );
        }
        return array(
            'secret_key' => give_get_option('senangpay_secret_key'),
            'merchant_id' => give_get_option('senangpay_merchant_id'),
            'description' => give_get_option('senangpay_description', true),
        );
    }

    public static function get_listener_url($payment_id)
    {
        // $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        // if (!$passphrase) {
        //     $passphrase = md5(site_url() . $payment_id;
        //     update_option(self::LISTENER_PASSPHRASE, $passphrase);
        // }

        // $arg = array(
        //     'order_id' => $payment_id
        // );
        // return add_query_arg($arg, site_url('/'));
    }

    public function process_payment($purchase_data)
    {
        // Validate nonce.
        give_validate_nonce($purchase_data['gateway_nonce'], 'give-gateway');

        $payment_id = $this->create_payment($purchase_data);

        // Check payment.
        if (empty($payment_id)) {
            // Record the error.
            give_record_gateway_error(__('Payment Error', 'give-senangpay'), sprintf( /* translators: %s: payment data */
                __('Payment creation failed before sending donor to senangPay. Payment data: %s', 'give-senangpay'), json_encode($purchase_data)), $payment_id);
            // Problems? Send back.
            give_send_back_to_checkout();
        }

        $senangpay_key = $this->get_senangpay($purchase_data);

        $name = $purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name'];

        $parameter = array(
            'order_id' => $payment_id,
            'email' => $purchase_data['user_email'],
            'name' => empty($name) ? 'Donor Name' : trim($name),
            'amount' => strval($purchase_data['price']),
            'detail' => substr(trim($senangpay_key['description']), 0, 120),
        );

        $parameter = apply_filters('give_senangpay_mandatory_param', $parameter);

        $is_staging = give_is_test_mode();
        $senangpay = new SenangpayGiveAPI(
            $senangpay_key['merchant_id'],
            $senangpay_key['secret_key'],
            $is_staging,
            $parameter
        );

        $payment_url = $senangpay->getPaymentUrl();

        give_update_meta($payment_id, 'senangpay_id', $parameter['order_id']);

        wp_redirect($payment_url);
        exit;
    }

    public function give_senangpay_cc_form($form_id)
    {
        // ob_start();

        $post_senangpay_customize_option = give_get_meta($form_id, 'senangpay_customize_senangpay_donations', true, 'global');

        // Enable Default fields (billing info)
        $post_senangpay_cc_fields = give_get_meta($form_id, 'senangpay_collect_billing', true);
        $global_senangpay_cc_fields = give_get_option('senangpay_collect_billing');

        // Output Address fields if global option is on and user hasn't elected to customize this form's offline donation options
        if (
            (give_is_setting_enabled($post_senangpay_customize_option, 'global') && give_is_setting_enabled($global_senangpay_cc_fields))
            || (give_is_setting_enabled($post_senangpay_customize_option, 'enabled') && give_is_setting_enabled($post_senangpay_cc_fields))
        ) {
            give_default_cc_address_fields($form_id);
            return true;
        }

        return false;
        // echo ob_get_clean();
    }

    private function publish_payment($payment_id, $data)
    {
        if ('publish' !== get_post_status($payment_id)) {
            give_update_payment_status($payment_id, 'publish');
            if ($data['type'] === 'redirect') {
                give_insert_payment_note($payment_id, "Payment ID: {$data['id']}.");
            } else {
                give_insert_payment_note($payment_id, "Payment ID: {$data['id']}. URL: {$data['url']}");
            }
        }
    }

    public function return_listener()
    {
        if (!isset($_GET[self::QUERY_VAR])) {
            return;
        }

        if (!isset($_GET['order_id'])) {
            status_header(403);
            exit;
        }

        $payment_id = preg_replace('/\D/', '', $_GET['order_id']);
        $form_id = give_get_payment_form_id($payment_id);

        $custom_donation = give_get_meta($form_id, 'senangpay_customize_senangpay_donations', true, 'global');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        if ($status) {
            $secret_key = trim(give_get_meta($form_id, 'senangpay_secret_key', true));
        } else {
            $secret_key = trim(give_get_option('senangpay_secret_key'));
        }

        try {
            $data = SenangpayGiveAPI::getResponse($secret_key);
        } catch (Exception $e) {
            status_header(403);
            exit('Failed Hash Validation');
        }

        if ($data['order_id'] !== give_get_meta($payment_id, 'senangpay_id', true)) {
            status_header(404);
            exit('No senangPay Payment ID found');
        }

        if ($data['paid'] && give_get_payment_status($payment_id)) {
            $this->publish_payment($payment_id, $data);
        }

        if ($data['type'] === 'return') {
            if ($data['paid']) {
                $return = add_query_arg(array(
                    'payment-confirmation' => 'senangpay',
                    'payment-id' => $payment_id,
                ), get_permalink(give_get_option('success_page')));
            } else {
                $return = give_get_failed_transaction_uri('?payment-id=' . $payment_id);
            }

            wp_redirect($return);
        } else {
            echo 'OK';
        }
        exit;
    }

    public function give_senangpay_success_page_content($content)
    {
        if ( ! isset( $_GET['payment-id'] ) && ! give_get_purchase_session() ) {
          return $content;
        }

        $payment_id = isset( $_GET['payment-id'] ) ? absint( $_GET['payment-id'] ) : false;

        if ( ! $payment_id ) {
            $session    = give_get_purchase_session();
            $payment_id = give_get_donation_id_by_key( $session['purchase_key'] );
        }

        $payment = get_post( $payment_id );
        if ( $payment && 'pending' === $payment->post_status ) {

            // Payment is still pending so show processing indicator to fix the race condition.
            ob_start();

            give_get_template_part( 'payment', 'processing' );

            $content = ob_get_clean();

        }

        return $content;
    }
}
Give_Senangpay_Gateway::get_instance();
