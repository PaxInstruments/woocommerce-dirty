<?php
/**
 * Plugin Name: Dirty Fulfillment
 * Plugin URI: http://paxinstruments.com
 * Description: Exports orders and imports orders
 * Version: 0.2.1
 * Author: Paxintruments
 * Author URI: http://github.com/paxinstruments
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*
Derived from: 
=== WooCommerce Simply Order Export ===
Contributors: ankitgadertcampcom
Donate link: http://sharethingz.com
Tags: woocommerce, order, export, csv, duration, woocommerce-order, woocommerce-order-export
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 1.1.5
License: GPLv2 or later (of-course)
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
#$path = plugin_dir_path( __FILE__ );

#require($path.'../aftership-woocommerce-tracking/aftership-fields.php');
#print_r($aftership_fields );
#exit;

add_action('admin_menu', 'dirty_admin');

$dirty_import_results = array();


$dirty_order_header = array( "Order ID",	"Customer Email",	"Delivery Name", "Company", 
 					"Delivery Street",	"Delivery Suburb"	,"Delivery City",	
 					"Delivery State",	"Delivery Post Code",	"Delivery Country"	, "Ship Dest Type"	,
					"Shipping Method"	, "Tracking", "Status", "Customers Telephone",	"Order Notes",	"ISO Country Code 2");	
# Product 0 Qty	Product 0 Price	Product 0 Name	Product 0 Model	Product 0 Attributes
$dirty_order_product = array("Product # Qty", "Product # Price", "Product # Name", "Product # Model", "Product # Attributes");
#if( !class_exists( 'wpg_order_export' ) ){

#class wdf_order_export {

function dirty_admin(){
	global $country_code;
      global $courier_method_translation;
      global $aftership_fields;
      global $dirty_import_results;
      
	if(! empty($_POST)){
            
		#print_r($_POST);
		if(isset($_POST['action']) and $_POST['action'] == 'wpg_dirty_order_export'){

			proccess_cvs(get_dirty_order_data());

                  $upload_dir =   wp_upload_dir();
                  $filename   =   $upload_dir['basedir']. '/dirty_order_export.csv';

                  header('Location: /wp-content/uploads/dirty_order_export.csv');

                  exit;
		}
            elseif (isset($_POST['action']) and ! empty($_FILES) and $_POST['action'] == 'wpg_dirty_order_import') {
                  #print "<pre>show array:\n";
                  $upload_dir =   wp_upload_dir();
                  $filename   =   $upload_dir['basedir']. '/dirty_order_import.csv';
                  
                  #print_r($_FILES);
                  #print_r($_POST);

                  $move_result = move_uploaded_file($_FILES['dirty_processing']['tmp_name'], $filename);
                  
                  $cvs_data = csv_to_array($filename);
                  #print_r($cvs_data);exit;
                  foreach ($cvs_data as $row) {
                        $dirty_results = array();

                        #print $row['Ship Dest Type'];
                        $post_id = $row['Order ID'];
                        $courier_method =  $row['Shipping Method'];
                        $tracking_number =  $row['Tracking'];
                        $order_status = $row['Status'];

                        $order = new WC_Order($post_id);
                        #print "$post_id, $courier_method, $tracking_number, $order_status \n ";
                        #print_r($order);
                        #print $order->get_status();
                        #exit;

                        #print "status:".$order->get_status()."\n";
                        if($order->get_status() == 'completed'){
                              #order has already been proccessed 
                              $dirty_import_results[] = array(
                                    'post_id' => $post_id,
                                    'message'=>'order has already been proccessed');
                              continue;
                        }

                        $meta_values = get_post_meta( $post_id );

                        if(   strtolower($order_status) != 'completed'
                              or ! isset( $courier_method_translation[$courier_method] ) 
                              ){
                                    #print "\nskipping because order status '$order_status' or method '$courier_method' not found.\n";
                                    #print_r($line);
                              $dirty_import_results[] = array(
                                    'post_id' => $post_id,
                                    'message'=>"skipping because order status '$order_status' is not complete or method '$courier_method' not found.");
                               continue;
                              }
                        # we should still update the status to complete
                        # if the order is in the state of not processing yet some how has tracking attached to it
                        // if( 
                        //       isset($meta_values['_aftership_tracking_number'])
                        //       and ! empty($meta_values['_aftership_tracking_number'][0])

                        //   ) {
                        //       print "\nskipping because tracking ".$meta_values['_aftership_tracking_number'][0]." exists\n" ;

                        //       $dirty_import_results[] = array(
                        //             'post_id' => $post_id
                        //             'message'=>"skipping because tracking ".$meta_values['_aftership_tracking_number'][0]." exists" );
                        //       continue;
                        // }

                        $message = "Your order is complete and has been shipped via $courier_method.";

                        

                        if(! empty($tracking_number) 
                                   and ( ! isset($meta_values['_aftership_tracking_number'])
                                          or empty($meta_values['_aftership_tracking_number'][0])
                                    )
                              ) {
                              $aftership_method = $courier_method_translation[$courier_method];
                              update_post_meta($post_id, '_aftership_tracking_provider_name', $courier_method);
                              update_post_meta($post_id, '_aftership_tracking_provider', $aftership_method);
                              update_post_meta($post_id, '_aftership_tracking_number', $tracking_number);
                              $message .= "<br>your tracking number is <a href=\"https://track.aftership.com/$tracking_number\">$tracking_number</a>";
                              $dirty_results['Post_id'] = $post_id;

                              $dirty_import_results[] = array(
                                    'post_id' => $post_id,
                                    'message'=>"tracking has been updated $tracking_number" );

                        } else {
                              $dirty_import_results[] = array(
                                    'post_id' => $post_id,
                                    'message'=>"not updating tracking, becasue tracking number is empty or there is existing tracking" );
                        }

                        
                        $order->update_status('wc-completed', "$message");

                        $dirty_import_results[] = array(
                                    'post_id' => $post_id,
                                    'message'=>"Order has been set to completed." );
                        #$dirty_results['Post_id'] = $post_id;
                        #$dirty_results['Post_id'] = $post_id;

                        

                  }
                  #exit;
            }
	}


	add_menu_page('Dirty Fulfillment', 'Dirty Fulfillment', 'read', 'dirty-filler', 'dirty_filler');
}

function csv_to_array( $csvfile ){
      if(! file_exists($csvfile)){
            return array();
      }
      $f = fopen($csvfile, 'r');

      $csv_data = array();

      $first_row_is_header = true;
      $header_keys = array();

      while($line = fgetcsv($f)){
            #array_push($csv_data, $line);
            if($first_row_is_header){
                  $header_keys = $line;
                  $first_row_is_header = false;
                  continue;
            }
            $row = array();
            #print = '<>';
            #print_r($header_keys);
            #print_r($line);
            #exit;
            for ($i=0; $i < count($line); $i++) {
                  if(!isset($header_keys[$i]) or !isset($line[$i])) continue;
                  $row[$header_keys[$i]] = $line[$i];
            }
            array_push($csv_data, $row);

      }
      fclose($f);
      #print_r($csv_data);
      return $csv_data;
}

function dirty_filler(){
      global $dirty_import_results;
	print "<h2>Export paid orders</h2>";
?>
	<form method='post' id='mainform' action>
		<input type="hidden" name="action" value="wpg_dirty_order_export" />
		<input type="hidden" id="wpg_order_export_nonce" name="nonce" value="<?php echo wp_create_nonce('wpg_order_export') ?>" />
		<input type='submit' value='Export Orders'>
	</form>

<?php

	print "<h2>Import proccessed orders</h2>";
		?>
	<form id='dirty_file_upload' enctype="multipart/form-data" method="POST" action>
            <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
		<input type='file' name='dirty_processing' id='dirty_processing' />
            <input type="hidden" name="action" value="wpg_dirty_order_import" />
            <input type='submit' value='Import Orders'>
	</form><br><br><br>DEBUG<hr><pre>
	<?php
      if(!empty($dirty_import_results)){
            print_r($dirty_import_results);
            $dirty_import_results=array();
      }
      #$woocommerce = function_exists('WC') ? WC() : $GLOBALS['woocommerce'];
      #$shippingCountries = method_exists($woocommerce->countries, 'get_shipping_countries')
      #                              ? $woocommerce->countries->get_shipping_countries()
      #                              : $woocommerce->countries->countries;
      #print_r($shippingCountries);
      #$hkp_settings = get_option('paxmanchris_debug');
      #$results = json_decode($hkp_settings);
      #print_r($hkp_settings);
      
      #$args = array( 'post_type'=>'product' );

      #$orders = new WP_Query( $args );
      #print_r($orders);

      #$js = '(function(e,t,n){var r,i=e.getElementsByTagName(t)[0];if(e.getElementById(n))return;r=e.createElement(t);r.id=n;r.src="//apps.aftership.com/all.js";i.parentNode.insertBefore(r,i)})(document,"script","aftership-jssdk")';
      #if (function_exists('wc_enqueue_js')) {
      #    wc_enqueue_js($js);
      #} else {
      #    global $woocommerce;
      #    $woocommerce->add_inline_js($js);
      #}

      #$track_button = '<div id="as-root"></div><div class="as-track-button" data-slug="dhl" data-tracking-number="' . 
      #'23456yt54r' . '" data-support="true" data-width="400" data-size="normal" data-hide-tracking-number="true"></div>';


      #print $track_button;

      #$ret = wp_mail("djfsodfkjsdfkjlsd@mailinator.com", 'from test', "message <a href=\"https://track.aftership.com/1ZA2R3170497077020\">Track your package </a>");
      #if(! $ret){
      #      print "failed, idk why!!!";
      #} else {
      #      print "success! but did it really. wtf is going on!";
      #}


}

#$dirty_info = array('_shipping_first_name', );

function proccess_cvs($csv_values){
	#$dirty_order_header
	#get_dirty_order_data
	# Product 0 Qty	Product 0 Price	Product 0 Name	Product 0 Model	Product 0 Attributes
	$cvs_file = create_csv_file();
	#$csv_values = array(array('Title1', 'Title2'), array('value1', 'value2'));
	foreach ($csv_values as $line) {
		fputcsv( $cvs_file, $line, ',' );
	}
      fclose($cvs_file);
	
}

function create_csv_file() {

	$upload_dir = wp_upload_dir();
	return $csv_file = fopen( $upload_dir['basedir']. '/dirty_order_export.csv', 'w+');
}


/*
		static function create_csv_file() {

			$upload_dir = wp_upload_dir();
			return $csv_file = fopen( $upload_dir['basedir']. '/order_export.csv', 'w+');
		}

						if( empty($csv_file) ) {
					return new WP_Error( 'not_writable', __( 'Unable to create csv file, upload folder not writable', 'woocommerce-simply-order-export' ) );
				}
#}
*/


