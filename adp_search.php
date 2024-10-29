<?php 
wp_enqueue_script('json2');
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-slider');
wp_enqueue_style('my_jQuery_ui_style',adp_URL.'/jquery-ui.css');
?>
  <script>
    jQuery(function($) {
		$( "#slider" ).slider({
		  orientation: "horizontal",
		  range: "min",
		  min: 0,
		  max: 100,
		  value: 60,
		  slide: function( event, ui ) {
			$( "#discount" ).html( ui.value + '%' );
		  }
		});
		$( "#discount" ).val( $( "#slider" ).slider( "value" ) + '%' );
  });
  </script>
<script type="text/javascript">
var country = 'us';
var category_index = '';
var ukCategoryDefault = ['All Departments','Amazon Instant Video','Apps & Games','Baby','Beauty','Books','CDs & Vinyl','Car & Motorbike','Classical','Clothing','Computers','DIY & Tools','DVD & Blu-ray','Digital Music','Electronics & Photo','Garden & Outdoors','Gift Cards','Grocery','Health & Personal Care','Jewellery','Kindle Store','Kitchen & Home','Large Appliances','Lighting','Luggage','Musical Instruments & DJ','PC & Video Games','Pet Supplies','Shoes & Bags','Software','Sports & Outdoors','Stationery & Office Supplies','Toys & Games','VHS','Watches'];
var ukIndexDefault = ['All','UnboxVideo','MobileApps','Baby','Beauty','Books','Music','Automotive','Classical','Apparel','PCHardware','Tools','DVD','MP3Downloads','Electronics','HomeGarden','GiftCards','Grocery','HealthPersonalCare','Jewelry','KindleStore','Kitchen','Appliances','Lighting','Luggage','MusicalInstruments','VideoGames','PetSupplies','Shoes','Software','SportingGoods','OfficeProducts','Toys','VHS','Watches'];
var usCategoryDefault = ['All Departments','Amazon Instant Video','Appliances','Apps & Games','Arts, Crafts & Sewing','Automotive','Baby','Beauty','Books','CDs & Vinyl','Cell Phones & Accessories','Clothing, Shoes & Jewelry','Clothing, Shoes & Jewelry - Baby','Clothing, Shoes & Jewelry - Boys','Clothing, Shoes & Jewelry - Girls','Clothing, Shoes & Jewelry - Men','Clothing, Shoes & Jewelry - Women','Collectibles & Fine Arts','Computers','Digital Music','Electronics','Gift Cards','Grocery & Gourmet Food','Health & Personal Care','Home & Kitchen','Industrial & Scientific','Kindle Store','Luggage & Travel Gear','Magazine Subscriptions','Movies & TV','Musical Instruments','Office Products','Patio, Lawn & Garden','Pet Supplies','Software','Sports & Outdoors','Tools & Home Improvement','Toys & Games','Video Games','Wine'];
var usIndexDefault = ['All','UnboxVideo','Appliances','MobileApps','ArtsAndCrafts','Automotive','Baby','Beauty','Books','Music','Wireless','Fashion','FashionBaby','FashionBoys','FashionGirls','FashionMen','FashionWomen','Collectibles','PCHardware','MP3Downloads','Electronics','GiftCards','Grocery','HealthPersonalCare','HomeGarden','Industrial','KindleStore','Luggage','Magazines','Movies','MusicalInstruments','OfficeProducts','LawnAndGarden','PetSupplies','Software','SportingGoods','Tools','Toys','VideoGames','Wine'];
var chinaCategoryDefault = ['全部分类','大家电','Kindle商店','个护健康','乐器','办公用品','厨具','图书','宠物用品','家居装修','家用','应用程序和游戏','摄影/摄像','服饰箱包','母婴用品','汽车用品','游戏/娱乐','玩具','珠宝首饰','电子','电脑/IT','礼品卡','美容化妆','软件','运动户外休闲','钟表','鞋靴','音乐','音像','食品'];
var chinaIndexDefault = ['All','Appliances','KindleStore','HealthPersonalCare','MusicalInstruments','OfficeProducts','Kitchen','Books','PetSupplies','HomeImprovement','Home','MobileApps','Photo','Apparel','Baby','Automotive','VideoGames','Toys','Jewelry','Electronics','PCHardware','GiftCards','Beauty','Software','SportingGoods','Watches','Shoes','Music','Video','Grocery'];
var canadaCategoryDefault = ['All Departments','Apps & Games','Automotive','Baby','Beauty','Books','Clothing & Accessories','Electronics','Gift Cards','Grocery & Gourmet Food','Health & Personal Care','Home & Kitchen','Jewelry','Kindle Store','Luggage & Bags','Movies & TV','Music','Musical Instruments, Stage & Studio','Office Products','Patio, Lawn & Garden','Pet Supplies','Shoes & Handbags','Software','Sports & Outdoors','Tools & Home Improvement','Toys & Games','Video Games','Watches'];
var canadaIndexDefault = ['All','MobileApps','Automotive','Baby','Beauty','Books','Apparel','Electronics','GiftCards','Grocery','HealthPersonalCare','Kitchen','Jewelry','KindleStore','Luggage','DVD','Music','MusicalInstruments','OfficeProducts','LawnAndGarden','PetSupplies','Shoes','Software','SportingGoods','Tools','Toys','VideoGames','Watches'];
jQuery(document).ready(function($){
	$('#last_search_button').hide();
	$('#search_result_table').hide();
	createDefaultCategorySelector();
	$.ajax({
		url:"<?php echo admin_url('admin-ajax.php'); ?>",
		type: "POST",
		dataType:"json",
		data:({
			action:"adp_config"
		}),
		success: function(r) {
			//console.log('ajax result: '+r);
			if(r['verified'] != 'yes'){
				//disable form				
				$('#search_form_cover').css('z-index',1000);
				$('#search_form_block').fadeTo(1,0.3);
				//$('#search_button

			}
        }
	});
	$('.country_selection').hide();
	$.ajax({
		url:"<?php echo admin_url('admin-ajax.php'); ?>",
		type: "POST",
		dataType:"json",
		data:({
			action:"adp_geo"
		}),
		success: function(r) {
			console.log('ajax result: '+r['country_code']);
			if(r['country_code'] == 'CA'){
				country = 'canada'
			}else
			if(r['country_code'] == 'CN'){
				country = 'china'
			}else
			if(r['country_code'] == 'GB'){
				country = 'uk'
			}else
			{
				country = 'us';
			}
			buildCatSelector(country);
			closeCountrySelector(country);
        },
		error: function(jqXHR,error, errorThrown,data) {  
			console.log(jqXHR.responseText + ' '+error+' '+errorThrown+' '+data);
			//console.log(this['url']);
			var text;
			if(jqXHR.responseText){
				text = jqXHR.responseText;
			}else
			{
				if(error){
					text = error;
				}else
				if(errorThrown){
					text = errorThrown;
				}
			}
			console.log("geo lookup failed."+text);
			buildCatSelector('us');
		},
		timeout: 6000
	});
	
			
});

