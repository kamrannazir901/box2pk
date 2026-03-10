<?php
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
$tabs = [
    'overview'   => ['label' => 'Addresses', 'icon' => 'dashicons-admin-home'],
    'calculator' => ['label' => 'Calculator', 'icon' => 'dashicons-calculator'],
    'shipment'   => ['label' => 'Order Confirmation', 'icon' => 'dashicons-plus-alt'],
    'history'    => ['label' => 'History', 'icon' => 'dashicons-list-view'],
    'profile'    => ['label' => 'Profile', 'icon' => 'dashicons-admin-users'],
];
?>

<div class="d-none d-md-flex flex-wrap shadow-sm rounded overflow-hidden mb-4" style="background-color: #1e293b;">
    <?php foreach ( $tabs as $id => $info ) : 
        $is_active = ($current_tab === $id);
        
        // Active State: White text with a bottom highlight
        // Inactive State: Light grey/blue text
        $link_style = $is_active 
            ? 'background: rgba(26, 156, 56,1); color: #ffffff; border-bottom: 4px solid #38bdf8;' 
            : 'background: rgba(26, 156, 56,0.6); color: #ffffff; border-bottom: 4px solid transparent;';
    ?>
        <a href="<?php echo esc_url(add_query_arg('tab', $id)); ?>" 
           class="p-3 text-decoration-none fw-bold d-flex align-items-center flex-grow-1 justify-content-center border-end border-secondary border-opacity-25 shipbox-nav-link" 
           style="<?php echo $link_style; ?> transition: all 0.3s ease;">
            <span class="dashicons <?php echo $info['icon']; ?> me-2"></span>
            <?php echo esc_html($info['label']); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="d-md-none d-flex justify-content-between align-items-center mb-3 p-3 border rounded bg-white shadow-sm">
    <span class="fw-bold text-dark">Menu</span>
    <button class="btn btn-dark btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardMobileMenu">
        <span class="dashicons dashicons-menu" style="vertical-align: middle;"></span>
    </button>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="dashboardMobileMenu">
    <div class="offcanvas-header border-bottom bg-light">
        <h5 class="offcanvas-title fw-bold">Navigation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ( $tabs as $id => $info ) : 
                $active_class = ($current_tab === $id) ? 'bg-primary bg-opacity-10 text-primary fw-bold' : 'text-dark';
            ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $id)); ?>" class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center <?php echo $active_class; ?>">
                    <span class="dashicons <?php echo $info['icon']; ?> me-3"></span>
                    <?php echo esc_html($info['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
    /* Hover effect for dark desktop nav */
    .shipbox-nav-link:hover {
        background: rgba(26, 156, 56) !important;
     
    }

    /* for dashbaord menu */


    /* Ensure the offcanvas menu looks professional */
    .offcanvas-end {
        width: 280px !important; /* Fixed width for the slide-out */
    }

    .offcanvas-body .list-group-item {
        font-size: 1.1rem;
        transition: background 0.2s;
    }

    .offcanvas-body .list-group-item:hover {
        background-color: #f1f8f5;
    }

    /* Style the mobile toggle button icon */
    .dashicons-menu {
        font-size: 24px;
        width: 24px;
        height: 24px;
    }
</style>