##Order ID	Customer Email	Delivery Name	Company	Delivery Street	Delivery Suburb	Delivery City	Delivery State	Delivery Post Code	Delivery Country	Ship Dest Type	Shipping Method	Customers Telephone	Order Notes	ISO Country Code 2	Product 0 Qty	Product 0 Price	Product 0 Name	Product 0 Model	Product 0 Attributes


function customer_meta( $order_id , $meta = '' ) {
	
	if( empty( $order_id ) || empty( $meta ) )
		return '';
	
	return get_post_meta( $order_id, $meta, true );
}


/*
]Product 0 Qty   Product 0 Price Product 0 Name  Product 0 Model Product 0 Attributes

SKU
for every product concat an array of 5 item
Quantity, Price, Name, Model and Attributes

where
quantity = number of items
Price = price of the item
Name = Title of the itme
Model = sku of the item
Attributes = jason code for the attributes of the item

example of 2 items:
array(1,23,'Cool Product', 'CLL23', "{color: red, lenght: '22cm'}",
		1,23,'Hot Product', 'HLL23', "{color: blue, lenght: '12cm'}")


*/
function product_info( $order_details ) {
			
	if( !is_a( $order_details, 'WC_Order' ) ){
		return '';
	}

	global $wpdb;

	$items_list = array();

	$items = $order_details->get_items();
      #print "<pre>";
      #$product = new WC_Product($item['item_meta']['variation_id'][0]);
      #$available_variations = $order_details->get_available_variations();
      #print_r($available_variations);

	if ( !empty( $items ) ) {

		foreach( $items as $key=>$item ) {

			$metadata = $order_details->has_meta( $key );

			#print "\nITEM:\n";
			#print_r($item);


			$item_post = get_post_meta($item['item_meta']['_product_id'][0]);
			#print "\nITEM post meta:\n";
			#print_r($item_post);
			$quantity = $item['item_meta']['_qty'][0];
			$name = $item['name'];
			$price = $item_post['_price'][0];
			$sku = $item_post['_sku'][0];
			#print "SKU: $sku";

                  #override sku with variation sku if exist
                  if(! empty($item['item_meta']['_variation_id'][0])){
                        $product = new WC_Product($item['item_meta']['_variation_id'][0]);
                        $sku = $product->get_sku();
                        #print "\n$vsku";

                  }

			$order_attrs = array();
	/*		foreach( $metadata as $meta ) {
				print "\nmatch $k\n";
				print_r($meta);
				if( ! preg_match ( "/^_.*\/", $k ) ){
					#$order_attrs[$k] = $meta[0];
					print "\nfound match $k\n";
				}
			}
			print "\nITEM order attrs:\n";
			print_r($order_attrs);
			*/
			#json_encode();
			#print "\nITEM variation:\n";
			$exclude_meta = apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
				) );

			foreach( $metadata as $k => $meta ) {

				if( in_array( $meta['meta_key'], $exclude_meta ) ){
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $meta['meta_value'] ) ) {
					continue;
				}
				
				// Get attribute data
				#$meta['meta_key'] $meta['meta_value']
				$order_attrs[$meta['meta_key']] = $meta['meta_value'];
			}

			#print_r($order_attrs);
                  $json_code = json_encode($order_attrs);
                  #$json_code = ( empty($json_code) ) ? '' : $json_code;
			$attributes = $json_code;
			$final_item = array($quantity,$price,$name,$sku,$attributes);
			#array_push($items_list, $final_item);
			#print_r($final_item);

			$items_list = array_merge($items_list, $final_item);
			
		} # end item loop
		
	} # end if
      
	return $items_list;
}


