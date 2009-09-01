<?php
/*
Plugin Name: WP-invites
Plugin URI: http://jehy.ru/wp-plugins.en.html
Description: Invites system for wordpress, wordpress MU and buddypress!
Author: Jehy
Version: 1.0
Author URI: http://jehy.ru/index.en.html
*/
/*
if ( !defined('WP_CONTENT_URL') )
    define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if (!defined('PLUGIN_URL'))
    define('PLUGIN_URL', WP_CONTENT_URL . '/plugins/');
if (!defined('PLUGIN_PATH'))
    define('PLUGIN_PATH', WP_CONTENT_DIR . '/plugins/');
*/
if(!function_exists('str_split'))
{
function str_split($str, $l=1) {
  $str_array = explode("\r\n", chunk_split($str,$l));
  return $str_array;
}
}

DEFINE('WP_INVITES_VERSION', '0.4' );
DEFINE('WP_INVITES_INVITE_LENGTH',12);#invite code length
DEFINE('WP_INVITES_INVITE_SPLIT',4);#visual split, number of characters
DEFINE('WP_INVITES_DEFNUM',50);#default number of invite codes to be generated
DEFINE('WP_INVITES_CHARS','1234567890qwertyuiopasdfghjklzxcvbnm');#symbols used in code
DEFINE('WP_INVITES_REMOVE_INTERVAL','30');#time after which we remove invite code from base

if (function_exists('is_site_admin'))
	DEFINE('IS_WPMU',1);
else
	DEFINE('IS_WPMU',0);

if (function_exists('bp_core_screen_general_settings'))
	DEFINE('IS_BUDDYPRESS',1);
else
	DEFINE('IS_BUDDYPRESS',0);

if(IS_WPMU)
	DEFINE('INVITES_PREFIX',$wpdb->base_prefix);
else
	DEFINE('INVITES_PREFIX',$wpdb->prefix);

//$InviteErrors=new WP_Error();

function invites_init()
{
	@session_start();
}

add_action('init', 'invites_init');

if(IS_WPMU)
{	
if ( file_exists( ABSPATH . 'wp-content/mu-plugins/wp-invites/langs/wp-invites-' . get_locale() . '.mo' ) )
	load_textdomain( 'wp-invites', ABSPATH . 'wp-content/mu-plugins/wp-invites/langs/wp-invites-' . get_locale() . '.mo' );

}
else#not MU
{
	if (function_exists('load_plugin_textdomain')) 
		load_plugin_textdomain('wp-invites', null, 'wp-invites/langs');
}
	
function invites_ifreal($val)#check if it's a real invite code
{global $wpdb;
	$sql = 'SELECT 1 FROM '.INVITES_PREFIX.'invites WHERE `value`="'.addslashes($val).'" LIMIT 1';
	#echo $sql;
	$result=mysql_query($sql);
	echo mysql_error();
	if(mysql_num_rows($result))
		return TRUE;
	return FALSE;
}

function invites_unbeautify($str)
{
	return addslashes(str_replace('-','',trim($str)));
}

function invites_beautify($str)
{
	return implode('-',str_split($str,WP_INVITES_INVITE_SPLIT));
}

/* Functions for handling the admin area tabs for administrators */

function invites_add($val)#add invite... ))
{global $wpdb;
	$sql = 'INSERT INTO '.INVITES_PREFIX.'invites (`value`,`datetime`) VALUES("'.addslashes($val).'",NOW())';
	$wpdb->query($sql);
}

function invites_make()#make new code
{global $wpdb;
	$str='';
	$chars=WP_INVITES_CHARS;
	for($i=0;$i<WP_INVITES_INVITE_LENGTH;$i++)
		$str.=$chars[rand(0,strlen($chars)-1)];
	
	#paranoid check
	if(invites_ifreal($str))#if sucj code already exists in base, generate new
		$str=invites_make();
	return $str;
}

function invites_admin( $message = '', $type = 'error' )
{
global $wpdb,$_REQUEST;
if ( ( $wpdb->get_var('show tables like "'.INVITES_PREFIX.'invites"') == false ))
	invites_install();
?><div align="center"><?php
if($_REQUEST['invites_admin_submit'])
{
?><div class="form-table" style="width:70%; border:1px solid #666; padding:10px; background-color:#CECECE;margin:10px;";><H2><?php _e('Generated invitation codes:', 'wp-invites') ?></h2><p style="text-align:left;"><?php
	for($i=0;$i<$_REQUEST['invites_num'];$i++)
	{
		$invite=invites_make();
		invites_add($invite);
		echo '<br>'.invites_beautify($invite);
	}?></div><?php
}
?><div class="form-table" style="width:70%; border:1px solid #666; padding:10px; background-color:#CECECE;margin:10px;";><p style="text-align:left;"><?php _e('Please, choose, how many invitation codes you are going to generate. After generation, please write down them somewhere - you will not be able to view them after it. Later, codes will be either assigned to registered users, or disapperar after a period of time. Do not worry about invite code brute forcing. Code has a length of', 'wp-invites') ?> <?php echo WP_INVITES_INVITE_LENGTH ?> <?php _e(' chars, and is combined from', 'wp-invites') ?> <?php echo strlen(WP_INVITES_CHARS) ?> <?php _e(' different chars, and, if not activated, is being removed after', 'wp-invites') ?> <?php echo WP_INVITES_REMOVE_INTERVAL;?> <?php _e(' days.', 'wp-invites') ?></p>
	<form method="post" action="<?php echo $location;?>">
	<input type="text" name="invites_num" value="<?=WP_INVITES_DEFNUM;?>"><input type="submit" name="invites_admin_submit" value="<?php _e('Generate', 'wp-invites') ?>"></form></div>
</div><?php
}

