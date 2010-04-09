<?php
/*
Plugin Name: WP-invites
Plugin URI: http://jehy.ru/wp-plugins.en.html
Description: Invites system for wordpress, wordpress MU and buddypress!
Author: Jehy
Version: 2.21
Author URI: http://jehy.ru/index.en.html
*/
if(!function_exists('str_split'))
{
function str_split($str, $l=1) {
  $str_array = explode("\r\n", chunk_split($str,$l));
  return $str_array;
}
}


if (function_exists('is_site_admin'))
	DEFINE('IS_WPMU',1);
else
	DEFINE('IS_WPMU',0);

if (defined('BP_PLUGIN_DIR'))
	DEFINE('IS_BUDDYPRESS',1);
else
	DEFINE('IS_BUDDYPRESS',0);

if(constant('IS_WPMU'))
	DEFINE('INVITES_PREFIX',$wpdb->base_prefix);
else
	DEFINE('INVITES_PREFIX',$wpdb->prefix);

function invites_init()
{
	@session_start();
	invites_get_options();
}

add_action('init', 'invites_init');

function invites_ifreal($val)#check if it's a real invite code
{global $wpdb;
	$sql = 'SELECT 1 FROM '.INVITES_PREFIX.'invites WHERE `value`="'.addslashes(invites_unbeautify($val)).'" LIMIT 1';
	$result=mysql_query($sql);
	echo mysql_error();
	if(mysql_num_rows($result))
		return TRUE;
	return FALSE;
}

function invites_unbeautify($str)
{global $wp_invites_options;
	return addslashes(str_replace($wp_invites_options['SEPARATOR'],'',trim($str)));
}

function invites_beautify($str)
{global $wp_invites_options;
	return implode($wp_invites_options['SEPARATOR'],str_split($str,$wp_invites_options['INVITE_SPLIT']));
}

/* Functions for handling the admin area tabs for administrators */

function invites_add($val)#add invite... ))
{global $wpdb;
	$sql = 'INSERT INTO '.INVITES_PREFIX.'invites (`value`,`datetime`) VALUES("'.invites_unbeautify($val).'",NOW())';
	$wpdb->query($sql);
}

function invites_make()#make new code
{global $wpdb,$wp_invites_options;
	$str='';
	$chars=$wp_invites_options['CHARS'];
	for($i=0;$i<$wp_invites_options['INVITE_LENGTH'];$i++)
		$str.=$chars[rand(0,strlen($chars)-1)];

	#paranoid check
	if(invites_ifreal($str))#if such code already exists in base, generate new
		$str=invites_make();
	return $str;
}

function invites_get_options()
{global $wp_invites_options;

  #initialize with defaults
  $wp_invites_options=array('INVITE_LENGTH'=>12,#invite code length
  'INVITE_SPLIT'=>4,#visual split, number of characters
  'CHARS'=>'1234567890qwertyuiopasdfghjklzxcvbnm',#symbols used in code
  'REMOVE_INTERVAL'=>'30',#time after which we remove invite code from base
  'SEPARATOR'=>'-'
  );
  #get the options from the database
  if (IS_WPMU)
   $options = get_site_option('wp-invites'); // get the options from the database
  else
   $options = get_option('wp-invites');
  if(sizeof($options)&&$options)
    foreach($options as $key => $val)
      $wp_invites_options[$key] = $val;}

