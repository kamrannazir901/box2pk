<?php
/**
 * ShipBox City & Domestic Range Management
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Shipbox_City_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'city',
            'plural'   => 'cities',
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'cb'         => '<input type="checkbox" />',
            'city_name'  => 'City Name',
            'weight_min' => 'Min Weight (KG)',
            'weight_max' => 'Max Weight (KG)',
            'price'      => 'Slab Price (USD)',
        ];
    }

    protected function get_sortable_columns() {
        return [
            'city_name'  => [ 'city_name', true ],
            'weight_min' => [ 'weight_min', false ],
            'price'      => [ 'price', false ]
        ];
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="city[]" value="%d" />', $item->id );
    }

    public function column_city_name( $item ) {
        $edit_url = sprintf( '?page=%s&action=edit&id=%d', $_REQUEST['page'], $item->id );
        $actions = [
            'edit'   => sprintf( '<a href="%s">Edit</a>', $edit_url ),
            'delete' => sprintf( 
                '<a href="?page=%s&action=delete&id=%d&_wpnonce=%s" onclick="return confirm(\'Delete this range?\')">Delete</a>', 
                $_REQUEST['page'], 
                $item->id, 
                wp_create_nonce('delete_city_'.$item->id) 
            ),
        ];

        return sprintf( '<strong>%s</strong> %s', esc_html($item->city_name), $this->row_actions($actions) );
    }

    public function column_weight_min( $item ) {
        return number_format($item->weight_min, 2) . ' kg';
    }

    public function column_weight_max( $item ) {
        return number_format($item->weight_max, 2) . ' kg';
    }

    public function column_price( $item ) {
        return '$' . number_format($item->price, 2);
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipbox_cities';
        
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        
        $orderby = ( !empty($_GET['orderby']) ) ? sanitize_sql_orderby($_GET['orderby']) : 'city_name';
        $order   = ( !empty($_GET['order']) && $_GET['order'] === 'desc' ) ? 'DESC' : 'ASC';

        $where = "";
        if ( !empty($_REQUEST['s']) ) {
            $search = $wpdb->esc_like( sanitize_text_field($_REQUEST['s']) );
            $where = $wpdb->prepare( " WHERE city_name LIKE %s", '%' . $search . '%' );
        }

        $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name $where" );
        $offset      = ($current_page - 1) * $per_page;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name $where ORDER BY $orderby $order, weight_min ASC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
    }
}

/**
 * Main Controller Function
 */
function shipbox_manage_cities_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shipbox_cities';
    $message = '';
    $action = $_REQUEST['action'] ?? 'list';

    // 1. HANDLE DELETE
    if ( $action === 'delete' && isset($_GET['id']) ) {
        check_admin_referer('delete_city_' . $_GET['id']);
        $wpdb->delete($table_name, ['id' => intval($_GET['id'])]);
        $message = '<div class="updated"><p>City range deleted successfully.</p></div>';
        $action = 'list';
    }

    // 2. HANDLE SAVE (ADD/EDIT)
    if ( isset($_POST['save_city']) ) {
        check_admin_referer('shipbox_city_form');

        $city_id    = !empty($_POST['city_id']) ? intval($_POST['city_id']) : 0;
        $city_name  = sanitize_text_field($_POST['city_name']);
        $weight_min = floatval($_POST['weight_min']);
        $weight_max = floatval($_POST['weight_max']);
        $price      = floatval($_POST['price']);

        // --- OVERLAP CHECK ---
        // Verify if the same city has an existing range that clashes with this one
        $overlap = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE city_name = %s AND id != %d 
             AND ( 
                (%f BETWEEN weight_min AND weight_max) OR 
                (%f BETWEEN weight_min AND weight_max) OR
                (weight_min BETWEEN %f AND %f)
             )",
            $city_name, $city_id, $weight_min, $weight_max, $weight_min, $weight_max
        ));

        if ( $overlap ) {
            $message = '<div class="error"><p><strong>Error:</strong> This weight range overlaps with an existing entry for ' . esc_html($city_name) . '.</p></div>';
            $action  = $city_id ? 'edit' : 'add'; 
        } else {
            $city_data = [
                'city_name'  => $city_name,
                'weight_min' => $weight_min,
                'weight_max' => $weight_max,
                'price'      => $price
            ];

            if ( $city_id ) {
                $wpdb->update($table_name, $city_data, ['id' => $city_id]);
                $message = '<div class="updated"><p>City range updated successfully!</p></div>';
            } else {
                $wpdb->insert($table_name, $city_data);
                $message = '<div class="updated"><p>City range added successfully!</p></div>';
            }
            $action = 'list';
        }
    }

    // 3. START RENDERING
    echo '<div class="wrap"><h1>Manage Cities & Weight Slabs</h1>' . $message;

    if ( $action === 'edit' || $action === 'add' ) {
        // Form View
        $item = ( $action === 'edit' ) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET['id'])) : null;
        
        // Repopulate on failure
        if ( isset($_POST['save_city']) && $overlap ) {
            $item = (object) $_POST;
            $item->id = $city_id;
        }
        ?>
        <div class="postbox" style="margin-top:20px; padding: 20px;">
            <form method="post">
                <?php wp_nonce_field('shipbox_city_form'); ?>
                <input type="hidden" name="city_id" value="<?php echo esc_attr($item->id ?? ''); ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="city_name">City Name</label></th>
                        <td><input type="text" name="city_name" id="city_name" value="<?php echo esc_attr($item->city_name ?? ''); ?>" required class="regular-text" placeholder="e.g. Karachi"></td>
                    </tr>
                    <tr>
                        <th><label>Weight Range (KG)</label></th>
                        <td>
                            <input type="number" step="0.01" name="weight_min" value="<?php echo esc_attr($item->weight_min ?? '0.00'); ?>" style="width:100px;"> 
                            to 
                            <input type="number" step="0.01" name="weight_max" value="<?php echo esc_attr($item->weight_max ?? '1.00'); ?>" style="width:100px;">
                            <p class="description">Define the min and max weight for this price slab.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="price">Slab Price (USD)</label></th>
                        <td><input type="number" step="0.01" name="price" id="price" value="<?php echo esc_attr($item->price ?? '0.00'); ?>" required></td>
                    </tr>
                </table>
                
                <?php submit_button('Save City Slab', 'primary', 'save_city'); ?>
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">Cancel</a>
            </form>
        </div>
        <?php
    } else {
        // List Table View
        $cityTable = new Shipbox_City_Table();
        $cityTable->prepare_items();
        ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="?page=<?php echo esc_attr($_GET['page']); ?>&action=add" class="button button-primary">Add New Range</a>
            </div>
            <form method="get" style="float:right;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php $cityTable->search_box('Search Cities', 'search_id'); ?>
            </form>
        </div>
        
        <form method="post">
            <?php $cityTable->display(); ?>
        </form>
        <?php
    }
    echo '</div>';
}