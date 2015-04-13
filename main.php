<?php
/**
 * Plugin Name: Dirty Filler
 * Plugin URI: http://paxinstruments.com
 * Description: Exports woo stuff
 * Version: 1.0.0
 * Author: Christopher Pax
 * Author URI: http://github.com/paxmanchis
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 with many thanks to 
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




add_action('admin_menu', 'dirty_admin');




$dirty_order_header = array( "Order ID",	"Customer Email",	"Delivery Name", "Company", 
 					"Delivery Street",	"Delivery Suburb"	,"Delivery City",	
 					"Delivery State",	"Delivery Post Code",	"Delivery Country"	, "Ship Dest Type"	,
					"Shipping Method"	, "Customers Telephone",	"Order Notes",	"ISO Country Code 2");	
# Product 0 Qty	Product 0 Price	Product 0 Name	Product 0 Model	Product 0 Attributes
$dirty_order_product = array("Product # Qty", "Product # Price", "Product # Name", "Product # Model", "Product # Attributes");
#if( !class_exists( 'wpg_order_export' ) ){

#class wdf_order_export {

function dirty_admin(){
	global $country_code;

	if(! empty($_POST)){
		#print_r($_POST);
		if(isset($_POST['action']) and $_POST['action'] == 'wpg_dirty_order_export'){
                  #print "<pre>"; 
                  #print_r(get_dirty_order_data());
                  #die();
			proccess_cvs(get_dirty_order_data());
			#print "proccess cvs:<pre>";
			#print_r(get_dirty_order_data());
			#print "</pre>";
                  $upload_dir =   wp_upload_dir();
                  $filename   =   $upload_dir['basedir']. '/dirty_order_export.csv';

                  header('Location: /wp-content/uploads/dirty_order_export.csv');
                  #define( 'OE_URL', plugins_url('', __FILE__) ); /* plugin url */
                  #define( 'OE_CSS', OE_URL. "/assets/css/" ); /* Define all necessary variables first */
                  #define( 'OE_JS',  OE_URL. "/assets/js/" );
                  #define( 'OE_IMG',  OE_URL. "/assets/img/" );

                  #wp_enqueue_script( 'order-export', OE_JS. 'export_order.js');

                  
                  #$response['url'] = $upload_dir['basedir'].'/dirty_order_export.csv';
                  #$response['msg'] = 'order_export';


                  #echo json_encode( $response );
                  exit;
		}
	}


	add_menu_page('Dirty Filler', 'Dirty Filler', 'read', 'dirty-filler', 'dirty_filler');
}


function dirty_filler(){
	print "<h2>Export paid orders</h2>";
?>
	<form method='post' id='mainform' action>
		<input type="hidden" name="action" value="wpg_dirty_order_export" />
		<input type="hidden" id="wpg_order_export_nonce" name="nonce" value="<?php echo wp_create_nonce('wpg_order_export') ?>" />
		<input type='submit'>
	</form>

<?php

	print "<h2>Import proccessed orders</h2>";
		?>
	<form>
		<input type='file' >
	</form><br><br><br><hr><pre>
	<?php

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

	$order_statuses	= array_keys( wc_get_order_statuses() );
	

	$args = array( 'post_type'=>'shop_order', 'posts_per_page'=>-1, 'post_status'=> apply_filters( 'wpg_order_statuses', $order_statuses ) );


	$orders = new WP_Query( $args );


	if(! $orders->have_posts() ){
		return new WP_Error( 'no_orders', "somthing went wrong");
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
		$country_code_t = ( isset( $country_code[$shipping_country][0] ) ) ? $country_code[$meta['_shipping_country'][0]] : $meta['_shipping_country'];

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
			'Residential',
			'DHL',
			$meta['_billing_phone'][0],
			'', #notes
			$meta['_shipping_country'][0]

		);
		
		#print_r($meta);
		$items = product_info($order_details);
		
            $dop = $dirty_order_product;
            foreach ($dop as $key => $value) {
                  $dop[$key] = str_replace("#", $dopc, $value);
            }
            $dopc ++;
            #array_push($dop);
            $all_items[0] = array_merge($all_items[0], $dop);
		array_push($all_items, array_merge($ship, $items));
		

	}
	return $all_items;
}





