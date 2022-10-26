# senangPay for GiveWP

Accept GiveWP donation by using this plugin.

## Requirements

* PHP 5.6, **7.0 (Recommended)**
* GiveWP **version 1.8 or later**

## Installation

* Download here: [**Plugin Files**](https://download.url)
* Login to **WordPress Dashboard**
* Navigate to **Plugins >> Add New >> Upload**
* Upload the files >> **Activate**

## GiveWP Configuration

1. Login to **WordPress Dashboard**
2. Navigate to **Donations >> Settings >> Payment Gateways**
3. Enable **senangPay**
4. Navigate to **senangPay Settings**
5. Set **Secret Key**, **Merchant ID** and **Billing Description**
6. Extra: To use senangPay sandbox account, enable GiveWP's payment gateway test mode
7. Extra: You can override senangPay's details in GiveWP's form, at senangPay's section

## senangPay Configuration

1. Login to **senangPay Dashboard**
2. Navigate to **Settings >> Profile >> Shopping Cart Integration Link**
2. Choose **SHA256** for the **Hash Type Preference**
3. Fill in the **Return URL** with [your-website]/?senangpay_givewp_return=yes, ex: https://yourdonationsite/?senangpay_givewp_return=yes
4. Fill in the **Callback URL** with [your-website]/?senangpay_givewp_return=yes, ex: https://yourdonationsite/?senangpay_givewp_return=yes
