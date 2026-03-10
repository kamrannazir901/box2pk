<div class="shipbox-history-container">
<style>
/* Light Backgrounds for each status */
/* Adds a subtle dark stripe on the left of each row for better color coding */
.status-row-awaiting_arrival td.ps-4 { border-left: 5px solid #ffc107 !important; }
.status-row-received         td.ps-4 { border-left: 5px solid #007bff !important; }
.status-row-in_transit       td.ps-4 { border-left: 5px solid #6f42c1 !important; }
.status-row-arrived_karachi  td.ps-4 { border-left: 5px solid #fd7e14 !important; }
.status-row-delivered        td.ps-4 { border-left: 5px solid #28a745 !important; }
 
/* Ensure text remains dark for readability */
.shipbox-custom-table tbody tr td { color: #333; border: none; }

</style>
<?php 
    if ( ! is_user_logged_in() || current_user_can('manage_options') ) : ?>
        <div class="p-4 mb-4 text-dark bg-info bg-opacity-25 border-start border-info border-4 rounded-3 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="dashicons dashicons-info text-info me-3" style="font-size: 30px; width: 30px; height: 30px;"></i>
                <p class="mb-0 fw-medium" style="font-size: 1.1rem;">
                    Please login with your customer account to access this page.
                </p>
            </div>
        </div>
<?php else : ?>
    
    <?php
        $payment_settings = get_option('shipbox_payment_settings', ['enable_online_payment' => false]);
        $is_payment_enabled = $payment_settings['enable_online_payment'];
    ?>

    <h1 class="shipbox-page-title">Order History</h1>
    
    <div class="mb-4 d-flex justify-content-between align-items-end flex-wrap">
        <div>
            <p class="text-uppercase mb-2" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                <?php echo count($shipments); ?> ORDER PLACED
            </p>
            <form method="GET" action="" class="d-flex" style="max-width: 350px;">
                <input type="hidden" name="tab" value="history">
                <div class="input-group border rounded-pill bg-white overflow-hidden" style="border: 1px solid #ddd !important;">
                    <input type="text" name="order_search" class="form-control border-0 px-3" 
                           placeholder="Search Order Number..." 
                           value="<?php echo isset($_GET['order_search']) ? esc_attr($_GET['order_search']) : ''; ?>">
                    <button class="btn btn-white text-muted border-0" type="submit">
                        <i class="dashicons dashicons-search"></i>
                    </button>
                </div>
                <?php if(isset($_GET['order_search'])): ?>
                    <a href="<?php echo remove_query_arg('order_search'); ?>" class="btn btn-link text-danger text-decoration-none py-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-borderless align-middle shipbox-custom-table" style="min-width: 900px; border-collapse: separate; border-spacing: 0 10px;">
            <thead>
                <tr class="text-dark fw-bold" style="font-size: 0.85rem;">
                    <th class="ps-4">ORDER DATE</th>
                    <th>ORDER #</th>
                    <th>STATUS</th>
                    <th>SHIP TO</th>
                    <th class="text-end">SHIPPING</th>
                    <th class="text-end pe-4">TOTAL AMOUNT</th>
                    <?php if ($is_payment_enabled) : ?>
                        <th class="text-end pe-4">ACTION</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty($shipments) ) : ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted bg-white rounded-4">No orders found matching your search.</td></tr>
                <?php else : foreach ( $shipments as $order ) : ?>
                    
                    <?php 
                        $map = [
                            'awaiting_arrival' => 'pending',
                            'received'         => 'received',
                            'in_transit'       => 'in transit',
                            'arrived_karachi'  => 'arrived',
                            'delivered'        => 'delivered'
                        ];

                       
                        
                        $backend_status = $order->status ?? 'awaiting_arrival';
                        $status = $map[$backend_status] ?? 'pending';

                        // This creates classes like "row-normal status-row-received"
                        $row_class = "row-normal status-row-" . esc_attr($backend_status);
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="ps-4 py-3 rounded-start-4">
                            <?php echo date('F d, Y', strtotime($order->created_at)); ?>
                        </td>
                        <td class="py-3">
                            <?php echo esc_html($order->order_number); ?>
                        </td>
                        <td class="py-3 capitalize">
                            <?php echo esc_html($status == 'in_transit' ? 'In progress' : $status); ?>
                        </td>
                        <td class="py-3">
                            <?php echo esc_html($order->city ?? '---'); ?>
                        </td>
                        <td class="py-3 text-end fw-medium text-dark">
                            <?php 
                                $s_price = $order->shipping_price ?? 0;
                                echo ($s_price > 0) ? 'PKR ' . number_format($s_price) : '<span class="text-muted fw-normal small">---</span>'; 
                            ?>
                        </td>
                        <td class="py-3 text-end pe-4 rounded-end-4 fw-bold text-dark">
                            <?php 
                                $f_price = $order->final_price ?? 0;
                                echo ($f_price > 0) ? 'PKR ' . number_format($f_price) : '<span class="text-muted fw-normal small">---</span>'; 
                            ?>
                        </td>

                        <?php if ($is_payment_enabled) : ?>
                            <td class="py-3 text-end pe-4 rounded-end-4">
                                <?php if (isset($order->payment_status) && $order->payment_status === 'paid') : ?>
                                    <span class="badge" style="background-color: var(--plugin-green); color: #fff; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem;">
                                         Paid
                                    </span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'billing', 'action' => 'pay_now', 'order_id' => $order->id])); ?>" 
                                    class="btn btn-sm rounded-pill px-3" 
                                    style="background-color: var(--plugin-green); color: #fff; border-color: var(--plugin-green);">
                                        Pay Now
                                    </a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr> 
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>

<style>
.shipbox-custom-table thead th { padding-bottom: 15px !important; letter-spacing: 0.5px; }
.shipbox-custom-table tbody tr td { background-color: #f8f9fa; border: none; font-size: 0.95rem; color: #333; }
.shipbox-custom-table tbody tr.row-highlight td { background-color: #fffdec; }
.rounded-start-4 { border-radius: 12px 0 0 12px !important; }
.rounded-end-4 { border-radius: 0 12px 12px 0 !important; }
.capitalize { text-transform: capitalize; }
</style>