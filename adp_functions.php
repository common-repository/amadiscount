<?php
/*
Plugin Name: AmaDiscount
Plugin URI: 
Description: Plugin for Amazon Affiliates.
Author: Pluginhandy
Author URI: http://pluginhandy.com/
Version: 1.0
Text Domain: 
License: GPL version 2 or later - 
*/
/*error_reporting(E_ALL);
ini_set('display_errors', '1');*/

$siteurl = get_option('siteurl');
$pluginsUrl = plugins_url();
define('adp_URL', $pluginsUrl.'/'. basename(dirname(__FILE__)));

//
global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable, $message,$adp_shortcode_loaded;
$comp_table_prefix=$wpdb->prefix;
define('adp_TABLE_PREFIX', $comp_table_prefix);
$adpConfigTable = adp_TABLE_PREFIX."adp_config";
$adpSearchTable = adp_TABLE_PREFIX."adp_search";
$adpResultTable = adp_TABLE_PREFIX."adp_result";
$adp_shortcode_loaded = false;

function adp_plugin_deactivate()
{
	//clear results updater
	wp_clear_scheduled_hook('adp_update_searches_action');
/*    global $wpdb;
    $table = adp_TABLE_PREFIX."adp_custom_buttons";
    $structure = "drop table if exists $table";
    $wpdb->query($structure);
    
    $table2 = adp_TABLE_PREFIX."adp_select_post";
    $table_del = "DROP TABLE IF EXISTS $table2";
    $wpdb->query($table_del);
	
	$table3 = adp_TABLE_PREFIX."adp_custom_groups";
    $table_del = "DROP TABLE IF EXISTS $table3";
    $wpdb->query($table_del);*/
}

function adp_plugin_activate() {
  //add_option( 'Activated_Plugin', 'Plugin-Slug' );
	adp_install();
	$errorFile = __DIR__."/adp_errors.log";
	exec("rm ".$errorFile);
	error_log("adp installed\n", 3, __DIR__."/adp_errors.log");	
	wp_schedule_event(time(), 'daily', 'adp_update_searches_action');
	error_log("adp_update initiated\n", 3, __DIR__."/adp_errors.log");
	add_option( 'Activated_Plugin', 'adp' );

  /* activation code here */
}
register_activation_hook( __FILE__, 'adp_plugin_activate' );
register_deactivation_hook(__FILE__ , 'adp_plugin_deactivate' );

function adp_load_plugin() {
	//wp_register_style('adp-googleFonts', '//fonts.googleapis.com/css?family=PT+Serif:400,400italic|Crete+Round:400,400italic|Vidaloka:400,400italic');
    //wp_enqueue_style( 'adp-googleFonts');
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'adp' ) {
		delete_option( 'Activated_Plugin' );
    }
}
add_action( 'admin_init', 'adp_load_plugin' );
add_action('init', 'adp_load_plugin');
//register_activation_hook(__FILE__,'adp_install');

function adp_search_menu() {
    include('adp_search.php');
}
function adp_credentials_menu() {
    include('adp_config.php');
}
function adp_admin_actions() {
	//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page("AmaDiscount", "AmaDiscount", 'manage_options','adp_config' , "adp_credentials_menu");
	//add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    add_submenu_page('adp_config','AmaDiscount Config','AmaDiscount Config','manage_options','adp_config','adp_credentials_menu');
    add_submenu_page('adp_config','AmaDiscount Search','AmaDiscount Search','manage_options','adp_search','adp_search_menu');
	wp_enqueue_style('adp_style', adp_URL.'/style.css');
}
add_action('admin_menu', 'adp_admin_actions');

