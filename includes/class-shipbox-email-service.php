<?php
/**
 * ShipBox Email Service
 * Updated: 18px Font, Fixed Header, and Automatic Two-Column Warehouse/Image Layout.
 */

if (!defined('ABSPATH')) exit;

class ShipBox_Email_Service {

    /**
     * Main handler for status-based emails
     */
    public static function send_status_change_email($order, $new_status, $warehouse = null) {
        if (!is_object($order) || empty($order->user_email) || !is_email($order->user_email)) {
            return false;
        }

        $template_map = [
            'awaiting_arrival'           => 'address_usage_confirmation',
            'address_usage_confirmation' => 'address_usage_confirmation',
            'received'                   => !empty($order->is_consolidated) ? 'package_received_partial' : 'package_received_single',
            'in_transit'                 => 'in_transit',
            'arrived_karachi'            => 'arrived_karachi',
            'delivered'                  => 'delivered'
        ];

        $template_key = $template_map[$new_status] ?? '';
        if (!$template_key) return false;

        $data = [
            'customer_name'  => $order->display_name ?? 'Customer',
            'customer_id'    => !empty($order->public_customer_id) ? $order->public_customer_id : ($order->customer_id ?? 'N/A'),
            'order_number'   => $order->order_number ?? 'N/A',
            'warehouse_data' => $warehouse, 
            'screenshot_url' => $order->screenshot_url ?? '',
            'shipping_price' => isset($order->shipping_price) ? (float)$order->shipping_price : 0,
            'final_price'    => isset($order->final_price) ? (float)$order->final_price : 0
        ];

        $saved_subjects = get_option('shipbox_email_subjects', []);
        $raw_subject = isset($saved_subjects[$template_key]) && !empty($saved_subjects[$template_key]) 
                       ? $saved_subjects[$template_key] 
                       : "Update regarding your order {order_number}";
        
        $final_subject = self::apply_placeholders($raw_subject, $data);
        $body = self::get_email_template($template_key, $data);

        return self::execute_mail($order->user_email, $final_subject, $body, $order->id ?? 0, $order->customer_id ?? 0, 'status_change');
    }

    /**
     * Handler for Custom Manual Emails
     */
    public static function send_custom_email($order, $subject, $message_body) {
        if (empty($order->user_email) || !is_email($order->user_email)) return false;

        $data = [
            'customer_name'  => $order->display_name ?? 'Customer',
            'customer_id'    => !empty($order->public_customer_id) ? $order->public_customer_id : ($order->customer_id ?? 'N/A'),
            'order_number'   => $order->order_number ?? 'N/A',
            'manual_message' => $message_body,
            'screenshot_url' => $order->screenshot_url ?? ''
        ];

        $final_subject = self::apply_placeholders($subject, $data);
        $saved_templates = get_option('shipbox_email_templates', []);
        
        if (!empty($saved_templates['manual_custom_email'])) {
            $body = self::get_email_template('manual_custom_email', $data);
        } else {
            $body = self::get_email_wrapper($message_body, $data['customer_name'], $data['customer_id']);
        }

        return self::execute_mail($order->user_email, $final_subject, $body, $order->id ?? 0, $order->customer_id ?? 0, 'manual');
    }

