<?php
/**
 * Stripe Payment Transaction Title Settings.
 *
 * @package     wolfiz_stripe_settings
 * @author      Wolfiz Team
 * @copyright   2018 The Wolfiz PVT LTD
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Stripe Payment Transaction Title Settings
 * Plugin URI:  https://wolfiz.com
 * Description: Change default stripe transaction title from site title to product name added in woocommerce during checkout 
 * Version:     1.1
 * Author:      Wolfiz Team
 * Author URI:  https://wolfiz.com/contact-us/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
**/

/** Security Checks **/
if( !function_exists( 'add_action' ) ) wp_die('Hey! I am just a plugin. Nothing i can do.');

if( !defined( 'ABSPATH' ) ) wp_die( 'Cheers Mate!' );

if( !class_exists( 'Wolfiz_Stripe_Settings' ) ){
	class Wolfiz_Stripe_Settings {

		/**
		 * Bootstraps the class and hooks required actions & filters.
		 *
		 */
		public static function init() {
			add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
			add_action( 'woocommerce_settings_tabs_wolfiz_custom_stripe_settings', __CLASS__ . '::settings_tab' );
			add_action( 'woocommerce_update_options_wolfiz_custom_stripe_settings', __CLASS__ . '::update_settings' );
			add_filter( 'wc_stripe_generate_payment_request',  __CLASS__ . '::wolfiz_filter_wc_stripe_payment_metadata', 10, 3 );
			register_activation_hook( __FILE__,  __CLASS__ . '::check_plugin_dependencies', 10, 3 );
		}
		
		
		/**
		 * Add a new settings tab to the WooCommerce settings tabs array.
		 *
		 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
		 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
		 */
		public static function add_settings_tab( $settings_tabs ) {
			$settings_tabs['wolfiz_custom_stripe_settings'] = __( 'Stripe Transaction Title', 'woocommerce-settings-tab-demo' );
			return $settings_tabs;
		}
		
		public static function check_plugin_dependencies(){
			$activated_plugins = get_option( 'active_plugins' );
			$plugin = 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php'; 
			if ( !class_exists( 'woocommerce' ) || !in_array( $plugin, $activated_plugins ) ) { 
				wp_die( __('Please activate woocommerce and stripe in order to use this plugin.') );
			}  
			return true; 
		}


		/**
		 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
		 *
		 * @uses woocommerce_admin_fields()
		 * @uses self::get_settings()
		 */
		public static function settings_tab() {
			woocommerce_admin_fields( self::get_settings() );
		}


		/**
		 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
		 *
		 * @uses woocommerce_update_options()
		 * @uses self::get_settings()
		 */
		public static function update_settings() {
			woocommerce_update_options( self::get_settings() );
		}


		/**
		 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
		 *
		 * @return array Array of settings for @see woocommerce_admin_fields() function.
		 */
		public static function get_settings() {

			$settings = array(
				'section_title' => array(
					'name'     => __( 'Stripe Transaction Title Settings', 'woocommerce-settings-tab-demo' ),  
					'type'     => 'title',
					'desc'     => '',
					'id'       => 'wc_settings_tab_wolfiz_custom_stripe_settings_section_title'
				),
				'title_of_product' => array(
					'name' => __( 'Include Title of product', 'woocommerce-settings-tab-demo' ),
					'type' => 'checkbox',
					'desc' => __( 'Show title of product in stripe transaction', 'woocommerce-settings-tab-demo' ),
					'id'   => 'wc_settings_tab_wolfiz_custom_stripe_setting_title_of_product'
				),
				'order_number' => array(
					'name' => __( 'Include Order number', 'woocommerce-settings-tab-demo' ),
					'type' => 'checkbox',
					'desc' => __( 'Show order number in stripe transaction', 'woocommerce-settings-tab-demo' ),
					'id'   => 'wc_settings_tab_wolfiz_custom_stripe_setting_order_number'
				),
				'quantity' => array(
					'name' => __( 'Include Quantity', 'woocommerce-settings-tab-demo' ),
					'type' => 'checkbox',
					'desc' => __( 'Show quantity of products in stripe transaction', 'woocommerce-settings-tab-demo' ),
					'id'   => 'wc_settings_tab_wolfiz_custom_stripe_setting_quantity'
				),
				'section_end' => array(
					'type' => 'sectionend',
					'id' => 'wc_settings_tab_demo_section_end'
				)
			);

			return apply_filters( 'wc_settings_wolfiz_custom_stripe_settings', $settings );
		}
		
		public function wolfiz_filter_wc_stripe_payment_metadata( $post_data, $order, $prepared_source ){
			
			$order_data = $order->get_data();

			$count = 1;
			
			foreach( $order->get_items() as $item_id => $line_item ){
				$item_data = $line_item->get_data();
				$product = $line_item->get_product();
				$product_name = $product->get_name();
				$item_quantity = $line_item->get_quantity();
				$item_total = $line_item->get_total();
				
				if( 'yes' === get_option( 'wc_settings_tab_wolfiz_custom_stripe_setting_title_of_product' ) ){
					$post_data['description'] = 'Product name: '.$product_name;
				}
				
				if( 'yes' === get_option( 'wc_settings_tab_wolfiz_custom_stripe_setting_order_number' ) ){
					$post_data['description'] .= ' | Order number: '.$order->get_id();
				}
				
				if( 'yes' === get_option( 'wc_settings_tab_wolfiz_custom_stripe_setting_quantity' ) ){
					$post_data['description'] .= ' | Item quantity: '.$item_data->get_quantity();
				}
				
				// $post_data['description'] = 'Product name: '.$product_name.' | Order number: '.$order->get_id();
				$post_data['description'] = 'Product name: '.$product_name.' | Order number: '.$order->get_id();
				$count += 1;
			}
			
			return $post_data;
			
		}

	}
	
}

Wolfiz_Stripe_Settings::init();

?>