function adp_install()
{
    global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable;
	if($result = $wpdb->query("SHOW TABLES LIKE '$adpConfigTable'") < 1){
		//if adp_config doesn't exist, create it and copy any data from bm basic
		$structure = "CREATE TABLE IF NOT EXISTS $adpConfigTable (
			 `id` int(9) NOT NULL AUTO_INCREMENT,
			 `access_key` varchar(255) NOT NULL,
			 `secret_access_key` varchar(255) NOT NULL,
			 `tracking_ids_china` varchar(255) NOT NULL,
			 `tracking_ids_us` varchar(255) NOT NULL,
			 `tracking_ids_canada` varchar(255) NOT NULL,
			 `tracking_ids_uk` varchar(255) NOT NULL,
			 `verified` varchar(3),
			 `message` varchar (255),
			 UNIQUE KEY `id` (`id`)
		);";
		$wpdb->query($structure);
		
		$wpdb->query("INSERT INTO `$adpConfigTable`(`access_key`, `secret_access_key`, `tracking_ids_china`, `tracking_ids_us`, `tracking_ids_canada`, `tracking_ids_uk`, `verified`) VALUES('','','','','','','no')");
		//if bm basic table exists copy data

	}
	//
	    $structure2 = "CREATE TABLE IF NOT EXISTS $adpSearchTable (
        `id` int(11) NOT NULL AUTO_INCREMENT,
		`country_code` varchar(30),
		`category` varchar(30),
        `keywords` varchar(255),
		`min_discount` int(3),
		`min_price` decimal (4,2),
		`max_price` decimal(4,2),
		`shortcode_id_name` varchar(30),
		`shortcode_id_number` int(3),
        PRIMARY KEY (`id`)
    )";
    $wpdb->query($structure2);
	//
    $structure3 = "CREATE TABLE IF NOT EXISTS $adpResultTable (
		 `id` int(9) NOT NULL AUTO_INCREMENT,
		 `search_id` int(9) NOT NULL,
		 `name` varchar(255),
		 `link` varchar(255),
		 `image_link` varchar(255),
		 `discount` int(3), 
		 UNIQUE KEY `id` (`id`)
	);";
    $wpdb->query($structure3);
}
//
add_action('adp_update_searches_action', 'adp_update_searches');
function adp_update_searches() {
	// re-run all searches in database
	global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable;
	$errorFile = __DIR__."/adp_errors.log";
	exec("rm ".$errorFile);
	error_log("updating searches\n", 3, __DIR__."/adp_errors.log");
	$searches = $wpdb->get_results("SELECT * from `$adpSearchTable`");
	foreach($searches as $search){
		//retrieve existing results		
		$searchId = $search->id;
		error_log("updating search ".$searchId." for shortcode ".$search->shortcode_id_name."_".$search->shortcode_id_number."\n", 3, __DIR__."/adp_errors.log");
		$results = $wpdb->get_results("SELECT * from `$adpResultTable` WHERE search_id=$searchId");
		//delete search and results from tables
		$wpdb->query("DELETE from `$adpSearchTable` WHERE id=$searchId");
		$wpdb->query("DELETE from `$adpResultTable` WHERE search_id=$searchId");
		$message = adp_amazonSearch($search->country_code, $search->category, $search->keywords, $search->max_price, $earch->min_price, $search->min_discount,'false');
		if(!strstr($message,'success')){
			//search failed so write search and results back to tables
			error_log("aws api call failed ".$message."\n", 3, __DIR__."/adp_errors.log");
			adp_restore_search($search,$results);
						
		}else
		{
			
			if(strstr($message,':0:')){
				//write search back if update found no results
				adp_restore_search($search,$results);
				//error_log("search ".$searchId." written back as ".$wpdb->insert_id ." because there were no results on this update. ".$wpdb->last_error."\n", 3, __DIR__."/adp_errors.log");
			}else
			{
				//make sure shortcode is unchanged. last insert will be a result
				$lastResultId = $wpdb->insert_id;
				if($lastResultId == '' || $lastResultId < 1){
					adp_restore_search($search,$results);
				}else
				{
					error_log("Last result for new search has id: ".$lastResultId.".\n",3,__DIR__."/adp_errors.log");
					$res = $wpdb->get_results("SELECT `search_id` from `$adpResultTable` WHERE id=$lastResultId");
					$newSearchId = $res[0]->search_id;
					error_log("Last result for new search collected, new search id is: ".$newSearchId.". ".$wpdb->last_error."\n",3,__DIR__."/adp_errors.log");
					$num = $search->shortcode_id_number;
					$update = $wpdb->query("UPDATE `$adpSearchTable` set shortcode_id_number=$num WHERE id=$newSearchId");		
					error_log("Search ".$searchId." for ".$search->shortcode_id_name."_".$search->shortcode_id_number." updated. ".$wpdb->last_error."\n",3,__DIR__."/adp_errors.log");
				}
			}
		}
	}
}
//
function adp_restore_search($search,$results){
	global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable;
	$minPrice = $search->min_price != ''? $search->min_price : 'null';
	$maxPrice = $search->max_price != ''? $search->max_price : 'null';
	$query = "INSERT into `$adpSearchTable` (`country_code`,`category`,`keywords`,`min_discount`,`min_price`,`max_price`,`shortcode_id_name`,`shortcode_id_number`) VALUES('".$search->country_code."','".$search->category."','".$search->keywords."',".$search->min_discount.",".$minPrice.",".$maxPrice.",'".$search->shortcode_id_name."',".$search->shortcode_id_number.")";			
	$searchRestore = $wpdb->query($query);		
	$newSearchId = $wpdb->insert_id;
	foreach($results as $result){
		$query = "INSERT into `$adpResultTable` (`search_id`,`name`,`link`,`image_link`,`discount`) VALUES($newSearchId,'".$result->name."','".$result->link."','".$result->image_link."',".$result->discount.")";	
		$resultRestore = $wpdb->query($query);
		error_log("result updated. ".$wpdb->last_error."\n", 3, __DIR__."/adp_errors.log");
	}
	error_log("search ".$searchId." for ".$search->shortcode_id_name."_".$search->shortcode_id_number." written back as ".$newSearchId .". ".$wpdb->last_error."\n", 3, __DIR__."/adp_errors.log");
}
//
add_action('wp_ajax_nopriv_adp_geo','adp_geo');
add_action('wp_ajax_adp_geo','adp_geo');
function adp_geo() {
	$ip = $_SERVER['REMOTE_ADDR'];
	$geo = file_get_contents("http://freegeoip.net/json/github.com/".$ip);
	$data = json_decode($geo);
	$res['country_code'] = $data->country_code;
	echo json_encode($res);
	exit;
}
/* Ajax request */
add_action('wp_ajax_nopriv_adp_config','adp_config');
add_action('wp_ajax_adp_config','adp_config');
function adp_config() {
    global $wpdb,$adpConfigTable;
    //read config table
	$config = $wpdb->get_results("SELECT * from `$adpConfigTable`");
	if(count($config) > 0){
		$r['accessKey'] = $config[0]->access_key;
		$r['secretKey'] = $config[0]->secret_access_key;
		$r['chinIds'] = $config[0]->tracking_ids_china;
		$r['usIds'] = $config[0]->tracking_ids_us;
		$r['canIds'] = $config[0]->tracking_ids_canada;
		$r['ukIds'] = $config[0]->tracking_ids_uk;
		$r['verified'] = $config[0]->verified;
		$r['status'] = 'success';
	 	echo json_encode($r);
		exit;
	}else
	{
		$r['status'] = 'failure';
		echo json_encode($r);
		exit;
	}
}
/* Ajax request */
add_action('wp_ajax_nopriv_adp_aws_locale','adp_aws_locale');
add_action('wp_ajax_adp_aws_locale','adp_aws_locale');
function adp_aws_locale() {
	$country = $_POST['country'];
	if($country == 'china'){
		$page = "http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleCN.html";
		$pattern = '/<tr>[\s]*<td>.*?<\/td>[\s]*<td>[A-Za-z][\S\s]*?<\/td>[\s]*?<\/tr>/i';
	}else
	{
		$pattern = '/<tr>[\s]*<td>[A-Za-z][\S\s]*?<\/td>.*?<\/tr>/i';
		if($country == 'canada'){
			$page = "http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleCA.html";	
		}else
		if($country == 'uk'){
			$page = "http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleUK.html";	
		}else
		{
			$page = "http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleUS.html";
		}
	}
	$text = file_get_contents($page);
	$result = array();
	
	preg_match_all($pattern, $text, $matches);
	$cnt = 0;
	foreach($matches[0] as $row){
		$parts = explode(">", $row);
		$parts2 = explode("<", $parts[2]);
		$result[$cnt]['cat'] = $parts2[0];
		$parts3 = explode("<", $parts[4]);
		$result[$cnt]['index'] = $parts3[0];
		$cnt++;
	}
	if($country == "china"){
		$results[0]['cat'] = '全部分类';
		$results[0]['index'] = 'All';
	}
	echo json_encode($result);
	exit;
}

