<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Shipbox_Customer_Table extends WP_List_Table {

    private $message = '';
    private $message_type = '';

    public function __construct() {
        parent::__construct( [
            'singular' => 'customer',
            'plural'   => 'customers',
            'ajax'     => false
        ] );
    }

    // Define the columns
    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'customer_id' => 'Customer ID',
            'name'        => 'Full Name',
            'email'       => 'Email',
            'phone'       => 'Phone',
            'city'        => 'City',
            'referral_source' => 'Referral Source',
            'total_orders' => 'Total Orders',
            'status'      => 'Status',
            'created_at'  => 'Registered',
            'actions'     => 'Actions'
        ];
    }

    // Make columns sortable
    protected function get_sortable_columns() {
        return [
            'customer_id' => [ 'customer_id', false ],
            'name'        => [ 'display_name', false ],
            'city'        => [ 'city', false ],
            'created_at'  => [ 'created_at', true ],
        ];
    }

    // Bulk Actions
    protected function get_bulk_actions() {
        return [
            'bulk_delete' => 'Delete'
        ];
    }

    // Handle delete actions
    protected function handle_table_actions() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'shipbox_customers';
        // Status Toggle Logic
        if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['customer'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'toggle_status_' . intval($_GET['customer']))) {
                
                $customer_id = intval($_GET['customer']);
                $new_status  = sanitize_text_field($_GET['status']);

                $updated = $wpdb->update(
                    $table_name,
                    ['status' => $new_status],
                    ['id' => $customer_id],
                    ['%s'],
                    ['%d']
                );

                if ($updated !== false) {
                    $this->message = "Customer status updated to " . ucfirst($new_status) . "!";
                    $this->message_type = 'success';
                }
            }
        }

        
        // Single delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['customer']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_customer_' . intval($_GET['customer']))) {
                $customer_id = intval($_GET['customer']);
                $deleted = $this->delete_customer($customer_id);
                
                if ($deleted) {
                    $this->message = 'Customer and all related data deleted successfully!';
                    $this->message_type = 'success';
                } else {
                    $this->message = 'Failed to delete customer.';
                    $this->message_type = 'error';
                }
            }
        }

        // Bulk delete
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && !empty($_POST['customer'])) {
            check_admin_referer('bulk-customers');
            $customer_ids = array_map('intval', $_POST['customer']);
            $deleted_count = 0;
            
            foreach ($customer_ids as $customer_id) {
                if ($this->delete_customer($customer_id)) {
                    $deleted_count++;
                }
            }
            
            if ($deleted_count > 0) {
                $this->message = $deleted_count . ' customer(s) deleted successfully!';
                $this->message_type = 'success';
            } else {
                $this->message = 'Failed to delete customers.';
                $this->message_type = 'error';
            }
        }
    }

    protected function extra_tablenav( $which ) {
        if ( $which == 'top' ) {
            $current_status = isset( $_REQUEST['filter_status'] ) ? sanitize_text_field( $_REQUEST['filter_status'] ) : '';
            $current_ref    = isset( $_REQUEST['filter_referral'] ) ? sanitize_text_field( $_REQUEST['filter_referral'] ) : '';
            ?>

            <div class="alignleft actions">
                <select name="filter_status">
                    <option value=""><?php _e( 'All Statuses', 'shipbox' ); ?></option>
                    <option value="active" <?php selected( $current_status, 'active' ); ?>>Active</option>
                    <option value="inactive" <?php selected( $current_status, 'inactive' ); ?>>Inactive</option>
                </select>

                <select name="filter_referral">
                    <option value=""><?php _e( 'All Referrals', 'shipbox' ); ?></option>
                    <option value="Social Media" <?php selected( $current_ref, 'Social Media' ); ?>>Social Media</option>
                    <option value="Google" <?php selected( $current_ref, 'Google' ); ?>>Google</option>
                    <option value="Friend" <?php selected( $current_ref, 'Friend' ); ?>>Friend/Referral</option>
                </select>

                <?php submit_button( __( 'Filter' ), 'button', 'filter_action', false ); ?>
            </div>
            <?php
        }

        
    }
    // Delete customer and all related data
    private function delete_customer($customer_id) {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'shipbox_customers';
        $orders_table = $wpdb->prefix . 'shipbox_orders';
        $email_logs_table = $wpdb->prefix . 'shipbox_email_logs';
        
        // Get user_id before deleting
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $customers_table WHERE id = %d",
            $customer_id
        ));
        
        if (!$user_id) {
            return false;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete email logs
            $wpdb->delete($email_logs_table, ['customer_id' => $customer_id], ['%d']);
            
            // Delete orders
            $wpdb->delete($orders_table, ['customer_id' => $customer_id], ['%d']);
            
            // Delete customer record
            $wpdb->delete($customers_table, ['id' => $customer_id], ['%d']);
            
            // Delete WordPress user
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            $user_deleted = wp_delete_user($user_id);
            
            if (!$user_deleted) {
                throw new Exception('Failed to delete WordPress user');
            }
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    // How to render each column
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'customer_id': 
                return '<strong>' . esc_html($item->customer_id) . '</strong>';
            
            case 'name':
                $delete_nonce = wp_create_nonce('delete_customer_' . $item->id);
                $delete_url = add_query_arg([
                    'page' => $_REQUEST['page'],
                    'action' => 'delete',
                    'customer' => $item->id,
                    '_wpnonce' => $delete_nonce
                ], admin_url('admin.php'));
                
                $actions = [
                    'delete' => sprintf(
                        '<a href="%s" style="color:#b32d2e;" onclick="return confirm(\'⚠️ WARNING: This will permanently delete:\\n\\n• Customer account\\n• WordPress user account\\n• All orders (%d orders)\\n• All email logs\\n\\nThis action cannot be undone. Continue?\')">Delete</a>',
                        esc_url($delete_url),
                        $item->total_orders
                    )
                ];
                
                return sprintf('%1$s %2$s', esc_html($item->display_name), $this->row_actions($actions));
            
            case 'email':       
                return '<a href="mailto:' . esc_attr($item->user_email) . '">' . esc_html($item->user_email) . '</a>';
            
            case 'phone':       
                return esc_html($item->phone);
            
            case 'city':        
                return esc_html($item->city);
            
            case 'total_orders':
                if ($item->total_orders > 0) {
                    return '<span class="badge badge-info">' . $item->total_orders . '</span>';
                }
                return '<span style="color:#999;">0</span>';

            case 'referral_source':
                return !empty($item->referral_source) 
                    ? '<span class="badge" style="background:#eee; color:#666;">' . esc_html($item->referral_source) . '</span>' 
                    : '<span style="color:#ccc;">N/A</span>';
                    
            case 'status':
                $current_status = $item->status;
                $new_status = ( $current_status === 'active' ) ? 'inactive' : 'active';
                $status_class = ( $current_status === 'active' ) ? 'success' : 'warning';
                
                // Create the toggle URL
                $toggle_url = add_query_arg([
                    'page'     => $_REQUEST['page'],
                    'action'   => 'toggle_status',
                    'customer' => $item->id,
                    'status'   => $new_status,
                    '_wpnonce' => wp_create_nonce('toggle_status_' . $item->id)
                ], admin_url('admin.php'));

                return sprintf(
                    '<a href="%s" title="Click to make %s" style="text-decoration:none;">
                        <span class="badge badge-%s">%s</span>
                    </a>',
                    esc_url($toggle_url),
                    esc_attr($new_status),
                    esc_attr($status_class),
                    esc_html(ucfirst($current_status))
                );     
            case 'created_at':
                return date('M d, Y', strtotime($item->created_at));
            
            case 'actions':
                return sprintf(
                    '<a href="?page=shipbox-customer-shipments&customer_id=%d" class="button button-small" style="background:#009640;color:#fff;border:none;">View Shipments (%d)</a>',
                    $item->id,
                    $item->total_orders
                );
            
            default: 
                return esc_html($item->$column_name);
        }
    }

    // Checkbox column
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="customer[]" value="%s" />', $item->id);
    }

    // The logic to fetch data, sort, and paginate
    public function prepare_items() {
        global $wpdb;
        
        // Handle delete actions first
        $this->handle_table_actions();
        
        $customers_table = $wpdb->prefix . 'shipbox_customers';
        $users_table = $wpdb->prefix . 'users';
        $orders_table = $wpdb->prefix . 'shipbox_orders';

        $per_page = 10;
        $current_page = $this->get_pagenum();
        
        $where = "WHERE 1=1";
        $search_params = [];

        if ( ! empty( $_REQUEST['filter_status'] ) ) {
            $where .= " AND c.status = %s";
            $search_params[] = sanitize_text_field( $_REQUEST['filter_status'] );
        }

        if ( ! empty( $_REQUEST['filter_referral'] ) ) {
            $where .= " AND c.referral_source = %s";
            $search_params[] = sanitize_text_field( $_REQUEST['filter_referral'] );
        }

        // Search & Filter Logic
        
        if ( ! empty( $_REQUEST['s'] ) ) {
            $search = '%' . $wpdb->esc_like( sanitize_text_field( $_REQUEST['s'] ) ) . '%';
            
            // Add to WHERE clause
            $where .= " AND (c.customer_id LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR c.phone LIKE %s OR c.alt_phone LIKE %s OR c.city LIKE %s)";
            
            // Add the search term once for each %s placeholder
            $search_params[] = $search; // customer_id
            $search_params[] = $search; // display_name
            $search_params[] = $search; // user_email
            $search_params[] = $search; // phone
            $search_params[] = $search; // alt_phone
            $search_params[] = $search; // city
        }

        // Sorting Logic
        $orderby_param = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_at';
        $order   = ( ! empty( $_GET['order'] ) && $_GET['order'] === 'asc' ) ? 'ASC' : 'DESC';
        
        $orderby_map = [
            'customer_id' => 'c.customer_id',
            'display_name' => 'u.display_name',
            'city' => 'c.city',
            'created_at' => 'c.created_at',
            'id' => 'c.id'
        ];
        
        $orderby = isset($orderby_map[$orderby_param]) ? $orderby_map[$orderby_param] : 'c.created_at';

        // Count total items
        $count_query = "
            SELECT COUNT(c.id) 
            FROM $customers_table c
            INNER JOIN $users_table u ON c.user_id = u.ID
            $where
        ";
        
        if (!empty($search_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $search_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Pagination Logic
        $offset = ( $current_page - 1 ) * $per_page;

        // SET PAGINATION ARGS
        $this->set_pagination_args( array(
            'total_items' => (int) $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );

        // Fetch items with order count
        $query = "
            SELECT 
                c.id,
                c.customer_id,
                c.referral_source,
                c.phone,
                c.city,
                c.status,
                c.created_at,
                c.user_id,
                u.user_email,
                u.display_name,
                COUNT(o.id) as total_orders
            FROM $customers_table c
            INNER JOIN $users_table u ON c.user_id = u.ID
            LEFT JOIN $orders_table o ON c.id = o.customer_id
            $where
            GROUP BY c.id
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d
        ";
        
        $query_params = array_merge($search_params, [$per_page, $offset]);
        $this->items = $wpdb->get_results($wpdb->prepare($query, $query_params));
        
        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns()
        ];
    }

    // Add custom display for when there are no items
    public function no_items() {
        esc_html_e( 'No customers found.', 'shipbox' );
    }

    // Render the page with messages
    public function render_page() {
        $this->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Customers</h1>
            <hr class="wp-header-end">

            <?php if (!empty($this->message)): ?>
                <div class="notice notice-<?php echo esc_attr($this->message_type); ?> is-dismissible">
                    <p><?php echo esc_html($this->message); ?></p>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php
                $this->search_box('Search Customers', 'customer_search');
                $this->display();
                ?>
            </form>
        </div>

        <style>
            .badge {
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                display: inline-block;
            }
            .badge-success {
                background: #46b450;
                color: white;
            }
            .badge-warning {
                background: #ffb900;
                color: #333;
            }
            .badge-info {
                background: #0073aa;
                color: white;
            }
        </style>
        <?php
    }
}