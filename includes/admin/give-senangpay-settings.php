<?php

/**
 * Class Give_Senangpay_Settings
 *
 * @since 3.0.2
 */
class Give_Senangpay_Settings
{

    /**
     * @access private
     * @var Give_Senangpay_Settings $instance
     */
    private static $instance;

    /**
     * @access private
     * @var string $section_id
     */
    private $section_id;

    /**
     * @access private
     *
     * @var string $section_label
     */
    private $section_label;

    /**
     * Give_Senangpay_Settings constructor.
     */
    private function __construct()
    {

    }

    /**
     * get class object.
     *
     * @return Give_Senangpay_Settings
     */
    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setup hooks.
     */
    public function setup_hooks()
    {

        $this->section_id = 'senangpay';
        $this->section_label = __('senangPay', 'give-senangpay');

        if (is_admin()) {
            // Add settings.
            add_filter('give_get_settings_gateways', array($this, 'add_settings'), 99);
            add_filter('give_get_sections_gateways', array($this, 'add_sections'), 99);
        }
    }

    /**
     * Add setting section.
     *
     * @param array $sections Array of section.
     *
     * @return array
     */
    public function add_sections($sections)
    {
        $sections[$this->section_id] = $this->section_label;

        return $sections;
    }

    /**
     * Add plugin settings.
     *
     * @param array $settings Array of setting fields.
     *
     * @return array
     */
    public function add_settings($settings)
    {
        $current_section = give_get_current_setting_section();

        if ($current_section != 'senangpay') {
            return $settings;
        }

        $give_senangpay_settings = array(
            array(
                'name' => __('senangPay Settings', 'give-senangpay'),
                'id' => 'give_title_gateway_senangpay',
                'type' => 'title',
            ),
            array(
                'name' => __('Secret Key', 'give-senangpay'),
                'desc' => __('Enter your senangPay Secret Key.', 'give-senangpay'),
                'id' => 'senangpay_secret_key',
                'type' => 'text',
                'row_classes' => 'give-senangpay-key',
            ),
            array(
                'name' => __('Merchant ID', 'give-senangpay'),
                'desc' => __('Enter your Merchant ID.', 'give-senangpay'),
                'id' => 'senangpay_merchant_id',
                'type' => 'text',
                'row_classes' => 'give-senangpay-merchant-id',
            ),
            array(
                'name' => __('Bill Description', 'give-senangpay'),
                'desc' => __('Enter description to be included in the bill.', 'give-senangpay'),
                'id' => 'senangpay_description',
                'type' => 'text',
                'row_classes' => 'give-senangpay-description',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'give_title_gateway_senangpay',
            ),
        );

        return array_merge($settings, $give_senangpay_settings);
    }
}

Give_Senangpay_Settings::get_instance()->setup_hooks();