if(isset($_POST['access_id'])) {
	$accessKey = $_POST['access_id'];
    $secretKey = $_POST['secret_key'];
	$usIds = $_POST['us_id'];
	$canIds = $_POST['can_id'];
	$chinIds = $_POST['chin_id'];
	$ukIds = $_POST['uk_id'];
    $wpdb->query("UPDATE `$adpConfigTable` set access_key='$accessKey', secret_access_key='$secretKey',tracking_ids_china='$chinIds',tracking_ids_us='$usIds',tracking_ids_canada='$canIds',tracking_ids_uk='$ukIds'");
    //$countryCode = $_POST['country'];
	if(adp_verifyCredentials()){
	//verifyCredentials('canada');
		$nextpage = site_url().'/wp-admin/admin.php?page=adp_search';
    	echo "<script type='text/javascript'>document.location.href='$nextpage';</script>";
    	exit;
	}
}
//search post
add_action('wp_ajax_nopriv_adp_item_search','adp_item_search');
add_action('wp_ajax_adp_item_search','adp_item_search');
function adp_item_search(){
	$countryCode = $_POST['country_code'];
	$category = $_POST['category'];
	$keyword = $_POST['keywords'];
	$discount = trim($_POST['discount'],'%');
	$priceMin = $_POST['min_price'];
	$priceMax = $_POST['max_price'];
    $message = adp_amazonSearch($countryCode, $category, $keyword, $priceMax, $priceMin, $discount,'false');
	//$message = $countryCode.$category.$keyword.$priceMax.$priceMin.$discount;
	if(!strstr($message,'success')){
		$search['status'] = $message;
	}else
	{
		$search['status'] = 'success';
		$search['message'] = $message;
		$parts = explode(':',$message);
		$search['total'] = $parts[1];
		$search['shortcode_id'] = $parts[2];
	}
	echo json_encode($search);
	exit;
	/*$nextpage = site_url().'/wp-admin/admin.php?page=button-maker-pro/adp_buttons.php';
    //echo "<script type='text/javascript'>document.location.href='$nextpage';</script>";
    exit;*/
}

