<?php
function get_password_reset_email($email, $reset_link) {
    $site_name = SITE_NAME;
    $site_url = SITE_URL;
    
    return <<<EMAIL
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
        .button { 
            display: inline-block; 
            background-color: #0d6efd; 
            color: white !important; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 15px 0;
        }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>$site_name</h2>
        </div>
        
        <div class="content">
            <p>Hello,</p>
            <p>You have requested to reset your password for your account at $site_name.</p>
            <p>Please click the button below to reset your password:</p>
            
            <p style="text-align: center;">
                <a href="$reset_link" class="button">Reset Password</a>
            </p>
            
            <p>If you didn't request this password reset, you can safely ignore this email.</p>
            <p>The password reset link will expire in 1 hour.</p>
        </div>
        
        <div class="footer">
            <p>&copy; $site_name. All rights reserved.</p>
            <p><a href="$site_url">$site_url</a></p>
        </div>
    </div>
</body>
</html>
EMAIL;
}
?>