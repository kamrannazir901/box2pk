<!DOCTYPE html>
<html>
<head>
    <style>
        .email-container { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; }
        .header { background: #fff; padding: 20px; text-align: center; border-bottom: 3px solid #009640; }
        .content { padding: 30px; }
        .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #777; }
        .status-badge { display: inline-block; padding: 8px 15px; background: #009640; color: #fff; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        .info-box { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="<?php echo plugin_dir_url(__FILE__) . '../../assets/logo.png'; ?>" alt="Box2PK" width="150">
            <p style="font-size: 12px; color: #666; margin-top: 5px;">US, UK & TR to your door — Easy, Fast, Reliable!</p>
        </div>
        <div class="content">
            <h3>Hello, <?php echo esc_html($customer_name); ?></h3>
            <p style="font-size: 13px; color: #888;">Customer ID: <?php echo esc_html($customer_id); ?></p>
            
            <?php echo $email_body; // This is where specific template content goes ?>
            
        </div>
        <div class="footer">
            <p><strong>Contact Us:</strong> +92 335 3387766 | info@box2pk.com</p>
            <p>Flat # 4, 2nd Floor. Plot # 65-C, 24th Street, Tauheed Commercial Area, Phase V, DHA, Karachi</p>
        </div>
    </div>
</body>
</html>