function get_dirty_order_data() {
	global $wpdb;
	global $country_code;
      global $dirty_order_header;
      global $dirty_order_product;

      $woocommerce = function_exists('WC') ? WC() : $GLOBALS['woocommerce'];

	#$order_statuses = array_keys( wc_get_order_statuses() );
	$order_statuses = array('wc-processing'); 

	$args = array( 'post_type'=>'shop_order', 'posts_per_page'=>-1, 'post_status'=> apply_filters( 'wpg_order_statuses', $order_statuses ) );


	$orders = new WP_Query( $args );
      print "<pre>";

	if(! $orders->have_posts() ){
            #print "no orders";
		return array($dirty_order_header);
	}
	$all_items = array();
	$ship = array();
	array_push($all_items, $dirty_order_header);
      $dopc=0;
	while( $orders->have_posts() ) {

		$orders->the_post();
		
		$order_details = new WC_Order( get_the_ID() );
		
		$order_id = $order_details->post->ID;

		$meta = get_post_meta( get_the_ID() );
		$shipping_country = $meta['_shipping_country'][0];

            $shippingCountries = method_exists($woocommerce->countries, 'get_shipping_countries')
                                          ? $woocommerce->countries->get_shipping_countries()
                                          : $woocommerce->countries->countries;
            
		$country_code_t = ( isset( $shippingCountries[$shipping_country] ) ) ? $shippingCountries[$shipping_country] : $shipping_country;

            #$order_details->get_shipping_methods(); gets more details
            $shipping_method = $order_details->get_shipping_method();
            #print "meta:"; print_r($meta);

            #exit;

		$ship = array( $order_id ,
			$meta['_billing_email'][0],
			$meta['_shipping_first_name'][0]." ".$meta['_shipping_last_name'][0],
                  '', #company
			$meta['_shipping_address_1'][0],
			$meta['_shipping_address_2'][0],
			$meta['_shipping_city'][0],
			$meta['_shipping_state'][0],
			$meta['_shipping_postcode'][0],
			$country_code_t,
			'Residential', #TODO: if company name field is set, set this to commerical
			$shipping_method, #'DHL',
                  '', #Tracking
                  'Processing', # status
			$meta['_billing_phone'][0],
			'', #notes
			$meta['_shipping_country'][0]

		);
		
		#print_r($meta);

		$items = product_info($order_details);

            if(count($items)/5 > $dopc) $dopc = count($items)/5;
		
            
            #array_push($dop);
            
		array_push($all_items, array_merge($ship, $items));
		

	}

      #if();
      for ($i=0; $i < $dopc; $i++) { 
            $dop = $dirty_order_product;
            foreach ($dop as $key => $value) {
                  $dop[$key] = str_replace("#", $i, $value);
            }
            $all_items[0] = array_merge($all_items[0], $dop);
      }
      
      print_r($all_items);
	return $all_items;
}




