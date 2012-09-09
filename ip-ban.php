<?php
/*
Plugin Name: Simple IP Ban
Plugin URI: http://www.sandorkovacs.ro/ip-ban-wordpress-plugin/
Description: Ban one or more Ip Address or User Agents
Author: Sandor Kovacs
Version: 1.0
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

    if ($_POST['submit']) {
        $ip_list      = $_POST['ip_list'];
        $ua_list      = $_POST['user_agent_list'];
        $redirect_url = $_POST['redirect_url'];

        update_option('s_ip_list',      $ip_list);
        update_option('s_ua_list',      $ua_list);
        update_option('s_redirect_url', $redirect_url);
    }

    $ip_list      = get_option('s_ip_list');
    $ua_list      = get_option('s_ua_list');
    $redirect_url = get_option('s_redirect_url');

?>

<div class="wrap" id='simple-ip-list'>
    <div class="icon32" id="icon-options-general"><br></div><h2><?php _e('Simple IP Ban'); ?></h2>

    <p>
        <?php _e('Add ip address or/and user agents in the textareas. Add only 1 item per line. 
        You may specify a redirect url; when a user from a banned ip/user agent access your site, 
        he will be redirected to the specified URL.' ) ?>
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
        <input type='submit' name='submit' value='<?php _e('Save') ?>' />
    </p>


    </form>

</div>

<?php

}



function simple_ip_ban() {
    $remote_ip = $_SERVER['REMOTE_ADDR'];
    $remote_ua = $_SERVER['HTTP_USER_AGENT'];
    if (s_check_ip_address($remote_ip, get_option('s_ip_list')) || 
        s_check_user_agent($remote_ua,get_option('s_ua_list'))) {
        $redirect_url = get_option('s_redirect_url');
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
    if (in_array($ip, $list_arr)) return true;


    return false;
}



function s_check_user_agent($ua, $ua_list) {
    $list_arr = explode("\r\n", $ua_list);
    if (in_array($ua, $list_arr)) return true;

    return false;
}