function invites_admin( $message = '', $type = 'error' )
{global $wp_invites_options;
global $wpdb;
if ( ( $wpdb->get_var('show tables like "'.INVITES_PREFIX.'invites"') == null ))
{
	echo '<br>No MySQL table found. Installing...';
	invites_install();
}
?><a href="?page=manage_invites&action=view">View created codes</a><br>
<a href="?page=manage_invites&action=options">Configure plugin</a><br>
<a href="?page=manage_invites">Generate new codes</a><br>
<a href="?page=manage_invites&action=add">Add codes manually</a><br>
<?php
?><div align="center"><?php

if($_REQUEST['action']=='options')
{  if($_REQUEST['step']=='2')
  {
   	if (IS_WPMU)
      update_site_option('wp-invites', $_REQUEST['wp_invites']);
    else
      update_option('wp-invites', $_REQUEST['wp_invites']);
      ?>Options updated!<?php  }
  invites_get_options();
  ?><div class="form-table" style="width:70%; border:1px solid #666; padding:10px; background-color:#CECECE;margin:10px;";><p style="text-align:left;">
	<form method="post" action="">
	<table>
	<tr><td>Code length</td><td><input name="wp_invites[INVITE_LENGTH]" value="<?php echo $wp_invites_options['INVITE_LENGTH'];?>"></td></tr>
	<tr><td>visual split of characters</td><td><input name="wp_invites[INVITE_SPLIT]" value="<?php echo $wp_invites_options['INVITE_SPLIT'];?>"></td></tr>
	<tr><td>chars, used for code generation</td><td><input name="wp_invites[CHARS]" value="<?php echo $wp_invites_options['CHARS'];?>"></td></tr>
	<tr><td>remove interval, in days. Set to 3650 (10 years), if you need infinite :)</td><td><input name="wp_invites[REMOVE_INTERVAL]" value="<?php echo $wp_invites_options['REMOVE_INTERVAL'];?>"></td></tr>
	<tr><td>Separator for output</td><td><input name="wp_invites[SEPARATOR]" value="<?php echo $wp_invites_options['SEPARATOR'];?>"></td></tr>
	</table>
	<input type="hidden" name="action" value="options">
	<input type="hidden" name="step" value="2">
	<input type="submit" value="<?php _e('Save', 'wp-invites') ?>"></form></div>
</div><?php
}
elseif($_REQUEST['action']=='add')
{  if($_REQUEST['step']=='2')
  {    $codes=explode("\n",$_REQUEST['codes']);
    for($i=0;$i<sizeof($codes);$i++)
    {      $invite=trim($codes[$i]);
      $invite=invites_unbeautify($invite);
      if($invite)
      {
		    invites_add($invite);
		    echo '<br>Code added: '.invites_beautify($invite);
		  }    }  }
  ?><form method="post" action="">Please add codes, one for each line. Default expiration date will be used for them. You can add them with or without separators.
  <br><textarea cols="60" rows="20" name="codes"></textarea>
  <input type="hidden" name="action" value="add">
  <input type="hidden" name="page" value="manage_invites">
  <input type="hidden" name="step" value="2"><br>
	<input type="submit"  value="<?php _e('Add', 'wp-invites') ?>"></form>
  <?php}
elseif($_REQUEST['action']=='view')
{  $sql = 'SELECT value,`datetime`,(`datetime`+ INTERVAL '.$wp_invites_options['REMOVE_INTERVAL'].' DAY) as `remove` FROM '.INVITES_PREFIX.'invites order by `datetime`';
	#echo $sql;
	$result=mysql_query($sql);
	echo mysql_error();
	if(mysql_num_rows($result))
	{	  ?>Generated codes:<table width="100%"><tr><td>Code</td><td>Generated on</td><td>Valid till</td></tr><?php
	  while($row=mysql_fetch_array($result))
	  {	    echo '<tr><td>'.invites_beautify($row['value']).'</td><td>'.$row['datetime'].'</td><td>'.$row['remove'].'</td></tr>';	  }
	  ?></table><?php
	}}
else
{  if($_REQUEST['step']=='2')
  {
    ?><div class="form-table" style="width:70%; border:1px solid #666; padding:10px; background-color:#CECECE;margin:10px;";><H2><?php _e('Generated invitation codes:', 'wp-invites') ?></h2><p style="text-align:left;"><?php
	  for($i=0;$i<$_REQUEST['invites_num'];$i++)
	  {
		  $invite=invites_make();
		  invites_add($invite);
		  echo '<br>'.invites_beautify($invite);
	  }?></div><?php
	}
?><div class="form-table" style="width:70%; border:1px solid #666; padding:10px; background-color:#CECECE;margin:10px;";><p style="text-align:left;"><?php _e('Please, choose, how many invitation codes you are going to generate. Later, codes will be either assigned to registered users, or disapperar after a period of time. Code has a length of', 'wp-invites') ?> <?php echo $wp_invites_options['INVITE_LENGTH'] ?> <?php _e(' chars, and is combined from', 'wp-invites') ?> <?php echo strlen($wp_invites_options['CHARS']) ?> <?php _e(' different chars, and, if not activated, is being removed after', 'wp-invites') ?> <?php echo $wp_invites_options['REMOVE_INTERVAL'];?> <?php _e(' days.', 'wp-invites') ?></p>
	<form method="post" action="">
	<input type="text" name="invites_num" value="50">
	<input type="hidden" name="action" value="generate">
	<input type="hidden" name="step" value="2">
	<input type="submit"  value="<?php _e('Generate', 'wp-invites') ?>"></form></div>
</div><?php
}
}

