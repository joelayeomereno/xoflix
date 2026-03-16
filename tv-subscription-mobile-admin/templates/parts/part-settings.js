// part-settings.js
// Registers: _XOFLIX._registerSettings → SettingsView
// Depends on: React, _XOFLIX.{ apiFetch, showToast, Icon, BackHeader, Field, Repeater, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerSettings = function () {
    const { useState, useEffect, createElement: el } = React;
    const { apiFetch, showToast, Icon, BackHeader, Field, Repeater, Loader, Empty } = window._XOFLIX;

    const SettingsView = ({ onBack }) => {
      const [conf, setConf]     = useState(null);
      const [tab, setTab]       = useState('support');
      const [health, setHealth] = useState(null);
      const [logs, setLogs]     = useState([]);
      const [saving, setSaving] = useState(false);

      useEffect(() => {
        apiFetch('settings').then(d => setConf(d || {})).catch(() => setConf({}));
        apiFetch('health').then(h => setHealth(h || {})).catch(() => setHealth({}));
        apiFetch('logs').then(l => setLogs(Array.isArray(l) ? l : [])).catch(() => setLogs([]));
      }, []);

      const save = async () => {
        setSaving(true);
        try { await apiFetch('settings/update', 'POST', conf); showToast('Settings saved ✅'); }
        catch (_) {}
        finally { setSaving(false); }
      };

      if (!conf) return el('div', { className: 'page-wrap' },
        el(BackHeader, { title: 'Settings', onBack }),
        el('div', { className: 'page-scroll no-nav' }, el(Loader))
      );

      /* FIX: null-safe deep setter */
      const S = (path, val) => {
        const keys = path.split('.');
        const next = JSON.parse(JSON.stringify(conf));
        let cur = next;
        for (let i = 0; i < keys.length - 1; i++) {
          if (cur[keys[i]] === undefined || cur[keys[i]] === null || typeof cur[keys[i]] !== 'object') cur[keys[i]] = {};
          cur = cur[keys[i]];
        }
        cur[keys[keys.length - 1]] = val;
        setConf(next);
      };

      const G = (path, fallback = '') => {
        try {
          const keys = path.split('.');
          let cur = conf;
          for (const k of keys) { if (cur == null || typeof cur !== 'object') return fallback; cur = cur[k]; }
          return cur ?? fallback;
        } catch (_) { return fallback; }
      };

      const tabs = ['support', 'notifications', 'panels', 'pages', 'health'];
      const SaveBtn = ({ label }) =>
        el('button', { className: 'btn btn-primary btn-full press', onClick: save, disabled: saving },
          saving ? el('div', { className: 'spinner', style: { width: 16, height: 16, border: '2px solid rgba(255,255,255,.3)', borderTopColor: '#fff', borderRadius: '50%' } }) : el(Icon, { name: 'check', size: 16 }),
          saving ? 'Saving…' : label
        );

      return el('div', { className: 'page-wrap' },
        el(BackHeader, { title: 'Settings', onBack }),

        el('div', { style: { padding: '10px 14px', borderBottom: '1px solid var(--border)', flexShrink: 0, overflowX: 'auto' } },
          el('div', { className: 'tab-row', style: { minWidth: 'max-content', width: '100%' } },
            tabs.map(t =>
              el('button', { key: t, className: `tab-btn ${tab === t ? 'active' : ''}`,
                onClick: () => { try { setTab(t); } catch (e) { console.error(e); } },
                style: { minWidth: 76 }
              }, t[0].toUpperCase() + t.slice(1))
            )
          )
        ),

        el('div', { className: 'page-scroll no-nav', style: { padding: 20 } },

          tab === 'support' && el('div', { className: 'col g14 anim-in' },
            el(Field, { label: 'WhatsApp Number',  value: G('support.whatsapp'), onChange: v => S('support.whatsapp', v), placeholder: '+2348000000000' }),
            el(Field, { label: 'Support Email',    value: G('support.email'),    onChange: v => S('support.email', v), type: 'email' }),
            el(Field, { label: 'Telegram Handle',  value: G('support.telegram'), onChange: v => S('support.telegram', v), placeholder: '@xoflixsupport' }),
            el(SaveBtn, { label: 'Save Support Settings' })
          ),

          tab === 'notifications' && el('div', { className: 'col g14 anim-in' },
            el(Field, { label: 'Expiry Alert Days (e.g. 7,3,1)', value: G('notifications.expiry_days'),        onChange: v => S('notifications.expiry_days', v) }),
            el(Field, { label: 'WhatsApp Gateway URL',           value: G('notifications.whatsapp_gateway'),  onChange: v => S('notifications.whatsapp_gateway', v) }),
            el(Field, { label: 'WhatsApp API Key',               value: G('notifications.whatsapp_key'),      onChange: v => S('notifications.whatsapp_key', v) }),
            el(Repeater, { label: 'Notification Templates', items: G('notifications.templates', []), onChange: v => S('notifications.templates', v), fields: [{ k: 'subject', l: 'Subject' }, { k: 'body', l: 'Body (HTML)' }] }),
            el(SaveBtn, { label: 'Save Notifications' })
          ),

          tab === 'panels' && el('div', { className: 'col g14 anim-in' },
            el(Repeater, { label: 'Xtream Panels', items: G('panels', []), onChange: v => S('panels', v),
              fields: [{ k: 'name', l: 'Name' }, { k: 'xtream_url', l: 'DNS URL' }, { k: 'smart_tv_url', l: 'M3U URL' }] }),
            el(SaveBtn, { label: 'Save Panels' })
          ),

          tab === 'pages' && el('div', { className: 'col g14 anim-in' },
            el(Field, { label: 'Plans Page ID',        value: String(G('pages.plans',   '')), onChange: v => S('pages.plans',   parseInt(v) || 0) }),
            el(Field, { label: 'Method Page ID',       value: String(G('pages.method',  '')), onChange: v => S('pages.method',  parseInt(v) || 0) }),
            el(Field, { label: 'Payment Page ID',      value: String(G('pages.payment', '')), onChange: v => S('pages.payment', parseInt(v) || 0) }),
            el(Field, { label: 'Proof Upload Page ID', value: String(G('pages.proof',   '')), onChange: v => S('pages.proof',   parseInt(v) || 0) }),
            el(SaveBtn, { label: 'Save Page IDs' })
          ),

          tab === 'health' && el('div', { className: 'col g14 anim-in' },
            health && el('div', { className: 'card2', style: { padding: 16 } },
              [['System Status', health.status || '–'], ['Cron Job', health.cron || '–'], ['DB Version', health.db_ver || '–']].map(([k, v]) =>
                el('div', { key: k, style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 0', borderBottom: '1px solid var(--border)' } },
                  el('span', { style: { fontSize: 14, color: 'var(--muted)', fontWeight: 600 } }, k),
                  el('span', { style: { fontWeight: 700, color: /ok/i.test(String(v)) ? 'var(--green)' : 'var(--amber)' } }, String(v))
                )
              )
            ),
            el('div', null,
              el('p', { style: { fontWeight: 800, fontSize: 15, marginBottom: 12 } }, 'Activity Logs'),
              logs.length === 0 && el(Empty, { text: 'No logs available', icon: 'server' }),
              logs.map((l, i) =>
                el('div', { key: l.id || i, className: 'card2', style: { padding: '10px 14px', marginBottom: 8, display: 'flex', gap: 10, alignItems: 'flex-start' } },
                  el('div', { style: { width: 7, height: 7, borderRadius: '50%', background: 'var(--accent)', marginTop: 5, flexShrink: 0 } }),
                  el('div', null,
                    el('p', { style: { fontWeight: 700, fontSize: 14 } }, l.action || l.type || 'Event'),
                    el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2 } }, l.time_ago || l.time || ''),
                    l.details && el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2, fontStyle: 'italic' } }, l.details)
                  )
                )
              )
            )
          )
        )
      );
    };

    window._XOFLIX.SettingsView = SettingsView;
  };
})();
