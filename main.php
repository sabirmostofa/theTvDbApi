<?php
/*
Plugin Name: WP-TheTvDbImport
Plugin URI: http://sabirul-mostofa.blogspot.com
Description: Import and show contents from thetvdb
Version: 1.0
Author: Sabirul Mostofa
Author URI: http://sabirul-mostofa.blogspot.com
*/


$wptheTvDbApi = new wptheTvDbApi();


class wptheTvDbApi{
	
	public $api_key = '24E39EC3326CD7DE';
	public $mirror_url = '';
	public $latest_url ='http://www.thetvdb.com/api/Updates.php?type=all&time=';
	public $mirror_path = 'http://thetvdb.com';
	public $lang = 'en';
	public $series_zip_url ='';
	public $eps_xml_url = '';
	public $banner_url =''; 
	public $self_dir='';
	public $processed_ids = array();
	public $info= array('id','Actors','ContentRating','FirstAired','Genre','IMDB_ID','Network','Overview','Rating','Runtime','SeriesName','Status','banner','fanart','poster');

	
	function __construct(){
		$this -> build_vars();
		add_action('wp_print_styles' , array($this,'front_css'));
		add_action('init', array($this,'add_post_type'));
		add_action('init', array($this,'return_image'));
		add_action('init', array($this,'edit_query'));
		add_action('thetvdb_cron',array($this,'start_cron'));
		register_activation_hook(__FILE__, array($this, 'create_table'));
		//register_activation_hook(__FILE__, array($this, 'init_cron'));
		register_deactivation_hook(__FILE__, array($this,'disable_cron'));
			
	}
	
	function build_vars(){
		$this -> mirror_url = "http://www.thetvdb.com/api/{$this->api_key}/mirrors.xml";
		$time = ($a=get_option('thetvdb_last_cron'))? $a : time()-(15*3600*24);	
		$this -> latest_url = $this ->latest_url.$time ; 
		$this -> series_zip_url = "{$this->mirror_path}/api/{$this->api_key}/series/<seriesid>/all/{$this->lang}.zip"; 	
		$this -> eps_xml_url = "{$this->mirror_path}/api/{$this->api_key}/episodes/<episodeid>/{$this->lang}.xml"; 	
		$this -> banner_url = "{$this->mirror_path}/banners/<filename>";
		$this -> self_dir = dirname(__FILE__);
	}
	function front_css(){
		if(!(is_admin())):
		wp_enqueue_style('kt_front_css', plugins_url('/' , __FILE__).'css/style_front.css');
		endif;
	}
		
	
	function add_post_type(){
			register_post_type( 'series',
				array(
					'labels' => array(
						'name' => __( 'Series' ),
						'singular_name' => __( 'Series' )
					),
				'public' => true,
				'has_archive' => true,
				)
			);
		
	}
	function init_cron(){		
		if(!wp_get_schedule('thetvdb_cron'))
			wp_schedule_event(time(), 'hourly', 'thetvdb_cron');
	}
	
	function edit_query(){
		if( isset($_REQUEST['search-tvdbseries']) && stripos($_SERVER['REQUEST_URI'], '+') !==false){
			$q_string = preg_replace( '/\+/','-',$_SERVER['REQUEST_URI'] );
			header("Location: ".$q_string);
			exit;
		}
	}
	
	function disable_cron(){
		wp_clear_scheduled_hook('thetvdb_cron');
		
		}
	function start_cron(){
		global $wpdb;
		/*
		if( defined('DOING_CRON') && DOING_CRON == true )
			set_time_limit(3500);
			*/
		ini_set('error_reporting', E_ALL);
		$this -> check_and_remove();
		
		$prev_series_ids = $wpdb->get_col("SELECT series_id from wp_thetvdb_series");
		
		//exit;
		
		$all_series = $this->get_latest_series();
		foreach( $all_series as $series ):
			if( !$this-> exists_in_table($series) ){
				$url = str_replace('<seriesid>', $series, $this -> series_zip_url);
				$this-> handle_zip($url);
				mysql_ping();
				if($this -> extract_process_data($series))				
					$wpdb -> query("insert into wp_thetvdb_series (series_id) values('$series') ");
			}		
			
		endforeach;
		//$this -> keep_hundred();
		$this -> end_cron_func();
		$final_series_ids = $wpdb->get_col("SELECT series_id from wp_thetvdb_series");
		echo "<br/>Previous Posts Count: ",count($prev_series_ids),"<br/> Final Post Count: ", count($final_series_ids),"<br/>New Posts: ",count($final_series_ids) - count($prev_series_ids);
		return $all_series;
		
	}
	
	function end_cron_func(){
		update_option('thetvdb_last_cron',time());		
	}
	
