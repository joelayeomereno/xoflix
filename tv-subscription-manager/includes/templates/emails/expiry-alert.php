<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/emails/expiry-alert.php
 *
 * Variables: {{user_name}}, {{plan_name}}, {{days_left}}
 * Version: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Your {{plan_name}} access expires in {{days_left}} days — renew to keep streaming",
    'btn_text' => "Renew My Access",
    'btn_url'  => home_url('/dashboard'),

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            Your <strong style='color:#18181b;'>{{plan_name}}</strong> subscription is expiring in
            <strong style='color:#d97706;'>{{days_left}} days</strong>.
            Renewing before the expiry date keeps your devices connected without any interruption or reconfiguration.
        </p>

        <!-- Expiry Warning -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:28px;'>
            <tr>
                <td style='background-color:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:20px 24px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:24px; line-height:1; padding-top:2px;'>&#9888;&#65039;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 5px; font-size:13px; font-weight:bold; color:#92400e; font-family:Arial,sans-serif;'>
                                    Expiring in {{days_left}} days
                                </p>
                                <p style='margin:0; font-size:13px; color:#a16207; line-height:1.7; font-family:Arial,sans-serif;'>
                                    Renewing early preserves your server line, credentials, and all device settings.
                                    No re-setup required on any device after renewal.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <p style='margin:0 0 16px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
            Everything that stays intact when you renew:
        </p>

        <!-- Benefit 1 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:10px;'>
            <tr>
                <td style='width:24px; vertical-align:top; padding-top:2px;'>
                    <span style='color:#16a34a; font-size:16px; font-weight:bold; font-family:Arial,sans-serif;'>&#10003;</span>
                </td>
                <td style='padding-left:10px; font-size:14px; color:#3f3f46; line-height:1.7; vertical-align:top; font-family:Arial,sans-serif;'>
                    <strong style='color:#18181b;'>Uninterrupted access</strong> to 25,000+ premium channels &mdash; no blackout, no re-login.
                </td>
            </tr>
        </table>

        <!-- Benefit 2 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:10px;'>
            <tr>
                <td style='width:24px; vertical-align:top; padding-top:2px;'>
                    <span style='color:#16a34a; font-size:16px; font-weight:bold; font-family:Arial,sans-serif;'>&#10003;</span>
                </td>
                <td style='padding-left:10px; font-size:14px; color:#3f3f46; line-height:1.7; vertical-align:top; font-family:Arial,sans-serif;'>
                    <strong style='color:#18181b;'>Your existing Xtream credentials</strong> &mdash; no re-login on any device.
                </td>
            </tr>
        </table>

        <!-- Benefit 3 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:26px;'>
            <tr>
                <td style='width:24px; vertical-align:top; padding-top:2px;'>
                    <span style='color:#16a34a; font-size:16px; font-weight:bold; font-family:Arial,sans-serif;'>&#10003;</span>
                </td>
                <td style='padding-left:10px; font-size:14px; color:#3f3f46; line-height:1.7; vertical-align:top; font-family:Arial,sans-serif;'>
                    <strong style='color:#18181b;'>Any loyalty discounts</strong> applied to your account &mdash; carried forward automatically.
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #f59e0b; padding:10px 14px; background-color:#fffbeb; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:13px; color:#92400e; line-height:1.6; font-family:Arial,sans-serif;'>
                        Click the button above and visit the <strong>Billing</strong> section to choose your preferred renewal duration.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
