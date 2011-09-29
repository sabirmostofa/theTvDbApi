<?php
//echo phpinfo();
//set_time_limit(3500);
ini_set( 'allow_url_fopen',true);
ini_set('error_reporting', E_ALL);
require_once '../../../wp-load.php';
ini_set('error_reporting', E_ALL);
global $wpdb;
$channel_url = 'http://www.channelchooser.com/tv/mobile/channels/';
$channel_base_url = 'http://www.channelchooser.com';
$dom = new DOMDocument();
$dom ->loadHTMLFile($channel_url);
$cats = $dom ->getElementById('categories');

$paged_links = array();

//creating link cat
wp_insert_term('Mobile','link_category');
$term = get_term_by('name','Mobile','link_category');
$term_id = $term -> term_id;  


//Making all the page lists
foreach( $cats -> getElementsByTagName('a') as $node){
    $cat_url = $channel_base_url. $node -> getAttribute('href');
    $cat_name = $node -> nodeValue; 
    $dom ->loadHTMLFile($cat_url);
    $link_div = $dom ->getElementById('list');
    $num = $link_div ->getElementsByTagName('h4')->item(0)->nodeValue;
    if(preg_match('/\d+/', $num, $matches))
            $num = $matches[0];
    $pages = ceil($num/25);
    
    for($i = 0;$i <$pages;$i++){
        if($i ==0)$paged_links[$cat_name][]=$cat_url;
        else
            $paged_links[$cat_name][]=$cat_url.'/'.(25*$i);
        
    }
  
    
}

$count = 0;

foreach($paged_links as $key => $value):
    //creating link Categories
    $current_cat = get_term_by('name', $key, 'link_category');
    if(!$current_cat ){
        wp_insert_term($key,'link_category',array('parent '=> $term_id));
        $current_cat = get_term_by('name', $key, 'link_category');
        
    }
    foreach($value as $link){
        
        $dom ->loadHTMLFile($link);
        $link_div = $dom ->getElementById('list');
        foreach($link_div ->getElementsByTagName('a') as $a ){       
           $href = $a -> getAttribute('href');
           if(!exists_in_table($href)){
                $des = $a -> getAttribute('title');
                $title = $a -> nodeValue;
                $link_data = array(
                    'link_name' => $title,
                    'link_url' => $href,
                    'link_description' => $des,            
                    'link_target' => '_blank',
                    'link_visible' => 'Y',
                    'link_category' => $current_cat
                );
                $link_id = wp_insert_link($link_data, false);
                if($link_id){
                    $wpdb->query("update wp_links_extrainfo set link_no_follow='Y' where link_id = '$link_id'");
					//if(++$count == 10)exit;
                }
           }

        }
        

    }
endforeach;

function exists_in_table($link_url){
	global $wpdb;
	$link_url = mysql_real_escape_string($link_url);
	//$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$result = $wpdb->get_results( "SELECT link_id FROM wp_links where link_url='$link_url'" );
	if(empty($result))
		return false;

	return true;
        }