$courier_method_translation = array(
      'DHL Express (2~3 business days)' => 'dhl',
      'Hong Kong Post' => 'hong-kong-post'
);

function html_show_array($table){
      echo "<table border='1'>";
      foreach ($table as $rows => $row)
      {
            echo "<tr>";
            foreach ($row as $col => $cell)
            {
            echo "<td>" . $cell . "</td>";
       }     
      echo "</tr>";
      }
      echo "</table>";
}



#add_action('aftership_meta_saved', 'aftership_meta_saved_handle');

###
# add action to handle mailing user if tracking gets updated
# -1 priority to load before aftership does, so we can see the tracking update
###
add_action('woocommerce_process_shop_order_meta', 'aftership_meta_saved_handle', -1, 2); # post_id, post
#add_action( 'woocommerce_new_customer_note_notification', array( $this, 'trigger' ) );

function aftership_meta_saved_handle($data, $post_info){
      global $woocommerce;
#function aftership_meta_saved_handle($post_id){
      #set_option('paxmanchris_debug', $data);
      
      
      #print" <pre>- post:\n";
      #print_r($_POST);exit;
          #[aftership_tracking_provider] => dhl
          #[aftership_tracking_provider_name] => DHL Express
          #[aftership_tracking_required_fields] => 
          #[aftership_tracking_number] => 2323423
      $post_meta = get_post_meta($_POST['post_ID']);
      $current_tracking = $post_meta['_aftership_tracking_number'][0] ;
      $new_tracking = $_POST['aftership_tracking_number'];
      $new_method = $_POST['aftership_tracking_provider_name'];#array_pop($_POST['aftership_tracking_method']);
      #print "results: $current_tracking =? $new_tracking\npost meta:\n ";
      #print_r($post_meta);
      if(isset($_POST['aftership_tracking_number'])
            and $current_tracking != $new_tracking){
            $tracking_message=array();
            #$tracking_message['order_id'] = $_POST['post_ID'];
            $tracking_message = "
                  Your package has been shipped via $new_method.<br> 
                  Your tracking number is <a href=\"https://track.aftership.com/".
                  $_POST['aftership_tracking_number']
                  ."\">".$_POST['aftership_tracking_number']."</a>.
            ";
            #"message <a href=\"https://track.aftership.com/1ZA2R3170497077020\">Track your package </a>"
             print_r($tracking_message);
            #$email_note = new WC_Order($_POST['post_ID']);

            # this uses the woocommerse note system, this will activate a email.
            $of = new WC_Order_Factory();
            $email_note = $of->get_order( $_POST['post_ID'] );
            $email_note->add_order_note($tracking_message, 1);
      }
      #print $data_r;
      #exit;
      #$ret = wp_mail("djfsodfkjsdfkjlsd@mailinator.com", 'from aftership', "$data_r");
      #if(! $ret){
      #      print "failed add action, idk why!!!";
      #} else {
      #      print "success add action!";
      #}
}


#####
## for handling redirects for products
##### moved to theme functions

#add_filter('post_type_link', 'wpse33551_post_type_link', 1, 3);

#function wpse33551_post_type_link( $link, $post = 0 ){
#    if ( $post->post_type == 'product' ){
#        return home_url( 'product/' . $post->ID );
#    } else {
#        return $link;
#    }
#}

/*
add_action( 'init', 'wpse33551_rewrites_init' );

function wpse33551_rewrites_init(){
    add_rewrite_rule(
        //'^products/([0-9]+)?$',
            'products/([0-9]+)?$',
        'index.php?product=$matches[1]',
       // 'index.php?post_type=product&p=$matches[1]',
        'top' );

}
*/

######
## for handling auto sku values based on product id (same as post_id)
######
# moved to theme functions
#function sv_change_sku_value( $sku, $product ) {
#
#    // Change the generated SKU to use the product's post ID instead of the slug
#    $sku = $product->get_post_data()->ID;
#    return $sku;
#}
#add_filter( 'wc_sku_generator_sku', 'sv_change_sku_value', 10, 2 );


