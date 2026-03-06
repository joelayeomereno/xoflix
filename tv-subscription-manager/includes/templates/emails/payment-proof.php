<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/emails/payment-proof.php
 *
 * Variables: {{user_name}}, {{plan_name}}
 * Version: 5.1.0
 *
 * SPAM FIXES:
 *  - Subject: removed "payment" and "verification" — both are financial spam triggers
 *    Old: "We received your payment for {{plan_name}} — review in progress"
 *    New: "Your {{plan_name}} submission is being reviewed"
 *  - Button text: removed "Track Verification Status" — "verification" is a trigger word
 *    New: "View My Account"
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "Your {{plan_name}} submission is being reviewed",
    'btn_text' => "View My Account",
    'btn_url'  => home_url('/dashboard'),

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            Thank you &mdash; we have received your documentation for
            <strong style='color:#18181b;'>{{plan_name}}</strong>.
            Your submission is now in our secure review queue and will be processed shortly.
        </p>

        <!-- Under Review Banner -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:28px;'>
            <tr>
                <td style='background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:20px 24px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:24px; line-height:1; padding-top:2px;'>&#128203;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 5px; font-size:13px; font-weight:bold; color:#1d4ed8; font-family:Arial,sans-serif;'>
                                    Under Review &mdash; {{plan_name}}
                                </p>
                                <p style='margin:0; font-size:13px; color:#1e40af; line-height:1.7; font-family:Arial,sans-serif;'>
                                    Our billing team typically completes reviews within
                                    <strong>1 to 4 business hours</strong>. A separate email will be sent
                                    the moment your service is activated.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <p style='margin:0 0 16px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
            What happens next:
        </p>

        <!-- Step 1 -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
            <tr>
                <td style='width:30px; vertical-align:top; padding-top:2px;'>
                    <table border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                            <td style='width:30px; height:30px; background-color:#1d4ed8; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>1</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Document review</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Our team cross-references your transaction details against the reference ID provided.
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
                            <td style='width:30px; height:30px; background-color:#1d4ed8; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>2</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Automatic provisioning</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        Once approved, your streaming lines activate automatically and credentials appear in your dashboard instantly.
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
                            <td style='width:30px; height:30px; background-color:#1d4ed8; border-radius:50%;
                                       text-align:center; vertical-align:middle;'>
                                <span style='color:#fff; font-size:12px; font-weight:bold; line-height:30px; display:block; font-family:Arial,sans-serif;'>3</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style='padding-left:12px; vertical-align:top; padding-top:4px;'>
                    <p style='margin:0 0 2px; font-size:14px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>Confirmation email</p>
                    <p style='margin:0; font-size:13px; color:#71717a; line-height:1.6; font-family:Arial,sans-serif;'>
                        You will receive a <em>Subscription Active</em> email with your full setup guide once complete.
                    </p>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #3b82f6; padding:10px 14px; background-color:#eff6ff; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:13px; color:#1d4ed8; line-height:1.6; font-family:Arial,sans-serif;'>
                        No further action is required from you. You can monitor the status of your account at any time from your dashboard.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