$country_code = array(
      'AF' => 'Afghanistan'  ,
      'AX' => 'Aland Islands'  ,
      'AL' => 'Albania'  ,
      'DZ' => 'Algeria'  ,
      'AS' => 'American Samoa'  ,
      'AD' => 'Andorra'  ,
      'AO' => 'Angola'  ,
      'AI' => 'Anguilla'  ,
      'AQ' => 'Antarctica'  ,
      'AG' => 'Antigua and Barbuda'  ,
      'AR' => 'Argentina'  ,
      'AM' => 'Armenia'  ,
      'AW' => 'Aruba'  ,
      'AU' => 'Australia'  ,
      'AT' => 'Austria'  ,
      'AZ' => 'Azerbaijan'  ,
      'BS' => 'Bahamas the'  ,
      'BH' => 'Bahrain'  ,
      'BD' => 'Bangladesh'  ,
      'BB' => 'Barbados'  ,
      'BY' => 'Belarus'  ,
      'BE' => 'Belgium'  ,
      'BZ' => 'Belize'  ,
      'BJ' => 'Benin'  ,
      'BM' => 'Bermuda'  ,
      'BT' => 'Bhutan'  ,
      'BO' => 'Bolivia'  ,
      'BA' => 'Bosnia and Herzegovina'  ,
      'BW' => 'Botswana'  ,
      'BV' => 'Bouvet Island  Bouvetoya)'  ,
      'BR' => 'Brazil'  ,
      'IO' => 'British Indian Ocean Territory  Chagos Archipelago)'  ,
      'VG' => 'British Virgin Islands'  ,
      'BN' => 'Brunei Darussalam'  ,
      'BG' => 'Bulgaria'  ,
      'BF' => 'Burkina Faso'  ,
      'BI' => 'Burundi'  ,
      'KH' => 'Cambodia'  ,
      'CM' => 'Cameroon'  ,
      'CA' => 'Canada'  ,
      'CV' => 'Cape Verde'  ,
      'KY' => 'Cayman Islands'  ,
      'CF' => 'Central African Republic'  ,
      'TD' => 'Chad'  ,
      'CL' => 'Chile'  ,
      'CN' => 'China'  ,
      'CX' => 'Christmas Island'  ,
      'CC' => 'Cocos  Keeling) Islands'  ,
      'CO' => 'Colombia'  ,
      'KM' => 'Comoros the'  ,
      'CD' => 'Congo'  ,
      'CG' => 'Congo the'  ,
      'CK' => 'Cook Islands'  ,
      'CR' => 'Costa Rica'  ,
      'CI' => 'Cote d\'Ivoire'  ,
      'HR' => 'Croatia'  ,
      'CU' => 'Cuba'  ,
      'CY' => 'Cyprus'  ,
      'CZ' => 'Czech Republic'  ,
      'DK' => 'Denmark'  ,
      'DJ' => 'Djibouti'  ,
      'DM' => 'Dominica'  ,
      'DO' => 'Dominican Republic'  ,
      'EC' => 'Ecuador'  ,
      'EG' => 'Egypt'  ,
      'SV' => 'El Salvador'  ,
      'GQ' => 'Equatorial Guinea'  ,
      'ER' => 'Eritrea'  ,
      'EE' => 'Estonia'  ,
      'ET' => 'Ethiopia'  ,
      'FO' => 'Faroe Islands'  ,
      'FK' => 'Falkland Islands  Malvinas)'  ,
      'FJ' => 'Fiji the Fiji Islands'  ,
      'FI' => 'Finland'  ,
      'FR' => 'France => French Republic'  ,
      'GF' => 'French Guiana'  ,
      'PF' => 'French Polynesia'  ,
      'TF' => 'French Southern Territories'  ,
      'GA' => 'Gabon'  ,
      'GM' => 'Gambia the'  ,
      'GE' => 'Georgia'  ,
      'DE' => 'Germany'  ,
      'GH' => 'Ghana'  ,
      'GI' => 'Gibraltar'  ,
      'GR' => 'Greece'  ,
      'GL' => 'Greenland'  ,
      'GD' => 'Grenada'  ,
      'GP' => 'Guadeloupe'  ,
      'GU' => 'Guam'  ,
      'GT' => 'Guatemala'  ,
      'GG' => 'Guernsey'  ,
      'GN' => 'Guinea'  ,
      'GW' => 'Guinea-Bissau'  ,
      'GY' => 'Guyana'  ,
      'HT' => 'Haiti'  ,
      'HM' => 'Heard Island and McDonald Islands'  ,
      'VA' => 'Holy See  Vatican City State)'  ,
      'HN' => 'Honduras'  ,
      'HK' => 'Hong Kong'  ,
      'HU' => 'Hungary'  ,
      'IS' => 'Iceland'  ,
      'IN' => 'India'  ,
      'ID' => 'Indonesia'  ,
      'IR' => 'Iran'  ,
      'IQ' => 'Iraq'  ,
      'IE' => 'Ireland'  ,
      'IM' => 'Isle of Man'  ,
      'IL' => 'Israel'  ,
      'IT' => 'Italy'  ,
      'JM' => 'Jamaica'  ,
      'JP' => 'Japan'  ,
      'JE' => 'Jersey'  ,
      'JO' => 'Jordan'  ,
      'KZ' => 'Kazakhstan'  ,
      'KE' => 'Kenya'  ,
      'KI' => 'Kiribati'  ,
      'KP' => 'Korea'  ,
      'KR' => 'Korea'  ,
      'KW' => 'Kuwait'  ,
      'KG' => 'Kyrgyz Republic'  ,
      'LA' => 'Lao'  ,
      'LV' => 'Latvia'  ,
      'LB' => 'Lebanon'  ,
      'LS' => 'Lesotho'  ,
      'LR' => 'Liberia'  ,
      'LY' => 'Libyan Arab Jamahiriya'  ,
      'LI' => 'Liechtenstein'  ,
      'LT' => 'Lithuania'  ,
      'LU' => 'Luxembourg'  ,
      'MO' => 'Macao'  ,
      'MK' => 'Macedonia'  ,
      'MG' => 'Madagascar'  ,
      'MW' => 'Malawi'  ,
      'MY' => 'Malaysia'  ,
      'MV' => 'Maldives'  ,
      'ML' => 'Mali'  ,
      'MT' => 'Malta'  ,
      'MH' => 'Marshall Islands'  ,
      'MQ' => 'Martinique'  ,
      'MR' => 'Mauritania'  ,
      'MU' => 'Mauritius'  ,
      'YT' => 'Mayotte'  ,
      'MX' => 'Mexico'  ,
      'FM' => 'Micronesia'  ,
      'MD' => 'Moldova'  ,
      'MC' => 'Monaco'  ,
      'MN' => 'Mongolia'  ,
      'ME' => 'Montenegro'  ,
      'MS' => 'Montserrat'  ,
      'MA' => 'Morocco'  ,
      'MZ' => 'Mozambique'  ,
      'MM' => 'Myanmar'  ,
      'NA' => 'Namibia'  ,
      'NR' => 'Nauru'  ,
      'NP' => 'Nepal'  ,
      'AN' => 'Netherlands Antilles'  ,
      'NL' => 'Netherlands the'  ,
      'NC' => 'New Caledonia'  ,
      'NZ' => 'New Zealand'  ,
      'NI' => 'Nicaragua'  ,
      'NE' => 'Niger'  ,
      'NG' => 'Nigeria'  ,
      'NU' => 'Niue'  ,
      'NF' => 'Norfolk Island'  ,
      'MP' => 'Northern Mariana Islands'  ,
      'NO' => 'Norway'  ,
      'OM' => 'Oman'  ,
      'PK' => 'Pakistan'  ,
      'PW' => 'Palau'  ,
      'PS' => 'Palestinian Territory'  ,
      'PA' => 'Panama'  ,
      'PG' => 'Papua New Guinea'  ,
      'PY' => 'Paraguay'  ,
      'PE' => 'Peru'  ,
      'PH' => 'Philippines'  ,
      'PN' => 'Pitcairn Islands'  ,
      'PL' => 'Poland'  ,
      'PT' => 'Portugal => Portuguese Republic'  ,
      'PR' => 'Puerto Rico'  ,
      'QA' => 'Qatar'  ,
      'RE' => 'Reunion'  ,
      'RO' => 'Romania'  ,
      'RU' => 'Russian Federation'  ,
      'RW' => 'Rwanda'  ,
      'BL' => 'Saint Barthelemy'  ,
      'SH' => 'Saint Helena'  ,
      'KN' => 'Saint Kitts and Nevis'  ,
      'LC' => 'Saint Lucia'  ,
      'MF' => 'Saint Martin'  ,
      'PM' => 'Saint Pierre and Miquelon'  ,
      'VC' => 'Saint Vincent and the Grenadines'  ,
      'WS' => 'Samoa'  ,
      'SM' => 'San Marino'  ,
      'ST' => 'Sao Tome and Principe'  ,
      'SA' => 'Saudi Arabia'  ,
      'SN' => 'Senegal'  ,
      'RS' => 'Serbia'  ,
      'SC' => 'Seychelles'  ,
      'SL' => 'Sierra Leone'  ,
      'SG' => 'Singapore'  ,
      'SK' => 'Slovakia  Slovak Republic)'  ,
      'SI' => 'Slovenia'  ,
      'SB' => 'Solomon Islands'  ,
      'SO' => 'Somalia => Somali Republic'  ,
      'ZA' => 'South Africa'  ,
      'GS' => 'South Georgia and the South Sandwich Islands'  ,
      'ES' => 'Spain'  ,
      'LK' => 'Sri Lanka'  ,
      'SD' => 'Sudan'  ,
      'SR' => 'Suriname'  ,
      'SJ' => 'Svalbard & Jan Mayen Islands'  ,
      'SZ' => 'Swaziland'  ,
      'SE' => 'Sweden'  ,
      'CH' => 'Switzerland => Swiss Confederation'  ,
      'SY' => 'Syrian Arab Republic'  ,
      'TW' => 'Taiwan'  ,
      'TJ' => 'Tajikistan'  ,
      'TZ' => 'Tanzania'  ,
      'TH' => 'Thailand'  ,
      'TL' => 'Timor-Leste'  ,
      'TG' => 'Togo'  ,
      'TK' => 'Tokelau'  ,
      'TO' => 'Tonga'  ,
      'TT' => 'Trinidad and Tobago'  ,
      'TN' => 'Tunisia'  ,
      'TR' => 'Turkey'  ,
      'TM' => 'Turkmenistan'  ,
      'TC' => 'Turks and Caicos Islands'  ,
      'TV' => 'Tuvalu'  ,
      'UG' => 'Uganda'  ,
      'UA' => 'Ukraine'  ,
      'AE' => 'United Arab Emirates'  ,
      'GB' => 'United Kingdom'  ,
      'US' => 'United States of America'  ,
      'UM' => 'United States Minor Outlying Islands'  ,
      'VI' => 'United States Virgin Islands'  ,
      'UY' => 'Uruguay => Eastern Republic of'  ,
      'UZ' => 'Uzbekistan'  ,
      'VU' => 'Vanuatu'  ,
      'VE' => 'Venezuela'  ,
      'VN' => 'Vietnam'  ,
      'WF' => 'Wallis and Futuna'  ,
      'EH' => 'Western Sahara'  ,
      'YE' => 'Yemen'  ,
      'ZM' => 'Zambia'  ,
      'ZW' => 'Zimbabwe' );

?>