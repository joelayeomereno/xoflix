<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/emails/reengage.php
 *
 * Variables: {{user_name}}, {{brand_name}}, {{days_passed}}, {{plan_name}}
 * Version: 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [

    'subject'  => "We've been busy — here is what is new at {{brand_name}}",
    'btn_text' => "See What Is New",
    'btn_url'  => home_url('/dashboard'),

    'body' => "
        <p style='margin:0 0 20px; font-size:15px; color:#3f3f46; font-family:Arial,sans-serif;'>
            Hi <strong style='color:#18181b;'>{{user_name}}</strong>,
        </p>

        <p style='margin:0 0 26px; font-size:15px; color:#3f3f46; line-height:1.75; font-family:Arial,sans-serif;'>
            It has been <strong style='color:#18181b;'>{{days_passed}} days</strong> since we last saw you.
            We spent that time making significant platform upgrades &mdash; and we think you will notice the
            difference from your very first stream.
        </p>

        <!-- What's New Card -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:28px;'>
            <tr>
                <td style='background-color:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; padding:22px 24px;'>
                    <p style='margin:0 0 16px; font-size:11px; font-weight:bold; color:#7c3aed;
                               text-transform:uppercase; letter-spacing:0.08em; font-family:Arial,sans-serif;'>
                        &#10024; What we have improved
                    </p>

                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
                        <tr>
                            <td style='width:18px; vertical-align:top; padding-top:3px; color:#7c3aed; font-size:12px; font-family:Arial,sans-serif;'>&#9670;</td>
                            <td style='padding-left:10px; font-size:13px; color:#4c1d95; line-height:1.7; vertical-align:top; font-family:Arial,sans-serif;'>
                                <strong>Expanded 4K HDR cluster</strong> &mdash; smoother high-bitrate playback with significantly fewer buffering events.
                            </td>
                        </tr>
                    </table>

                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:12px;'>
                        <tr>
                            <td style='width:18px; vertical-align:top; padding-top:3px; color:#7c3aed; font-size:12px; font-family:Arial,sans-serif;'>&#9670;</td>
                            <td style='padding-left:10px; font-size:13px; color:#4c1d95; line-height:1.7; vertical-align:top; font-family:Arial,sans-serif;'>
                                <strong>2,500+ new VOD titles</strong> including the latest cinema releases, premium box sets, and exclusive sports archives.
                            </td>
                        </tr>
                    </table>

                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:18px; vertical-align:top; padding-top:3px; color:#7c3aed; font-size:12px; font-family:Arial,sans-serif;'>&#9670;</td>
                            <td style='padding-left:10px; font-size:13px; color:#4c1d95; line-height:1.7; vertical-align:top; font-family:Arial,sans-serif;'>
                                <strong>Optimised routing</strong> for lower latency and faster channel switching, especially in your region.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Account still here -->
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:26px;'>
            <tr>
                <td style='background-color:#f9fafb; border:1px solid #e4e4e7; border-radius:8px; padding:20px 24px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td style='width:36px; vertical-align:top; font-size:24px; line-height:1; padding-top:2px;'>&#128274;</td>
                            <td style='padding-left:12px; vertical-align:top;'>
                                <p style='margin:0 0 5px; font-size:13px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;'>
                                    Your account is still here
                                </p>
                                <p style='margin:0; font-size:13px; color:#71717a; line-height:1.7; font-family:Arial,sans-serif;'>
                                    Your account and your previous <strong style='color:#18181b;'>{{plan_name}}</strong> configuration
                                    are saved. Re-activate your streaming lines instantly &mdash; no re-setup needed on any device.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='border-left:3px solid #8b5cf6; padding:10px 14px; background-color:#f5f3ff; border-radius:0 8px 8px 0;'>
                    <p style='margin:0; font-size:13px; color:#6d28d9; line-height:1.6; font-family:Arial,sans-serif;'>
                        Log in to your dashboard to explore what is new and check for any return offers available exclusively on your account.
                    </p>
                </td>
            </tr>
        </table>
    ",
];
