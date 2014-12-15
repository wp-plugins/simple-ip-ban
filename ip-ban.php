<?php
/*
Plugin Name: Simple IP Ban
Plugin URI: http://www.sandorkovacs.ro/ip-ban-wordpress-plugin/
Description: Ban one or more Ip Address or User Agents. Also you may add an IP RANGE to iplist ex: 82.11.22.100-82.11.22-177
Author: Sandor Kovacs
Version: 1.2.5
Author URI: http://sandorkovacs.ro/
*/

// Do the magic stuff
add_action( 'plugins_loaded', 'simple_ip_ban' );

add_action( 'admin_init', 'simple_ip_ban_init' );
add_action('admin_menu', 'register_simple_ip_ban_submenu_page');
   
function simple_ip_ban_init() {
   /* Register our stylesheet. */
   wp_register_style( 'ip-ban', plugins_url('ip-ban.css', __FILE__) );
   wp_enqueue_style('ip-ban');
}

function register_simple_ip_ban_submenu_page() {
    add_submenu_page( 
        'options-general.php', __('Simple IP Ban'), __('Simple IP Ban'), 
        'manage_options', 
        'simple-ip-ban', 
        'simple_ip_ban_callback' ); 
}

function simple_ip_ban_callback() {

    // By Default activate do not redirect for logged in users
    if (!get_option('s_not_for_logged_in_user'))    update_option('s_not_for_logged_in_user', 1);

    // form submit  and save values
    if (isset( $_POST['_wpprotect'] ) 
        && wp_verify_nonce( $_POST['_wpprotect'], 'ipbanlist' ) ) {
        $ip_list                = wp_kses($_POST['ip_list'], array());
        $ua_list                = wp_kses($_POST['user_agent_list'], array());
        $redirect_url           = sanitize_text_field($_POST['redirect_url']);
        $not_for_logged_in_user = sanitize_text_field($_POST['not_for_logged_in_user']);

        update_option('s_ip_list',                $ip_list);
        update_option('s_ua_list',                $ua_list);
        update_option('s_redirect_url',           $redirect_url);
        update_option('s_not_for_logged_in_user', $not_for_logged_in_user);
    }

    // read values from option table

    $ip_list      = get_option('s_ip_list');
    $ua_list      = get_option('s_ua_list');
    $redirect_url = get_option('s_redirect_url');
    $not_for_logged_in_user = (intval(get_option('s_not_for_logged_in_user')) == 1 ) ? 1 : 0;


?>

<div class="wrap" id='simple-ip-list'>
    <div class="icon32" id="icon-options-general"><br></div><h2><?php _e('Simple IP Ban'); ?></h2>

    <p>
        <?php _e('Add ip address or/and user agents in the textareas. Add only 1 item per line. 
        You may specify a redirect url; when a user from a banned ip/user agent access your site, 
        he will be redirected to the specified URL.' ) ?>
    </p>    

    <p>
        <?php _e('or add an IP RANGE, ex:  <strong>82.11.22.100-82.11.22-177</strong>' ) ?>
    </p>

    <form action="" method="post">

    <p>
    <label for='ip-list'><?php _e('IP List'); ?></label> <br/>
    <textarea name='ip_list' id='ip-list'><?php echo $ip_list ?></textarea>
    <p>

    <p>
    <label for='user-agent-list'><?php _e('User Agent List'); ?></label> <br/>
    <textarea name='user_agent_list' id='user-agent-list'><?php echo $ua_list ?></textarea>
    <p>

    <p>
    <label for='redirect-url'><?php _e('Redirect URL'); ?></label> <br/>
    <input  type='url' name='redirect_url' id='redirect-url' 
            value='<?php echo $redirect_url; ?>' 
            placeholder='<?php _e('Enter a valid URL') ?>' />
    <p>
    <p>
    <label for='not-for-logged-in-user'><?php _e('Do Not Redirect for Logged In User'); ?></label> <br/>
    <input  type='checkbox' name='not_for_logged_in_user' id='not-for-logged-in-user' 
            value='1' 
            <?php echo ($not_for_logged_in_user == 1 )  ? " checked='checked'" : "" ?>
             />
             <br/>
             <small><?php _e('If this box is checked the IP BAN will be disabled for logged in users.') ?></small>
    <p>

    <?php wp_nonce_field('ipbanlist', '_wpprotect') ?>

    <p>
        <input type='submit' name='submit' value='<?php _e('Save') ?>' />
    </p>


    </form>

</div>

<?php

}



function simple_ip_ban() {

    // Do nothing for admin user
    if ((is_user_logged_in() && is_admin()) ||
        (intval(get_option('s_not_for_logged_in_user')) == 1  && is_user_logged_in())) return '';




    $remote_ip = $_SERVER['REMOTE_ADDR'];
    $remote_ua = $_SERVER['HTTP_USER_AGENT'];
    if (s_check_ip_address($remote_ip, get_option('s_ip_list')) || 
        s_check_user_agent($remote_ua,get_option('s_ua_list'))) {
        $redirect_url = get_option('s_redirect_url');
	if ( simple_ip_ban_get_current_url() == $redirect_url ) return '';  //suggested by umchal

        wp_redirect( $redirect_url );
        exit;
    }
}

/**
 * Check for a given ip address. 
 *
 * @param: string $ip The ip adddress
 * @param: string $ip_list The list with the banned ip addresss
 *
 * @return: boolean If founded it will return true, otherwise false
 **/

function s_check_ip_address($ip, $ip_list) {
    
    $list_arr = explode("\r\n", $ip_list);

    // Check for exact IP
    if (in_array($ip, $list_arr)) return true;

    // Check in IP range
    foreach ($list_arr as $k => $v) {
        if (substr_count($v, '-')) {
            // It's an ip range
            $curr_ip_range = explode('-', $v);
            if (ip2long($ip) >= ip2long(trim($curr_ip_range[0])) && 
                ip2long($ip) <= ip2long(trim($curr_ip_range[1] ))) return true;
        }   
    }

    return false;
}



function s_check_user_agent($ua, $ua_list) {
    $list_arr = explode("\r\n", $ua_list);
    if (in_array($ua, $list_arr)) return true;

    return false;
}


// Suggested solution by umchal
// Support link: http://wordpress.org/support/topic/too-many-redirects-22

function simple_ip_ban_get_current_url() {
	$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80")
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	}
	else
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}
