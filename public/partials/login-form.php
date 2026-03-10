<?php
/**
 * Box2PK Professional Login Template
 * Full Width Container | One-Liner Mobile Header | 18px Labels
 */
if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/dashboard' ) ); 
    exit;
}
?>

<style>
    /* ─── Global & Isolation ────────────────────────────────── */
    #sb-login-isolation {
        all: unset;
        display: block;
        width: 100% !important;
        max-width: 800px !important; /* Page width as requested */
        margin: 60px auto !important;
        padding: 0 20px !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        box-sizing: border-box !important;
    }

    #sb-login-isolation *, #sb-login-isolation *::before, #sb-login-isolation *::after {
        box-sizing: border-box !important;
    }

    /* ─── One-Liner Header ────────────────────────── */
    .login-header-area {
        margin-bottom: 45px !important;
        text-align: left !important;
    }

    .login-welcome-title {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        font-size: 2.5rem !important;
        font-weight: 500 !important;
        color: #000 !important;
        line-height: 1 !important;
        margin: 0 !important;
        white-space: nowrap !important;
    }

    .login-logo {
        height: 65px !important;
        width: auto !important;
        margin: 0 12px !important;
        flex-shrink: 0 !important;
    }

    /* ─── Form Styling ──────────────────────────────────────── */
    .sb-field-group {
        margin-bottom: 25px !important;
    }

    .sb-label {
        display: block !important;
        font-size: 18px !important; /* Requested size */
        font-weight: 500 !important;
        color: #000 !important;
        margin-bottom: 10px !important;
    }

    .sb-input {
        width: 100% !important;
        height: 55px !important;
        font-size: 16px !important;
        border: 1px solid #ced4da !important;
        border-radius: 8px !important;
        padding: 0 20px !important;
        color: #333 !important;
        background-color: #fff !important;
        outline: none !important;
    }

    /* Black Placeholders */
    .sb-input::placeholder {
        color: #000 !important;
        opacity: 1 !important;
    }

    .sb-input:focus {
        border-color: #00A651 !important;
        box-shadow: 0 0 0 3px rgba(0, 166, 81, 0.1) !important;
    }

    .pass-container {
        position: relative !important;
    }

    .eye-toggle {
        position: absolute !important;
        right: 15px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        cursor: pointer !important;
        opacity: 0.7 !important;
        display: flex !important;
        align-items: center !important;
    }

    /* ─── Buttons & Social ──────────────────────────────────── */
    .btn-submit-green {
        width: 100% !important;
        background-color: #00A651 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 16px !important;
        font-size: 18px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        margin-top: 10px !important;
        transition: opacity 0.3s !important;
    }

    .btn-submit-green:hover {
        opacity: 0.9 !important;
    }

    .social-divider {
        text-align: center !important;
        margin: 40px 0 !important;
        position: relative !important;
    }

    .social-divider hr {
        border: 0 !important;
        border-top: 1px solid #eee !important;
    }

    .social-divider span {
        position: absolute !important;
        top: -12px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        background: #fff !important;
        padding: 0 15px !important;
        color: #999 !important;
        font-size: 0.9rem !important;
    }

    .social-row {
        display: flex !important;
        gap: 15px !important;
    }

    .btn-social-outline {
        flex: 1 !important;
        background: #fff !important;
        border: 1px solid #ddd !important;
        border-radius: 8px !important;
        padding: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        color: #333 !important;
        font-size: 16px !important;
    }

    /* ─── Mobile Optimization ──────────────────────────────── */
    @media (max-width: 768px) {
        .login-welcome-title {
            font-size: 1.8rem !important; /* Small enough for one line on mobile */
        }
        .login-logo {
            height: 32px !important; /* Scaled logo */
            margin: 0 8px !important;
        }
        .sb-label {
            font-size: 16px !important;
        }
        .social-row {
            flex-direction: column !important;
        }
    }

    @media (max-width: 400px) {
        .login-welcome-title {
            font-size: 1.4rem !important;
        }
        .login-logo {
            height: 26px !important;
        }
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
        
          <?php if (isset($result) && is_wp_error($result)) : ?>
              <div class="alert alert-danger py-2"><?php echo $result->get_error_message(); ?></div>
          <?php endif; ?>
          
        <div class="sb-field-group">
            <label class="sb-label">Phone Number or Email*</label>
            <input type="text" name="log" class="sb-input" placeholder="Please Enter Your Phone Number or Email" required>
        </div>

        <div class="sb-field-group">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <label class="sb-label" style="margin:0;">Password</label>
                <a href="<?php echo wp_lostpassword_url(); ?>" style="color: #00A651; font-size: 0.95rem; text-decoration: none; font-weight: 600;">Forgot Password?</a>
            </div>
            <div class="pass-container">
                <input type="password" name="pwd" id="login-password" class="sb-input" placeholder="Please Enter Your Password" required>
                <span class="eye-toggle" onclick="togglePass()">
                    <span id="eye-icon-svg">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </span>
                </span>
            </div>
        </div>

        <button type="submit" name="shipbox_login_submit" class="btn-submit-green">LOGIN</button>

        <!-- <div class="social-divider">
            <hr>
            <span>Or, login with</span>
        </div> -->

        <!-- <div class="social-row">
            <a href="#" class="btn-social-outline">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/2021_Facebook_icon.svg" width="20" style="margin-right: 12px;"> Facebook
            </a>
            <a href="#" class="btn-social-outline">
                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" width="18" style="margin-right: 12px;"> Google
            </a>
        </div> -->
    </form>
</div>

<script>
function togglePass() {
    const p = document.getElementById("login-password");
    const icon = document.getElementById("eye-icon-svg");
    const open = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const close = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
    
    if (p.type === "password") {
        p.type = "text";
        icon.innerHTML = open;
    } else {
        p.type = "password";
        icon.innerHTML = close;
    }
}
</script>