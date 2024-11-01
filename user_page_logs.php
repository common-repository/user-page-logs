<?php
/**
 * Plugin Name: User Page Logs 
 * Plugin URI:  http://sandree.com
 * Description: Logging and Tracking your User visit History
 * Version: 1.0
 * Author: stefanus andree
 */


 
function tb_user_page_logs_uninstall() {
	
	
	global $wpdb;


	$table_name = $wpdb->prefix . 'user_page_logs';

    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);

	update_option( "tb_user_page_log_version", "0" );
	

	
}


function tb_user_page_logs_install() {
	
	
	global $wpdb;
	$tb_user_page_log_version= '1.0';
	$installed_ver = get_option( "tb_user_page_log_version" );

	if ( $installed_ver != $tb_user_page_log_version ) {

		$table_name = $wpdb->prefix . 'user_page_logs';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
		    user_id bigint(20) NOT NULL,
		    user_name varchar(150) NOT NULL,
		    post_id bigint(20) NOT NULL,
		    viewed_time bigint(20) NOT NULL,
			unique_id varchar(30) NOT NULL,
			PRIMARY KEY  (id)
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( "tb_user_page_log_version", $user_page_logs_tb_user_log_version );
	}
	
}
function tb_user_log_insert_data($table_data) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'user_page_logs';
	
	
	$last_post_id=0;
	$last_post_viewed_time=0;
	if(isset($_COOKIE["tb_user_page_log_insert_data_post_id"])) 
	{
		$last_post_id=intval($_COOKIE["tb_user_page_log_insert_data_post_id"]);
	}
	
	if(isset($_COOKIE["tb_user_page_log_insert_data_post_viewed_t"])) 
	{
		$last_post_viewed_time=intval($_COOKIE["tb_user_page_log_insert_data_post_viewed_t"]);	
	}
	$ok=1;	
	if(intval($table_data['post_id'])==$last_post_id)
	{
		$time_range=intval($table_data['viewed_time'])-$last_post_viewed_time;
		if($time_range<=60)
		{
			$ok=0;
		}
	}
	setcookie("tb_user_page_log_insert_data_post_id", $table_data['post_id'], time() + (3600), "/");
	setcookie("tb_user_page_log_insert_data_post_viewed_t", $table_data['viewed_time'], time() + (3600), "/");
	$ok=1;
	if($ok==1)
	{
		$wpdb->insert( 
			$table_name, 
			array( 
				'user_id' => $table_data['user_id'], 
				'user_name' => $table_data['user_name'], 
				'post_id' => $table_data['post_id'],
				'viewed_time' => $table_data['viewed_time'],
				'unique_id' => $table_data['unique_id']
			) 
		);
	}
	
	
	
	$unique_id=date('Ymd');
	$distinct_unique_id_count=0;
	$wp_user_page_logs = $wpdb->get_results( "SELECT distinct(unique_id) as distinct_unique_id FROM $table_name where user_id='".$table_data['user_id']."' and unique_id like '%".$unique_id."%'" );
	if($wp_user_page_logs)
	{
		foreach ( $wp_user_page_logs as $wp_user_page_log ) 
		{
			$distinct_unique_id_count +=1;
			$distinct_unique_id[$distinct_unique_id_count-1] =$wp_user_page_log->distinct_unique_id;
		}		
	}
	if($distinct_unique_id_count>0)
	{
		for($iv=0;$iv<=$distinct_unique_id_count-1;$iv++)
		{
			$distinct_post_id_count=0;
			$wp_user_page_logs = $wpdb->get_results( "SELECT distinct(post_id) as distinct_post_id FROM $table_name where user_id='".$table_data['user_id']."' and unique_id='".$distinct_unique_id[$iv]."'" );
			if($wp_user_page_logs)
			{
				foreach ( $wp_user_page_logs as $wp_user_page_log ) 
				{
					$distinct_post_id_count +=1;
					$distinct_post_id[$distinct_post_id_count-1] =$wp_user_page_log->distinct_post_id;
				}		
			}
			
			if($distinct_post_id_count>0)
			{
				for($iv2=0;$iv2<=$distinct_post_id_count-1;$iv2++)
				{
					$wp_user_page_logs = $wpdb->get_row( "SELECT count(unique_id) as total FROM $table_name where user_id='".$table_data['user_id']."' and unique_id='".$distinct_unique_id[$iv]."' and post_id='".$distinct_post_id[$iv2]."'" );
					$total_same_unique_id=0;
					if($wp_user_page_logs)
					{
						$total_same_unique_id=intval($wp_user_page_logs->total);
					}
					if($total_same_unique_id>1)
					{
						$wp_user_page_logs = $wpdb->get_row( "SELECT max(id) as max_id FROM $table_name where user_id='".$table_data['user_id']."' and unique_id='".$distinct_unique_id[$iv]."' and post_id='".$distinct_post_id[$iv2]."'" );
						$max_id_same_unique_id=0;
						if($wp_user_page_logs)
						{
							$max_id_same_unique_id=intval($wp_user_page_logs->max_id);
						}
						if($max_id_same_unique_id>0)
						{
							$sql = "delete from $table_name where user_id='".$table_data['user_id']."' and unique_id='".$distinct_unique_id[$iv]."' and post_id='".$distinct_post_id[$iv2]."' and id != '".$max_id_same_unique_id."'";
							$wpdb->query($sql);
						}
					}
				}		
				
			}
	
			
		}
	}
	
	
	
	
	
}



 
		

 
 
 
add_filter( 'the_content', 'cmn_restrict_post_filter_the_content' ,999);

