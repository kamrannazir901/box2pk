<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Shipbox_Slabs_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'slab',
            'plural'   => 'slabs',
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'service_type' => 'Service/Country',
            'weight_min'   => 'Min Weight (KG)',
            'weight_max'   => 'Max Weight (KG)',
            'price'        => 'Price',
            'currency'     => 'Currency'
        ];
    }

    protected function get_sortable_columns() {
        return [
            'service_type' => [ 'service_type', true ],
            'weight_min'   => [ 'weight_min', false ],
            'price'        => [ 'price', false ]
        ];
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="slab[]" value="%d" />', $item->id );
    }

    public function column_service_type( $item ) {
        $edit_url = sprintf( '?page=%s&action=edit&id=%d', $_REQUEST['page'], $item->id );
        $actions = [
            'edit'   => sprintf( '<a href="%s">Edit</a>', $edit_url ),
            'delete' => sprintf( 
                '<a href="?page=%s&action=delete&id=%d&_wpnonce=%s" onclick="return confirm(\'Delete this slab?\')">Delete</a>', 
                $_REQUEST['page'], $item->id, wp_create_nonce('delete_slab_'.$item->id) 
            ),
        ];

        return sprintf( '<strong>%s</strong> %s', esc_html(strtoupper($item->service_type)), $this->row_actions($actions) );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'weight_min':
            case 'weight_max':
                return number_format($item->$column_name, 2) . ' KG';
            case 'price':
                $symbol = ( strtoupper($item->currency) === 'GBP' ) ? '£' : '$';
                return $symbol . number_format($item->price, 2);
            case 'currency':
                return esc_html(strtoupper($item->currency));
            default:
                return isset($item->$column_name) ? esc_html($item->$column_name) : '';
        }
    }

    /**
     * Display search box
     */
    public function search_box( $text = 'Search', $input_id = 'search' ) {
        if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
            return;
        }

        $input_id = $input_id . '-search-input';
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php echo isset($_REQUEST['s']) ? esc_attr( wp_unslash( $_REQUEST['s'] ) ) : ''; ?>" placeholder="Search service type, weight, price..." />
            <?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </p>
        <?php
    }



    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipbox_weight_slabs';
        
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $per_page     = 50;
        $current_page = $this->get_pagenum();
        
        // Sorting logic
        $orderby = ( !empty($_GET['orderby']) ) ? sanitize_sql_orderby($_GET['orderby']) : 'service_type, weight_min';
        $order   = ( !empty($_GET['order']) && strtolower($_GET['order']) === 'desc' ) ? 'DESC' : 'ASC';

        // Search logic
        $search = isset($_REQUEST['s']) ? trim($_REQUEST['s']) : '';
        $where_clause = '1=1';
        $where_values = array();

        if ( !empty($search) ) {
            // Check if search is numeric (for weight or price search)
            if ( is_numeric($search) ) {
                $search_num = floatval($search);
                // Search in service_type, weight ranges, or price
                $where_clause = "
                    (service_type LIKE %s 
                    OR weight_min = %f 
                    OR weight_max = %f 
                    OR price = %f
                    OR (weight_min <= %f AND weight_max >= %f))
                ";
                $where_values = array(
                    '%' . $wpdb->esc_like($search) . '%',
                    $search_num,
                    $search_num,
                    $search_num,
                    $search_num,
                    $search_num
                );
            } else {
                // Text search - service_type or currency
                $where_clause = "(service_type LIKE %s OR currency LIKE %s)";
                $where_values = array(
                    '%' . $wpdb->esc_like($search) . '%',
                    '%' . $wpdb->esc_like($search) . '%'
                );
            }
        }

        // Build the WHERE clause
        $where_sql = $where_clause;
        if ( !empty($where_values) ) {
            $where_sql = $wpdb->prepare($where_clause, $where_values);
        }

        // Get total items with WHERE clause
        $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE $where_sql" );
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        // Get items with WHERE clause
        $offset = ($current_page - 1) * $per_page;
        $this->items = $wpdb->get_results( 
            "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby $order LIMIT $per_page OFFSET $offset"
        );
    }

    /**
     * Display when no items found
     */
    public function no_items() {
        if ( !empty($_REQUEST['s']) ) {
            esc_html_e( 'No slabs found matching your search criteria.', 'shipbox' );
        } else {
            esc_html_e( 'No slabs found.', 'shipbox' );
        }
    }
}