function buildCatSelector(){
	jQuery.ajax({
		url:"<?php echo admin_url('admin-ajax.php'); ?>",
		type: "POST",
		dataType:"json",
		data:({
			country: country,
			action:"adp_aws_locale"
		}),
		success: function(r) {
			var html = arg = '';
			for(var n=1;n<r.length;n++){
				arg = "'"+r[n]['cat']+"','"+r[n]['index']+"'";
				html += '<div class="row category_selection"><span class="cell clickable" onclick="closeCategorySelector('+arg+');">'+r[n]['cat']+'</span></div>';
			}
			jQuery('#category_select_box').html(html);
			jQuery('.category_selection').hide();
			jQuery('#chosen_category').html(r[1]['cat']);
			category_index = r[1]['index'];
			jQuery('#category').val(category_index);
        },
		error: function(jqXHR,error, errorThrown,data) {  
			//console.log(jqXHR.responseText + ' '+error+' '+errorThrown+' '+data);
			//console.log(this['url']);
			var text;
			if(jqXHR.responseText){
				text = jqXHR.responseText;
			}else
			{
				if(error){
					text = error;
				}else
				if(errorThrown){
					text = errorThrown;
				}
			}
			createDefaultCategorySelector();
			console.log("aws locale search failed. "+text);
		},
		timeout: 3000
	});
}

function createDefaultCategorySelector(){
	var html = arg = '';
	var categoryDefault = indexDefault = [];
	if(country == 'china'){
		categoryDefault = chinaCategoryDefault;
		indexDefault = chinaIndexDefault;
	}else
	if(country == 'canada'){
		categoryDefault = canadaCategoryDefault;
		indexDefault = canadaIndexDefault;
	}else
	if(country == 'uk'){
		categoryDefault = ukCategoryDefault;
		indexDefault = ukIndexDefault;
	}else
	{
		categoryDefault = usCategoryDefault;
		indexDefault = usIndexDefault; 
	}
	for(var n=1;n<categoryDefault.length;n++){
		arg = "'"+categoryDefault[n]+"','"+indexDefault[n]+"'";
		html += '<div class="row category_selection"><span class="cell clickable" onclick="closeCategorySelector('+arg+');">'+categoryDefault[n]+'</span></div>';
	}
	jQuery('#category_select_box').html(html);
	jQuery('.category_selection').hide();
	jQuery('#chosen_category').html(categoryDefault[1]);
	category_index = indexDefault[1];
	jQuery('#category').val(category_index);
}
function openCountrySelector(){
	jQuery('#keyword_logo_box').hide();
	jQuery('.country_selection').show(function(){
		jQuery('.country_selection').css('z-index',2000);
		
	});
	
}
function closeCountrySelector(c){
	jQuery('.country_selection').hide();
	var cText = 'United States';
	if(c == 'canada'){cText = 'Canada';}
	if(c == 'uk'){cText = 'UK';}
	if(c == 'china'){cText = 'China';}
	jQuery('#chosen_country').html(cText);
	var flag = '';
	if(c == 'us'){
		flag = 'us.gif';
	}else
	if(c == 'canada'){
		flag = 'canada.png';
	}else
	if(c == 'uk'){
		flag = 'uk.gif';	
	}else
	{
		flag = 'china.jpg';
	}
	country = c;
	buildCatSelector();
	var flag_base = "<?php echo adp_URL.'/images/'?>";
	var src = flag_base + flag;
	jQuery('#chosen_flag').attr("src", src);
	jQuery('#keyword_logo_box').show();
	jQuery('#country_code').val(country);
	//document.getElementById('chosen_flag').src = flag_base + flag;
}