function cmn_restrict_post_filter_the_content( $content ) {
 
    if ( (is_single() && is_singular('post')) || (is_page())) 
	{
		
			 if(is_user_logged_in())
			 {
				//logged in user
				$current_user = wp_get_current_user();					
				$table_data['user_id']=$current_user->ID; 
				$table_data['user_name']=$current_user->user_login; 
				$table_data['post_id']=get_the_ID();
				$table_data['viewed_time']=intval(strtotime("now"));
				$table_data['unique_id']=$current_user->ID.date('YmdHi');
				tb_user_log_insert_data($table_data);	
			}
			else
			{
				$current_user = wp_get_current_user();					
				$table_data['user_id']=0; 
				$table_data['user_name']='non logged in user'; 
				$table_data['post_id']=get_the_ID();
				$table_data['viewed_time']=intval(strtotime("now"));
				$table_data['unique_id']='0'.date('YmdHi');
				tb_user_log_insert_data($table_data);	
			}
	}
	
	
	 
    return $content;
	
}



function user_page_logs_after_login() {
	tb_user_page_logs_install();
	//tb_user_page_logs_uninstall();
	if((isset($_POST['action'])) && (isset($_POST['nonce'])))
	{	
		$action=sanitize_text_field($_POST['action']);
		if($action=='export_csv_user_logs')
		{
			if(wp_verify_nonce($_POST['nonce'], 'export_csv_user_logs'))
			{
				user_page_logs_export_csv();	
			}
			
		}	
	}
	
	

}
add_action('init','user_page_logs_after_login');


// Admin footer modification
  
function upl_admin_footer ($text) 
{
	if (strpos($_SERVER['REQUEST_URI'], 'menu-user_page_logs-handle') !== false) {
		$text .=' <span id="footer-thankyou">Developed by <a href="http://sandree.com" target="_blank">sandree.com</a></span>';
	}
	return $text;
}
 
add_filter('admin_footer_text', 'upl_admin_footer');

