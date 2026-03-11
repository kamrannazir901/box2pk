<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Shipbox_Customer_Shipments_Table extends WP_List_Table {

    private $customer_id;
    private $customer_info;

    public function __construct($customer_id = null) {
        parent::__construct([
            'singular' => 'shipment',
            'plural'   => 'shipments',
            'ajax'     => false
        ]);
        
        $this->customer_id = $customer_id ? intval($customer_id) : (isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0);
        $this->load_customer_info();
    }


// 2. ADD THIS METHOD: The actual logic that talks to the database
    public function handle_delete_action() {
        // Check if the action is 'delete' and we have an ID
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete' || !isset($_GET['shipment_id'])) {
            return;
        }

        $shipment_id = intval($_GET['shipment_id']);

        // Verify the security nonce (the 'delete_shipment_' + ID you created in column_order_number)
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_shipment_' . $shipment_id)) {
            wp_die('Security check failed.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'shipbox_orders';

        // Delete the row
        $deleted = $wpdb->delete(
            $table_name,
            array('id' => $shipment_id),
            array('%d')
        );

        if ($deleted !== false) {
            // Redirect back to the same page without the action/id params to show the success message
            wp_redirect(add_query_arg([
                'page' => sanitize_text_field($_GET['page']),
                'customer_id' => $this->customer_id,
                'message' => 'deleted'
            ], admin_url('admin.php')));
            exit;
        }
    }

    // Load customer information
    private function load_customer_info() {
        global $wpdb;
        
        if (!$this->customer_id) {
            return;
        }
        
        $customers_table = $wpdb->prefix . 'shipbox_customers';
        $users_table = $wpdb->prefix . 'users';
        
        $this->customer_info = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                c.customer_id,
                c.phone,
                c.city,
                c.province,
                c.status,
                u.display_name,
                u.user_email
            FROM $customers_table c
            INNER JOIN $users_table u ON c.user_id = u.ID
            WHERE c.id = %d",
            $this->customer_id
        ));
    }

    // Define columns based on actual database schema
    public function get_columns() {
        return [
            'order_number'   => 'Order Number',
            'merchant'       => 'Merchant',
            'warehouse'      => 'Warehouse',
            'product_value'  => 'Product Value',
            'final_price'    => 'Final Price',
            'consolidation'  => 'Consolidated',
            
            'status'         => 'Status',
            'created_at'     => 'Order Date',
            'actions'        => 'Actions'
        ];
    }

    // Sortable columns
    protected function get_sortable_columns() {
        return [
            'order_number'  => ['order_number', false],
            'created_at'    => ['created_at', true],
            'status'        => ['status', false],
            'final_price'   => ['final_price', false]
        ];
    }

    // Add filter dropdowns in the top tablenav area
    protected function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        
        // Check if any filters are active
      $has_filters = !empty($_GET['filter_country']) || 
               (isset($_GET['filter_cons']) && $_GET['filter_cons'] !== '') || 
               !empty($_GET['filter_status']);
        
        ?>
        <div class="alignleft actions">
            <!-- Warehouse Country Filter -->
            <select name="filter_country" id="filter-country">
                <option value="">All Countries</option>
                <option value="usa" <?php selected(isset($_GET['filter_country']) && $_GET['filter_country'] === 'usa'); ?>>
                    🇺🇸 USA
                </option>
              
                <option value="uk" <?php selected(isset($_GET['filter_country']) && $_GET['filter_country'] === 'uk'); ?>>
                    🇬🇧 UK
                </option>
                <option value="turkey" <?php selected(isset($_GET['filter_country']) && $_GET['filter_country'] === 'turkey'); ?>>
                    🇹🇷 Turkey
                </option>
            </select>
            
            <!-- Consolidation Filter -->
            <select name="filter_cons" id="filter-cons">
                <option value="">All Types</option>
                <option value="1" <?php selected(isset($_GET['filter_cons']) && $_GET['filter_cons'] === '1'); ?>>
                    Consolidated: Yes
                </option>
                <option value="0" <?php selected(isset($_GET['filter_cons']) && $_GET['filter_cons'] === '0'); ?>>
                    Consolidated: No
                </option>
            </select>
            
           
            <!-- Status Filter -->
           <select name="filter_status" id="filter-status">
                <option value="">All Status</option>
                <option value="awaiting_arrival" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] === 'awaiting_arrival'); ?>>
                    Awaiting Arrival
                </option>
                <option value="received" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] === 'received'); ?>>
                    Received
                </option>
                <option value="in_transit" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] === 'in_transit'); ?>>
                    In Transit
                </option>
                <option value="arrived_karachi" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] === 'arrived_karachi'); ?>>
                    Arrived in Karachi
                </option>
                <option value="delivered" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] === 'delivered'); ?>>
                    Delivered
                </option>
            </select>
            
            <?php submit_button('Filter', 'button', 'filter_action', false); ?>
            
            <?php if ($has_filters): ?>
                <a href="<?php echo esc_url(add_query_arg(['page' => $_GET['page'], 'customer_id' => $this->customer_id], admin_url('admin.php'))); ?>" 
                   class="button" 
                   style="margin-left: 5px;">
                    Reset Filters
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    // Add row actions to order number column
    public function column_order_number($item) {
        $edit_url = add_query_arg([
            'page' => 'shipbox-order-detail',
            'id' => $item->id
        ], admin_url('admin.php'));
        
        $delete_url = wp_nonce_url(
            add_query_arg([
                'page' => $_GET['page'],
                'customer_id' => $this->customer_id,
                'action' => 'delete',
                'shipment_id' => $item->id
            ], admin_url('admin.php')),
            'delete_shipment_' . $item->id
        );
        
        $actions = [
            'view' => sprintf('<a href="%s">View Details</a>', esc_url($edit_url)),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'Are you sure you want to delete order #%s?\');" style="color:#b32d2e;">Delete</a>',
                esc_url($delete_url),
                esc_attr($item->order_number)
            )
        ];

      
        
        return sprintf(
            '<strong><a href="%s">#%s</a></strong>%s',
            esc_url($edit_url),
            esc_html($item->order_number),
            $this->row_actions($actions)
        );
    }
    
    // Render columns
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'merchant':
                $merchant_html = esc_html($item->merchant);
                if ($item->merchant_order_number) {
                    $merchant_html .= '<br><small style="color:#666;">Order: ' . esc_html($item->merchant_order_number) . '</small>';
                }
                return $merchant_html;
            
            case 'warehouse':
                // Warehouse badges with country flags and styling
                $badges = [
                    'usa' => ['🇺🇸 USA', '#0073aa', '#fff', 'normal', '600'],
                    'uk' => ['🇬🇧 UK', '#d63638', '#fff', 'normal', '600'],
                    'turkey' => ['🇹🇷 Turkey', '#00a32a', '#fff', 'normal', '600']
                ];
                
                $country = strtolower($item->warehouse_country);
                $badge_info = isset($badges[$country]) ? $badges[$country] : [strtoupper($country), '#999', '#fff', 'normal', '600'];
                
                return sprintf(
                    '<span style="background:%s;color:%s;padding:3px 10px;border-radius:3px;font-size:11px;font-weight:%s;font-style:%s;display:inline-block;">%s</span>',
                    $badge_info[1], // background color
                    $badge_info[2], // text color
                    $badge_info[4], // font-weight
                    $badge_info[3], // font-style
                    $badge_info[0]  // label
                );
            
            case 'product_value':
                return esc_html($item->currency) . ' ' . number_format($item->product_value, 2);
            
            case 'final_price':
                if ($item->final_price > 0) {
                    $locked_icon = $item->final_price_locked ? ' 🔒' : '';
                    return '<strong>Rs ' . number_format($item->final_price, 2) . $locked_icon . '</strong>';
                }
                return '<span style="color:#999;">Not set</span>';
            
            case 'consolidation':
                if ($item->is_consolidated) {
                    return '<span style="background:#0073aa;color:white;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;">Yes</span>';
                } else {
                    return '<span style="color:#999;">—</span>';
                }
           
            case 'status':
                $status_colors = [
                  'awaiting_arrival' => ['#dba617', '#fff'],
                  'received'         => ['#2271b1', '#fff'],
                  'in_transit'       => ['#8c65b2', '#fff'],
                  'arrived_karachi'  => ['#e65100', '#fff'],
                  'delivered'        => ['#00a32a', '#fff']
              ];
                
               $status_labels = [
                  'awaiting_arrival' => 'Awaiting Arrival',
                  'received'         => 'Received',
                  'in_transit'       => 'In Transit',
                  'arrived_karachi'  => 'Arrived Karachi',
                  'delivered'        => 'Delivered'
              ];
                
                $colors = isset($status_colors[$item->status]) ? $status_colors[$item->status] : ['#999', 'white'];
                $label = isset($status_labels[$item->status]) ? $status_labels[$item->status] : ucfirst($item->status);
                
                return sprintf(
                    '<span style="background:%s;color:%s;padding:4px 10px;border-radius:3px;font-size:11px;font-weight:600;display:inline-block;">%s</span>',
                    $colors[0],
                    $colors[1],
                    esc_html($label)
                );
            
            case 'created_at':
                return date('M d, Y', strtotime($item->created_at));
            
            case 'actions':
                return sprintf(
                    '<a href="?page=shipbox-order-detail&id=%d" class="button button-small">View Details</a>',
                    $item->id
                );
            
            default:
                return esc_html($item->$column_name);
        }
    }

    // Prepare items with search and filter functionality
    public function prepare_items() {
        global $wpdb;
        
        if (!$this->customer_id) {
            $this->items = [];
            return;
        }
        
        $orders_table = $wpdb->prefix . 'shipbox_orders';
        $per_page = 10;
        $current_page = $this->get_pagenum();
        
        // Build WHERE clause
        $where = "WHERE customer_id = %d";
        $where_params = [$this->customer_id];
        
        // Search functionality
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where .= " AND (order_number LIKE %s OR merchant LIKE %s OR merchant_order_number LIKE %s OR merchant_tracking_number LIKE %s)";
            $where_params[] = $search_like;
            $where_params[] = $search_like;
            $where_params[] = $search_like;
            $where_params[] = $search_like;
        }
        
        // Country/Warehouse Filter
        if (!empty($_GET['filter_country'])) {
            $filter_country = sanitize_text_field($_GET['filter_country']);
            
            // Handle both 'usa', 'uk', 'turkey'
            $allowed_countries = ['usa', 'uk', 'turkey'];
            if (in_array($filter_country, $allowed_countries)) {
                $where .= " AND warehouse_country = %s";
                $where_params[] = $filter_country;
            }
        }
        
        // Consolidation Filter
        if (isset($_GET['filter_cons']) && $_GET['filter_cons'] !== '') {
            $is_consolidated = intval($_GET['filter_cons']);
            $where .= " AND is_consolidated = %d";
            $where_params[] = $is_consolidated;
        }

       
        
        // Status Filter
        if (!empty($_GET['filter_status'])) {
            $filter_status = sanitize_text_field($_GET['filter_status']);
            $allowed_statuses = ['awaiting_arrival', 'received', 'in_transit', 'arrived_karachi', 'delivered'];
            if (in_array($filter_status, $allowed_statuses)) {
                $where .= " AND status = %s";
                $where_params[] = $filter_status;
            }
        }
        
        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
        
        // Allowed columns for sorting
        $allowed_orderby = ['order_number', 'created_at', 'status', 'final_price'];
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'created_at';
        }
        
        // Count total items
        $count_query = "SELECT COUNT(id) FROM $orders_table $where";
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_params));
        
        // Pagination
        $offset = ($current_page - 1) * $per_page;
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        // Fetch items - only relevant fields from database
        $query = "
            SELECT 
                id,
                order_number,
                merchant,
                merchant_order_number,
                warehouse_country,
                product_value,
                currency,
                final_price,
                final_price_locked,
                is_consolidated,
                status,
                created_at
            FROM $orders_table 
            $where
            ORDER BY $orderby $order 
            LIMIT %d OFFSET %d
        ";
        
        $query_params = array_merge($where_params, [$per_page, $offset]);
        $this->items = $wpdb->get_results($wpdb->prepare($query, $query_params));
        
        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns()
        ];
    }

    // No items message
    public function no_items() {
        esc_html_e('No shipments found for this customer.', 'shipbox');
    }

    // Render the full page
    public function render_page() {
        if (!$this->customer_id || !$this->customer_info) {
            echo '<div class="wrap"><h1>Invalid Customer</h1><p>Customer not found.</p></div>';
            return;
        }
        
        $this->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Customer Shipments</h1>
            <a href="?page=shipbox-manager" class="page-title-action">← Back to Customers</a>
            <hr class="wp-header-end">
            
            <?php
            // Display success message
            if (isset($_GET['message']) && $_GET['message'] === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>Shipment deleted successfully.</p></div>';
            }
            ?>

            <!-- Customer Info Card -->
            <div class="card" style="max-width: 100%; margin: 20px 0; padding: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">Customer Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 15px;">
                    <div>
                        <strong style="color: #666;">Customer ID:</strong><br>
                        <span style="font-size: 18px; color: #0073aa; font-weight: 600;"><?php echo esc_html($this->customer_info->customer_id); ?></span>
                    </div>
                    <div>
                        <strong style="color: #666;">Name:</strong><br>
                        <span style="font-size: 15px;"><?php echo esc_html($this->customer_info->display_name); ?></span>
                    </div>
                    <div>
                        <strong style="color: #666;">Email:</strong><br>
                        <a href="mailto:<?php echo esc_attr($this->customer_info->user_email); ?>" style="font-size: 15px;">
                            <?php echo esc_html($this->customer_info->user_email); ?>
                        </a>
                    </div>
                    <div>
                        <strong style="color: #666;">Phone:</strong><br>
                        <span style="font-size: 15px;"><?php echo esc_html($this->customer_info->phone); ?></span>
                    </div>
                    <div>
                        <strong style="color: #666;">Location:</strong><br>
                        <span style="font-size: 15px;"><?php echo esc_html($this->customer_info->city . ', ' . $this->customer_info->province); ?></span>
                    </div>
                    <div>
                        <strong style="color: #666;">Status:</strong><br>
                        <?php 
                        $status_color = $this->customer_info->status === 'active' ? '#46b450' : '#ffb900';
                        ?>
                        <span style="background:<?php echo $status_color; ?>;color:white;padding:4px 10px;border-radius:3px;font-size:12px;font-weight:600;display:inline-block;margin-top:5px;">
                            <?php echo ucfirst(esc_html($this->customer_info->status)); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipments Table -->
            <div style="background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">Order History (<?php echo $this->get_pagination_arg('total_items'); ?> orders)</h2>
                
                <!-- Active Filters Display -->
                <?php
                $active_filters = [];
                if (!empty($_GET['filter_country'])) {
                    $country_labels = [
                        'usa' => '🇺🇸 USA',
                        'uk' => '🇬🇧 UK',
                        'turkey' => '🇹🇷 Turkey'
                    ];
                    $active_filters[] = 'Country: ' . $country_labels[$_GET['filter_country']];
                }
                if (isset($_GET['filter_cons']) && $_GET['filter_cons'] !== '') {
                    $active_filters[] = 'Consolidated: ' . ($_GET['filter_cons'] === '1' ? 'Yes' : 'No');
                }

                

                if (!empty($_GET['filter_status'])) {
                   $status_labels = [
                      'awaiting_arrival' => 'Awaiting Arrival',
                      'received'         => 'Received',
                      'in_transit'       => 'In Transit',
                      'arrived_karachi'  => 'Arrived Karachi',
                      'delivered'        => 'Delivered'
                  ];
                    $active_filters[] = 'Status: ' . $status_labels[$_GET['filter_status']];
                }
                
                if (!empty($active_filters)):
                ?>
                    <div class="notice notice-info inline" style="margin: 0 0 15px 0; padding: 10px 15px;">
                        <strong>Active Filters:</strong> <?php echo implode(' | ', $active_filters); ?>
                    </div>
                <?php endif; ?>
                
                <form method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                    <input type="hidden" name="customer_id" value="<?php echo esc_attr($this->customer_id); ?>" />
                    <?php 
                    $this->search_box('Search by Order Number, Merchant', 'order_search');
                    $this->display(); 
                    ?>
                </form>
            </div>
        </div>
        <?php
    }
}