function openCategorySelector(){
	jQuery('#keyword_logo_box').hide();
	jQuery('.category_selection').show(function(){
		jQuery('.category_selection').css('z-index',2000);
		
	});
	
}
function closeCategorySelector(c,i){
	jQuery('.category_selection').hide();
	jQuery('#chosen_category').html(c);
	category_index = i;
	jQuery('#keyword_logo_box').show();
	console.log(category_index);
	jQuery('#category').val(i);
	//document.getElementById('chosen_flag').src = flag_base + flag;
}
function submitSearch(){
	var keywords = document.getElementById('keywords');
	if(keywords.value == ''){
		//alert('Please supply at least one keyword');
		jQuery('#keyword_warning').html(' Please add a keyword');
		return;
	}
	jQuery('#search_result').html('Searching Amazon...');
	jQuery('#last_search_button').hide();
	jQuery('#search_result_table').hide();
	//
	jQuery.ajax({
		url:"<?php echo admin_url('admin-ajax.php'); ?>",
		type: "POST",
		dataType:"json",
		data:({
			country_code: jQuery('#country_code').val(),
			category: jQuery('#category').val(),
			keywords: jQuery('#keywords').val(),
			discount: jQuery('#discount').val(),
			min_price: jQuery('#min_price').val(),
			max_price: jQuery('#max_price').val(),
			action:"adp_item_search"
		}),
		success: function(r) {
			console.log('item search successful');
			if(r['status'] != 'success'){
				console.log(r['status']);
				var message = 'Sorry, no results were found for this search';
			}else
			{
				console.log(r['message']);
				if(r['total'] > 0){
					var message = r['total'] + ' results returned. Shortcode for this search is [adp id='+r['shortcode_id']+'] .';
					jQuery('#last_search_button').show();
				}else
				{
					var message = 'Sorry, no results were found for this search';
				}
			}
			jQuery('#search_result').html(message);
        },
		error: function(jqXHR,error, errorThrown,data) {  
			//console.log(jqXHR.responseText + ' '+error+' '+errorThrown+' '+data);
			//console.log(this['url']);
			var text;
			if(jqXHR.responseText){
				text = jqXHR.responseText;
			}else
			{
				if(error){
					text = error;
				}else
				if(errorThrown){
					text = errorThrown;
				}
			}
			console.log("aws item search failed. "+text);
			jQuery('#search_result').html('Sorry, search failed.');
		},
		timeout: 40000
	});
}
//
function displayLastSearch(){
	if(jQuery('#search_result_table').is(":visible")){
		jQuery('#search_result_table').hide();
	}else
	{
		jQuery.ajax({
			url:"<?php echo admin_url('admin-ajax.php'); ?>",
			type: "POST",
			dataType:"json",
			data:({
				action:"adp_item_preview"
			}),
			success: function(r) {
				console.log('item preview successful');
				if(r['status'] != 'success'){
					console.log(r['status']);
					//var message = 'Sorry, no results were found for this search';
				}else
				{
					console.log(r['message']);
					jQuery('#search_result_table').html(r['html']);
				}
			},
			error: function(jqXHR,error, errorThrown,data) {  
				//console.log(jqXHR.responseText + ' '+error+' '+errorThrown+' '+data);
				//console.log(this['url']);
				var text;
				if(jqXHR.responseText){
					text = jqXHR.responseText;
				}else
				{
					if(error){
						text = error;
					}else
					if(errorThrown){
						text = errorThrown;
					}
				}
				console.log("aws item search failed. "+text);
			},
			timeout: 30000
		});
		jQuery('#search_result_table').show();
	}
}
function removeKeywordWarning(){
	jQuery('#keyword_warning').html('');
}
</script>
<div id="adp_paid"><a href="http://pluginhandy.com/amazondiscount" target="_blank"><img src="http://pluginhandy.com/amazondiscount/wp-content/uploads/2015/10/Screen-Shot-2015-10-10-at-12.25.30.png"/></a></div>

