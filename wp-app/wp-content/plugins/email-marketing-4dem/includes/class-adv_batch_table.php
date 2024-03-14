<?php
//Our class extends the WP_List_Table class, so we need to make sure that it's there
if(!class_exists('WP_List_Table')){
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Batch_List_Table extends WP_List_Table {

    private $before_table = "";
    private $after_table = "";

   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    function __construct() {
        parent::__construct( array(
            'singular'=> 'wp_list_text_link', //Singular label
            'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
            'ajax'   => true //We won't support Ajax for this table
        ) );
    }

    /**
    * Add extra markup in the toolbars before or after the list
    * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
    */
    function extra_tablenav( $which ) {
        if ( $which == "top" ){
            //The code that goes before the table is here
            echo $this->before_table;
        }
        if ( $which == "bottom" ){
            //The code that goes after the table is there
            echo $this->after_table;
        }
    }

    function set_table_before_after( $before = "", $after= "" ) {
        $this->after_table = $after;
        $this->before_table = $before;
    }

    /**
    * Define the columns that are going to be used in the table
    * @return array $columns, the array of columns to use with the table
    */
    function get_columns() {
        return $columns= array(
            //'cb'=>"esc_html__('Seleziona')",
            'col_batch_Id'=>esc_html__('ID'),
            'col_batch_Status'=>esc_html__('Status'),
            'col_batch_Operations'=>esc_html__('Numero Operazioni'),
            'col_batch_Type'=>esc_html__('Tipologia'),
            'col_batch_Start_Time'=>esc_html__('Inizio Batch'),
            'col_batch_Finish_Time'=>esc_html__('Fine Batch'),
            'col_batch_Actions'=>esc_html__('Azioni')
        );
    }

    // Displaying checkboxes!
    // function column_cb($item) {
    //     return sprintf(
    //         '<input type="checkbox" name="%1$s" id="%2$s" data-target="%3$s" value="checked" />',
    //         //$this->_args['singular'],
    //          $item->ID.'_status',
    //          $item->ID,
    //          $item->ID
    //     );
    // }

    function column_col_batch_Id($item){
        return $item->batch_Id;
    }

    function column_col_batch_Operations($item){
        return $item->batch_Operations;
    }

    function column_col_batch_Start_Time($item){
        return $item->batch_Start_Time;
    }

    function column_col_batch_Finish_Time($item){
        return $item->batch_Finish_Time;
    }

    function column_col_batch_Type($item){
        return $item->batch_Type;
    }

    function column_col_batch_Status($item){
        $errors = "";
        if($item->batch_Status == "ARCHIVED_WITH_ERRORS"){
            $errorArray = json_decode($item->batch_Result);
            if(count((array)$errorArray) > 0){
                $errorString = "";
                foreach($errorArray as $oneId => $oneIdErrors ){
                    $errorString .= $item->batch_Type.' ID '.$oneId.':<br>';
                    foreach($oneIdErrors->error as $singleError => $singleErrorValue) {
                        $errorString .= '- ' . $singleErrorValue[0] . '<br>';
                    }
                }
            }else{
                $errorString = $item->batch_Result;
            }
            $errors = '<span class="dashicons dashicons-warning tooltip_information"><div class="tooltip_information_text">' . esc_html__('Elenco errori: ','adv_dem') . '<br>' . $errorString . '</span>';
        }
        return $item->batch_Status.' '.$errors;
    }

    function column_col_batch_Actions($item){
        if($item->batch_Status == "ARCHIVED" || $item->batch_Status == "ARCHIVED_WITH_ERRORS" || $item->batch_Status == "OVER TIME") {
            return '<span class="table-batch-delete" data-target="'.$item->batch_Id.'"> <span class="dashicons dashicons-post-trash"></span> </span>';
        }
    }

    // function column_col_link_name($item){
    //     $actions = array(
    //         'delete' => sprintf('Delete it',$_REQUEST['page'],'delete',$item->ID),
    //         'view' => sprintf('View',get_permalink($item->ID)));
    //     return "ID, true ) . "'>". $item->post_title .” ” . $this->row_actions($actions);
    // }

    /**
    * Decide which columns to activate the sorting functionality on
    * @return array $sortable, the array of columns that can be sorted by the user
    */
    public function get_sortable_columns() {
        return $sortable = array(
            'col_batch_Id'=>array('batch_Id',true),
            'col_batch_Start_Time'=>array('batch_Start_Time',true),
            'col_batch_Finish_Time'=>array('batch_Finish_Time',true),
            'col_batch_Status'=>array('batch_Status', true)
        );
    }

    /**
    * Prepare the table with different parameters, pagination, columns and table elements
    */
    function prepare_items( ) {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();
        /* -- Preparing your query -- */
        $query = "SELECT * FROM " . $wpdb->prefix . "adv_dem_batches";

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty(sanitize_sql_orderby(wp_unslash($_GET["orderby"]))) ? sanitize_sql_orderby(wp_unslash($_GET["orderby"])) : 'ASC';
        $order = !empty(sanitize_text_field(wp_unslash($_GET["order"]))) ? sanitize_text_field(wp_unslash($_GET["order"])) : '';
        if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 25;
        //Which page is this?
        $paged = !empty(sanitize_text_field(wp_unslash($_GET["paged"]))) ? sanitize_text_field(wp_unslash($_GET["paged"])) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($perpage)){ $offset=($paged-1)*$perpage; $query.=' LIMIT '.(int)$offset.','.(int)$perpage; }
        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ) );
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        // $columns = $this->get_columns();
        // $_wp_column_headers[$screen->id]=$columns;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }


    /**
    * Display the rows of records in the table
    * @return string, echo the markup of the rows
    */
    // function display_rows() {

    //     //Get the records registered in the prepare_items method
    //     $records = $this->items;

    //     //Get the columns registered in the get_columns and get_sortable_columns methods
    //     list( $columns, $hidden ) = $this->get_column_info();
    //     //Loop for each record
    //     if(!empty($records)){
    //         foreach($records as $rec){
    //             //Open the line
    //             echo '< tr id="record_'.$rec->ID.'">';
    //             foreach ( $columns as $column_name => $column_display_name ) {

    //                 //Style attributes for each col
    //                 $class = "class='$column_name column-$column_name'";
    //                 $style = "";
    //                 if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
    //                 $attributes = $class . $style;

    //                 //edit link
    //                 $editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->ID;
    //                 //Display the cell
    //                 switch ( $column_name ) {
    //                     case "col_user_id":  echo '< td '.$attributes.'>'.stripslashes($rec->ID).'< /td>';   break;
    //                     case "col_user_name": echo '< td '.$attributes.'>'.stripslashes($rec->display_name).'< /td>'; break;
    //                     case "col_user_email": echo '< td '.$attributes.'>'.stripslashes($rec->user_email).'< /td>'; break;
    //                     case "col_user_registrazione": echo '< td '.$attributes.'>'.stripslashes($rec->user_registered).'< /td>'; break;
    //                     case "col_user_status": echo '< td '.$attributes.'>'.stripslashes($rec->user_status).'< /td>'; break;
    //                 }
    //             }

    //             //Close the line
    //             echo'< /tr>';
    //         }
    //     }
    // }

}
