<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/auth/auth-password-changed.php
 *
 * Variables: {{user_name}}, {{user_email}}, {{brand_name}}, {{login_url}}, {{changed_at}}
 * Version: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Your {{brand_name}} password has been changed",
    'btn_text' => "Secure My Account",

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            This is a security confirmation that the password for your
            <strong style='color:#18181b;'>{{brand_name}}</strong> account has been successfully changed.
        </p>

        <!-- Change Summary Card -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:20px;'>
            <tr>
                <td style='background-color:#f9fafb; border:1px solid #e4e4e7; border-radius:8px; padding:20px 24px;'>
                    <p style='margin:0 0 14px; font-size:10px; font-weight:bold; color:#71717a;
                               text-transform:uppercase; letter-spacing:0.08em; font-family:Arial,sans-serif;'>
                        &#128203; Change Summary
                    </p>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='font-size:13px; color:#a1a1aa; padding-bottom:8px; width:100px; vertical-align:top; font-family:Arial,sans-serif;'>Account</td>
                            <td style='font-size:13px; color:#18181b; font-weight:bold; padding-bottom:8px; vertical-align:top; font-family:Arial,sans-serif;'>{{user_email}}</td>
                        </tr>
                        <tr>
                            <td style='font-size:13px; color:#a1a1aa; padding-bottom:8px; width:100px; vertical-align:top; font-family:Arial,sans-serif;'>Changed at</td>
                            <td style='font-size:13px; color:#18181b; font-weight:bold; padding-bottom:8px; vertical-align:top; font-family:Arial,sans-serif;'>{{changed_at}}</td>
                        </tr>
                        <tr>
                            <td style='font-size:13px; color:#a1a1aa; width:100px; vertical-align:middle; font-family:Arial,sans-serif;'>Action</td>
                            <td style='vertical-align:middle;'>
                                <span style='background:#dcfce7; color:#15803d; font-size:11px; font-weight:bold;
                                             padding:3px 10px; border-radius:20px; font-family:Arial,sans-serif;'>
                                    &#10003; Password Updated
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Security Warning -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:26px;'>
            <tr>
                <td style='background-color:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:20px 24px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:22px; line-height:1; padding-top:2px;'>&#128680;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 5px; font-size:13px; font-weight:bold; color:#b91c1c; font-family:Arial,sans-serif;'>
                                    Was this you?
                                </p>
                                <p style='margin:0; font-size:13px; color:#991b1b; line-height:1.7; font-family:Arial,sans-serif;'>
                                    If you did not make this change, your account may be compromised.
                                    Click the button below to secure your account immediately, or contact our support team via your dashboard.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #22c55e; padding:10px 14px; background-color:#f0fdf4; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:13px; color:#15803d; line-height:1.6; font-family:Arial,sans-serif;'>
                        If everything looks correct, no further action is needed. Your new password is active and you can log in normally.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
