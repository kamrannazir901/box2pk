<?php
if (!defined('ABSPATH')) exit;

use Dompdf\Dompdf;
use Dompdf\Options;

class ShipBox_Invoice {

    public static function generate_invoice($order_number) {
        global $wpdb;

        // 1. Check if user is logged in at all
        if ( ! is_user_logged_in() ) {
            wp_die('Please log in to view this invoice.');
        }

        // 2. Fetch the order with user/customer ID details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, 
                    c.customer_id as cid, c.address, c.city, c.province, c.phone, c.user_id as owner_id,
                    u.display_name 
             FROM {$wpdb->prefix}shipbox_orders o
             LEFT JOIN {$wpdb->prefix}shipbox_customers c ON o.customer_id = c.id
             LEFT JOIN {$wpdb->prefix}users u ON c.user_id = u.ID
             WHERE o.order_number = %s", $order_number
        ));

        if (!$order) {
            wp_die("Order Not Found: " . esc_html($order_number));
        }

        // 3. Security Check: Admin can see everything, User can only see their own
        if ( ! current_user_can('manage_options') ) {
            if ( (int)$order->owner_id !== get_current_user_id() ) {
                wp_die('Security Error: You do not have permission to view this invoice.');
            }
        }

        // 4. Fixed Path to DOMPDF (looking inside current includes/lib folder)
        require_once plugin_dir_path(__FILE__) . 'lib/dompdf/autoload.inc.php';
        
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::get_template($order));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Use 'Attachment' => true if you want it to download, false to open in browser
        $dompdf->stream("Invoice-{$order->order_number}.pdf", ["Attachment" => false]);
        exit;
    }

    private static function get_template($order) {
        // --- PRESERVED ORIGINAL DATA LOGIC ---
        $warehouse_settings = get_option('shipbox_warehouse_settings', []);
        $country_key = strtolower($order->warehouse_country);
        $wh = isset($warehouse_settings[$country_key]) ? $warehouse_settings[$country_key] : null;

        $merchants = array_map('trim', explode(',', $order->merchant));
        $order_numbers = array_map('trim', explode(',', $order->merchant_order_number));
        $max_items = max(count($merchants), count($order_numbers));

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                @page { margin: 20px 40px 60px 40px; }
                body { font-family: 'DejaVu Sans', sans-serif; color: #333; font-size: 14px; line-height: 1.3; font-weight: normal; }
                
                /* Reset bold */
                b, strong, th { font-weight: normal; }

                /* Header Section */
                .header-table { width: 100%; margin-bottom: 15px; border: none; border-collapse: collapse; }
                .invoice-heading { font-size: 28px; color: #000; margin: 0; font-weight:500 }
                .header-meta { font-size: 14px; color: #999; }
                .logo { width: 150px; margin-top:10px }

                /* Address Boxes - Fixed Alignment */
                .address-wrapper { width: 100%; margin-bottom: 20px; border-collapse: collapse; table-layout: fixed; }
                .address-box-cell { width: 48%; vertical-align: top; }
                .address-spacer { width: 4%; }
                
                .address-box { border: 1.5px solid #000; border-radius: 8px; overflow: hidden; background: #fff; }
                .address-header { background: #E9E9E9; padding: 4px 12px; font-size: 14px; border-bottom: 1px solid #ccc; color: #000; }
                .address-content { padding: 6px 12px; font-size: 12px; line-height:1.2 }

                /* Tables - Outer Border Rounded */
                .table-container { border: 1.5px solid #000; border-radius: 8px; overflow: hidden; margin-bottom: 15px; width: 100%; }
                .data-table { width: 100%; border-collapse: collapse; }
                
                /* Table Header Rows */
                .table-main-header { background: #E9E9E9; padding: 4px 12px; text-align: left; font-size: 14px; color: #000; border-bottom: 1px solid #ccc; }
                
                /* Column Headers with BG color */
                .data-table th { background: #E9E9E9; border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; padding: 4px; text-align: center; font-size: 14px; color: #000; }
                .data-table th:last-child { border-right: none; }

                /* Cells */
                .data-table td { border-bottom: 1px solid #dbd8d8; border-right: 1px solid #dbd8d8; padding: 3px; font-size: 12px; text-align: center; }
                .data-table td:last-child { border-right: none; }
                .data-table tr:last-child td { border-bottom: none; }

                /* Totals Box */
                .totals-container { width: 100%; margin-top: 10px; }
                .totals-table { float: right; width: 35%; border-collapse: separate; border: 1.5px solid #000; border-radius: 8px; overflow: hidden; }
                .totals-table td { padding:1px 6px; font-size: 12px; text-align: center; }
                .total-label { background: #fff; border-right: 1.5px solid #000; width: 50%; text-align:left !important; }

                /* Footer */
                footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: left; font-size: 12px; border-top: 1px solid #dbd8d8; padding-top: 8px; color: #333; line-height:1.1em !important; font-style:italic }
                .f-num { color: #cd0613; }
                .f-web { color: #1a9c38; }
                .f-email { color: #000; }
                footer span{
                    display: inline-block;
                    margin-top:16px !important;
                    padding-right:10px !important;
                    font-weight:bold !important;
                }
            </style>
        </head>
        <body>
            <table class="header-table">
                <tr>
                    <td valign="top">
                        <div class="invoice-heading">Invoice</div>
                        <div class="header-meta">
                            NO: <?php echo $order->order_number; ?> &nbsp; Date: <?php echo date('d/m/Y'); ?><br>
                            Customer ID: <?php echo $order->cid; ?> &nbsp; Order# : <?php echo $order->order_number; ?>
                        </div>
                    </td>
                    <td align="right" valign="center">
                        <img src="https://box2pk.com/wp-content/uploads/2026/02/unnamed-e1772281321920.jpg" class="logo">
                    </td>
                </tr>
            </table>

            <table class="address-wrapper">
                <tr>
                    <td class="address-box-cell">
                        <div class="address-box">
                            <div class="address-header">Billing Address</div>
                            <div class="address-content">
                                <span class="address-name"><?php echo esc_html($order->display_name); ?></span>
                                <?php echo nl2br(esc_html($order->address)); ?><br>
                                <?php echo esc_html($order->city . ', ' . $order->province); ?><br>
                                Phone: <?php echo esc_html($order->phone); ?>
                            </div>
                        </div>
                    </td>
                    <td class="address-spacer"></td>
                    <td class="address-box-cell">
                        <div class="address-box">
                            <div class="address-header">Shipping Address</div>
                            <div class="address-content">
                                <span class="address-name"><?php echo esc_html($order->display_name); ?></span>
                                <?php echo nl2br(esc_html($order->address)); ?><br>
                                <?php echo esc_html($order->city . ', ' . $order->province); ?><br>
                                Phone: <?php echo esc_html($order->phone); ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="table-container">
                <div class="table-main-header">Product Details</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="10%">No.</th>
                            <th width="50%">Merchant Name</th>
                            <th width="40%">Order Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $max_items; $i++): ?>
                        <tr>
                            <td><?php echo ($i + 1); ?></td>
                            <td><?php echo isset($merchants[$i]) ? esc_html($merchants[$i]) : '-'; ?></td>
                            <td><?php echo isset($order_numbers[$i]) ? esc_html($order_numbers[$i]) : '-'; ?></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead class="table-main-header">
                        <tr>
                            <th width="10%"></th>
                            <th width="50%">Carrier Service</th>
                            <th width="20%">Billing Weight</th>
                            <th width="20%">Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td></td>
                            <td><?php echo esc_html($order->carrier_name ?? 'Not Assigned'); ?></td>
                            
                            <td><?php echo isset($order->billing_weight) ? esc_html($order->billing_weight) . ' kg' : '—'; ?></td>
                            
                            <td>PKR <?php echo number_format($order->service_fee ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                             <td></td>
                            <td>All duties + Govt. Levies</td>
                            <td>As per state law</td>
                            <td>PKR <?php echo number_format($order->duties_levies ?? 0, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="totals-container">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">TOTAL</td>
                        <td>
                            PKR 
                            <?php 
                                // Dynamic Total: Sum of service_fee and duties_levies
                                // We use (float) to ensure the math works even if the values are null strings
                                $calculated_total = (float)($order->service_fee ?? 0) + (float)($order->duties_levies ?? 0);
                                echo number_format($calculated_total, 2); 
                            ?>
                        </td>
                    </tr>
                </table>
                <div style="clear: both;"></div>
            </div>

            <footer>
                Flat # 4, 2nd Floor. Plot # 65-C, 24th Street<br> Tauheed Commercial Area, Phase V, DHA. Karachi<br>
                <span class="f-num">+92 335 3387766</span> <span class="f-email">info@box2pk.com</span> <span class="f-web">www.box2pk.com</span>
            </footer>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}