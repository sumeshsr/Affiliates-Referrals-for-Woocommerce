<?php
/*
 * aff-wc-referral-list.php
 */
 

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}	
	
if( ! class_exists('Admin_Affiliates_Referral_Edits') ) :

class Admin_Affiliates_Referral_Edits {
	
	public static function edit_affiliates_referrals_options() {
		global $wpdb;
		if ( isset( $_POST['submit'] )  ) {
			if ( wp_verify_nonce( $_POST[NONCE], SET_ADMIN_OPTIONS ) ) {
				$update_table =$wpdb->update( AFF_WC_TABLE , array( 'referral_amount' => $_POST['referral_amount'] ), array( 'id' => $_POST['referrer_id'] ), 	array( '%d' ), 	array( '%d' ) );
				if($update_table){
					add_action( 'admin_notices', self::data_insert__success()  );
				}else{
					add_action( 'admin_notices', self::data_insert__fails()  );
				}
			}
		}
		if ( isset( $_POST['delete'] )  ) {	
		if ( wp_verify_nonce( $_POST[NONCE], SET_ADMIN_OPTIONS ) ) {
			$delete_data = $wpdb->delete( AFF_WC_TABLE , array( 'ID' => $_POST['referrer_id'] ) );
			if($delete_data){
					add_action( 'admin_notices', self::data_delete__success()  );
			}else{
					add_action( 'admin_notices', self::data_delete__fails()  );
			}
		}			
		}
		
		$query = 'SELECT * FROM '.AFF_WC_TABLE.' ;';
		$referral_lists = $wpdb->get_results($query , ARRAY_A);
		
		
		
		$output = '';
		
		$output .= '<div> <h2>'.__( 'Modify / Delete Affiliates Referrals', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</h2> </div>';
		
		
		if ( !current_user_can( 'edit_theme_options' ) ) {
			wp_die( __( 'Access denied.', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) );
		}	
		
		if( !empty($referral_lists) ):
		$counter = 1;
		$output .= '<table class="widefat" style="text-align: center;" >';
		$output .= '<tr>';
		$output .= '<th style="text-align: center;" >S NO</th>';
		$output .= '<th style="text-align: center;" >Affiliate ID</th>';
		$output .= '<th style="text-align: center;" >Affiliate Name</th>';
		$output .= '<th style="text-align: center;" >Referral Amount	</th>';
		$output .= '<th style="text-align: center;" ></th>';
		$output .= '<th style="text-align: center;" ></th>';
		$output .= '</tr>';
		foreach( $referral_lists as $referral_list ){
			$output .= '<tr><form method="post" >';
			$output .= '<td>'.$counter.'</td>';
			$output .= '<td>'.$referral_list['affiliate_id'].'</td>';
			$output .= '<td>'.$referral_list['affiliate_name'].'</td>';
			$output .= '<td><input type="number" name="referral_amount" value="'.$referral_list['referral_amount'].'"   min="0" max="100" required /></td>';
			$output .= '<td><input type="hidden" name="referrer_id" value="'.$referral_list['id'].'"/></td>';
			$output .= wp_nonce_field( SET_ADMIN_OPTIONS, NONCE, true, false );
			$output .= '<td><input class="button-primary" type="submit" name="submit" value="' . __( 'Update', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . '"/></td>';
			$output .= '<td><input class="button-primary" type="submit" name="delete" value="' . __( 'Delete Referral', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ) . '"/></td>';
			$output .= '</form></tr>';
			$counter++;
		}
		$output .= '</table>';
		else:
		$output .= '<div class="notice" style="font-size: 18px; padding: 10px; background-color: rgba(0, 0, 0, 0.7); border-left: 5px solid red; color: rgb(255, 255, 255); ">No Data to Display</div>';
		endif;
		
		echo $output;

	}
	
	public static function data_insert__success() {
		echo '<div class="notice notice-success is-dismissible"><p>'. __( 'Data Updated Successfully !', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</p></div>';    
	}
	
	public static function data_insert__fails() {
		echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Failed to Update Data!!!!', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</p> </div>';
	}
	
	public static function data_delete__success() {
		echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Data Deleted Successfully !!!!', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</p> </div>';
	}
	
	public static function data_delete__fails() {
		echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Failed to delete Data!!!!', AFF_REF_WC_INTEGRATION_PLUGIN_DOMAIN ).'</p> </div>';
	}
	
	
	public static function init(){		
		add_action('admin_menu', array(__CLASS__, 'affiliate_woocommerce_referral_edit_menu'));	  
	}
	
	public static function affiliate_woocommerce_referral_edit_menu(){
		
		add_submenu_page('aff-admin-referrals-wc-integration','Affiliates WooCommerce Refferal Edits' , 'Referral Edits', 'edit_theme_options' , 'aff-wc-referral-edits' , array( __CLASS__, 'edit_affiliates_referrals_options' ) );
	}
	
}

Admin_Affiliates_Referral_Edits::init();


endif;

?>