	function check_and_remove(){
		global $wpdb;
		$upload_dir = wp_upload_dir();
		
		$series_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'series'");
		foreach($series_ids as $series):
			if($meta = get_post_meta($series,'series_meta')):			
				extract( $meta[0] );
				$dir = $upload_dir['basedir'].'/'.$id;
				if( !is_file($dir.'/banner.jpg') || !is_file($dir.'/poster.jpg') || strlen($Overview) == 0 ){
					wp_delete_post($series,true);
					$wpdb->query("DELETE FROM wp_thetvdb_series where series_id='$id'");
					if( is_file($dir.'/banner.jpg') )unlink($dir.'/banner.jpg');
					if( is_file($dir.'/poster.jpg') )unlink($dir.'/poster.jpg');
					if( is_file($dir.'/fanart.jpg') )unlink($dir.'/fanart.jpg');
					rmdir($dir);
				}
			endif;
		endforeach;
	}
	function remove_all(){
		global $wpdb;
		$upload_dir = wp_upload_dir();
		
		$series_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'series'");
		foreach($series_ids as $series):	
					wp_delete_post($series,true);	
		endforeach;		
	
	}
	
	function keep_hundred(){
		global $wpdb;
		$upload_dir = wp_upload_dir();		
		$series_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'series' and post_status = 'publish'");
		var_dump($series_ids);
		if(count($series_ids) > 100){
			$num = count($series_ids);
			$keep_it = array_slice($series_ids,$num -100);
			$delete_it = array_diff($series_ids, $keep_it);
			foreach($delete_it as $del):
				if($meta = get_post_meta($del,'series_meta')):			
					extract( $meta[0] );
					$dir = $upload_dir['basedir'].'/'.$id;
					wp_delete_post($del,true);
					if( is_file($dir.'/banner.jpg') )unlink($dir.'/banner.jpg');
					if( is_file($dir.'/poster.jpg') )unlink($dir.'/poster.jpg');
					if( is_file($dir.'/fanart.jpg') )unlink($dir.'/fanart.jpg');
					rmdir($dir);
					
				endif;
				
			endforeach;
			
			
		}
	}
	
	
	function extract_process_data($series){
		$check = false;
		$all_info = array();
		$dom = $this-> return_dom( $this -> self_dir.'/tmp/en.xml' );
		$all_ids = $this -> return_nodes($dom, 'id');
		$to_process = $all_ids[0];
		if( $to_process == $series ){
			
			$all_info = $this -> get_info($dom);
			if( $all_info['Status'] != 'Ended' && !$this->exists_in_table_posts(sanitize_title($all_info['SeriesName'])) && strlen($all_info['Overview']) > 100 && strlen($all_info['banner']) > 4 && strlen($all_info['poster']) > 4  ) 
				{
					$this -> save_as_post($all_info);
					$check = true;
				}
			$this -> processed_ids[] = $to_process;
		}
		return $check;
	}
	
	function save_as_post($info){
		//array_walk($info, create_function('&$value,$key','$value = mysql_real_escape_string($value);'));
		extract($info);
		$my_post = array(
		 'post_title' => $SeriesName,
		 'post_content' => $Overview ,
		 'post_type' =>'series' ,
		 'post_status' => 'publish',
		 'post_author' => 1,
		 'post_excerpt' => substr( $Overview,0,50 )
		);

       // Insert the post into the database
		$res=wp_insert_post( $my_post );
		if($res)
			add_post_meta($res,'series_meta',$info);
		$this->upload_image($id,array('banner' => $banner, 'poster' => $poster))	;
			
	}
	
	function upload_image($id,$files){
	$upload_dir = wp_upload_dir();
	$dir = $upload_dir['basedir'].'/'.$id;
	if(!is_dir($dir))mkdir($dir);
		foreach($files as $name => $file){
			if(!is_file($dir.'/'.$name)){
				$url = str_replace('<filename>',$file,$this->banner_url);
				$data = file_get_contents($url);
				file_put_contents( $dir.'/'.$name.$this -> return_file_ext($file) ,$data );
			}
		}
		
	}
	
	function return_file_ext($loc){
		if( preg_match('/\..*/',$loc,$match))
			return $match[0];
		
	}
	
	
	function get_info($dom){
		$all_info = array();
		foreach($this -> info as $val){
			$vals = $this -> return_nodes($dom, $val);
			$all_info[$val] = $vals[0];			
		}
		return $all_info;
	}
	
	function get_mirror(){
		$doc = $this -> return_dom ($this->mirror_url);		
		foreach($doc->getElementsByTagName('mirrorpath') as $item)
		 var_dump($item->nodeValue);	
	}
	
	function get_latest_series(){
		$all_series = array();		
		$doc = $this -> return_dom ( $this->latest_url);
		$all_series = $this ->return_nodes($doc, 'Series');		
		return array_unique ( $all_series );
			
		
	}
	
	function return_dom($url){
		$content = file_get_contents($url);	
		$doc = new DOMDocument();
		$doc ->loadXML($content);
		return $doc;		
	}
	
	function return_nodes($doc,$name){
		$items = array();
		foreach($doc->getElementsByTagName($name) as $item)
			$items[] = $item -> nodeValue;
		return $items;		
	}
	