function invites_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = ' DEFAULT CHARACTER SET '.$wpdb->charset;
	
	$sql = 'CREATE TABLE '.INVITES_PREFIX.'invites (
			 `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			 `value` varchar(255) NOT NULL,
			 `datetime` datetime default NULL
	)';
	$sql.=$charset_collate.';';
	$result=$wpdb->query($sql);
	if($result===FALSE)#possibly, mysql 3 or 4, does not support encoding parameter 
	{
		$sql.=';';
		$result=$wpdb->query($sql);
		f($result===FALSE)
		{
			echo '<font color="red">WP invites table could not be installed! Please check database permissions. <br><b>Query:</b><br> '.$sql.'<br><b>Error:</b>';
			$wpdb->print_error();
			echo '</font>';
		}
	}
}


/* Functions to handle the modification and saving of signup pages */


function invites_add_signup_fields() {
	global $errors;
	$error = $errors->get_error_message('wp_invites_error');
	//if($error)
	//	echo '<div style="background-color:red;color:#000000;">' . $error . '</div>';
	?>
	<p>
		<label><?php _e('Invite code', 'wp-invites') ?><br>
		<?php _e('Please, input here invitation code, received from the blog owner', 'wp-invites') ?><br>
		<input type="text" name="invite_code" tabindex="21" class="input" value="<?php echo $_REQUEST['invite_code'];?>" style="
background:#FBFBFB none repeat scroll 0 0;
border:1px solid #E5E5E5;
font-size:24px;
margin-bottom:16px;
margin-right:6px;
margin-top:2px;
padding:3px;
width:97%;"></label>
	</p>
	<?php
}

function invites_validate_signup_fields( $result )
{global $user_name,$user_email,$wpdb,$errors;
	// $errors is a global variable. However, it may not work in simple (not MU) wordpress...
	$sql = 'DELETE FROM '.INVITES_PREFIX.'invites WHERE `datetime` < NOW() - INTERVAL '.WP_INVITES_REMOVE_INTERVAL.' DAY';//remove old codes
	$wpdb->query($sql);
	//echo mysql_error();
	if($_REQUEST['invite_code'])
		$_SESSION['invite_code']=$_REQUEST['invite_code'];
	
	if(IS_WPMU)
	{
		extract($result);
		if(!invites_ifreal(invites_unbeautify($_SESSION['invite_code'])))
		{
			$errors->add('wp_invites_error', __('<b>Error:</b>Wrong invite code', 'wp-invites') );
			$result = array('user_name' => $user_name, 'user_email' => $user_email,	'errors' => $errors);
			
		}
	}
	else
	{
		if(!invites_ifreal(invites_unbeautify($_SESSION['invite_code'])))
			$result->add('wp_invites_error', __('<b>Error:</b>Wrong invite code', 'wp-invites') );
		
	}
	return $result;
}



function invites_on_activate_user( $user_id, $password='', $meta='') 
{	global $wpdb;
	update_usermeta( $user_id, 'invite_code',invites_unbeautify($_SESSION['invite_code']));
	#delete invite
	$sql = 'DELETE FROM '.INVITES_PREFIX.'invites WHERE `value`="'.invites_unbeautify($_SESSION['invite_code']).'"';
	$wpdb->query($sql);
	echo mysql_error();
}

function invites_add_admin_menu() 
{
global $wpdb, $bp;

if(IS_WPMU)
{
	if ( is_site_admin() )
		add_submenu_page( 'wpmu-admin.php', 'WP-invites', 'WP-invites', 8, "manage_invites", "invites_admin" );
}
else
	add_submenu_page('plugins.php','WP-invites','WP-invites',8,__FILE__,'invites_admin');
}


function setup_WP_INVITES()
{global $bp;
if( $bp->current_component == 'profile'&&$bp->current_action=='public'&&$bp->current_userid)
{
	add_action('loop_start','output_WP_INVITES',3);
}
}

function output_WP_INVITES()
{global $wpdb,$bp;
$code=invites_beautify(get_usermeta($bp->current_userid,'invite_code'));
if(!$code)
	$code=__('No code assigned', 'wp-invites');
if(is_site_admin())
{
?>
<div class="info-group">
<h4>Invitation code</h4>
<table class="profile-fields">
<tr><td class="label"><?php _e('Code', 'wp-invites') ?></td><td class="data"><?=$code;?></td></tr></table></div><?php
}
}


function output_invites($id)
{
global $profileuser;
$code=invites_beautify(get_usermeta($profileuser->ID,'invite_code'));
if(!$code)
	$code=__('No code assigned', 'wp-invites');
#if(is_site_admin())
{
?>
<h3><?php _e('Invitation code', 'wp-invites') ?></h3>

<table class="form-table">
<tr>
	<th><label for="invite_code"><?php _e('Code', 'wp-invites') ?></label></th>
	<td><input readonly class="regular-text" value="<?php echo $code;?>"></td>
</tr></table>
<?php
}
}

add_action('show_user_profile','output_invites',99);
add_action('edit_user_profile','output_invites',99);

if(IS_BUDDYPRESS)
	add_action('wp','setup_WP_INVITES',99);

if(IS_WPMU)
{
	add_action( 'signup_extra_fields', 'invites_add_signup_fields',1);
	add_filter( 'wpmu_validate_user_signup', 'invites_validate_signup_fields',99,1);
	add_filter( 'wpmu_activate_user','invites_on_activate_user');

}
else
{
	add_action('register_form', 'invites_add_signup_fields',1);
	add_filter( 'registration_errors', 'invites_validate_signup_fields',99,1);
	add_filter( 'user_register', 'invites_on_activate_user');
}
add_action( 'admin_menu', 'invites_add_admin_menu' );


?>