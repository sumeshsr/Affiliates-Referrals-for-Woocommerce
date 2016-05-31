<?php

/*
 * affiliates-referrals-woocommerce-integration.php
 */
 
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}	


if( !class_exists('Affiliates_Referrals_Woocommerce_Integration') ) :

class Affiliates_Referrals_Woocommerce_Integration {	
	

	
	private static $shop_order_link_modify_pages = array(
		'affiliates-admin-referrals',
		'affiliates-admin-hits',
		'affiliates-admin-hits-affiliate'
	);

	private static $admin_messages = array();

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			foreach ( self::$admin_messages as $msg ) {
				echo $msg;
			}
		}
	}
	public static function data_insert__success() {
		echo '<div class="notice notice-success is-dismissible"><p>'. __( 'Data added Successfully !', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</p></div>';    
	}
	
	public static function data_insert__fails() {
		echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Failed to add Data!!!!', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</p> </div>';
	}
	
	/**
	 * Initial Plugin Checks for dependencies
	 */
	public static function init() {

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;
		$valid = true;
		$disable = false;
		$active_plugins = get_option( 'active_plugins', array() );
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}		
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
		}
		$affiliates_is_active = in_array( 'affiliates/affiliates.php', $active_plugins ) || in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) || in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
		$woocommerce_is_active = in_array( 'woocommerce/woocommerce.php', $active_plugins );
		$affiliates_woocommerce_is_active = in_array( 'affiliates-woocommerce/affiliates-woocommerce.php', $active_plugins );
		$queries = "CREATE TABLE IF NOT EXISTS " . AFF_WC_TABLE . "( id bigint(20) unsigned NOT NULL AUTO_INCREMENT, affiliate_id bigint(20) unsigned NOT NULL, affiliate_name varchar(255) NOT NULL , referral_amount bigint(20) unsigned NOT NULL , PRIMARY KEY ( id , affiliate_id )	) $charset_collate;";
		if ( !$affiliates_is_active ) {
			self::$admin_messages[] = "<div class='error'>" . __( 'The <strong>Affiliates Referrals Woocommerce Integration</strong> plugin requires the <a href="http://wordpress.org/plugins/affiliates/">Affiliates</a> plugin.', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . "</div>";
		}
		if ( !$woocommerce_is_active ) {
			self::$admin_messages[] = "<div class='error'>" . __( 'The <strong>Affiliates Referrals Woocommerce Integration</strong> plugin requires the <a href="http://wordpress.org/plugins/woocommerce/">WooCommerce</a> plugin to be activated.', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . "</div>";
		}
		if ( $affiliates_woocommerce_is_active ) {
			self::$admin_messages[] = "<div class='error'>" . __( 'You do not need to use the <srtrong>Affiliates Referrals Woocommerce Integration</strong> plugin because you are already using the advanced Affiliates WooCommerce Integration plugin. Please deactivate the <strong>Affiliates Referrals Woocommerce Integration</strong> plugin now.', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . "</div>";
		}
		if ( !$affiliates_is_active || !$woocommerce_is_active || $affiliates_woocommerce_is_active ) {
			if ( $disable ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				deactivate_plugins( array( __FILE__ ) );
			}
			$valid = false;
		}

		if ( $valid ) {
			
		   add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'woocommerce_checkout_order_processed' ) );				    
		   add_filter( 'post_type_link', array( __CLASS__, 'post_type_link' ), 10, 4 );
		   add_action( 'admin_menu', array( __CLASS__, 'affiliates_admin_menu' ) );	
			
			dbDelta($queries);
			
		}
	}

	


	
	public static function affiliates_admin_menu() {
		
		$page = add_menu_page ( 'Affiliates Referrals Woocommerce Integration', 'Referrals', AFFILIATES_ADMINISTER_OPTIONS, 'aff-admin-referrals-wc-integration', array( __CLASS__, 'aff_wc_admin_integration' ) , plugins_url( 'affiliates-referrals-for-woocommerce/assets/images/icon.png' )  , 36  );
		
		$pages[] = $page;
		add_action( 'admin_print_styles-' . $page, 'affiliates_admin_print_styles' );
		add_action( 'admin_print_scripts-' . $page, 'affiliates_admin_print_scripts' );
	}

	/**************Admin Section***********/
	
	public static function aff_wc_admin_integration() {
		global $wpdb;
		$output = '';
		
		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( __( 'Access denied.', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) );
		}
		
		
		if ( isset( $_POST['submit'] )  ) {
			$affiliate_id  = $_POST[AFFILIATE_LIST];
			$refferal_rate  = $_POST[REFERRAL_RATE];
			$query = "SELECT name FROM ".$wpdb->prefix."aff_affiliates WHERE affiliate_id =".$affiliate_id." ; ";
			$affiliate_name = $wpdb->get_results( $query , OBJECT );
			$affiliate_name = $affiliate_name[0]->name;
			if ( wp_verify_nonce( $_POST[NONCE], SET_ADMIN_OPTIONS )  && !empty( $affiliate_id ) && !empty( $refferal_rate ) ) {
				$insert_data = $wpdb->insert( AFF_WC_TABLE , 	array('affiliate_id' => $affiliate_id ,'affiliate_name' => $affiliate_name ,'referral_amount' => $refferal_rate ), 	array( '%d', '%s' , '%d' ) );		
				if($insert_data){
					add_action( 'admin_notices', self::data_insert__success()  );
				}else{
					add_action( 'admin_notices', self::data_insert__fails() );
				}
			}else{
				add_action( 'admin_notices', self::data_insert__fails() );
			}
			
		}
		
		$query = "SELECT * FROM ".$wpdb->prefix."aff_affiliates WHERE status = 'active' AND affiliate_id NOT IN ( SELECT affiliate_id  FROM ".AFF_WC_TABLE." ); ";
		$affiliates = $wpdb->get_results( $query , ARRAY_A );
		
		$output .= '<div> <h2>'.__( 'Affiliates Referrals Woocommerce Integration', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</h2> </div>';

		$output .= '<div class="manage" style="padding:2em;margin-right:1em;display:inline-block;">';
		$output .= '<form action="" name="options" method="post">';        
		$output .= '<div>';
		$output .= '<h3>' . __( 'Referral Rate', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . '</h3>';
		$output .= '<p>';
		$output .= '<label style="display:block;" for="'.AFFILIATE_LIST.'">' . __( 'Select An Affiliate', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN) . '</label>';
		$output .= '&nbsp;';
		$output .= '<select name="'.AFFILIATE_LIST.'" style="width:100%;" >';
		foreach( $affiliates as $affiliate ):
		$output .= '<option value="'.$affiliate['affiliate_id'].'">'.$affiliate['name'].'</option>';
		endforeach;
		$output .= '</select>';
		$output .= '</p>';		
		$output .= '<p>';
		$output .= '<label style="display:block;" for="'.REFERRAL_RATE.'">' . __( 'Referral rate in Percentage( % )', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN) . '</label>';
		$output .= '&nbsp;';
		$output .= '<input name="'.REFERRAL_RATE.'" type="number" min="0" max="100" style="width:100%;" value="' . esc_attr( $referral_rate ) . '"/>';
		$output .= '</p>';
		
		
		$output .= '<p>';
		$output .= wp_nonce_field( SET_ADMIN_OPTIONS, NONCE, true, false );
		$output .= '<input class="button-primary" type="submit" name="submit" value="' . __( 'Save', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . '"/>';
		$output .= '</p>';
		
		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';

		echo $output;

	}
	

	public static function post_type_link( $post_link, $post, $leavename, $sample ) {
		$link = $post_link;
		if ( isset( $post->post_type) && ( $post->post_type == SHOP_ORDER_POST_TYPE ) && is_admin() && isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], self::$shop_order_link_modify_pages ) &&	(( preg_match( "/" . SHOP_ORDER_POST_TYPE . "=([^&]*)/", $post_link, $matches ) === 1 ) && isset( $matches[1] ) && ( $matches[1] === $post->post_name )	||  ( strpos( $post_link, 'post_type=' . SHOP_ORDER_POST_TYPE ) !== false ) && ( preg_match( '/p=([0-9]+)/', $post_link, $matches ) === 1 ) && isset( $matches[1] ) && ( $matches[1] == $post->ID )	)) {
			$link = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
		}
		return $link;
	}

	
	public static function woocommerce_checkout_order_processed( $order_id ) {


		global $wpdb;
		$order_subtotal 					= null;
		//$currency       					= get_option( 'woocommerce_currency' );
		$referrals_lists 					= $wpdb->get_results( "SELECT * FROM ".AFF_WC_TABLE.";" , ARRAY_A );
		$current_user 						= wp_get_current_user();
		$current_user_id 					= $current_user->ID;
		$affilates_plugin_path 				=  ABSPATH . '/wp-content/plugins/affiliates';
		$affilates_pro_plugin_path 			=  ABSPATH . '/wp-content/plugins/affiliates-pro';
		$affilates_enterprise_plugin_path 	=  ABSPATH . '/wp-content/plugins/affiliates-enterprise';
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if( file_exists($affilates_plugin_path ) ){
			include_once( $affilates_plugin_path.'/affiliates.php' );
		}
		if( file_exists($affilates_pro_plugin_path ) ){
			include_once( $affilates_pro_plugin_path.'/affiliates-pro.php' );
		}
		if( file_exists($affilates_enterprise_plugin_path ) ){
			include_once( $affilates_enterprise_plugin_path.'/affiliates-enterprise.php' );
		}
		

		
		


		
		
		
		
		foreach( $referrals_lists as $referral_list ){
			
		$query = "SELECT DISTINCT user_id FROM ".$wpdb->prefix."aff_referrals WHERE affiliate_id = ".$referral_list['affiliate_id']." ;"; 
		$aff__Users = $wpdb->get_results( $query , ARRAY_A );

		foreach( $aff__Users as $aff__User ){
				
	    if( $aff__User['user_id'] == $current_user_id ){
					
				

			if ( function_exists( 'wc_get_order' ) ) {
				if ( $order = wc_get_order( $order_id ) ) {
					if ( method_exists( $order, 'get_subtotal' ) ) {
						$order_subtotal = $order->get_subtotal();
					}
					if ( method_exists( $order, 'get_total_discount' ) ) {
						$order_subtotal -= $order->get_total_discount(); // excluding tax
						if ( $order_subtotal < 0 ) {
							$order_subtotal = 0;
						}
					}
					if ( method_exists( $order, 'get_order_currency' ) ) {
						$currency = $order->get_order_currency();
					}
				}
			}



		if ( $order_subtotal === null ) {
			$order_total        = get_post_meta( $order_id, '_order_total', true );
			$order_tax          = get_post_meta( $order_id, '_order_tax', true );
			$order_shipping     = get_post_meta( $order_id, '_order_shipping', true );
			$order_shipping_tax = get_post_meta( $order_id, '_order_shipping_tax', true );
			$order_subtotal     = $order_total - $order_tax - $order_shipping - $order_shipping_tax;
		}

		$order_link = '<a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '">';
		$order_link .= sprintf( __( 'Order #%s', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ), $order_id );
		$order_link .= "</a>";

		$data = array(
			'order_id' => array(
				'title' => 'Order #',
				'domain' => AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN,
				'value' => esc_sql( $order_id )
			),
			'order_total' => array(
				'title' => 'Total',
				'domain' =>  AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN,
				'value' => esc_sql( $order_subtotal )
			),
			'order_currency' => array(
				'title' => 'Currency',
				'domain' =>  AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN,
				'value' => esc_sql( $currency )
			),
			'order_link' => array(
				'title' => 'Order',
				'domain' =>  AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN,
				'value' => esc_sql( $order_link )
			)
		);

		$amount = round( (  floatval( $referral_list['referral_amount']  ) / 100 ) * floatval( $order_subtotal )  );
		$description = sprintf( 'Order #%s', $order_id );

		if(function_exists(affiliates_add_referral)){
		   $process = affiliates_add_referral( $referral_list['affiliate_id'] , $order_id, $description, $data, $amount, $currency ,null,null, null ,$current_user_id );
			
		}
		
			
			
		  }
				
		 }
		}
	}
}


Affiliates_Referrals_Woocommerce_Integration::init();

endif;
