<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/includes/templates/emails/email-wrapper.php
 *
 * Version: 5.0.0 — Anti-spam hardened, white card, professionally redesigned
 *
 * SPAM FIXES IN THIS VERSION:
 *  ✓ Background: neutral #f4f4f5 (not blue-grey, not promotional-looking)
 *  ✓ Email card: pure white #ffffff
 *  ✓ Header: light #f9fafb (NOT dark — dark headers score as phishing)
 *  ✓ No background-image / CSS gradients on outer wrapper (spam trigger)
 *  ✓ Preheader padded with zero-width joiners (prevents body-bleed into preview)
 *  ✓ CTA button includes VML fallback for Outlook + visible plain-text URL
 *  ✓ Footer includes reason line (CAN-SPAM / GDPR compliance requirement)
 *  ✓ Font stack uses web-safe Arial (no Google Fonts requests = no tracker flag)
 *  ✓ No red/yellow text in the wrapper itself (reserved for body templates)
 *
 * @var string $body_content
 * @var string $title
 * @var string $badge_label
 * @var string $badge_bg
 * @var string $accent_hex
 * @var string $btn_url
 * @var string $btn_text
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$brand_name = get_bloginfo( 'name' );
$site_url   = home_url();
$year       = date( 'Y' );

$preheader_raw = substr( wp_strip_all_tags( $body_content ), 0, 90 );

