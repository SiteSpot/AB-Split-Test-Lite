<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BT_BB_AB_EDD_SL_STORE_URL', 'https://absplittest.com' );
define( 'BT_BB_AB_EDD_ITEM_NAME', 'AB Split Test' );

if ( ! class_exists( 'ABST_Plugin_Updater' ) ) {
	// load our custom updater.
	include dirname( __FILE__ ) . '/ABST_Plugin_Updater.php';
}


class BT_BB_AB_Update_checker
{

	public function __construct()
	{
		add_action( 'init', [$this, 'bt_bb_ab_update_checker'], 0 );
	}
	public function check_license_status()
	{
		$license_key = trim( apply_filters('bb_bt_ab_licence_key',  ab_get_admin_setting('bt_bb_ab_licence') ) );

		$api_params = [
			'edd_action' => 'check_license',
			'license'    => $license_key,
			'item_id'    => BT_AB_TEST_ITEM_ID, // the name of our product in EDD
			'url'        => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		];

		$response = wp_remote_post( BT_BB_AB_EDD_SL_STORE_URL, [ 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ] );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return;
		}

		// decode the license data
		$license_data  = json_decode( wp_remote_retrieve_body( $response ) );
		$license_limit = (isset($license_data->license_limit))? $license_data->license_limit : 0;
		$price_id = isset($license_data->price_id) ? $license_data->price_id : 0;
		if(in_array($price_id,['1','2','6','13','14','21']))
				$user_level = "pro";
		else if(in_array($price_id,['11']))
				$user_level = "free";
		else
				$user_level = "agency";

		$status = [
			'active'    => $license_data->license,
			'sites'     => $license_limit,
			'multisite' => is_multisite(),
                        'user_level' => $user_level, // free pro or agency
                        'price_id' => $price_id
                ];
		
		update_admin_setting( 'bt_bb_ab_lic', $status );
	}

	public function bt_bb_ab_update_checker()
	{
		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$license_key = trim( apply_filters('bb_bt_ab_licence_key',  ab_get_admin_setting('bt_bb_ab_licence') ) );

		// setup the updater.
		$edd_updater = new ABST_Plugin_Updater(
			BT_BB_AB_EDD_SL_STORE_URL,
			BT_AB_TEST_PLUGIN_DIR,
			array(
				'version' => BT_AB_TEST_VERSION, // current version number.
				'license' => $license_key, // license key (used get_option above to retrieve from DB).
				'item_id' => BT_AB_TEST_ITEM_ID, // id of this product in EDD.
				'author'  => 'SiteSpot DEV', // author of this plugin.
				'url'     => home_url(),
			)
		);	
	}



}