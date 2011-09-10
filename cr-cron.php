<?php
chdir(dirname(__FILE__));
ini_set('error_reporting', E_ALL);
ignore_user_abort(true);
set_time_limit(3500);
require_once '../../../wp-load.php';
global $wpdb,$wptheTvDbApi;
$wptheTvDbApi -> start_cron();