if ( empty( $accent_hex ) ) { $accent_hex = '#4f46e5'; }
if ( empty( $badge_bg ) )   { $badge_bg   = '#ede9fe'; }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
    <title><?php echo esc_html( $brand_name ); ?> — <?php echo esc_html( $title ); ?></title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style type="text/css">
        body,table,td,a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table,td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { -ms-interpolation-mode:bicubic; border:0; height:auto; line-height:100%; outline:none; text-decoration:none; }
        body { margin:0 !important; padding:0 !important; background-color:#f4f4f5; }

        @media only screen and (max-width:620px) {
            .email-card  { width:100% !important; border-radius:0 !important; }
            .c-pad       { padding:28px 20px !important; }
            .h-pad       { padding:20px !important; }
            .f-pad       { padding:20px !important; }
            .btn-cell    { display:block !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;">

    <!-- PREHEADER hidden text -->
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f4f4f5;">
        <?php echo esc_html( $preheader_raw ); ?>&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <!-- OUTER WRAPPER -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f5; min-width:100%;">
        <tr>
            <td align="center" valign="top" style="padding:40px 16px 52px;">

                <!-- EMAIL CARD -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="email-card"
                       style="max-width:600px; width:100%; background-color:#ffffff;
                              border-radius:10px; overflow:hidden;
                              border:1px solid #e4e4e7;
                              box-shadow:0 2px 16px rgba(0,0,0,0.07);">

                    <!-- TOP COLOUR BAR -->
                    <tr>
                        <td style="height:5px; background-color:<?php echo esc_attr($accent_hex); ?>; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>

                    <!-- ═══════════════════════════════════
                         HEADER — light grey, not dark
                    ═══════════════════════════════════════ -->
                    <tr>
                        <td class="h-pad" style="padding:22px 40px; background-color:#f9fafb; border-bottom:1px solid #e4e4e7;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td valign="middle">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <!-- Brand initial block -->
                                                <td style="width:34px; height:34px; background-color:<?php echo esc_attr($accent_hex); ?>;
                                                           border-radius:6px; text-align:center; vertical-align:middle;">
                                                    <span style="color:#ffffff; font-size:15px; font-weight:bold;
                                                                 line-height:34px; display:block; font-family:Arial,sans-serif;">
                                                        <?php echo esc_html( strtoupper( mb_substr( $brand_name, 0, 1 ) ) ); ?>
                                                    </span>
                                                </td>
                                                <td style="padding-left:10px; vertical-align:middle;">
                                                    <span style="font-size:16px; font-weight:bold; color:#18181b; font-family:Arial,sans-serif;">
                                                        <?php echo esc_html( $brand_name ); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <?php if ( ! empty( $badge_label ) ) : ?>
                                    <td valign="middle" align="right">
                                        <span style="display:inline-block; padding:4px 12px;
                                                     background-color:<?php echo esc_attr($badge_bg); ?>;
                                                     color:<?php echo esc_attr($accent_hex); ?>;
                                                     border-radius:20px; font-size:10px; font-weight:bold;
                                                     text-transform:uppercase; letter-spacing:0.06em; font-family:Arial,sans-serif;">
                                            <?php echo esc_html( $badge_label ); ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ═══════════════════════════════════
                         PAGE TITLE
                    ═══════════════════════════════════════ -->
                    <tr>
                        <td style="padding:34px 40px 0; background-color:#ffffff;">
                            <h1 style="margin:0; color:#18181b; font-size:22px; font-weight:bold;
                                       line-height:1.3; font-family:Arial,sans-serif;">
                                <?php echo esc_html( $title ); ?>
                            </h1>
                        </td>
                    </tr>

                    <!-- ═══════════════════════════════════
                         BODY CONTENT
                    ═══════════════════════════════════════ -->
                    <tr>
                        <td class="c-pad" style="padding:22px 40px 36px; background-color:#ffffff;">
                            <div style="color:#3f3f46; font-size:15px; line-height:1.75; font-family:Arial,sans-serif;">
                                <?php echo $body_content; ?>
                            </div>

                            <?php if ( ! empty( $btn_url ) ) : ?>
                            <!-- CTA BUTTON (table-centred + VML for Outlook) -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top:36px;">
                                <tr>
                                    <td align="center">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
                                            xmlns:w="urn:schemas-microsoft-com:office:word"
                                            href="<?php echo esc_url($btn_url); ?>"
                                            style="height:48px;v-text-anchor:middle;width:230px;"
                                            arcsize="10%" stroke="f" fillcolor="<?php echo esc_attr($accent_hex); ?>">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">
                                            <?php echo esc_html($btn_text); ?>
                                        </center></v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td class="btn-cell" align="center"
                                                    style="background-color:<?php echo esc_attr($accent_hex); ?>; border-radius:6px;">
                                                    <a href="<?php echo esc_url($btn_url); ?>"
                                                       target="_blank"
                                                       style="display:inline-block;
                                                              background-color:<?php echo esc_attr($accent_hex); ?>;
                                                              color:#ffffff; padding:14px 34px; border-radius:6px;
                                                              font-size:15px; font-weight:bold;
                                                              font-family:Arial,sans-serif;
                                                              text-decoration:none; white-space:nowrap;">
                                                        <?php echo esc_html($btn_text); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <!-- Plain-text fallback URL -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top:14px;">
                                <tr>
                                    <td align="center">
                                        <p style="margin:0; font-size:11px; color:#a1a1aa; font-family:Arial,sans-serif;">
                                            Button not working? Copy this link:&nbsp;
                                            <a href="<?php echo esc_url($btn_url); ?>"
                                               style="color:<?php echo esc_attr($accent_hex); ?>; word-break:break-all; font-size:11px;">
                                                <?php echo esc_url($btn_url); ?>
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>

                        </td>
                    </tr>

                    <!-- DIVIDER -->
                    <tr>
                        <td style="padding:0 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr><td style="border-top:1px solid #e4e4e7; height:1px; font-size:0; line-height:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ═══════════════════════════════════
                         FOOTER — CAN-SPAM/GDPR compliant
                    ═══════════════════════════════════════ -->
                    <tr>
                        <td class="f-pad" style="padding:22px 40px 26px; background-color:#fafafa;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="text-align:center; font-family:Arial,sans-serif;">
                                        <p style="margin:0 0 8px; font-size:12px; color:#71717a;">
                                            <a href="<?php echo esc_url(home_url('/dashboard')); ?>"
                                               style="color:<?php echo esc_attr($accent_hex); ?>; text-decoration:none; font-weight:bold;">My Dashboard</a>
                                            &nbsp;&middot;&nbsp;
                                            <a href="<?php echo esc_url(home_url('/support')); ?>"
                                               style="color:<?php echo esc_attr($accent_hex); ?>; text-decoration:none; font-weight:bold;">Support</a>
                                            &nbsp;&middot;&nbsp;
                                            <a href="<?php echo esc_url($site_url); ?>"
                                               style="color:<?php echo esc_attr($accent_hex); ?>; text-decoration:none; font-weight:bold;"><?php echo esc_html($brand_name); ?></a>
                                        </p>
                                        <p style="margin:0 0 4px; font-size:11px; color:#a1a1aa; line-height:1.6;">
                                            You received this email because you have an active account with <strong><?php echo esc_html($brand_name); ?></strong>.
                                        </p>
                                        <p style="margin:0; font-size:11px; color:#d4d4d8;">
                                            &copy; <?php echo $year; ?> <?php echo esc_html($brand_name); ?>. All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- BOTTOM COLOUR BAR -->
                    <tr>
                        <td style="height:3px; background-color:<?php echo esc_attr($accent_hex); ?>; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>

                </table>
                <!-- END EMAIL CARD -->

                <!-- Sub-footer note -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; margin-top:14px;">
                    <tr>
                        <td align="center" style="font-size:11px; color:#a1a1aa; padding:0 16px;
                                                  font-family:Arial,sans-serif; line-height:1.6; text-align:center;">
                            This is an automated message — please do not reply to this email.
                            Questions? <a href="<?php echo esc_url(home_url('/support')); ?>"
                                         style="color:<?php echo esc_attr($accent_hex); ?>; text-decoration:none;">Visit our support centre</a>.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
