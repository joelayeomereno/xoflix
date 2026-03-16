<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/auth/auth-welcome.php
 *
 * Variables: {{user_name}}, {{user_email}}, {{brand_name}}, {{login_url}}
 * Version: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Welcome to {{brand_name}} — your account is ready",
    'btn_text' => "Access My Account",

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            Welcome aboard! Your <strong style='color:#18181b;'>{{brand_name}}</strong> account has been
            created successfully. You now have full access to our streaming platform.
        </p>

        <!-- Account Details Card -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:28px;'>
            <tr>
                <td style='background-color:#f9fafb; border:1px solid #e4e4e7; border-radius:8px; padding:20px 24px;'>
                    <p style='margin:0 0 14px; font-size:10px; font-weight:bold; color:#71717a;
                               text-transform:uppercase; letter-spacing:0.08em; font-family:Arial,sans-serif;'>
                        &#128273; Your Account Details
                    </p>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='font-size:13px; color:#a1a1aa; padding-bottom:8px; width:90px; vertical-align:top; font-family:Arial,sans-serif;'>Username</td>
                            <td style='font-size:13px; color:#18181b; font-weight:bold; padding-bottom:8px; vertical-align:top; font-family:Arial,sans-serif;'>{{user_name}}</td>
                        </tr>
                        <tr>
                            <td style='font-size:13px; color:#a1a1aa; padding-bottom:8px; width:90px; vertical-align:top; font-family:Arial,sans-serif;'>Email</td>
                            <td style='font-size:13px; color:#18181b; font-weight:bold; padding-bottom:8px; vertical-align:top; font-family:Arial,sans-serif;'>{{user_email}}</td>
                        </tr>
                        <tr>
                            <td style='font-size:13px; color:#a1a1aa; width:90px; vertical-align:middle; font-family:Arial,sans-serif;'>Status</td>
                            <td style='vertical-align:middle;'>
                                <span style='background:#dcfce7; color:#15803d; font-size:11px; font-weight:bold;
                                             padding:3px 10px; border-radius:20px; font-family:Arial,sans-serif;'>
                                    &#10003; Active
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <p style='margin:0 0 16px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
            Get started in 3 steps:
        </p>

        <!-- Step 1 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
            <tr>
                <td style='width:30px; vertical-align:top; padding-top:2px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='width:30px; height:30px; background-color:#6366f1; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>1</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Log in to your account</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Use your email address and the password you set during registration.
                    </p>
                </td>
            </tr>
        </table>

        <!-- Step 2 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
            <tr>
                <td style='width:30px; vertical-align:top; padding-top:2px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='width:30px; height:30px; background-color:#6366f1; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>2</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Choose a streaming plan</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Browse our available plans and select the one that fits your needs.
                    </p>
                </td>
            </tr>
        </table>

        <!-- Step 3 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:26px;'>
            <tr>
                <td style='width:30px; vertical-align:top; padding-top:2px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='width:30px; height:30px; background-color:#6366f1; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>3</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Start streaming</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Once your payment is confirmed, your credentials appear in your dashboard instantly.
                    </p>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #6366f1; padding:10px 14px; background-color:#f5f3ff; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:13px; color:#4338ca; line-height:1.6; font-family:Arial,sans-serif;'>
                        <strong>Security tip:</strong> We will never ask for your password by email.
                        If you did not create this account, contact our support team immediately.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
