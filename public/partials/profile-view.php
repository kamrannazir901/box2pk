<?php
if ( ! is_user_logged_in() ) return;

global $wpdb;
$user_id = get_current_user_id();

// Fetch Customer details from your custom table
$customer = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}shipbox_customers WHERE user_id = %d",
    $user_id
) );

$user_data = get_userdata($user_id);

// Define the Edit URL
$edit_url = home_url('/dashboard/?tab=editprofile');
?>

<div class="shipbox-profile-view">
    <div class="d-flex justify-content-between align-items-center mb-1">
            <h1 class="shipbox-page-title">Profile</h1>

        <a href="<?php echo esc_url($edit_url); ?>" class="profile-top-edit-btn">Edit</a>
    </div>
    <p class="text-muted mb-4" style="font-size: 1.1rem;">User details</p>

    <div class="profile-details-table">
        <div class="profile-row d-flex align-items-center">
            <div class="profile-label">Name:</div>
            <div class="profile-value"><?php echo esc_html($user_data->display_name); ?></div>
        </div>

        <div class="profile-row d-flex align-items-center">
            <div class="profile-label">Customer ID:</div>
            <div class="profile-value"><?php echo esc_html($customer->customer_id ?? 'N/A'); ?></div>
        </div>

        <div class="profile-row d-flex align-items-center">
            <div class="profile-label">Password:</div>
            <div class="profile-value">************</div>
        </div>

        <div class="profile-row d-flex align-items-center">
            <div class="profile-label">E-mail:</div>
            <div class="profile-value"><?php echo esc_html($user_data->user_email); ?></div>
        </div>

        <div class="profile-row d-flex align-items-center">
            <div class="profile-label">Phone:</div>
            <div class="profile-value"><?php echo esc_html($customer->phone ?? 'N/A'); ?></div>
        </div>

        <div class="profile-row d-flex align-items-center no-border">
            <div class="profile-label">Address:</div>
            <div class="profile-value"><?php echo esc_html($customer->address ?? 'N/A'); ?></div>
        </div>
    </div>
</div>

<style>
    /* 1. Only Button uses Brown variable */
    .profile-top-edit-btn {
        background-color: var(--plugin-brown);
        color: #fff !important;
        text-decoration: none !important;
        font-weight: 500;
        font-size: 1.2rem;
        padding: 8px 24px;
        border-radius: 8px;
        transition: opacity 0.2s ease;
        display: inline-block;
    }

    .profile-top-edit-btn:hover {
        opacity: 0.9;
    }

    /* 2. Table & Borders: Pure Black */
    .profile-details-table {
        border: 1.5px solid #000;
        border-radius: 25px;
        overflow: hidden;
        padding: 10px 0;
        background-color: #fff;
    }

    .profile-row {
        padding: 12px 25px;
        border-bottom: 1.5px solid #000;
    }

    .profile-row.no-border {
        border-bottom: none;
    }

    /* 3. Text: All Black */
    .profile-label {
        width: 180px;
        font-weight: 600;
        font-size: 1.1rem;
        color: #000; /* Overridden from purple to black */
    }

    .profile-value {
        flex-grow: 1;
        font-size: 1.1rem;
        color: #000;
    }

    @media (max-width: 768px) {
        .profile-label { width: 120px; }
    }
</style>