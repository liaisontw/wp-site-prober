<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'LIAISIPR_List_Table_Custom_Log' ) )
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-list-table-custom-log.php';

class LIAISIPR_List_Table_Implicit extends LIAISIPR_List_Table_Custom_Log {

}
