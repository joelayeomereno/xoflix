<?php
/**
 * EXACT FILE PATH:
 *   tv-subscription-manager/admin/views/view-settings-email-test.php
 *
 * NEW FILE — sits alongside all other view-settings-*.php files.
 *
 * Accessed via: ?page=tv-settings-general&tab=email-test
 *
 * To activate this tab you must make TWO small edits to existing files.
 * Both edits are clearly marked at the bottom of this file as code comments.
 *
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<style>
.tv-etc { max-width: 860px; }

/* Hero */
.tv-etc-hero {
    background: linear-gradient(135deg, #0a0f1e 0%, #1e1b4b 100%);
    border-radius: 14px; padding: 28px 32px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
}
.tv-etc-hero h2 { font-size: 18px; font-weight: 900; color: #f8fafc; letter-spacing: -.03em; margin: 0 0 5px; }
.tv-etc-hero p  { font-size: 13px; color: #94a3b8; margin: 0; }
.tv-etc-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    border-radius: 100px; padding: 5px 14px; font-size: 11px; font-weight: 700; color: #94a3b8;
}
.tv-etc-pill i { width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block; }

/* Status toast */
#tv-etc-status {
    display: none; padding: 12px 18px; border-radius: 8px;
    font-size: 13px; font-weight: 700; margin-bottom: 18px;
}
.tv-etc-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.tv-etc-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* Send bar */
.tv-etc-bar {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 18px 22px; margin-bottom: 20px;
    display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
}
.tv-etc-bar .f { flex: 1; min-width: 200px; }
.tv-etc-bar label {
    display: block; font-size: 11px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;
}
.tv-etc-bar input[type=email],
.tv-etc-bar input[readonly] {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-family: inherit; font-size: 14px; color: #0f172a; background: #f8fafc;
    outline: none; transition: border-color .15s;
}
.tv-etc-bar input[type=email]:focus { border-color: #6366f1; background: #fff; }
.tv-etc-bar input[readonly]         { color: #6366f1; font-weight: 700; cursor: default; }
#tv-etc-btn {
    padding: 11px 24px; background: #6366f1; color: #fff; border: none;
    border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 800;
    cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: .15s;
}
#tv-etc-btn:hover    { background: #4f46e5; }
#tv-etc-btn:disabled { background: #c7d2fe; cursor: not-allowed; }

/* Section label */
.tv-etc-lbl {
    font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;
    letter-spacing: .08em; margin: 0 0 12px; display: flex; align-items: center; gap: 8px;
}
.tv-etc-lbl::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

/* Card grid */
.tv-etc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-bottom: 22px; }

.tv-etc-card {
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px;
    padding: 16px 18px; cursor: pointer; position: relative;
    overflow: hidden; transition: .15s; user-select: none;
}
.tv-etc-card:hover    { border-color: #a5b4fc; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,.06); }
.tv-etc-card.selected { border-color: #6366f1; background: #fafafe; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.tv-etc-card .tv-bar  { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 12px 12px 0 0; }
.tv-etc-card .tv-ico  { font-size: 20px; margin-bottom: 8px; display: block; }
.tv-etc-card .tv-nam  { font-size: 12px; font-weight: 800; color: #0a0f1e; margin-bottom: 3px; }
.tv-etc-card .tv-dsc  { font-size: 11px; color: #64748b; line-height: 1.4; }
.tv-etc-card .tv-typ  {
    position: absolute; top: 13px; right: 13px; font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .05em; padding: 2px 7px; border-radius: 100px;
}

/* Log */
.tv-etc-log { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.tv-etc-log-hdr {
    padding: 12px 18px; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
    font-size: 11px; font-weight: 700; color: #64748b;
    display: flex; justify-content: space-between; align-items: center;
}
.tv-etc-log-hdr button { background: none; border: none; font-size: 11px; color: #94a3b8; cursor: pointer; font-weight: 700; }
#tv-etc-log { list-style: none; margin: 0; padding: 0; max-height: 220px; overflow-y: auto; }
#tv-etc-log li { display: flex; gap: 8px; padding: 9px 18px; border-bottom: 1px solid #f8fafc; font-size: 12px; color: #475569; }
#tv-etc-log li:last-child { border-bottom: none; }
#tv-etc-log .ld { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
#tv-etc-log .lt { color: #94a3b8; width: 64px; flex-shrink: 0; }
#tv-etc-log .lo .ld { background: #10b981; }
#tv-etc-log .le .ld { background: #ef4444; }
#tv-etc-log .li .ld { background: #6366f1; }
</style>

<div class="tv-etc">

    <!-- Hero -->
    <div class="tv-etc-hero">
        <div>
            <h2>&#9993;&nbsp; Email Template Test Centre</h2>
            <p>Select a template, enter a recipient email, and fire a live test through your configured SMTP.</p>
        </div>
        <div class="tv-etc-pill">
            <i></i>
            <?php echo esc_html( get_option( 'tv_smtp_from_email', get_option( 'admin_email' ) ) ); ?>
        </div>
    </div>

    <!-- Status -->
    <div id="tv-etc-status" role="alert"></div>

    <!-- Send bar -->
    <div class="tv-etc-bar">
        <div class="f">
            <label for="tv-etc-email">Recipient Email</label>
            <input type="email" id="tv-etc-email"
                   placeholder="you@example.com"
                   value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
        </div>
        <div class="f">
            <label>Selected Template</label>
            <input id="tv-etc-display" readonly placeholder="← Select a card below">
        </div>
        <button type="button" id="tv-etc-btn" disabled>Send Test &rarr;</button>
    </div>

    <!-- Subscription templates -->
    <p class="tv-etc-lbl">Subscription &amp; Payment Templates</p>
    <div class="tv-etc-grid">

        <div class="tv-etc-card" data-key="payment_approved" data-group="subscription" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#10b981;"></div>
            <span class="tv-typ" style="background:#dcfce7;color:#166534;">Payment</span>
            <span class="tv-ico">&#9989;</span>
            <div class="tv-nam">Payment Approved</div>
            <div class="tv-dsc">Admin approves payment; subscription activates.</div>
        </div>

        <div class="tv-etc-card" data-key="payment_proof_uploaded" data-group="subscription" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#0ea5e9;"></div>
            <span class="tv-typ" style="background:#e0f2fe;color:#0369a1;">Payment</span>
            <span class="tv-ico">&#128203;</span>
            <div class="tv-nam">Proof Received</div>
            <div class="tv-dsc">User uploads a payment screenshot or transaction ID.</div>
        </div>

        <div class="tv-etc-card" data-key="payment_rejected" data-group="subscription" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#ef4444;"></div>
            <span class="tv-typ" style="background:#fee2e2;color:#991b1b;">Payment</span>
            <span class="tv-ico">&#10060;</span>
            <div class="tv-nam">Payment Rejected</div>
            <div class="tv-dsc">Admin rejects proof; service activation placed on hold.</div>
        </div>

        <div class="tv-etc-card" data-key="expiry" data-group="subscription" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#f59e0b;"></div>
            <span class="tv-typ" style="background:#fef3c7;color:#92400e;">Auto</span>
            <span class="tv-ico">&#9888;</span>
            <div class="tv-nam">Expiry Reminder</div>
            <div class="tv-dsc">Automated alert sent X days before subscription end_date.</div>
        </div>

        <div class="tv-etc-card" data-key="reengage" data-group="subscription" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#8b5cf6;"></div>
            <span class="tv-typ" style="background:#f5f3ff;color:#5b21b6;">Auto</span>
            <span class="tv-ico">&#128156;</span>
            <div class="tv-nam">Re-engagement</div>
            <div class="tv-dsc">Win-back email on 14-day cycles after expiry.</div>
        </div>

    </div>

    <!-- Auth templates -->
    <p class="tv-etc-lbl">Auth &amp; Account Templates</p>
    <div class="tv-etc-grid">

        <div class="tv-etc-card" data-key="auth-welcome" data-group="auth" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#6366f1;"></div>
            <span class="tv-typ" style="background:#ede9fe;color:#4338ca;">Auth</span>
            <span class="tv-ico">&#127881;</span>
            <div class="tv-nam">Welcome / Registration</div>
            <div class="tv-dsc">Sent to new users when their account is created.</div>
        </div>

        <div class="tv-etc-card" data-key="auth-password-reset" data-group="auth" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#f59e0b;"></div>
            <span class="tv-typ" style="background:#fef3c7;color:#92400e;">Auth</span>
            <span class="tv-ico">&#128274;</span>
            <div class="tv-nam">Password Reset Request</div>
            <div class="tv-dsc">Sent when a user requests a reset link from the login page.</div>
        </div>

        <div class="tv-etc-card" data-key="auth-password-changed" data-group="auth" onclick="tvEtcPick(this)">
            <div class="tv-bar" style="background:#ef4444;"></div>
            <span class="tv-typ" style="background:#fee2e2;color:#991b1b;">Auth</span>
            <span class="tv-ico">&#9728;</span>
            <div class="tv-nam">Password Changed</div>
            <div class="tv-dsc">Security confirmation after a successful password change.</div>
        </div>

    </div>

    <!-- Activity log -->
    <p class="tv-etc-lbl">Session Activity Log</p>
    <div class="tv-etc-log">
        <div class="tv-etc-log-hdr">
            <span>Recent test sends this session</span>
            <button onclick="tvEtcClearLog()">Clear</button>
        </div>
        <ul id="tv-etc-log">
            <li id="tv-etc-log-empty" style="padding:24px;text-align:center;color:#94a3b8;font-style:italic;font-size:12px;">
                No tests sent yet this session.
            </li>
        </ul>
    </div>

</div><!-- /tv-etc -->

<script>
(function() {
    'use strict';

    var _key   = null;
    var _group = null;

    window.tvEtcPick = function(card) {
        document.querySelectorAll('.tv-etc-card').forEach(function(c) { c.classList.remove('selected'); });
        card.classList.add('selected');
        _key   = card.dataset.key;
        _group = card.dataset.group;
        document.getElementById('tv-etc-display').value = card.querySelector('.tv-nam').textContent;
        document.getElementById('tv-etc-btn').disabled  = false;
        document.getElementById('tv-etc-status').style.display = 'none';
    };

    document.getElementById('tv-etc-btn').addEventListener('click', function() {
        var email = document.getElementById('tv-etc-email').value.trim();
        if (!email || !email.includes('@')) {
            tvEtcStatus('err', '&#9888;  Please enter a valid recipient email address.');
            return;
        }
        if (!_key) {
            tvEtcStatus('err', '&#9888;  Please select a template card first.');
            return;
        }

        var btn = this;
        btn.disabled     = true;
        btn.textContent  = 'Sending\u2026';
        tvEtcLog('li', 'Dispatching [' + _key + '] to ' + email + '\u2026');

        var fd = new FormData();
        fd.append('action',          'tv_send_test_email');
        fd.append('_nonce',          '<?php echo wp_create_nonce( 'tv_send_test_email' ); ?>');
        fd.append('template_key',    _key);
        fd.append('template_group',  _group);
        fd.append('to_email',        email);

        fetch(ajaxurl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    tvEtcStatus('ok', '&#9989;  Test dispatched to <strong>' + email + '</strong>. Check your inbox (and spam folder).');
                    tvEtcLog('lo', '[' + _key + '] sent successfully to ' + email);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Unknown SMTP error.';
                    tvEtcStatus('err', '&#10060;  Failed: ' + msg);
                    tvEtcLog('le', '[' + _key + '] failed: ' + msg);
                }
            })
            .catch(function(err) {
                tvEtcStatus('err', '&#10060;  Network error: ' + err.message);
                tvEtcLog('le', 'Network error: ' + err.message);
            })
            .finally(function() {
                btn.disabled    = false;
                btn.textContent = 'Send Test \u2192';
            });
    });

    function tvEtcStatus(type, html) {
        var el  = document.getElementById('tv-etc-status');
        el.className    = type === 'ok' ? 'tv-etc-ok' : 'tv-etc-err';
        el.innerHTML    = html;
        el.style.display = 'block';
        clearTimeout(el._t);
        el._t = setTimeout(function() { el.style.display = 'none'; }, 9000);
    }

    function tvEtcLog(cls, msg) {
        var ul    = document.getElementById('tv-etc-log');
        var empty = document.getElementById('tv-etc-log-empty');
        if (empty) empty.remove();

        var d   = new Date();
        var pad = function(n) { return n.toString().padStart(2, '0'); };
        var ts  = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());

        var li = document.createElement('li');
        li.className = cls;
        li.innerHTML = '<span class="ld"></span><span class="lt">' + ts + '</span><span>' + msg + '</span>';
        ul.insertBefore(li, ul.firstChild);
    }

    window.tvEtcClearLog = function() {
        document.getElementById('tv-etc-log').innerHTML =
            '<li id="tv-etc-log-empty" style="padding:24px;text-align:center;color:#94a3b8;font-style:italic;font-size:12px;">No tests sent yet this session.</li>';
    };

})();
</script>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 *  EDIT 1 OF 2 — tv-subscription-manager/admin/classes/class-tv-admin-settings.php
 *
 *  In the constructor, add ONE line after the existing AJAX hooks:
 *
 *      add_action('wp_ajax_tv_test_smtp',      [$this, 'ajax_test_smtp']);
 *      add_action('wp_ajax_tv_test_wassenger', [$this, 'ajax_test_wassenger']);
 *  +   add_action('wp_ajax_tv_send_test_email', [$this, 'ajax_send_test_email']); // ← ADD
 *
 *  Then add this method to the same class:
 *
 *  public function ajax_send_test_email(): void {
 *      check_ajax_referer('tv_send_test_email', '_nonce');
 *      if (!current_user_can('manage_options')) {
 *          wp_send_json_error(['message' => 'Unauthorized.']);
 *      }
 *
 *      $key   = sanitize_text_field($_POST['template_key']   ?? '');
 *      $group = sanitize_text_field($_POST['template_group'] ?? 'subscription');
 *      $to    = sanitize_email($_POST['to_email']            ?? '');
 *
 *      if (!is_email($to)) {
 *          wp_send_json_error(['message' => 'Invalid email address.']);
 *      }
 *
 *      if ($group === 'auth') {
 *          if (!class_exists('TV_Auth_Notifications')) {
 *              require_once TV_MANAGER_PATH . 'includes/class-tv-auth-notifications.php';
 *          }
 *          $ok = TV_Auth_Notifications::send_test($key, $to);
 *      } else {
 *          if (!class_exists('TV_Notification_Engine')) {
 *              require_once TV_MANAGER_PATH . 'includes/class-tv-notification-engine.php';
 *          }
 *          $dummy = (object)['id' => 0, 'user_id' => get_current_user_id(), 'plan_id' => 0];
 *          $ctx   = [
 *              'plan_name'     => 'Test Plan (Demo)',
 *              'days_left'     => 5,
 *              'days_passed'   => 21,
 *              'admin_message' => 'This is a sample admin note included for test purposes.',
 *              'user_name'     => 'Test User',
 *              'brand_name'    => get_bloginfo('name'),
 *              'login_url'     => home_url('/login'),
 *          ];
 *          add_filter('wp_mail', function($args) use ($to) {
 *              $args['to']      = $to;
 *              $args['subject'] = '[TEST] ' . $args['subject'];
 *              return $args;
 *          });
 *          TV_Notification_Engine::send_notification($dummy, $key, $ctx['admin_message'], true);
 *          $ok = true;
 *      }
 *
 *      if ($ok) {
 *          wp_send_json_success(['message' => 'Test email dispatched.']);
 *      } else {
 *          wp_send_json_error(['message' => 'wp_mail() returned false. Check SMTP settings.']);
 *      }
 *  }
 *
 * ═══════════════════════════════════════════════════════════════════════════
 *  EDIT 2 OF 2 — tv-subscription-manager/admin/views/view-settings-tabs.php
 *
 *  In the $allowed_tabs array inside render() in class-tv-admin-settings.php,
 *  add 'email-test':
 *
 *      $allowed_tabs = [
 *          'general', 'notifications', 'panels', 'support',
 *          'integrations', 'channel-engine', 'recycle-bin',
 *  +       'email-test',  // ← ADD
 *      ];
 *
 *  In view-settings-tabs.php, add ONE entry to $_settings_tabs:
 *
 *      'recycle-bin' => ['label' => 'Recycle Bin', 'icon' => 'dashicons-trash',   'page' => 'tv-settings-recycle'],
 *  +   'email-test'  => ['label' => 'Email Tests', 'icon' => 'dashicons-email',   'page' => 'tv-settings-general'],
 *
 *  The 'page' value for email-test must be 'tv-settings-general' (same as all other
 *  settings tabs) so the ?tab=email-test query param routes correctly.
 * ═══════════════════════════════════════════════════════════════════════════
 */
?>
