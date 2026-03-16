<?php
/**
 * FILE PATH: tv-subscription-manager/admin/views/view-settings-tabs.php
 *
 * REPLACE the existing file at this exact location.
 * Only change from original: 'email-test' entry added to $_settings_tabs array.
 */
if (!defined('ABSPATH')) { exit; }

$_current_settings_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

$_settings_tabs = [
    'general'        => ['label' => 'General',        'icon' => 'dashicons-admin-generic',  'page' => 'tv-settings-general'],
    'notifications'  => ['label' => 'Notifications',  'icon' => 'dashicons-bell',            'page' => 'tv-settings-notifications'],
    'panels'         => ['label' => 'Panels',          'icon' => 'dashicons-desktop',         'page' => 'tv-settings-panels'],
    'support'        => ['label' => 'Support',         'icon' => 'dashicons-sos',             'page' => 'tv-settings-support'],
    'integrations'   => ['label' => 'Integrations',   'icon' => 'dashicons-admin-plugins',   'page' => 'tv-settings-integrations'],
    'channel-engine' => ['label' => 'Channel Engine', 'icon' => 'dashicons-performance',     'page' => 'tv-settings-channel'],
    'recycle-bin'    => ['label' => 'Recycle Bin',    'icon' => 'dashicons-trash',           'page' => 'tv-settings-recycle'],
    'email-test'     => ['label' => 'Email Tests',    'icon' => 'dashicons-email-alt',       'page' => 'tv-settings-general'],
];
?>

<div class="tv-page-header">
    <div>
        <h1>Settings</h1>
        <p>Configure your TV Manager system, panels, notifications, and integrations.</p>
    </div>
</div>

<div style="display:flex; gap:4px; flex-wrap:wrap; background:var(--tv-surface-active); border:1px solid var(--tv-border); border-radius:14px; padding:5px; margin-bottom:24px;">
    <?php foreach ($_settings_tabs as $_stab_key => $_stab_info):
        $_is_active = ($_current_settings_tab === $_stab_key);
        $_href = ($_stab_key === 'email-test')
            ? esc_url(admin_url('admin.php?page=tv-settings-general&tab=email-test'))
            : esc_url(admin_url('admin.php?page=' . $_stab_info['page']));
    ?>
    <a href="<?php echo $_href; ?>"
       style="display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:10px;
              font-size:13px; font-weight:<?php echo $_is_active ? '700' : '600'; ?>;
              text-decoration:none;
              color:<?php echo $_is_active ? 'var(--tv-primary)' : 'var(--tv-text-muted)'; ?>;
              background:<?php echo $_is_active ? 'var(--tv-card)' : 'transparent'; ?>;
              border:1px solid <?php echo $_is_active ? 'var(--tv-border)' : 'transparent'; ?>;
              box-shadow:<?php echo $_is_active ? 'var(--tv-shadow-xs)' : 'none'; ?>;
              transition:all .15s ease;">
        <span class="dashicons <?php echo esc_attr($_stab_info['icon']); ?>"
              style="font-size:14px; width:14px; height:14px;"></span>
        <?php echo esc_html($_stab_info['label']); ?>
    </a>
    <?php endforeach; ?>
</div>
