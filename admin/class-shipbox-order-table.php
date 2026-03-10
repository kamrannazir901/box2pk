<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Shipbox_Order_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'shipment',
            'plural'   => 'shipments',
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'order_number'      => 'Order ID',
            'customer_name'     => 'Customer',
            'merchant'          => 'Merchant',
            'warehouse_country' => 'Warehouse',
            'value'             => 'Value',
            'is_consolidated'   => 'Consolidated',
            'status'            => 'Status',
            'created_at'        => 'Date',
            'actions'           => 'Actions'
        ];
    }

    protected function get_sortable_columns() {
        return [
            'order_number'      => [ 'order_number', false ],
            'created_at'        => [ 'created_at', true ],
            'status'            => [ 'status', false ],
            'warehouse_country' => [ 'warehouse_country', false ]
        ];
    }

    // Add Filters at the top of the table
    protected function extra_tablenav( $which ) {
        if ( $which == "top" ) {
            ?>
            <div class="alignleft actions">
                <!-- Country Filter -->
                <select name="filter_country">
                    <option value="">All Warehouses</option>
                    <option value="usa" <?php selected($_GET['filter_country'] ?? '', 'usa'); ?>>USA</option>
                    <option value="uk" <?php selected($_GET['filter_country'] ?? '', 'uk'); ?>>UK</option>
                    <option value="turkey" <?php selected($_GET['filter_country'] ?? '', 'turkey'); ?>>Turkey</option>
                </select>

                <!-- Consolidation Filter -->
                <select name="filter_cons">
                    <option value="">Consolidation (All)</option>
                    <option value="1" <?php selected($_GET['filter_cons'] ?? '', '1'); ?>>Yes</option>
                    <option value="0" <?php selected($_GET['filter_cons'] ?? '', '0'); ?>>No</option>
                </select>

               

                <!-- Status Filter -->
                <select name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="awaiting_arrival" <?php selected($_GET['filter_status'] ?? '', 'awaiting_arrival'); ?>>Awaiting Arrival</option>
                    <option value="received" <?php selected($_GET['filter_status'] ?? '', 'received'); ?>>Received</option>
                    <option value="in_transit" <?php selected($_GET['filter_status'] ?? '', 'in_transit'); ?>>In Transit</option>
                    <option value="arrived_karachi" <?php selected($_GET['filter_status'] ?? '', 'arrived_karachi'); ?>>Arrived in Karachi</option>
                    <option value="delivered" <?php selected($_GET['filter_status'] ?? '', 'delivered'); ?>>Delivered</option>
                </select>

                <?php submit_button( __( 'Filter' ), 'button', 'filter_action', false ); ?>
                
               <?php 
                $is_filtered = !empty($_GET['filter_country']) || 
                            !empty($_GET['filter_cons']) || 
                            !empty($_GET['filter_status']);

                if ( $is_filtered ) : ?>
                    <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">Reset Filters</a>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'order_number':
                return sprintf(
                    '<strong><a href="?page=shipbox-order-detail&id=%d">%s</a></strong>',
                    $item->id,
                    esc_html($item->order_number)
                );
            
            case 'customer_name':
                $name = isset($item->display_name) ? $item->display_name : 'Unknown';
                $sb_id = isset($item->customer_id) ? $item->customer_id : 'N/A';
                return sprintf(
                    '%s<br><small style="color:#666;">(%s)</small>',
                    esc_html($name),
                    esc_html($sb_id)
                );
            
            case 'merchant':
                return esc_html($item->merchant);
            
            case 'warehouse_country':
                $country_labels = [
                    'usa' => '🇺🇸 USA',
                    'uk' => '🇬🇧 UK',
                    'turkey' => '🇹🇷 Turkey'
                ];
                return $country_labels[strtolower($item->warehouse_country)] ?? strtoupper(esc_html($item->warehouse_country));
            
            case 'value':
                return '<strong>' . esc_html($item->currency . ' ' . number_format($item->product_value, 2)) . '</strong>';
            
            case 'is_consolidated':
                return $item->is_consolidated 
                    ? '<span style="color:#009640; font-weight:bold;">✓ Yes</span>' 
                    : '<span style="color:#999;">✗ No</span>';
            
            case 'status':
               $status_colors = [
                  'awaiting_arrival' => '#dba617',
                  'received'         => '#2271b1',
                  'in_transit'       => '#8c65b2',
                  'arrived_karachi'  => '#e65100',
                  'delivered'        => '#00a32a'
              ];
                $color = $status_colors[$item->status] ?? '#666';
                return sprintf(
                    '<span class="status-badge" style="background:%s; color:#fff; padding:4px 8px; border-radius:3px; font-size:11px; font-weight:600;">%s</span>',
                    $color,
                    ucfirst(str_replace('_', ' ', esc_html($item->status)))
                );
            
            case 'created_at':
                return date('d M Y', strtotime($item->created_at)) . '<br><small>' . date('h:i A', strtotime($item->created_at)) . '</small>';
            
            case 'actions':
                return sprintf(
                    '<a href="?page=shipbox-order-detail&id=%d" class="button button-small">View Details</a>',
                    $item->id
                );
                
            default:
                return isset($item->$column_name) ? esc_html($item->$column_name) : 'n/a';
        }
    }

    public function prepare_items() {
        global $wpdb;
        $order_table = $wpdb->prefix . 'shipbox_orders';
        $customer_table = $wpdb->prefix . 'shipbox_customers';
        $user_table = $wpdb->prefix . 'users';

        // Set column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = 20;
        $current_page = $this->get_pagenum();

        // 1. Build WHERE clause
        $where = ["1=1"];

        // Filter by Specific Customer (when coming from customer list)
        if ( !empty($_GET['view_shipments']) ) {
            $where[] = $wpdb->prepare("o.customer_id = %d", intval($_GET['view_shipments']));
        }

        // Filter by Country
        if ( !empty($_GET['filter_country']) ) {
            $where[] = $wpdb->prepare("o.warehouse_country = %s", sanitize_text_field($_GET['filter_country']));
        }

        // Filter by Consolidation
        if ( isset($_GET['filter_cons']) && $_GET['filter_cons'] !== '' ) {
            $where[] = $wpdb->prepare("o.is_consolidated = %d", intval($_GET['filter_cons']));
        }

       

        // Filter by Status
        if ( !empty($_GET['filter_status']) ) {
            $where[] = $wpdb->prepare("o.status = %s", sanitize_text_field($_GET['filter_status']));
        }

        // Search Bar
        if ( !empty($_REQUEST['s']) ) {
            $search = '%' . $wpdb->esc_like( sanitize_text_field($_REQUEST['s']) ) . '%';
            $where[] = $wpdb->prepare(
                "(o.order_number LIKE %s OR o.merchant LIKE %s OR o.merchant_order_number LIKE %s OR u.display_name LIKE %s)",
                $search, $search, $search, $search
            );
        }

        $where_sql = "WHERE " . implode(" AND ", $where);

        // 2. Sorting
        $orderby_param = (!empty($_GET['orderby'])) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = (!empty($_GET['order']) && $_GET['order'] === 'asc') ? 'ASC' : 'DESC';
        
        // Map to actual column names
        $orderby_map = [
            'order_number' => 'o.order_number',
            'created_at' => 'o.created_at',
            'status' => 'o.status',
            'warehouse_country' => 'o.warehouse_country'
        ];
        $orderby = $orderby_map[$orderby_param] ?? 'o.created_at';

        // 3. Count total items
        $total_items = $wpdb->get_var("
            SELECT COUNT(o.id) 
            FROM $order_table o
            LEFT JOIN $customer_table c ON o.customer_id = c.id
            LEFT JOIN $user_table u ON c.user_id = u.ID
            $where_sql
        ");

        $offset = ($current_page - 1) * $per_page;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        // 4. Fetch items with JOIN
        $query = $wpdb->prepare(
            "SELECT o.*, c.customer_id, u.display_name, u.user_email
             FROM $order_table o
             LEFT JOIN $customer_table c ON o.customer_id = c.id
             LEFT JOIN $user_table u ON c.user_id = u.ID
             $where_sql 
             ORDER BY $orderby $order 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        );

        $this->items = $wpdb->get_results($query);

        // Debug (remove in production)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ShipBox Orders - Total: ' . $total_items . ', Fetched: ' . count($this->items));
        }
    }

    public function no_items() {
        esc_html_e( 'No shipments found.', 'shipbox' );
    }
}