	function get_time(){
		
	}
	
	// Unzip the zipped file and store at tmp 
	
	function handle_zip($remote_file){
			$dir = $this->self_dir .'/tmp';
			$ch = curl_init($remote_file);			
			if(!is_dir($dir))mkdir($dir);
			
			$fh = fopen("{$dir}/series.zip", "w");
			curl_setopt($ch, CURLOPT_FILE, $fh);
			curl_exec($ch);
			curl_close($ch);
			
			$this ->ezip($dir.'/series.zip' , $dir );
	
	}
	
	
	
	function ezip($zip, $hedef = ''){
       $root='';
        $zip = zip_open($zip);
        while($zip_icerik = zip_read($zip)):
            $zip_dosya = zip_entry_name($zip_icerik);
            if(strpos($zip_dosya, '.')):
                $hedef_yol = $root . $hedef .'/'.$zip_dosya;
                touch($hedef_yol);
                $yeni_dosya = fopen($hedef_yol, 'w+');
                $size = zip_entry_filesize($zip_icerik);
                fwrite($yeni_dosya, zip_entry_read($zip_icerik,$size));
                fclose($yeni_dosya); 
            else:
                @mkdir($root . $hedef .'/'.$zip_dosya);
            endif;
        endwhile;
    }
    
    function create_table(){
		$sql = "CREATE TABLE IF NOT EXISTS `wp_thetvdb_series` (
		`id` int unsigned NOT NULL AUTO_INCREMENT, 
		`series_id` int unsigned  NOT NULL,
		PRIMARY KEY (`id`),
		key `series`(`series_id`)	
		)";
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);
	}
	function return_image(){
		if( isset($_GET['get_image']) && isset($_GET['poster_id']) ){
			$id = $_REQUEST['poster_id'];
			$di = wp_upload_dir();
			$dir = $di['basedir']."/$id";
			$img = imagecreatefromjpeg($dir.'/poster.jpg');
			$width = imagesx( $img );
			$height = imagesy( $img );


			$newwidth =136;
			$newheight =200;

			// Create a new temporary image.
			$tmpimg = imagecreatetruecolor( $newwidth, $newheight );

			// Copy and resize old image into new image.
			imagecopyresampled( $tmpimg, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );

			// Save thumbnail into a file.
			header('Content-Type: image/jpeg');
			imagejpeg( $tmpimg);

			// release the memory
			imagedestroy($tmpimg);
			imagedestroy($img);
			exit;
		}
	}
	function get_query(){
		global $wpdb;
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

		$q_array_base = array(
		 'post_type' => 'series',
		 'post_status' => 'publish',
		 'posts_per_page' => 10, 
		 'paged' => $paged 
		);
		$my_query = new WP_Query($q_array_base);

		if( isset($_REQUEST['tvdb-search']) ){
			$s = str_replace('-',' ', $_REQUEST['search-tvdbseries']);
			if(preg_match('/\S/',$s)){
				$my_query -> posts = array();
				
				$my_query = new WP_Query( array_merge($q_array_base, array('s' => $s, 'posts_per_page' => -1, 'paged'=>$paged )) );
				$found_posts = array();
				foreach( $my_query -> posts as $single)
					$found_posts[] = $single -> ID;
			
				$series_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'series' and post_status = 'publish'");
				
				$to_query = array_diff($series_ids, $found_posts);
				$posts_to_add= array();
				foreach($to_query as $id){
						$meta = get_post_meta($id, 'series_meta');
						foreach( $meta[0] as $each_meta)
							if(stripos($each_meta, $s) !== false)						
								$posts_to_add[] = $id;
								
				}
				$posts_to_add = array_unique($posts_to_add);
				if(! empty($posts_to_add))
					$new_query= new WP_Query( array('post__in' => $posts_to_add, 'posts_per_page' => -1 , 'paged'=>'') );
				else 
					$new_query = new WP_Query(array('post_type' => 'random_invalid_post_type'));
				$all_posts = array_merge($new_query -> posts, $my_query -> posts);				
				$my_query -> post_count = 10;
				$my_query -> found_posts = count($all_posts);//count($found_posts) + count($posts_to_add);
				$my_query -> max_num_pages = ceil($my_query -> found_posts /10);
				$my_query -> posts = array_slice($all_posts,($paged-1)*10,10);
				$my_query -> s = $s;
				
			}	

		}
	return $my_query;
		
	}
	
	function exists_in_table($id){
	global $wpdb;
	//$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$result = $wpdb->get_results( "SELECT id FROM wp_thetvdb_series where series_id='$id'" );
	if(empty($result))
		return false;

	return true;
	}
	
	function exists_in_table_posts($name){
	global $wpdb;
	//$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$query = "SELECT post_name FROM {$wpdb->prefix}posts where post_name='$name'";
	$result = $wpdb->get_results( $query );
	if(empty($result))
		return false;

	return true;
	}


}
