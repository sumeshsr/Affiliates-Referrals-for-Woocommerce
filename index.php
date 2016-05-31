<?php 
/*
 * Plugin Name: Affiliates Referral for WC
 * Plugin URI: 
 * Description: This Plugin Helps you add referral rates to individual affiliates integrating with woocommerce product purchase.
 * Version: 1.0.0
 * Author: 	Sumesh S
 * Author URI: 
*/



if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

define('AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN', 'affiliates-referrals-woocommerce-integration' );
define('AFF_REF_WC_INTEGRATION_PLUGIN_PATH', dirname(__FILE__).'/' );
define('SHOP_ORDER_POST_TYPE','shop_order');
define('NONCE','aff_ref_wc_integration');
define('SET_ADMIN_OPTIONS','set_admin_options');
define('AFF_WC_TABLE', $wpdb->prefix.'aff_wc_referral'  );

define('REFERRAL_RATE','referral-rate');
define('AFFILIATE_LIST','affiliate_list');


require AFF_REF_WC_INTEGRATION_PLUGIN_PATH .'includes/index.php';










