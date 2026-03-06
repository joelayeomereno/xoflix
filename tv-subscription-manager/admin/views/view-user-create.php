<?php if (!defined('ABSPATH')) exit; ?>
<?php
/**
 * File: tv-subscription-manager/admin/views/view-user-create.php
 * Path: /tv-subscription-manager/admin/views/view-user-create.php
 */
?>
<div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px;">
    <div>
        <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--tv-text);">Create User</h2>
        <div style="color:var(--tv-text-muted); font-size:12px; margin-top:4px;">Manual user creation (admin).</div>
    </div>
    <a class="tv-btn tv-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=tv-subs-manager&tab=users')); ?>">← Back to Users</a>
</div>

<div class="tv-card" style="padding:16px; max-width:720px;">
    <form method="post">
        <?php wp_nonce_field('tv_create_user_verify'); ?>
        <input type="hidden" name="tv_create_user" value="1">

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div class="tv-field">
                <input class="tv-control tv-input" type="text" name="user_login" placeholder=" " required>
                <label class="tv-label">Username (login)</label>
            </div>
            <div class="tv-field">
                <input class="tv-control tv-input" type="email" name="user_email" placeholder=" " required>
                <label class="tv-label">Email</label>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div class="tv-field">
                <input class="tv-control tv-input" type="text" name="display_name" placeholder=" ">
                <label class="tv-label">Full Name</label>
            </div>
            <div class="tv-field">
                <input class="tv-control tv-input" type="text" name="phone" placeholder=" ">
                <label class="tv-label">Phone</label>
            </div>
        </div>

        <div class="tv-field">
            <input class="tv-control tv-input" type="text" name="user_pass" placeholder=" ">
            <label class="tv-label">Password (optional)</label>
            <div class="tv-help">Leave blank to auto-generate</div>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:14px;">
            <label class="tv-switch" style="font-size:12px; color:var(--tv-text-muted);">
                <input type="checkbox" name="notify_user" value="1" checked class="tv-toggle-input">
                <span class="tv-toggle-ui" aria-hidden="true"></span>
                <span>Notify user</span>
            </label>

            <button class="tv-btn tv-btn-primary" type="submit">Create User</button>
        </div>
    </form>
</div>
