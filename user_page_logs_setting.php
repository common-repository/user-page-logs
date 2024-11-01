<?php


if ( ! class_exists( 'user_page_logs_setting' ) ) :


class  user_page_logs_setting
{

	function  user_page_logs_setting()
	{
		if (isset($this))
		{

			add_action('admin_menu', array(&$this, 'admin_menu'));

		}
	}
	function admin_menu()
	{
		if (function_exists('add_menu_page')) {
			$menu = add_menu_page(__('User Page Logs','menu-user_page_logs'), __('User Page Logs','menu-user_page_logs2'), 'manage_options',
			'menu-user_page_logs-handle', 'user_page_logs_admin_handler' );
			

	
			
		}
		
	}
	

}

endif;

$user_page_logs_setting = new  user_page_logs_setting();


?>