    /**
     * Internal Helper: Replaces {tags} with actual data
     */
    private static function apply_placeholders($text, $data) {
        $ship_cost    = "PKR " . number_format($data['shipping_price'] ?? 0, 2);
        $final_cost   = "PKR " . number_format($data['final_price'] ?? 0, 2);
        $invoice_url  = home_url('/?sb_invoice=' . urlencode($data['order_number'] ?? ''));
        $invoice_link = "<a href='{$invoice_url}' style='color: #1a9c38; text-decoration: none;'>attached invoice</a>";
 
        $address_block = '';
        if (!empty($data['warehouse_data'])) {
            if (is_array($data['warehouse_data'])) {
                $w = $data['warehouse_data'];
                $lines = [
                    "Warehouse Address:",
                    esc_html($data['customer_name']),
                    esc_html($w['address_line1'] ?? ''),
                    esc_html($w['address_line2_prefix'] ?? ''),
                    esc_html(($w['city'] ?? '') . ', ' . ($w['state'] ?? '') . ' ' . ($w['zip_code'] ?? '')),
                    esc_html($w['country_code'] ?? ''),
                    'Cell: ' . esc_html($w['phone'] ?? '')
                ];
                $address_block = implode("<br>", array_filter($lines));
            } else {
                $address_block = "<strong>Warehouse Address:</strong><br>" . $data['warehouse_data'];
            }
        }

        // AUTO TWO-COLUMN LOGIC: If image exists, force it next to the warehouse address
        $warehouse_placeholder_content = $address_block;

        if (!empty($data['screenshot_url']) && !empty($address_block)) {
            $warehouse_placeholder_content = "
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='margin-top:10px;'>
                <tr>
                    <td width='55%' align='left' valign='top' style='font-size:18px; line-height:1.2; padding-right:15px;'>
                        {$address_block}
                    </td>
                    <td width='45%' align='right' valign='top'>
                        <img src='" . esc_url($data['screenshot_url']) . "' style='width:100%; max-width:280px; height:auto; border-radius:4px; display:block;'>
                    </td>
                </tr>
            </table>";
        }

        $placeholders = [
            '{customer_name}'     => $data['customer_name'],
            '{customer_id}'       => $data['customer_id'],
            '{order_number}'      => $data['order_number'],
            '{shipping_price}'    => $ship_cost,
            '{final_price}'       => $final_cost,
            '{invoice_link}'      => $invoice_link,
            '{warehouse_address}' => $warehouse_placeholder_content,
            '{message}'           => $data['manual_message'] ?? ''
        ];

        return strtr($text, $placeholders);
    }

    private static function get_email_template($type, $data) {
        $saved_templates = get_option('shipbox_email_templates', []);
        $raw_content = isset($saved_templates[$type]) ? $saved_templates[$type] : '';

        if (empty($raw_content) && $type !== 'manual_custom_email') {
            return "No template found for $type";
        }

        $processed_content = self::apply_placeholders($raw_content, $data);
        return self::get_email_wrapper($processed_content, $data['customer_name'], $data['customer_id']);
    }

    /**
     * Standard HTML Wrapper: Fixed 18px Font and Layout
     */
    private static function get_email_wrapper($content, $name, $id) {
        $logo = 'https://box2pk.com/wp-content/uploads/2026/02/unnamed-e1772281321920.jpg';
        $dashboard_url = 'https://box2pk.com/dashboard/?tab=history';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 700px; margin: auto; color: #000; background: #fff; border: 1px solid #eee;'>
            <div style='padding: 20px; text-align: left; border-bottom: 2px solid #eee;'>
                <img src='{$logo}' width='150' style='display: block;'>
            </div>
            
            <div style='font-size: 18px; line-height: 1.2; padding: 20px;'>
                <div style='margin-bottom: 20px;'>
                    {$name}<br>
                    Customer ID: {$id}
                </div>

                <div style='margin-top:10px;'>
                    " . wpautop($content) . "
                </div>
            </div>

            <div style='padding:0px 20px 20px 20px;'>
                <div style='margin-bottom: 8px;'>
                    <a href='{$dashboard_url}' style='background:#009640; color:#fff; padding:10px 25px; text-decoration:none; border-radius:4px; display:inline-block; font-size: 16px; font-weight:bold;'>View Order</a>
                    <p style='font-size: 16px; color: #777; margin-top: 15px;'>You can reply to this email.</p>
                </div>
            </div>
        </div>";
    }

    private static function execute_mail($to, $subject, $body, $order_id, $cust_id, $type) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Box2PK <info@box2pk.com>',
            'Reply-To: info@box2pk.com'
        ];
        
        $sent = wp_mail($to, $subject, $body, $headers);

        if ($sent) {
            global $wpdb;
            $table = $wpdb->prefix . 'shipbox_email_logs';
            $wpdb->insert($table, [
                'customer_id'   => $cust_id,
                'order_id'      => $order_id,
                'email_type'    => $type,
                'email_to'      => $to,
                'email_subject' => $subject,
                'email_body'    => $body,
                'sent_at'       => current_time('mysql'),
                'status'        => 'sent'
            ]);
        }
        return $sent;
    }
}