function invites_install() {
	global $bp, $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = ' DEFAULT CHARACTER SET '.$wpdb->charset;

	$sql1 = 'CREATE TABLE '.INVITES_PREFIX.'invites (
			 `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			 `value` varchar(255) NOT NULL,
			 `datetime` datetime default NULL
	)';
	$sql=$sql1.$charset_collate.';';
	$result=$wpdb->query($sql);
	if($result===FALSE)#possibly, mysql 3 or 4, does not support encoding parameter
	{
		$sql=$sql1.';';
		$result=$wpdb->query($sql);
		if($result===FALSE)
		{
			echo '<p class="error">WP invites table could not be installed! Please check database permissions. <br><b>Query:</b><br> '.$sql.'<br><b>Error:</b>';
			$wpdb->print_error();
			echo '</p>';
		}
	}
}


function invites_add_signup_fields($errors_mu) {
	global $errors,$bp;
	//print_R($errors);die;
	if(IS_BUDDYPRESS)
	  $error=$bp->signup->errors['wp_invites_error'];
	elseif(IS_WPMU)
		$error = $errors_mu->get_error_message('wp_invites_error');
	else
		$error = $errors->get_error_message('wp_invites_error');
	if($error)
		echo '<p class="error">' . $error . '</p>';
	?>
	<div style="width:100%;"><hr style="clear: both; margin-bottom: 1.5em; border: 0; border-top: 1px solid #999; height: 1px;" /></div>
  <p>
		<label for="wp-invites"><?php _e('Invite code', 'wp-invites') ?></label><br />
		<?php _e('Please, input here invitation code, received from the blog owner', 'wp-invites') ?><br>
		<input type="text" name="invite_code" value="<?php echo $_REQUEST['invite_code'];?>" style="width:200px">
	</p>
	<?php
}

function invites_validate_signup_fields( $result )
{global $wpdb,$bp,$wp_invites_options;
  $sql = 'DELETE FROM '.INVITES_PREFIX.'invites WHERE `datetime` < NOW() - INTERVAL '.$wp_invites_options['REMOVE_INTERVAL'].' DAY';//remove old codes
  $wpdb->query($sql);
  if($_REQUEST['invite_code'])
    $_SESSION['invite_code']=$_REQUEST['invite_code'];

  if(!invites_ifreal(invites_unbeautify($_SESSION['invite_code'])))
  {    if(IS_BUDDYPRESS)
      $bp->signup->errors['wp_invites_error']=__('<b>Error:</b>Wrong invite code', 'wp-invites');
    elseif(IS_WPMU)
	    $result['errors']->add('wp_invites_error', __('<b>Error:</b>Wrong invite code', 'wp-invites') );
	  else
	    $result->add('wp_invites_error', __('<b>Error:</b>Wrong invite code', 'wp-invites') );
  }
	return $result;
}



function invites_on_activate_user( $user_id, $password='', $meta='')
{
	update_usermeta( $user_id, 'invite_code',$meta['invite_code']);
}

function wp_invites_on_activate_user( $user_id)
{global $wpdb;

  $sql = 'DELETE FROM '.INVITES_PREFIX.'invites WHERE `value`="'.invites_unbeautify($_SESSION['invite_code']).'"';
  $wpdb->query($sql);
  echo mysql_error();
	update_usermeta( $user_id, 'invite_code',$_SESSION['invite_code']);
}

function bp_invites_on_activate_user($meta='',$key='')
{
	update_usermeta( $meta['user_id'], 'invite_code',$meta['meta']['invite_code']);
}

function invites_add_admin_menu()
{
global $wpdb, $bp;

if(constant('IS_BUDDYPRESS'))
{
   add_submenu_page( 'bp-general-settings', 'WP-invites', 'WP-invites', 8, "manage_invites", "invites_admin" );
}
else if(constant('IS_WPMU'))
{
	if ( is_site_admin() )
		add_submenu_page( 'wpmu-admin.php', 'WP-invites', 'WP-invites', 8, "manage_invites", "invites_admin" );
}
else
	add_submenu_page('plugins.php','WP-invites','WP-invites',8,"manage_invites",'invites_admin');
}


function wp_output_invites($user)
{
$code=invites_beautify(get_usermeta($user->ID,'invite_code'));
if(!$code)
	$code=__('No code assigned', 'wp-invites');
if(
  !function_exists('is_site_admin')||
  (
    is_site_admin()||
    (get_current_user_id()==$user->ID)
  )
  )
{
?><table class="form-table">
<tr>
	<th>
<label for="invite code"><?php _e('Invite code', 'wp-invites') ?></label></th>
	<td><input type="text"  disabled="disabled" class="regular-text" value="<?=$code;?>"></td></tr></table><?php
}
}


function bp_output_invites($id)
{
global $bp;
if($bp->current_component!='profile')
  return;
$code=invites_beautify(get_usermeta($bp->displayed_user->id,'invite_code'));
if(!$code)
	$code=__('No code assigned', 'wp-invites');
if (bp_is_home()||is_site_admin())
{
?>
<div class="bp-widget">
<h4><?php _e('Invitation code', 'wp-invites') ?></h4>
<table class="profile-fields">
	<tr class="field_1">
    <td class="label"><?php _e('Code', 'wp-invites') ?></td>
    <td class="data"><p><?php echo $code;?></p></td>
  </tr>
</table>
</div>
<?php
}
}

function wpmu_invites_add_signup_meta($meta) {
global $wpdb;

$sql = 'DELETE FROM '.INVITES_PREFIX.'invites WHERE `value`="'.invites_unbeautify($_SESSION['invite_code']).'"';
$wpdb->query($sql);
echo mysql_error();

$add_meta = array('invite_code' => invites_unbeautify($_SESSION['invite_code']));
$meta = array_merge($add_meta, $meta);
return $meta;
}




if(constant('IS_WPMU'))
{
  #for WPMU and buddypress

  #validate signup for wpmu and buddy
	add_filter( 'wpmu_validate_user_signup', 'invites_validate_signup_fields',99,1);

	#activate - add meta for wpmu and buddy
	add_filter('wpmu_activate_user', 'invites_on_activate_user', 1, 3);
  
  #add refistration field in wpmu and for byddypress wpmu themes
	add_action( 'signup_extra_fields', 'invites_add_signup_fields');
}
if(constant('IS_BUDDYPRESS'))
{  #add registration field in buddy
	add_action('bp_before_account_details_fields','invites_add_signup_fields',99);
  #output code in buddy profile
  add_action('bp_after_profile_header_content','bp_output_invites',99);

  #set meta for buddy
  add_filter('bp_signup_usermeta','wpmu_invites_add_signup_meta',1,1);

  #if blog is selected, it is neccessary...
	add_filter('bp_core_account_activated', 'bp_invites_on_activate_user', 1, 2);
}

if(constant('IS_WPMU') && !constant('IS_BUDDYPRESS'))#for MU only
{

	#set meta for wpmu
  add_filter('add_signup_meta','wpmu_invites_add_signup_meta',1,1);
}
if(!constant('IS_WPMU'))#for simple wordpress
{
	add_action('register_form', 'invites_add_signup_fields');
	add_filter( 'registration_errors', 'invites_validate_signup_fields',99,1);
	add_action( 'user_register', 'wp_invites_on_activate_user');
}
add_action( 'admin_menu', 'invites_add_admin_menu',20);
// NOTE the addition of a higher priority seems to solve the problems where
//  WPMU starts trying to tell you that you don't have access 

  #output in wp innerpanel
add_action( 'show_user_profile', 'wp_output_invites',99,1);
add_action( 'edit_user_profile', 'wp_output_invites',99,1);
?>