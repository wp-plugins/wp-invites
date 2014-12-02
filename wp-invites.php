<?php
/*
Plugin Name: WP-invites
Author URI: http://jehy.ru/articles/
Plugin URI: http://jehy.ru/articles/2009/02/09/wordpress-plugins/
Description: Invites system for wordpress, wordpress MU and buddypress! To set up, visit <a href="options-general.php?page=wp-invites/wp-invites.php">configuration panel</a>.
Author: Jehy
Version: 2.41
*/
if(!function_exists('str_split'))
{
function str_split($str, $l=1) {
  $str_array = explode("\r\n", chunk_split($str,$l));
  return $str_array;
}
}


function init_lang()
{
  $plugin_dir = basename(dirname(__FILE__));
  load_plugin_textdomain( 'wp-invites', false, $plugin_dir.'/lang');
}

if (is_multisite())
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
  init_lang();
	invites_get_options();
}

add_action('init', 'invites_init');

function invites_ifreal($val)#check if it's a real invite code
{global $wpdb;

$meta_key = 'miles';
$res = $wpdb->get_var( $wpdb->prepare( 
	"SELECT 1 FROM ".INVITES_PREFIX."invites WHERE `value`= %s LIMIT 1",addslashes(invites_unbeautify($val))));
if(is_null($res))
{
	echo $wpdb->last_error;
   return FALSE;
}
if(!$res)
   return FALSE;
return TRUE;
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
	$sql = 'INSERT INTO '.INVITES_PREFIX.'invites (`value`,`datetime`) VALUES(%s,NOW())';
	$wpdb->query($wpdb->prepare($sql,invites_unbeautify($val)));
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

function invites_menu()
{
echo'
<ul style="font-size:14px;"><li><a href="?page=wp-invites/wp-invites.php&action=view">'.__('View created codes','wp-invites').'</a></li>
<li><a href="?page=wp-invites/wp-invites.php&action=options">'.__('Configure plugin','wp-invites').'</a></li>
<li><a href="?page=wp-invites/wp-invites.php">'.__('Generate new codes','wp-invites').'</a></li>
<li><a href="?page=wp-invites/wp-invites.php&action=add">'.__('Add codes manually','wp-invites').'</a></li></ul>
';
}

function invites_admin( $message = '', $type = 'error' )
{global $wp_invites_options,$wpdb;
if ( ( $wpdb->get_var('show tables like "'.INVITES_PREFIX.'invites"') == null ))
{
	echo '<br>'.__('No MySQL table found. Installing...','wp-invites');
	invites_install();
}
invites_menu();
?><?php

if($_REQUEST['action']=='options')
{  if($_REQUEST['step']=='2')
  {
   	if (IS_WPMU)
      update_site_option('wp-invites', $_REQUEST['wp_invites']);
    else
      update_option('wp-invites', $_REQUEST['wp_invites']);
      echo '<div class="updated">'.__('Options updated!','wp-invites').'</div>';  }
  invites_get_options();
  ?>
  <h2><?php _e('WP-Invites options','wp-invites');?></h2>
  <form method="post" action="">
	<table>
	<tr><td><?php _e('Code length','wp-invites');?></td><td><input name="wp_invites[INVITE_LENGTH]" value="<?php echo $wp_invites_options['INVITE_LENGTH'];?>"></td></tr>
	<tr><td><?php _e('visual split of characters','wp-invites');?></td><td><input name="wp_invites[INVITE_SPLIT]" value="<?php echo $wp_invites_options['INVITE_SPLIT'];?>"></td></tr>
	<tr><td><?php _e('chars, used for code generation','wp-invites');?></td><td><input name="wp_invites[CHARS]" value="<?php echo $wp_invites_options['CHARS'];?>"></td></tr>
	<tr><td><?php _e('remove interval, in days. Set to 3650 (10 years), if you need infinite','wp-invites');?> :)</td><td><input name="wp_invites[REMOVE_INTERVAL]" value="<?php echo $wp_invites_options['REMOVE_INTERVAL'];?>"></td></tr>
	<tr><td><?php _e('Separator for output','wp-invites');?></td><td><input name="wp_invites[SEPARATOR]" value="<?php echo $wp_invites_options['SEPARATOR'];?>"></td></tr>
	</table>
	<input type="hidden" name="action" value="options">
	<input type="hidden" name="step" value="2">
	<input type="submit" value="<?php _e('Save', 'wp-invites') ?>" class="button button-primary"></form>
<?php
}
elseif($_REQUEST['action']=='add')
{  if($_REQUEST['step']=='2')
  {    $codes=explode("\n",$_REQUEST['codes']);
    echo '<div class="updated">';
    for($i=0;$i<sizeof($codes);$i++)
    {      $invite=trim($codes[$i]);
      $invite=invites_unbeautify($invite);
      if($invite)
      {
		    invites_add($invite);
		    echo '<br>'.__('Code added:','wp-invites').' '.invites_beautify($invite);
		  }    }    echo '</div>';  }
  ?><form method="post" action=""><?php _e('Please add codes, one for each line. Default expiration date will be used for them. You can add them with or without separators.', 'wp-invites') ?><br>
  <br><textarea rows="20" name="codes"  class="large-text code" style="width:300px;"></textarea>
  <input type="hidden" name="action" value="add">
  <input type="hidden" name="page" value="wp-invites/wp-invites.php">
  <input type="hidden" name="step" value="2">
	<input type="submit"  value="<?php _e('Add', 'wp-invites') ?>" class="button button-primary" style="width:80px;"></form>
  <?php}
elseif($_REQUEST['action']=='view')
{  $sql = 'SELECT value,`datetime`,(`datetime`+ INTERVAL '.$wp_invites_options['REMOVE_INTERVAL'].' DAY) as `remove` FROM '.INVITES_PREFIX.'invites order by `datetime`';
  $res=$wpdb->get_results($sql,ARRAY_A);
  if(is_null($res))
  {
	 echo $wpdb->last_error;
  }
  elseif(is_array($res))
  {
	  echo '<h2>'.__('Generated codes:', 'wp-invites').'</h2><table width="100%"><tr><td>'.__('Code', 'wp-invites').'</td><td>'.__('Generated on', 'wp-invites').'</td><td>'.__('Valid till', 'wp-invites').'</td></tr>';
  foreach($res as $row)	    echo '<tr><td>'.invites_beautify($row['value']).'</td><td>'.$row['datetime'].'</td><td>'.$row['remove'].'</td></tr>';
	  ?></table><?php	}}
else
{  if($_REQUEST['step']=='2')
  {
    ?><div class="updated"><H2><?php _e('Generated invitation codes:', 'wp-invites') ?></h2><p><?php
	  for($i=0;$i<$_REQUEST['invites_num'];$i++)
	  {
		  $invite=invites_make();
		  invites_add($invite);
		  echo '<br>'.invites_beautify($invite);
	  }?></p></div><?php
	}
?><h2><?php _e('Generate codes','wp-invites');?></h2><p><?php _e('Please, choose, how many invitation codes you are going to generate. Later, codes will be either assigned to registered users, or disapperar after a period of time. Code has a length of', 'wp-invites') ?> <?php echo $wp_invites_options['INVITE_LENGTH'] ?> <?php _e(' chars, and is combined from', 'wp-invites') ?> <?php echo strlen($wp_invites_options['CHARS']) ?> <?php _e(' different chars, and, if not activated, is being removed after', 'wp-invites') ?> <?php echo $wp_invites_options['REMOVE_INTERVAL'];?> <?php _e(' days.', 'wp-invites') ?></p>
  <p><?php _e('You can always change code generation parameters on options page.', 'wp-invites') ?></p>
	<form method="post" action="">
	<input type="text" name="invites_num" value="50" class="regular-text ltr" style="width:50px;">
	<input type="hidden" name="action" value="generate">
	<input type="hidden" name="step" value="2">
	<input type="submit"  value="<?php _e('Generate', 'wp-invites') ?>" class="button button-primary"></form></div>
<?php
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
			echo '<div class="error">'.__('WP invites table could not be installed! Please check database permissions.', 'wp-invites').' <br><b>'.__('Query:', 'wp-invites').'</b><br> '.$sql.'<br><b>'.__('Error:', 'wp-invites').'</b>';
			$wpdb->print_error();
			echo '</div>';
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
		<input type="text" name="invite_code" value="<?php echo $_REQUEST['invite_code'];?>" class="regular-text ltr">
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
      $bp->signup->errors['wp_invites_error']='<b>'.__('Error:', 'wp-invites').'</b>'.__('Wrong invite code', 'wp-invites');
    elseif(IS_WPMU)
	    $result['errors']->add('wp_invites_error', '<b>'.__('Error:', 'wp-invites').'</b>'.__('Wrong invite code', 'wp-invites') );
	  else
	    $result->add('wp_invites_error', '<b>'.__('Error:', 'wp-invites').'</b>'.__('Wrong invite code', 'wp-invites') );
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
  $res=$wpdb->query($sql);
  if($res===FALSE)
    echo $wpdb->last_error;
  update_usermeta( $user_id, 'invite_code',$_SESSION['invite_code']);
}

function bp_invites_on_activate_user($meta='',$key='')
{
	update_usermeta( $meta['user_id'], 'invite_code',$meta['meta']['invite_code']);
}

function invites_add_admin_menu()
{
/*global $wpdb, $bp;
if(constant('IS_BUDDYPRESS'))
{
   add_submenu_page( 'bp-general-settings', 'WP-invites', 'WP-invites', 8, "wp-invites", "invites_admin" );
}
if(constant('IS_WPMU'))
{
	if ( is_site_admin() )
		add_submenu_page( 'wpmu-admin.php', 'WP-invites', 'WP-invites', 8, "wp-invites", "invites_admin" );
}
else #same for buddypress and simple wordpress
	//add_submenu_page('plugins.php','WP-invites','WP-invites',8,"wp-invites",'invites_admin');
  {  */
	add_options_page(
		'WP-invites',
		'WP-invites',
		'manage_options',
		__FILE__,
		'invites_admin'
		);
  //}
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
	<td><input type="text"  disabled="disabled" class="regular-text ltr" value="<?php echo $code;?>"></td></tr></table><?php
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

$sql = 'DELETE FROM '.INVITES_PREFIX.'invites WHERE `value`=%s';
$res=$wpdb->query($wpdb->prepare($sql,invites_unbeautify($_SESSION['invite_code'])));
if($res===FALSE)
  echo $wpdb->last_error;

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