function user_page_logs_admin_handler() 
{
	
$action='';
if(isset($_POST['action']))
{
	$action=sanitize_text_field($_POST['action']);
}

if($action=='clear_user_logs')
{
	if(isset($_POST['nonce']))
	{
		if(wp_verify_nonce($_POST['nonce'], 'clear_user_logs'))
		{
			global $wpdb;

			$table_name = $wpdb->prefix . 'user_page_logs';
			$count = $wpdb->query("DELETE FROM $table_name");	
		}
	}
	
}

	
$sec="";
if(isset($_GET['sec']))
{
	$sec=sanitize_text_field($_GET['sec']);	
}




	echo "
	<style>
	#upl {
	  font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;
	  
	}
	#upl_header {
	  border-bottom: 1px solid #ccc;
	  
	}
	#upl_info_head {
	  border-bottom: 1px solid #eee;
	  font-size: 14px;
		padding: 8px 12px;
		margin: 0;
		line-height: 1.4;
	  
	}
	#upl_info_content {
	  border-bottom: 1px solid #eee;
	  font-size: 14px;
		padding: 8px 12px;
		margin: 0;
		line-height: 1.4;
	  
	}
	</style>";

		echo '<div id="upl">';

		$admin_url=admin_url();
		echo '<div id="upl_header">';
		echo '<h3> User Page Logs</h3>';
		if($sec=='')
		{
			echo '<a href="'.$admin_url.'admin.php?page=menu-user_page_logs-handle" class="button" style="background-color:white;margin-bottom:5px;">Dashboard</a>';
			echo '<a href="'.$admin_url.'admin.php?page=menu-user_page_logs-handle&sec=top" class="button">Top Active User</a>';
		}
		else if($sec=='top')
		{
			echo '<a href="'.$admin_url.'admin.php?page=menu-user_page_logs-handle" class="button">Dashboard</a>';
			echo '<a href="'.$admin_url.'admin.php?page=menu-user_page_logs-handle&sec=top" class="button" style="background-color:white;margin-bottom:5px;">Top Active User</a>';
		}
		echo '</div>';
		
	if($sec=='')
	{
		
		
		
		echo '<p>Current Time : '.date('d M Y H:i:s',strtotime("now")).'</p>';
		
		?>
		
		<div class='postbox'>
		<h4 id="upl_info_head"><span><b>Action List</b></span></h4>
		<div id="upl_info_content">
		
			<p>
			Clear all logs data from table. &nbsp;
			<button onclick="document.getElementById('id01').style.display='block'" class="w3-button w3-red">Clear Logs</button>
			</p>
			
			<p>
			
			<form method='post'>
					<input type="hidden" name="action" value="export_csv_user_logs">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('export_csv_user_logs'); ?>">
					Export all logs data from table to csv format and download it. &nbsp;
					
					<input type="submit" value="Export to CSV" class="w3-button w3-green ">
				</form>
			</p>
			
			
		</div>	
		</div>
		
		<div class='postbox'>
		<h4 id="upl_info_head"><span><b>Search User</b></span></h4>
		<div id="upl_info_content">
		
			<p>
			Search spesific user base on their username or their user email.
			</p>
			
			<p>
			
			<form id="upl_ul_user_search_form" method="post">
			<input type="hidden" name="action" value="upl_ul_user_search" />
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('upl_ul_user_search'); ?>">
			<input type="text" name="upl_ul_user_search" />
			<input type="submit" name="Submit" value="Search" class='button' />&nbsp;&nbsp;&nbsp;<span style="color:red;">Please use the entire username or email address for search</span>
			</form>
			</p>
			
			
		</div>	
		</div>
		
		
		
		

	  <div id="id01" class="w3-modal" style="padding-top:300px !important;">
		<div class="w3-modal-content">
		  <div class="w3-container">
			<span onclick="document.getElementById('id01').style.display='none'" class="w3-button w3-display-topright">&times;</span>
		   
			<form method='post'>
				<table>
				<tr>
				</tr>
				<tr>
					<td >
					<input type="hidden" name="action" value="clear_user_logs">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('clear_user_logs'); ?>">
					   Are you sure? If you clear logs, all logs will be removed.<br><br><input type="submit" value="Clear Logs" class="w3-button w3-red">
					   <br>
					   <br>
					</td>   
					
				</tr>

				  
				</table>
				</form>
		  </div>
		</div>
	  </div>
	  
		<div class='postbox'>
		<h4 id="upl_info_head"><span><b>Table Data</b></span></h4>
		<div id="upl_info_content">
		<p> Click on table header (ID or User name) to sort the data by (ID or User name).</p>
		<?php
		
		
		$ul_page=1;
		$ul_page_limit=100;
		if(isset($_GET['ul_page']))
		{
			$ul_page=intval(sanitize_text_field($_GET['ul_page']));
		}
		$ul_ob="ID_desc";
		if(isset($_GET['ob']))
		{
			$ul_ob=sanitize_text_field($_GET['ob']);
		}
		$upl_ul_user_search="";
		if((isset($_POST['upl_ul_user_search'])) && (isset($_POST['nonce'])))
		{
			if(wp_verify_nonce($_POST['nonce'], 'upl_ul_user_search'))
			{
				$upl_ul_user_search=sanitize_text_field($_POST['upl_ul_user_search']);	
			}
			
		}
		
		if(isset($_GET['ul_us']))
		{
			$upl_ul_user_search=sanitize_text_field($_GET['ul_us']);
		}
		
		
		
		$ul_first_index=($ul_page_limit*($ul_page-1));
		
		$total_ul=0;
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'user_page_logs';
		
		if(strlen($upl_ul_user_search)>0)
		{
			if (strpos($upl_ul_user_search, '@') !== false) {
				
				$user_search = get_user_by( 'email', $upl_ul_user_search );
				$user_search_ID="0";
				if($user_search)
				{
					$user_search_ID=$user_search->ID;
				}
				$wp_user_page_logs = $wpdb->get_row( "SELECT count(id) as total FROM $table_name where user_id='".$user_search_ID."'" );
				
			}
			else
			{
				$wp_user_page_logs = $wpdb->get_row( "SELECT count(id) as total FROM $table_name where user_name='".$upl_ul_user_search."'" );
			}
		}
		else
		{
			$wp_user_page_logs = $wpdb->get_row( "SELECT count(id) as total FROM $table_name" );
		}
		
		
		

		if($wp_user_page_logs)
		{
			$total_ul=intval($wp_user_page_logs->total);
		}
		

		$ul_max_page=ceil($total_ul/$ul_page_limit);
		
		$admin_url=admin_url();
		
		$link_by_id ="<a href='".$admin_url."admin.php?page=menu-user_page_logs-handle&sec=ul&ob=ID_desc"."&ul_page=".$ul_page."'>ID</a> ";
		$link_by_name ="<a href='".$admin_url."admin.php?page=menu-user_page_logs-handle&sec=ul&ob=un_asc"."&ul_page=".$ul_page."'>User Name</a> ";
		$ul_order="id desc";
		if($ul_ob=="ID_desc")
		{
			$link_by_id ="<a href='".$admin_url."admin.php?page=menu-user_page_logs-handle&sec=ul&ob=ID_asc"."&ul_page=".$ul_page."'>ID</a> ";
			$ul_order="id desc";
		}
		else if($ul_ob=="un_asc")
		{
			$link_by_name ="<a href='".$admin_url."admin.php?page=menu-user_page_logs-handle&sec=ul&ob=un_desc"."&ul_page=".$ul_page."'>User Name</a> ";
			$ul_order="user_name asc";
		}
		else if($ul_ob=="un_desc")
		{
			$ul_order="user_name desc";
		}
		else if($ul_ob=="ID_asc")
		{
			$ul_order="id asc";
		}
		
		
		
		
		$table_ul="
		<style>
		#tb_us {
		  font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;
		  border-collapse: collapse;
		  width: 80%;
		  font-size: 14px;
		  background-color: #ffffff;
		}

		#tb_us td, #tb_us th {
		  border: 1px solid #ddd;
		  padding: 8px;
		  background-color: #ffffff;
		}

		#tb_us tr:nth-child(even){background-color: #f2f2f2;}

		#tb_us tr:hover {background-color: #ddd;}

		#tb_us th {
		  padding-top: 12px;
		  padding-bottom: 12px;
		  text-align: left;
		  background-color: #4CAF50;
		  color: white;
		}


		</style>"."<table id='tb_us'>";	
		$table_ul .="<tr>";
		$table_ul .="<th>$link_by_id</th>";
		$table_ul .="<th>User ID</th>";
		$table_ul .="<th>$link_by_name</th>";
		$table_ul .="<th>User Email</th>";
		$table_ul .="<th>Post</th>";
		$table_ul .="<th>Viewed Time (GMT)</th>";
		$table_ul .="</tr>";
		
		
		$pagination="";
		for($ii=1;$ii<=$ul_max_page;$ii++)
		{
			if(strlen($upl_ul_user_search)>0)
			{
				$pagination .="<a href='".$admin_url."admin.php?page=menu-user_page_logs-handle&sec=ul"."&ul_us=".$upl_ul_user_search."&ul_page=".$ii."'>".$ii."</a> ";
			}
			else
			{
				$pagination .="<a href='".$admin_url."admin.php?page=menu-user_page_logs-handle&sec=ul"."&ul_page=".$ii."'>".$ii."</a> ";	
			}
			
		}
		
		$table_ul .="<tr>";
		$table_ul .="<td colspan=6>"."Current Page : $ul_page   "."Page : ".$pagination." "."</td>";
		$table_ul .="</tr>";
		if(strlen($upl_ul_user_search)>0)
		{
			if (strpos($upl_ul_user_search, '@') !== false) {
				
				$user_search = get_user_by( 'email', $upl_ul_user_search );
				$user_search_ID="0";
				if($user_search)
				{
					$user_search_ID=$user_search->ID;
				}
					$wp_user_page_logs = $wpdb->get_results( "SELECT * FROM $table_name where user_id='".$user_search_ID."'" );	
				
				
			}
			else
			{
				$wp_user_page_logs = $wpdb->get_results( "SELECT * FROM $table_name where user_name='".$upl_ul_user_search."'" );
			}
		}
		else
		{
			$wp_user_page_logs = $wpdb->get_results( "SELECT * FROM $table_name order by $ul_order limit $ul_first_index,$ul_page_limit" );	
		}
		
		
		
		if($wp_user_page_logs)
		{
			foreach ( $wp_user_page_logs as $wp_user_page_log ) 
			{
				$p_permalink=get_permalink($wp_user_page_log->post_id);
				$p_title=get_the_title($wp_user_page_log->post_id);
				$table_ul .="<tr>";
					$table_ul .="<td>".$wp_user_page_log->id."</td>";
					$table_ul .="<td>".$wp_user_page_log->user_id."</td>";
					$table_ul .="<td>".$wp_user_page_log->user_name."</td>";
					$user_info = get_userdata($wp_user_page_log->user_id);
					$user_emaill="-";
					if($user_info)
					{
						$user_emaill=$user_info->user_email;	
					}
					$table_ul .="<td>".$user_emaill."</td>";
					$table_ul .="<td>ID : ".$wp_user_page_log->post_id.
					"<br>Link : <a href='".$p_permalink."'>".$p_permalink."</a>".
					"<br>Title : <a href='".$p_permalink."'>".$p_title."</a>".
					"</td>";
					$table_ul .="<td>".date('d M Y H:i:s',$wp_user_page_log->viewed_time)."</td>";
				$table_ul .="</tr>";
			}

					
		}
		
		
		$table_ul .="</table>";	

		echo $table_ul;
		echo "</div>";
		echo "</div>";

	}
	else if($sec=='top')
	{
		
		
		?>
		
		
		<div class='postbox'>
		<h4 id="upl_info_head"><span><b>Top Active Users</b></span></h4>
		<div id="upl_info_content">
		<p> Here is list of your top active users.</p>
		<?php
		
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'user_page_logs';
		
		$wp_user_page_logs = $wpdb->get_results( "SELECT distinct(user_id) as distinct_user_id FROM $table_name" );	
		
		$total_distinct_user=0;
		if($wp_user_page_logs)
		{
			foreach ( $wp_user_page_logs as $wp_user_page_log ) 
			{
				$total_distinct_user +=1;
				$distinct_user_id[$total_distinct_user-1] =$wp_user_page_log->distinct_user_id;
				$distinct_user_view[$distinct_user_id[$total_distinct_user-1]] =0;
			}
		}
		if($total_distinct_user>0)
		{
			for($i=0;$i<=$total_distinct_user-1;$i++)
			{
				
				$wp_user_page_logs = $wpdb->get_row( "SELECT count(id) as total FROM $table_name where user_id='".$distinct_user_id[$i]."'" );
				if($wp_user_page_logs)
				{
					$distinct_user_view[$distinct_user_id[$i]] =$wp_user_page_logs->total;
				}
			}
			arsort($distinct_user_view);
		}
		
		
		
		
		$table_ul="
		<style>
		#tb_us {
		  font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;
		  border-collapse: collapse;
		  width: 80%;
		  font-size: 14px;
		  background-color: #ffffff;
		}

		#tb_us td, #tb_us th {
		  border: 1px solid #ddd;
		  padding: 8px;
		  background-color: #ffffff;
		}

		#tb_us tr:nth-child(even){background-color: #f2f2f2;}

		#tb_us tr:hover {background-color: #ddd;}

		#tb_us th {
		  padding-top: 12px;
		  padding-bottom: 12px;
		  text-align: left;
		  background-color: #4CAF50;
		  color: white;
		}


		</style>"."<table id='tb_us'>";	
		$table_ul .="<tr>";
		$table_ul .="<th>User ID</th>";
		$table_ul .="<th>User name</th>";
		$table_ul .="<th>User Email</th>";
		$table_ul .="<th>Total View</th>";
		$table_ul .="</tr>";
		
		if($total_distinct_user>0)
		{
			$top_user=0;
			$max_top_user=20;
			foreach($distinct_user_view as $x_user_id => $x_user_view) {
				//echo "Key=" . $x_user_id . ", Value=" . $x_user_view;
				$top_user +=1;
				$table_ul .="<tr>";
					$table_ul .="<td>".$x_user_id."</td>";
					$user_emaill="-";
					$user_namee="-";
					if($x_user_id==0)
					{
						$user_namee="non logged in user";
					}
					else if($x_user_id>0)
					{
						$user_info = get_userdata($x_user_id);
						if($user_info)
						{
							$user_emaill=$user_info->user_email;
							$user_namee=$user_info->user_login;		
						}	
					}
					$table_ul .="<td>".$user_namee."</td>";
					$table_ul .="<td>".$user_emaill."</td>";
					$table_ul .="<td>".$x_user_view."</td>";
				$table_ul .="</tr>";
				if($top_user>=$max_top_user)
				{
					break;
				}
				
			}
		}
		
		$table_ul .="</table>";	

		echo $table_ul;
		echo "</div>";
		echo "</div>";
		
		$distinct_user_id=null;
		$distinct_user_view=null;	
	}
	echo "</div>";
	



}

