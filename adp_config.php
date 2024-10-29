<script type="text/javascript">
var country = 'us';
var text_changed = false;
jQuery(document).ready(function($){
	$('#save_verify_button').hide();
	$('#save_verify_cancel').hide();
	
	 $('.text_field').each(function() {
	   var elem = $(this);
	
	   // Save current value of element
	   elem.data('oldVal', elem.val());
	
	   // Look for changes in the value
	   elem.bind("propertychange change click keyup input paste", function(event){
		  // If value has changed...
		  if (elem.data('oldVal') != elem.val()) {
		   // Updated stored value
		   elem.data('oldVal', elem.val());
	
		   // Do action
		   if($('#access_id').val().length > 0 && $('#secret_key').val().length > 0 &&($('#us_id').val().length > 0 || $('#can_id').val().length > 0 || $('#uk_id').val().length > 0 || $('#chin_id').val().length > 0)){
			$('#save_verify_button').show();
			$('#save_verify_cancel').show();
			$('#credentials_verified_button').hide();
		}
		 }
	   });
	 });

	
});
</script>
<?php 
//read config table
global $wpdb;
$accessKey = $secretKey = $verified = $usIds = $canIds = $chinIds = $ukIds = "";
$adpConfigTable = adp_TABLE_PREFIX."adp_config";
$config = $wpdb->get_results("SELECT * from `$adpConfigTable`");
if(count($config) > 0){
	$accessKey = $config[0]->access_key;
	$secretKey = $config[0]->secret_access_key;
	$chinIds = $config[0]->tracking_ids_china;
	$usIds = $config[0]->tracking_ids_us;
	$canIds = $config[0]->tracking_ids_canada;
	$ukIds = $config[0]->tracking_ids_uk;
	$verified = $config[0]->verified;
	$message = $config[0]->message;
}

?>
<form method="post" action="" id="config_form" onsubmit="">
	<input type="hidden" id="country" name="country"/>
    <div class="manage_btns">
	<?php 
		if(strstr($message,'access key')){
			echo '<div class="message">'.$message.'</div>';
		}
	?>
        <h2>Amazon Credentials</h2>
		The following fields are required in order to send requests to Amazon and retrieve data about products and listings. If you do not already
		have access visit the <a target="_blank" href="http://aws.amazon.com">AWS Account Management</a> page to create and retrieve them.
        <div class="form_section">
            <div class="field_content">
                <label class="field_label">Access Key ID : </label>
                <input class="text_field" type="text" id="access_id" name="access_id" value="<?php echo $accessKey; ?>"/>
            </div>
			<div class="field_content">
                <label class="field_label">Secret Access Key : </label>
                <input class="text_field" type="text" id="secret_key" name="secret_key" value="<?php echo $secretKey; ?>" />
            </div>
			
        </div>
		<?php 
			if(strstr($message,'Associate')){
			echo '<div class="message">'.$message.'</div>';
			}
		?>
		<h2>Amazon Associates</h2>
		Enter your Amazon Associate Tracking ID's below. You must enter at least one.<br><font style="font-weight:bold;">Be careful to get these right; Amazon will allow the search but if the associate ID is not correct you will not be credited for any sales !</font>
		<div class="form_section">
            <div class="field_content">
                <label class="field_label">United states : </label>
                <input class="text_field" type="text" id="us_id" name="us_id" value="<?php echo $usIds; ?>"/>
				<a href="https://affiliate-program.amazon.com/gp/associates/network/your-account/manage-tracking-ids.html" target="_blank">Sign Up</a>
    		</div>
			<div class="field_content">
                <label class="field_label">Canada : </label>
                <input class="text_field" type="text" id="can_id" name="can_id" value="<?php echo $canIds; ?>" />
				<a href="https://associates.amazon.ca/gp/associates/network/your-account/manage-tracking-ids.html" target="_blank">Sign Up</a>
            </div>
			<div class="field_content">
                <label class="field_label">UK : </label>
                <input class="text_field" type="text" id="uk_id" name="uk_id" value="<?php echo $ukIds; ?>" />
				<a href="https://affiliate-program.amazon.com/gp/associates/network/your-account/manage-tracking-ids.html" target="_blank">Sign Up</a>
            </div>
			<div class="field_content">
                <label class="field_label">China : </label>
                <input class="text_field" type="text" id="chin_id" name="chin_id" value="<?php echo $chinIds; ?>" />
				<a href="https://associates.amazon.cn/gp/associates/network/your-account/manage-tracking-ids.html" target="_blank">Sign Up</a>
            </div>
        </div>
		<?php 
			if($verified != "yes" && $accessKey != "" && $secretKey !=""){
				//echo '<div class="btn_square" onclick=jQuery("#config_form").submit();>Verify credentials</div>';
			}
			if($verified == "yes")
			{
				echo '<div class="btn_square" style="cursor:none;" id="credentials_verified_button">Credentials verified</div>';
			}
		?>
		<div class="btn_square" id="save_verify_button" onclick="jQuery('#config_form').submit();">Save and Verify credentials</div>
		<div class="btn_square" id="save_verify_cancel" onclick="jQuery('#restore_form').submit();">Cancel changes</div>
    </div>

</form>	
<form method="post" action="" id="restore_form">
	<input type="hidden" id="restore" name="restore"/>
</form