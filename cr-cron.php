<?php
chdir(dirname(__FILE__));
ini_set('error_reporting', E_ALL);
ignore_user_abort(true);
set_time_limit(3500);
require_once '../../../wp-load.php';
global $wpdb,$wptheTvDbApi;

$series_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'series' and post_status = 'publish'");
wp_insert_term('TvDbSeries','category');
$term = get_term_by('name','TvDbSeries','category');
$term_id = $term -> term_id;
foreach($series_ids as $id){
wp_set_post_terms( $id,$term_id,'category', true );
}
$wptheTvDbApi -> start_cron();