function user_page_logs_export_csv()
{

	$separation="|::|";
	$data[0]='Log ID'.$separation.'User ID'.$separation.'User Name'.$separation.'User Email'.$separation.'Post ID'.$separation.'Post Link'.$separation.'Post Title'.$separation.'Viewed Time';
	$tempDataTotal=0;


	date_default_timezone_set('Asia/Shanghai');
		
	global $wpdb;

	$table_name = $wpdb->prefix . 'user_page_logs';
	$wp_user_page_logs = $wpdb->get_results( "SELECT * FROM $table_name order by id desc" );
	if($wp_user_page_logs)
	{
		foreach ( $wp_user_page_logs as $wp_user_page_log ) 
		{
			$tempDataTotal +=1;
			$p_permalink=get_permalink($wp_user_page_log->post_id);
			$p_title=get_the_title($wp_user_page_log->post_id);
			
			$user_info = get_userdata($wp_user_page_log->user_id);
			$user_emaill="";
			if($user_info)
			{
				$user_emaill=$user_info->user_email;	
			}
			
			
			$data[$tempDataTotal]=$wp_user_page_log->id.$separation.$wp_user_page_log->user_id.$separation.$wp_user_page_log->user_name.$separation.$user_emaill.
			$separation.$wp_user_page_log->post_id.$separation. $p_permalink.$separation. $p_title.
			$separation. date('d M Y H:i:s',$wp_user_page_log->viewed_time);
		}
	}
	
	
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="User Page Logs.csv"');
	

	$fp = fopen('php://output', 'wb');
	foreach ( $data as $line ) {
		$val = explode($separation, $line);
		fputcsv($fp, $val);
	}
	fclose($fp);

	exit;

}


add_action('admin_enqueue_scripts', 'upl_reg_css_and_js');

function upl_reg_css_and_js($hook)
{
	$page_viewed = basename($_SERVER['REQUEST_URI']);
				
	if (strpos($page_viewed, 'page=menu-user_page_logs-handle') !== false) 
	{
		wp_enqueue_style( 'myCSS1', plugins_url( '/upl.css', __FILE__ ) );
	}
}
	

require_once(dirname(__FILE__).'/user_page_logs_setting.php');
	
?>