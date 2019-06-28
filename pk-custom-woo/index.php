<?php
/**
 * Plugin Name: Packerland Custom WooCommerce
 * Plugin URI: http://www.packerlandwebsites.com/
 * Description: Creates Shortcodes For WooCommcer
 * Version: 1.0
 * Author: Mike Mcgraw
 * Author URI: http://www.packerlandwebsites.com
 */

$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require_once $autoloader;
}

use Automattic\WooCommerce\Client;



function PK_Woo_Client_Info(){
    
       $woocommerce = new Client(
        'http://woocommerce.test/',
        'ck_06e8dc9b88a55f61467d55418822027ae35c0514',
        'cs_b3141b55235caddb0665a6a52648a7d94edc43f0',
        [
            'wp_api'  => true,
            'version' => 'wc/v2',
        ]
    );
    
    return $woocommerce;

    
}


add_shortcode( 'showorders', 'PK_Orders_shortcode' );

function PK_Get_All_Orders(){
    
    $woocommerce = PK_Woo_Client_Info();
 
    $orders = $woocommerce->get('orders');

    return $orders;
}

function PK_Get_Orders_By_Product_ID($id){
    
   $woocommerce = PK_Woo_Client_Info();

    $data = [
        'status' => 'completed',
        'product' => $id,
    ];

    $orders = $woocommerce->get('orders', $data);

    return $orders;
}



function PK_Get_Orders_Shortcode( $atts , $content = null ) {

    if(is_admin()){
        return;
    }

    $html = '';

	// Attributes
	$atts = shortcode_atts(
		array(
			'product_id' => '0',
		),
		$atts
    );
    
    $orderData = PK_Get_Orders_By_Product_ID($atts['product_id']);
    
    for($x = 0; $x <= 1; $x++){
        
        $ad_content = $orderData[$x]->line_items[0]->meta_data[0]->value;
    
        $ad_username = $orderData[$x]->billing->first_name . " " . $orderData[$x]->billing->last_name;
    
        $html .= "Product ID: " . $atts['product_id'] . ", Content: " . $ad_content. ", Username: ". $ad_username .'<br>';
        
    }

   
//    return print_r($orderData);
    return $html;

}
add_shortcode( 'showorders', 'PK_Get_Orders_Shortcode' );


function cfwc_create_custom_field() {
     $args = array(
         'id' => 'ad_content',
         'label' => __( 'Ad Content Label', 'cfwc' ),
         'class' => 'cfwc-custom-field',
         'desc_tip' => true,
         'description' => __( 'Enter the title of your custom text field.', 'ctwc' ),
     );
    
     woocommerce_wp_text_input( $args );
    
     $args = array(
         'id' => 'want_val',
         'label' => __( 'Force Ad Content', 'cfwc' ),
         'class' => 'cfwc-custom-field',
         'desc_tip' => true,
         'description' => __( 'Do you want to force ad content?', 'ctwc' ),
     );
    
     woocommerce_wp_checkbox( $args );
}
add_action( 'woocommerce_product_options_general_product_data', 'cfwc_create_custom_field' );

function cfwc_save_custom_field( $post_id ) {
     $product = wc_get_product( $post_id );
     $title = isset( $_POST['ad_content'] ) ? $_POST['ad_content'] : '';
     $val = isset( $_POST['want_val'] ) ? $_POST['want_val'] : '';
     $product->update_meta_data( 'ad_content', sanitize_text_field( $title ) );
     $product->update_meta_data( 'want_val', $val );
     $product->save();
}
add_action( 'woocommerce_process_product_meta', 'cfwc_save_custom_field' );

function cfwc_display_custom_field() {
     global $post;
     // Check for the custom field value
     $product = wc_get_product( $post->ID );
     $want_val = $product->get_meta( 'want_val' );
     $title = $product->get_meta( 'ad_content' );
     if( $title) {
         // Only display our field if we've got a value for the field title
         printf(
         '<div class="cfwc-custom-field-wrapper"><label for="cfwc-title-field">%s</label><input type="text" id="cfwc-title-field" name="cfwc-title-field" value=""></div>',
         esc_html( $title )
         );
     }
    if($want_val == "yes"){
        // Only display our field if we've got a value for the field title
         printf(
         '<div class="cfwc-custom-field-wrapper" style="display:none;"><input type="checkbox" id="cfwc-checkbox-field" name="cfwc-checkbox-field" checked></div>',
            esc_html( $want_val )
         );
    }else{
        // Only display our field if we've got a value for the field title
         printf(
         '<div class="cfwc-custom-field-wrapper" style="display:none;"><input type="checkbox" id="cfwc-checkbox-field" name="cfwc-checkbox-field"></div>',
            esc_html( $want_val )
         );
    }
}
add_action( 'woocommerce_before_add_to_cart_button', 'cfwc_display_custom_field' );

function cfwc_validate_custom_field( $passed, $product_id, $quantity ) {
    
    $product = wc_get_product( $product_id );
    $title = $product->get_meta( 'ad_content' );
    $want_val = $product->get_meta( 'want_val' );
    
     if( empty( $_POST['cfwc-title-field'] ) and $want_val == '') {
         
     }else if(empty( $_POST['cfwc-title-field'] ) and $want_val == 'yes'){
         // Fails validation
         $passed = false;
         wc_add_notice( __( 'Please enter a value into the text field', 'cfwc' ), 'error' );
     }
 return $passed;
}

add_filter( 'woocommerce_add_to_cart_validation', 'cfwc_validate_custom_field', 10, 3 );

function cfwc_add_custom_field_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
 if( ! empty( $_POST['cfwc-title-field'] ) ) {
 // Add the item data
 $cart_item_data['title_field'] = $_POST['cfwc-title-field'];
 }
 return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'cfwc_add_custom_field_item_data', 10, 4 );

function cfwc_cart_item_name( $name, $cart_item, $cart_item_key ) {
 if( isset( $cart_item['title_field'] ) ) {
 $name .= sprintf(
 '<p>%s</p>',
 esc_html( $cart_item['title_field'] )
 );
 }
 return $name;
}
add_filter( 'woocommerce_cart_item_name', 'cfwc_cart_item_name', 10, 3 );

function cfwc_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {
     foreach( $item as $cart_item_key=>$values ) {
         if( isset( $values['title_field'] ) ) {
            $item->add_meta_data( __( 'Custom Field', 'cfwc' ), $values['title_field'], true );
         }
     }
}
add_action( 'woocommerce_checkout_create_order_line_item', 'cfwc_add_custom_data_to_order', 10, 4 );

  add_action( 'woocommerce_after_shop_loop_item', 'remove_add_to_cart_buttons', 1 );

    function remove_add_to_cart_buttons() {
      if( is_product_category() || is_shop()) { 
        remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
      }
    }