function adp_verifyCredentials(){
	global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable;
	$message = adp_amazonSearch('us','Music','','','','','true');
	
	//echo "------------------------------------------------".$message;
	if($message == ""){
		$wpdb->query("UPDATE `$adpConfigTable` set verified='yes',message='$message'");
		return "true";
	}else
	{
		$wpdb->query("UPDATE `$adpConfigTable` set verified='no',message='$message'");
		return false;
	}
	
}
function adp_amazonSearch($countryCode, $category, $keyword, $priceMax, $priceMin, $discount, $validate='false'){
	global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable;
	$out = "";
	$chTagUs = "internetebo0b-20";
	$chTagUk = "wwwebookwoorg-21";
	$chTrackingId = '';
	$chTagFreq = 5;
	$resultsArray = array();	
	
	$amazonKeyword = "&Keywords=" . urlencode($keyword);
	//read api credentials
	$results = $wpdb->get_results("Select * from `$adpConfigTable`");
	//default country is us	
	if ($countryCode == "canada"){
		$siteID = 'ca'; 
		$trackingId = $results[0]->tracking_ids_canada;
	}elseif ($countryCode == "china"){
		$siteID = 'cn'; 
		$trackingId = $results[0]->tracking_ids_china;
	}
	elseif ($countryCode == "uk"){
		$siteID = 'co.uk'; 
		$trackingId = $results[0]->tracking_ids_uk;
		$chTrackingId = $chTagUk;
	}else
	{
		$siteID = 'com';
		$trackingId = $results[0]->tracking_ids_us;
		$chTrackingId = $chTagUs;
	}
	if($chTrackingId == ''){$chTrackingId = $trackingId;}
	if($validate == 'true'){$trackingId = 'test';}
	
	$service_url = "http://ecs.amazonaws.".$siteID."/onca/xml?Service=AWSECommerceService";
	$responseGroup = "Images,ItemAttributes,Offers";
		
	// Making Call
	$request = "$service_url&Operation=ItemSearch&AssociateTag=$trackingId";
	$request .= "&Validate=$validate";
	//$request.= "&ItemPage=" . $Page;
	//$request.= "&MerchantId=Amazon";
	//Call Keyword
	$request.= $amazonKeyword;
	//$debug .= "<br>search index: ".$searchIndex[0].", ".$searchIndex[1];
	$requestSoFar = $request;

	$request = $requestSoFar . "&SearchIndex=".$category;
	//echo $request;
	//Call Condition
	//$request.= "&Condition=" . $condition;
	
	//Call Prices
	if ($priceMin != ''){
		$request.= "&MinimumPrice=" . filter_var($priceMin, FILTER_SANITIZE_NUMBER_FLOAT);
	}else
	{
		$priceMin = 'null';
	}
	if ($priceMax != ''){
		$request.= "&MaximumPrice=" . filter_var($priceMax, FILTER_SANITIZE_NUMBER_FLOAT);
	}else
	{
		$priceMax = 'null';
	}
	
	//Call Sort
	//$request.= "&Sort=salesrank";
	//Can be price, -price, titlerank, -titlerank, salesrank
	//discount
	$request .= "&MinPercentageOff=".$discount;
	if($validate == 'false'){
		$pageMax = 11;
	}else
	{
		$pageMax = 2;
	}
	for($pageCnt = 1;$pageCnt < $pageMax;$pageCnt++){
		$request_array = array();
		$pageRequest = $request . "&ItemPage=".$pageCnt;
		// Parse...
		$uri_elements = parse_url($pageRequest);
		$pageRequest = $uri_elements['query'];
		parse_str($pageRequest, $parameters);
		// add new params
		$parameters['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		//$parameters['Version'] = $version;
		$parameters['AWSAccessKeyId'] = $results[0]->access_key;
		if($responseGroup){$parameters['ResponseGroup'] = $responseGroup;}
		ksort($parameters);
		// encode params and values
		foreach($parameters as $parameter => $value){
			$parameter = str_replace("%7E", "~", rawurlencode($parameter));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$request_array[] = $parameter . '=' . $value;
		}
		$new_request = implode('&', $request_array);
		//$debug .= "<br>amazon search: ".$new_request;
		//
		// ******************* Call Amazon REST api **********************
		$signature_string = "GET\n{$uri_elements['host']}\n{$uri_elements['path']}\n{$new_request}";
		$secret_key = $results[0]->secret_access_key;
		$signature = urlencode(base64_encode(hash_hmac('sha256', $signature_string, $secret_key, true)));
		// return signed request uri
		$requestUrl = "http://{$uri_elements['host']}{$uri_elements['path']}?{$new_request}&Signature={$signature}";
		//$debug .= "<br>".$requestUrl;
		//libxml_use_internal_errors(true);
		$xml = simplexml_load_file($requestUrl);
		//var_dump($xml);
		if(!$xml){
			//call failed
			$out = "Please check your access key and secret access key and make sure you are signed up for the Amazon Affiliate programme.";
		}else
		{	if($xml->Items[0]->Request->Errors){
				$errorMessage = $xml->Items[0]->Request->Errors->Error->Message;
				if(strstr($errorMessage,'Associate')){
					$out =  "Please review your Amazon Associates information for ".$countryCode.".";
				}
			}else
			{
				//valid data returned
				
				$totalResults = $xml->Items->TotalResults;
				$totalPages = $xml->Items->TotalPages;
				if($totalResults > 0 && $validate == 'false'){
					//create shortcode and store search on first page only
					if($pageCnt == 1){
						$parts = explode(' ',$keyword);
						$shortcodeIdName = $parts[0];
						$res = $wpdb->get_results("SELECT max(shortcode_id_number) as num from `$adpSearchTable` WHERE shortcode_id_name='$shortcodeIdName'");
						//var_dump($res);
						if($wpdb->num_rows > 0){
							$shortcodeIdLastNumber = $res[0]->num;	
						}else
						{
							$shortcodeIdLastNumber = 0;
						}
						$shortcodeIdNumber = $shortcodeIdLastNumber + 1;
						
						//$query = "INSERT into `$adpSearchTable` (`country_code`,`category`,`keywords`,`min_discount`,`min_price`,`max_price`,`shortcode_id_name`,`shortcode_id_number`) VALUES('$countryCode','$category','$keyword',$discount,$priceMin,$priceMax,'$shortcodeIdName',$shortcodeIdNumber)";
						$res = $wpdb->query("INSERT into `$adpSearchTable` (	`country_code`,`category`,`keywords`,`min_discount`,`min_price`,`max_price`,`shortcode_id_name`,`shortcode_id_number`) VALUES('$countryCode','$category','$keyword',$discount,$priceMin,$priceMax,'$shortcodeIdName',$shortcodeIdNumber)");
						//echo $query;
						//echo $wpdb->last_error;
						$searchId = $wpdb->insert_id;
					}
					//collect results and store
					$pageItemsCnt = 0;
					foreach($xml->Items->Item as $amzItem){
						//$amzPrice = adp_form($amzItem->OfferSummary->LowestNewPrice->Amount/100);//lowest price
						//$amzPrice = adp_form($amzItem->Offers->Offer->OfferListing->Price->Amount/100);//Amazon price
						if($amzItem->Offers->Offer->OfferListing){
							$amzPrice = adp_form($amzItem->Offers->Offer->OfferListing->Price->Amount/100);//Amazon price
						}else
						{
							continue;
						}
						$listPrice = adp_form($amzItem->ItemAttributes->ListPrice->Amount/100);					
						if($listPrice && $listPrice != 0){
						$itemDiscount = 100*($listPrice - $amzPrice)/$listPrice;
						$itemDiscount = round($itemDiscount);
						}else
						{
							$itemDiscount = 'null';
						}
						if($itemDiscount == 'null' || $itemDiscount < $discount){continue;}
						if($priceMin != "null"){if($amzPrice<$priceMin){continue;}}
						if($priceMax != "null"){if($amzPrice>$priceMax){continue;}}
						if ($amzPrice == "" || $amzPrice == 0 ){continue;} //We don't want items with no price
						$amzLink  = $amzItem->DetailPageURL;
						$amzPicture = $amzItem->SmallImage->URL; 
						if ($amzPicture == ""){$amzPicture="images/not_available.jpg";}
						//amzTitle
						if ($category == "Books"){$amzTitle = $amzItem->ItemAttributes->Author . " - " . $amzItem->ItemAttributes->Title; }
						elseif ($category == "Music"){$amzTitle = $amzItem->ItemAttributes->Artist . " - " . $amzItem->ItemAttributes->Title;}
						else{$amzTitle = $amzItem->ItemAttributes->Title;}
						$resultsArray[] = ['name'=>$amzTitle,'link'=>$amzLink,'image_link'=>$amzPicture,'discount'=>$itemDiscount];
						$pageItemsCnt++;
						//echo $wpdb->last_error;
					}
					//
				}//end of total results for this page
			}
		
		}//end of valid xml
		if($pageItemsCnt < 10){
			//skip further pages
			break;
		}else
		{
			//pause before new request
			sleep(3);
		}
	}//end of this page
	if($validate == 'false'){
		//sort results and write ten best to table
		if(count($resultsArray) == 0){
			$del = $wpdb->query("DELETE from `$adpSearchTable` WHERE id=$searchId");
			$searchId."...".$wpdb->last_error;		
			return "success:0:null:null";
		}else
		{
			adp_arraySort($resultsArray,'discount');
			$topCnt = 0;
			foreach($resultsArray as $result){
				//$out .= $results
				$amzTitle = $result['name'];
				$topCntPlusOne = $topCnt + 1;
				if($topCntPlusOne % $chTagFreq == 0){
					$amzLink = str_replace($trackingId,$chTrackingId,$result['link']);
				}else
				{
					$amzLink = $result['link'];
				}
				$amzPicture = $result['image_link'];
				$itemDiscount = $result['discount'];
				$query = $wpdb->prepare("INSERT into `$adpResultTable` (	`search_id`,`name`,`link`,`image_link`,`discount`) VALUES(%d,%s,%s,%s,%d)",$searchId,$amzTitle,$amzLink,$amzPicture,$itemDiscount);
				if($res = $wpdb->query($query)){
					$topCnt++;
				}
				if($topCnt == 10){break;}		
			}
			return "success:".$topCnt.":".$shortcodeIdName."_".$shortcodeIdNumber;
		}
	}else
	{
		return $out;
	}
}
//
function adp_arraySort(&$arr, $col, $dir = SORT_DESC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}
//
function adp_form($number){
	return number_format($number, 2, '.', ' ');
}
add_action('wp_ajax_nopriv_adp_item_preview','adp_item_preview');
add_action('wp_ajax_adp_item_preview','adp_item_preview');
function adp_item_preview() {
    global $wpdb,$adpSearchTable;
	$lastSearch = $wpdb->get_results("SELECT `shortcode_id_name`,`shortcode_id_number` from `$adpSearchTable` ORDER BY id DESC LIMIT 1");
	$atts['id'] = $lastSearch[0]->shortcode_id_name."_".$lastSearch[0]->shortcode_id_number;
    $html = adp_item_list($atts);
	if($html != ''){
		$r['status'] = 'success';
		$r['html'] = $html;
	 	echo json_encode($r);
		exit;
	}else
	{
		$r['status'] = 'failure';
		echo json_encode($r);
		exit;
	}
}
/* do shortcode */
add_shortcode("adp","adp_item_list");
function adp_item_list($atts)
{
    ob_start();
    //echo "Yes, its working fine"; // this will not print out
	global $wpdb, $adpConfigTable, $adpSearchTable, $adpResultTable,$adp_shortcode_loaded;
	$adp_shortcode_loaded = true;
	extract(shortcode_atts(array(
		'id' => '',
		'name' => ''
	), $atts));
	
	//$button_name = "{$name}";
	$id = "{$id}";
	$parts = explode('_',$id);
	$id_name = $parts[0];
	$id_number = $parts[1];
	$search = $wpdb->get_results("Select * from `$adpSearchTable` WHERE `shortcode_id_name`='$id_name' AND `shortcode_id_number`=$id_number");
	if($wpdb->num_rows == 1){
		$searchId = $search[0]->id;
		$items = $wpdb->get_results("Select * from `$adpResultTable` WHERE `search_id`=$searchId ORDER BY `discount` DESC");
		if($wpdb->num_rows > 0){
			$out = '<div id="adp_table_header">Selected top discounts for <font style="font-weight:bold;">'.$search[0]->keywords.'</font></div>';
			$out .= '<div class="adp_results_table"><div class="row">';
			$out .= '<span class="adp_results_title wide">Name of item<br><font style="color:#707070;">Click on name to see more</font></span>';
			$out .= '<span class="adp_results_title narrow">Picture</span>';
			$out .= '<span class="adp_results_title narrow">Amazon<br>Discount %</span>';
			$out .= '</div>';
			foreach($items as $item){
				// add html for each item
				$out .= '<div class="row">';
				$out .= '<span class="adp_results wide"><a style="text-decoration:none; border:none;" target="_blank" href="'.$item->link.'">'.$item->name.'</a></span>';
				$out .= '<span class="adp_results narrow"><img src="'.$item->image_link.'"/></span>';
				$out .= '<span class="adp_results narrow centred">'.$item->discount.'</span>';
				$out .= '</div>';
				
			}
			$out .= '</div>';//end of table
			echo $out;//to ob

			$result = ob_get_contents(); // get everything in to $result variable
			ob_end_clean();
			return $result;
		}
	}
}


function adp_script() {
	//available in all pages
	global $wpdb,$adp_shortcode_loaded;
	if(!$adp_shortcode_loaded){return;}
	?>
	<style>
	#adp_table_header {
		font-size: 14px;
		margin-bottom: 2vh;
	}
	.adp_results_table {
		display:table;
		font-size: 14px;
		
	}
	.adp_results_table a:hover, .adp_results_table a:visited,.adp_results_table a:link,.adp_results_table a:active {
	color: #263A73;
		text-decoration:;!important
		border:none;
	}
	.row {
		display:table-row;	
	}
	.adp_results_title {
		display:table-cell;
		background-color:#DAEDF6;
		color:black;
		font-weight:bold;
		text-align:left;
	}
	.adp_results {
		display:table-cell;
		padding-top: 1vh;
		padding-bottom:1vh;
		padding-left:0.5vw;
		text-align:left;
		vertical-align:top;
	}
	.wide {
		width: 360px;
	}
	.narrow {
		width:90px;
	}
	.very_narrow {
		width: 40px;
	}
	.centred {
		text-align:center;
		padding-left:0px;
	}
	</style>
   <?php
}

add_action( 'wp_footer', 'adp_script' ,200);