<div id="search_form_block">
<form method="post" action="" id="search_form">
    <input type="hidden" id="country_code" name="country_code"/>
	<input type="hidden" id="category" name="category"/>
    <div class="manage_btns">
		<div id="search_logo_box"><img id="search_logo" src="<?php echo adp_URL."/images/search_logo.png"?>"/></div>
        <div class="black" id="plugin_title">AmaDiscount Plugin</div>
		<div class="black" id="plugin_slogan">Find Amazon's best discount deals for your customers and more commissions for you !</div>

		<div style="display:table;">
			<div class="row">
				<span class="cell2">
					<div class="select_box">
						<div class="row">
							<span class="cell"><img id="chosen_flag" src="<?php echo adp_URL."/images/us.gif"?>"/></span>
							<span class="cell" id="chosen_country">United States</span>
							<span class="cell clickable" onclick="openCountrySelector();"><img src="<?php echo adp_URL."/images/toggle.png"?>"/></span>
						</div>
					</div>
					<div id="country_select_box">
						<div class="row country_selection">
							<span class="cell"><img id="chosen_flag" src="<?php echo adp_URL."/images/us.gif"?>"/></span>
							<span class="cell clickable" onclick="closeCountrySelector('us');">United States</span>
						</div>
						<div class="row country_selection">
							<span class="cell"><img id="chosen_flag" src="<?php echo adp_URL."/images/canada.png"?>"/></span>
							<span class="cell clickable" onclick="closeCountrySelector('canada');">Canada</span>
						</div>
						<div class="row country_selection">
							<span class="cell"><img id="chosen_flag" src="<?php echo adp_URL."/images/uk.gif"?>"/></span>
							<span class="cell clickable" onclick="closeCountrySelector('uk');">UK</span>
						</div>
						<div class="row country_selection">
							<span class="cell"><img id="chosen_flag" src="<?php echo adp_URL."/images/china.jpg"?>"/></span>
							<span class="cell clickable" onclick="closeCountrySelector('china');">China</span>
						</div>
					</div>
				</span>
				<span class="cell">
					<div class="select_box" style="width:260px;">
						<div class="row">
							<span class="cell" style="" id="chosen_category">All Departments</span>
							<span class="cell clickable" onclick="openCategorySelector();"><img src="<?php echo adp_URL."/images/toggle.png"?>"/></span>
						</div>
					</div>
					<div id="category_select_box">
					</div>
				</span>
			</div>
		</div>
		<div id="keyword_logo_box">
			<div class="row">
				<span class="cell"><img src="<?php echo adp_URL."/images/keyword.png"?>"/></span>
				<span class="cell"><br><font class="black">Keyword(s)</font><font id="keyword_warning" style="color:red"></font></span>
			</div>
		</div>
		<div class="field_content">
			<input class="text_field" type="text" id="keywords" name="keywords" placeholder="e.g. iPhone case" value="" onchange="removeKeywordWarning();" />
		</div>
		<div id="discount_logo_box">
			<div class="row">
				<span class="cell"><img src="<?php echo adp_URL."/images/pig.png"?>"/></span>
				<span class="cell"><font class="black">Select the minimum discount % for which you want to search</font><br></span>
			</div>
		</div>
		<div id="slider" style="width:400px; margin-top: 1vh;"></div>
		<div style="width:400px; text-align:center; margin-top: 12px;">
			<div class="btn_square" id="min_discount">0%</div>
			<div class="btn_square" id="discount">60%</div>
			<div class="btn_square" id="max_discount">100%</div>
		</div>
		<br>
		<!--<div style="width:400px; text-align:center;"><font style="color:#F99B2A; font-size:18px;">- </font>Advanced Settings</div>-->
		<div id="price_logo_box">
			<div class="row">
				<span class="cell"><img src="<?php echo adp_URL."/images/price.png"?>"/></span>
				<span class="cell"><font class="black">Price</font><br>You can filter by minimum / maximum price</span>
			</div>
		</div>
		<div class="field_content">
			<input class="text_field short" type="text" id="min_price" name="min_price" placeholder="e.g. 20.00" value=""/>
			<input class="text_field short" type="text" id="max_price" name="max_price" placeholder="e.g. 100.00" value=""/>
		</div>
		<div class="field_content">
			<div class="btn_square" id="search_button" onclick="submitSearch();">Search</div>
		</div>
        <div id="search_result"></div>
		<div class="btn_square" id="last_search_button" onclick="displayLastSearch();">Show results</div>
		<div id="search_result_table">
		
		</div>   
    </div>
</form>

</div>
<div id="search_form_cover"></div>

