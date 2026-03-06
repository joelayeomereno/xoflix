<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/emails/payment-approved.php
 *
 * Variables: {{user_name}}, {{plan_name}}, {{admin_message}}
 * Version: 5.1.0
 *
 * SPAM FIXES:
 *  - Subject: removed exclamation mark and "all set" (promotional trigger)
 *  - Button: removed "Go to My Dashboard" — generic, safe
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Your {{plan_name}} subscription is now active",
    'btn_text' => "Open My Dashboard",
    'btn_url'  => home_url('/dashboard'),

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            Your payment has been confirmed and your
            <strong style='color:#18181b;'>{{plan_name}}</strong> subscription is
            <strong style='color:#16a34a;'>now fully active</strong>.
            Your streaming credentials are ready in your dashboard.
        </p>

        <!-- Subscription Active Banner -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:28px;'>
            <tr>
                <td style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:20px 24px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:24px; line-height:1; padding-top:2px;'>&#9989;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 5px; font-size:13px; font-weight:bold; color:#15803d; font-family:Arial,sans-serif;'>
                                    Subscription Active &mdash; {{plan_name}}
                                </p>
                                <p style='margin:0; font-size:13px; color:#166534; line-height:1.7; font-family:Arial,sans-serif;'>
                                    Your service uses the <strong>Xtream Codes API</strong>. Your <strong>Host URL</strong>,
                                    <strong>Username</strong>, and <strong>Password</strong> are in the
                                    <strong>My Subscriptions</strong> section of your dashboard.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <p style='margin:0 0 16px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
            How to start streaming:
        </p>

        <!-- Step 1 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
            <tr>
                <td style='width:30px; vertical-align:top; padding-top:2px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='width:30px; height:30px; background-color:#18181b; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>1</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Choose a media player</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        We recommend <em>IPTV Smarters Pro</em> (Android &amp; iOS) or <em>TiviMate</em> (Android TV / Firestick).
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
                            <td style='width:30px; height:30px; background-color:#18181b; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>2</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Retrieve your credentials</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Log in and go to <strong>My Subscriptions</strong> to view your Host URL, Username, and Password.
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
                            <td style='width:30px; height:30px; background-color:#18181b; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>3</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Connect via Xtream Codes API</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        In your player, tap <strong>Add User</strong>, select <strong>Xtream Codes API</strong>, and enter your details exactly as shown.
                    </p>
                </td>
            </tr>
        </table>

        <!-- Admin note -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:24px;'>
            <tr>
                <td style='border-left:3px solid #6366f1; padding:12px 16px;
                           background-color:#f5f3ff; border-radius:0 8px 8px 0;'>
                    <p style='margin:0 0 4px; font-size:10px; font-weight:bold; color:#4338ca;
                               text-transform:uppercase; letter-spacing:0.06em; font-family:Arial,sans-serif;'>
                        Note from Support
                    </p>
                    <p style='margin:0; font-size:13px; color:#3730a3; line-height:1.6; font-family:Arial,sans-serif;'>
                        {{admin_message}}
                    </p>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #e4e4e7; padding:10px 14px; background-color:#fafafa; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:12px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Your service runs on a global CDN with 99.9% uptime. Our support team is available around the clock via your dashboard.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
