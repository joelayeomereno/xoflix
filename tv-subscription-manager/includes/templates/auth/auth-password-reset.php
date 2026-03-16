<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/auth/auth-password-reset.php
 *
 * Variables: {{user_name}}, {{reset_url}}, {{brand_name}}, {{expiry_time}}
 * Version: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Reset your {{brand_name}} password",
    'btn_text' => "Reset My Password",

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            We received a request to reset the password for your
            <strong style='color:#18181b;'>{{brand_name}}</strong> account.
            Click the button below to set a new password.
        </p>

        <!-- Link Info -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:20px;'>
            <tr>
                <td style='background-color:#f9fafb; border:1px solid #e4e4e7; border-radius:8px; padding:18px 22px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:22px; line-height:1; padding-top:2px;'>&#128274;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 4px; font-size:13px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
                                    One-time secure link
                                </p>
                                <p style='margin:0; font-size:13px; color:#71717a; line-height:1.7; font-family:Arial,sans-serif;'>
                                    This link expires in <strong style='color:#18181b;'>{{expiry_time}}</strong> and can only be used once.
                                    After expiry you will need to request a new one.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Security Warning -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:26px;'>
            <tr>
                <td style='background-color:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:18px 22px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:22px; line-height:1; padding-top:2px;'>&#9888;&#65039;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 4px; font-size:13px; font-weight:bold; color:#92400e; font-family:Arial,sans-serif;'>
                                    Didn't request this?
                                </p>
                                <p style='margin:0; font-size:13px; color:#a16207; line-height:1.7; font-family:Arial,sans-serif;'>
                                    If you did not request a password reset, you can safely ignore this email.
                                    Your current password will remain unchanged and your account is secure.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #e4e4e7; padding:10px 14px; background-color:#f9fafb; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:12px; color:#a1a1aa; line-height:1.6; font-family:Arial,sans-serif;'>
                        If the button above does not work, copy and paste the reset link from the fallback URL shown below the button.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
