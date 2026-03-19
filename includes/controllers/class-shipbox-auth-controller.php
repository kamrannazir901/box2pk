<?php
if (!defined('ABSPATH')) {
    exit;
}

class ShipBox_Auth_Controller {

    private $model;
    public $auth_error = null;
    
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . '../models/class-shipbox-customer-model.php';
        $this->model = new ShipBox_Customer_Model();
    }

    /**
     * Process Registration
     */
   public function handle_registration() {
        // 1. Security & Terms Check
        if (!isset($_POST['shipbox_reg_nonce']) || !wp_verify_nonce($_POST['shipbox_reg_nonce'], 'shipbox_register_action')) {
            return new WP_Error('security_fail', 'Security check failed.');
        }

        if (!isset($_POST['terms_accept'])) {
            return new WP_Error('terms_error', 'You must accept the Terms and Conditions.');
        }

        // 2. Password Confirmation Check
        if ($_POST['password'] !== $_POST['confirm_password']) {
            return new WP_Error('password_mismatch', 'Passwords do not match.');
        }

        // 3. Sanitization
        $email    = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $cnic     = sanitize_text_field($_POST['cnic']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name  = sanitize_text_field($_POST['last_name']);
        $zip_code        = sanitize_text_field($_POST['zip_code']);
        $referral_source = sanitize_text_field($_POST['referral_source']);
        $phone_alt       = sanitize_text_field($_POST['phone_alt']);

        // 4. Validation
        if (email_exists($email)) {
            return new WP_Error('email_taken', 'This email is already registered.');
        }

        // 5. Create WP User
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) return $user_id;

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ]);

        // 6. Create ShipBox Customer Record
        $customer_data = [
            'user_id'     => $user_id,
            'customer_id' => $this->model->get_next_customer_id(),
            'cnic'        => $cnic,
            'phone'       => sanitize_text_field($_POST['phone']),
            'alt_phone'   => sanitize_text_field($_POST['phone_alt']), // Added this to match your table!
            'address'     => sanitize_textarea_field($_POST['address']),
            'city'        => sanitize_text_field($_POST['city']),
            'province'    => sanitize_text_field($_POST['province']),
            'postal_code' => sanitize_text_field($_POST['zip_code']), // Match the DB column name
            'status'      => 'active',
            'referral_source' => sanitize_text_field($_POST['referral_source'])
        ];

        if ($this->model->add_customer($customer_data)) {
            $this->model->increment_counter();
            
            // Optional: Save referral source to user meta
            if (!empty($_POST['referral_source'])) {
                update_user_meta($user_id, 'shipbox_referral', sanitize_text_field($_POST['referral_source']));
            }

            wp_set_auth_cookie($user_id);
            wp_set_current_user($user_id);


            $redirect_url = home_url('/dashboard/');
            if (!headers_sent()) {
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                echo '<script>window.location.href="' . esc_url($redirect_url) . '";</script>';
                exit;
            }
        }

        return new WP_Error('db_error', 'Profile creation failed.');
    }

        /**
         * Process Login
         */
    public function handle_login() {
        if (!isset($_POST['shipbox_login_nonce']) || !wp_verify_nonce($_POST['shipbox_login_nonce'], 'shipbox_login_action')) {
            return new WP_Error('security_fail', 'Security check failed.');
        }

        $creds = [
            'user_login'    => sanitize_text_field($_POST['log']),
            'user_password' => $_POST['pwd'],
            'remember'      => false,
        ];

        $user = wp_signon($creds, false);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $customer_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d",
            $user->ID
        ));

        if ($customer_status && $customer_status !== 'active') {
            wp_logout();
            return new WP_Error('account_blocked', '<strong>Your account is blocked.</strong> Please contact support.');
        }

        wp_set_current_user($user->ID);

        $redirect_url = current_user_can('manage_options') ? admin_url() : home_url('/dashboard/');

        if (!headers_sent()) {
            wp_safe_redirect($redirect_url);
            exit;
        }

        // Fallback for when output already started
        echo '<script>window.location.href="' . esc_url($redirect_url) . '";</script>';
        exit;
    }

    public function process_auth_actions() {
        if ( isset($_POST['shipbox_reg_nonce']) ) {
            $result = $this->handle_registration();
            // If it's a WP_Error, the shortcode will handle showing the message
        }
        
        if ( isset($_POST['shipbox_login_nonce']) ) {
            $result = $this->handle_login();
        }
    }

    /**
     * Render and Process Profile Update
     */
  
    public function handle_profile_update() {
        if ( ! is_user_logged_in() || current_user_can('manage_options') ) return '<div class="p-4 mb-4 text-dark bg-info bg-opacity-25 border-start border-info border-4 rounded-3 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="dashicons dashicons-info text-info me-3" style="font-size: 30px; width: 30px; height: 30px;"></i>
                    <p class="mb-0 fw-medium" style="font-size: 1.1rem;">
                        Please login with your customer account to access this page.
                    </p>
                </div>
            
            </div>';

        global $wpdb;
        $user_id = get_current_user_id();
        $message = '';

        if ( isset($_POST['shipbox_profile_save']) ) {
            if ( ! isset($_POST['shipbox_profile_nonce']) || ! wp_verify_nonce($_POST['shipbox_profile_nonce'], 'shipbox_profile_action') ) {
                $message = '<div class="alert alert-danger">Security check failed.</div>';
            } else {
                $user_data = array( 'ID' => $user_id );
                $error = false;

                // Password Change Logic
                if ( ! empty($_POST['new_password']) ) {
                    if ( $_POST['new_password'] === $_POST['confirm_password'] ) {
                        $user_data['user_pass'] = $_POST['new_password'];
                    } else {
                        $message = '<div class="alert alert-danger">Passwords do not match.</div>';
                        $error = true;
                    }
                }

                

                if ( ! $error ) {
                    // Update WordPress User Table
                    $first_name = sanitize_text_field($_POST['first_name']);
                    $last_name  = sanitize_text_field($_POST['last_name']);

                    // Combine names for Display Name
                    $display_name = trim($first_name . ' ' . $last_name);

                    // Update WordPress User Table Data
                    $user_data['first_name']   = $first_name;
                    $user_data['last_name']    = $last_name;
                    $user_data['display_name'] = $display_name;
                        
                    wp_update_user($user_data);

                    // Update ShipBox Custom Table
                    $wpdb->update(
                        $wpdb->prefix . 'shipbox_customers',
                        array(
                            'phone'       => sanitize_text_field($_POST['phone']),
                            'alt_phone'   => sanitize_text_field($_POST['alt_phone']),
                            'city'        => sanitize_text_field($_POST['city']),
                            'province'    => sanitize_text_field($_POST['province']),
                            'postal_code' => sanitize_text_field($_POST['zip_code']),
                            'address'     => sanitize_textarea_field($_POST['address']),
                            // If you have a country column in your table:
                            // 'country'  => sanitize_text_field($_POST['country']),
                        ),
                        array( 'user_id' => $user_id )
                    );
                    $message = '<div class="alert alert-success">Profile updated successfully!</div>';
                }
            }
        }

        $user = wp_get_current_user();
        $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d", $user_id ) );

        ob_start();
        include plugin_dir_path( __FILE__ ) . '../../public/partials/edit-profile-view.php';
        return $message . ob_get_clean();
    }
}