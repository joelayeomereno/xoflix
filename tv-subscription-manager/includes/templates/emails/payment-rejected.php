<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/emails/payment-rejected.php
 *
 * Variables: {{user_name}}, {{plan_name}}, {{admin_message}}
 * Version: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Action needed: We could not verify your {{plan_name}} payment",
    'btn_text' => "Resubmit My Payment",
    'btn_url'  => home_url('/dashboard'),

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            We were unable to verify your recent payment submission for
            <strong style='color:#18181b;'>{{plan_name}}</strong>.
            Your service activation is currently on hold &mdash; but this is straightforward to resolve.
        </p>

        <!-- Reason Banner -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:28px;'>
            <tr>
                <td style='background-color:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:20px 24px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:24px; line-height:1; padding-top:2px;'>&#10060;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 6px; font-size:13px; font-weight:bold; color:#b91c1c; font-family:Arial,sans-serif;'>
                                    Reason for hold
                                </p>
                                <p style='margin:0; font-size:14px; color:#991b1b; line-height:1.7; font-weight:bold; font-family:Arial,sans-serif;'>
                                    {{admin_message}}
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <p style='margin:0 0 16px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
            How to fix this:
        </p>

        <!-- Step 1 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
            <tr>
                <td style='width:30px; vertical-align:top; padding-top:2px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='width:30px; height:30px; background-color:#dc2626; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>1</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Check your screenshot</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Ensure the image clearly shows the recipient account, the exact amount paid, and the transaction date.
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
                            <td style='width:30px; height:30px; background-color:#dc2626; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>2</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Resubmit your proof</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Click the button below to return to your dashboard and upload the corrected documentation.
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
                            <td style='width:30px; height:30px; background-color:#dc2626; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>3</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Still need help?</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Reply to this email or use the live chat available inside your dashboard.
                    </p>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #f59e0b; padding:10px 14px; background-color:#fffbeb; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:13px; color:#92400e; line-height:1.6; font-family:Arial,sans-serif;'>
                        Once correct documentation is received, we will prioritise your review and activate your service as quickly as possible.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
