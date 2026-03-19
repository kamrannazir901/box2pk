<?php
if ( is_user_logged_in() ) {
    if ( !headers_sent() ) {
        wp_safe_redirect( home_url( '/dashboard/' ) );
    } else {
        echo '<script>window.location.href="' . esc_url( home_url( '/dashboard/' ) ) . '";</script>';
    }
    exit;
}
?>

<style>
    /* ─── Global & Isolation ────────────────────────────────── */
    #sb-login-isolation {
        all: unset;
        display: block;
        width: 100% !important;
        max-width: 1000px !important; /* Adjusted for image proportions */
        margin: 80px auto !important;
        padding: 0 30px !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        box-sizing: border-box !important;
    }

    #sb-login-isolation *, #sb-login-isolation *::before, #sb-login-isolation *::after {
        box-sizing: border-box !important;
    }

    /* ─── Header Styling ────────────────────────── */
    .login-header-area {
        margin-bottom: 50px !important;
        text-align: left !important;
    }

    .login-welcome-title {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        flex-wrap: nowrap !important;
        font-size: 3rem !important;
        font-weight: 500 !important;
        color: #000 !important;
        margin: 0 !important;
        letter-spacing: -1px !important;
    }

   

    .login-logo {
        height: 90px !important;
        width: auto !important;
        /* Reduced horizontal margin (the second value) to 5px or 0 */
        margin: -60px 5px !important; 
        flex-shrink: 0 !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }

    /* ─── Form & Spacing ────────────────────────────────────── */
    .sb-field-group {
        margin-bottom: 35px !important; /* Spacing between input blocks */
    }

    .sb-label-row {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        padding-left: 20px !important; /* Aligns label text with placeholder text */
        margin-bottom: 12px !important; /* Spacing between label and input */
    }

    .sb-label {
        font-size: 20px !important;
        font-weight: 500 !important;
        color: #333 !important;
        padding: 0 !important;
    }

    /* ─── Input Styling ─────────────────────────────────────── */
    .sb-input {
        width: 100% !important;
        height: 45px !important;
        font-size: 20px !important;
        border: 1.5px solid #d1d5db !important;
        border-radius: 12px !important; /* Softer, rounder corners like the pic */
        padding: 0 20px !important;
        color: #333 !important;
        background-color: #fff !important;
        outline: none !important;
        transition: border-color 0.2s !important;
    }

    .sb-input::placeholder {
        color: #4b5563 !important;
        opacity: 0.9 !important;
    }

    .sb-input:focus {
        border-color: #00A651 !important;
    }

    .pass-container {
        position: relative !important;
    }

    .eye-toggle {
        position: absolute !important;
        right: 25px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        cursor: pointer !important;
        opacity: 0.5 !important;
    }

    /* ─── Button ────────────────────────────────────────────── */
    .btn-submit-green {
        width: 100% !important;
        background-color: #00A651 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 12px !important; /* Slightly less round than inputs for visual weight */
        padding: 10px 8px !important;
        font-size: 18px !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        cursor: pointer !important;
        margin-top: 15px !important;
    }

    .btn-submit-green:hover {
        background-color: #008f45 !important;
    }

    /* ─── Links ─────────────────────────────────────────────── */
    .forgot-link {
        color: #00A651 !important;
        font-size: 20px !important;
        text-decoration: none !important;
        font-weight: 500 !important;
    }

    /* ─── Mobile ────────────────────────────────────────────── */
    @media (max-width: 800px) {
        .login-welcome-title { font-size: 1.5rem !important; flex-wrap: wrap !important; }
        .login-logo { height: 40px !important; }
        .sb-input, .btn-submit-green { height: 40px !important; font-size: 18px !important; }
        .btn-submit-green { padding: 8px 6px !important; margin-top: 5px !important; }
        .sb-label { font-size: 16px !important; }
        .forgot-link { font-size: 14px !important; }
    }
</style>

<div id="sb-login-isolation">
    <div class="login-header-area">
        <h1 class="login-welcome-title">
            Welcome to 
            <img src="https://box2pk.com/wp-content/uploads/2026/01/box2pk-logo-01-2048x880.jpg" alt="Box2PK" class="login-logo"> 
            Please Login
        </h1>
    </div>

    <form method="post" id="shipbox-login-form">
        <?php wp_nonce_field('shipbox_login_action', 'shipbox_login_nonce'); ?>
        
        <div class="sb-field-group">
            <div class="sb-label-row">
                <label class="sb-label">Phone Number or Email*</label>
            </div>
            <input type="text" name="log" class="sb-input" placeholder="Please Enter Your Phone Number or Email" required>
        </div>

        <div class="sb-field-group">
            <div class="sb-label-row">
                <label class="sb-label">Password</label>
                <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-link">Forgot Password?</a>
            </div>
            <div class="pass-container">
                <input type="password" name="pwd" id="login-password" class="sb-input" placeholder="Please Enter Your Password" required>
                <span class="eye-toggle" onclick="togglePass()">
                    <span id="eye-icon-svg">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </span>
                </span>
            </div>
        </div>

        <button type="submit" name="shipbox_login_submit" class="btn-submit-green">LOGIN</button>
    </form>
</div>

<script>
function togglePass() {
    const p = document.getElementById("login-password");
    const icon = document.getElementById("eye-icon-svg");
    const open = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const close = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
    
    if (p.type === "password") {
        p.type = "text";
        icon.innerHTML = open;
    } else {
        p.type = "password";
        icon.innerHTML = close;
    }
}
</script>