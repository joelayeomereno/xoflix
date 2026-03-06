// part-dashboard.js
// Registers: _XOFLIX._registerDashboard → Dashboard
// Depends on: React, _XOFLIX.{ apiFetch, Icon, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerDashboard = function () {
    const { useState, useEffect, createElement: el } = React;
    const { apiFetch, Icon, Loader, Empty } = window._XOFLIX;

    const Dashboard = () => {
      const [data, setData]     = useState(null);
      const [health, setHealth] = useState(null);

      useEffect(() => {
        apiFetch('dashboard').then(d => setData(d)).catch(() => {});
        apiFetch('health').then(h => setHealth(h)).catch(() => {});
      }, []);

      const statConfigs = [
        { key: 'revenue',       label: 'Revenue',     icon: 'dollar',   color: '#6366f1', bg: 'rgba(99,102,241,.12)' },
        { key: 'active_subs',   label: 'Active Subs', icon: 'tv',       color: '#22c55e', bg: 'rgba(34,197,94,.12)'  },
        { key: 'pending_tasks', label: 'Pending',     icon: 'activity', color: '#f59e0b', bg: 'rgba(245,158,11,.12)' },
        { key: 'users',         label: 'Users',       icon: 'users',    color: '#06b6d4', bg: 'rgba(6,182,212,.12)'  },
      ];

      return el('div', { className: 'page-wrap' },

        el('div', { className: 'page-header top-safe' },
          el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
            el('div', null,
              el('p', { style: { fontSize: 11, color: 'var(--muted)', fontWeight: 700, letterSpacing: '.08em', textTransform: 'uppercase' } }, 'XOFLIX ADMIN'),
              el('h1', { style: { fontSize: 24, fontWeight: 900, marginTop: 2, lineHeight: 1.1 } },
                `Hey, ${(TVMA.user || 'Admin').split(' ')[0]} 👋`
              )
            ),
            health && el('div', { style: { display: 'flex', alignItems: 'center', gap: 6, padding: '6px 12px', borderRadius: 999, background: 'var(--surface2)', border: '1px solid var(--border)' } },
              el('div', { style: { width: 8, height: 8, borderRadius: '50%', background: health.cron === 'OK' || health.status === 'ok' ? 'var(--green)' : 'var(--red)' } }),
              el('span', { style: { fontSize: 12, fontWeight: 700, color: health.cron === 'OK' || health.status === 'ok' ? 'var(--green)' : 'var(--red)' } },
                health.cron === 'OK' || health.status === 'ok' ? 'Live' : 'Offline'
              )
            )
          )
        ),

        el('div', { className: 'page-scroll' },

          !data && el('div', { style: { padding: 16 } },
            el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 16 } },
              [1, 2, 3, 4].map(i => el('div', { key: i, className: 'skeleton', style: { height: 110, borderRadius: 16 } }))
            ),
            [1, 2, 3].map(i => el('div', { key: i, className: 'skeleton', style: { height: 60, borderRadius: 12, marginBottom: 10 } }))
          ),

          data && el('div', { className: 'anim-up', style: { padding: '16px 16px 0' } },
            el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 } },
              statConfigs.map(cfg => {
                const raw    = data.stats ? data.stats[cfg.key] : data[cfg.key];
                const rawVal = (raw !== null && raw !== undefined)
                  ? (typeof raw === 'object' ? (raw.value ?? raw.formatted ?? 0) : raw)
                  : 0;
                const lines = String(rawVal).split(/\n|\\n/).map(s => s.trim()).filter(Boolean);

                return el('div', { key: cfg.key, className: 'card stat-card press' },
                  el('div', { className: 'stat-glow', style: { background: `radial-gradient(circle at 80% 20%,${cfg.color},transparent 70%)` } }),
                  el('div', { style: { width: 36, height: 36, borderRadius: 10, background: cfg.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', color: cfg.color, flexShrink: 0 } },
                    el(Icon, { name: cfg.icon, size: 18 })
                  ),
                  el('div', { style: { minWidth: 0, flex: 1 } },
                    el('p', { style: { fontSize: 10, color: 'var(--muted)', fontWeight: 700, letterSpacing: '.06em', textTransform: 'uppercase', marginBottom: 4 } }, cfg.label),
                    lines.length > 1
                      ? el('div', { style: { display: 'flex', flexDirection: 'column', gap: 2 } },
                          lines.map((line, li) => el('p', {
                            key: li,
                            style: {
                              fontSize: li === 0 ? 20 : 14, fontWeight: 900, lineHeight: 1.2,
                              overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap',
                              fontVariantNumeric: 'tabular-nums',
                              color: li === 0 ? 'var(--text)' : 'var(--muted)'
                            }
                          }, line))
                        )
                      : el('p', {
                          style: { fontSize: 22, fontWeight: 900, lineHeight: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', fontVariantNumeric: 'tabular-nums' }
                        }, lines[0] || '0')
                  )
                );
              })
            )
          ),

          data && el('div', { className: 'anim-up', style: { padding: 16 } },
            el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 } },
              el('h3', { style: { fontSize: 16, fontWeight: 800 } }, 'Activity Log'),
              data.csv_url && el('a', { href: data.csv_url, style: { fontSize: 12, color: 'var(--accent)', fontWeight: 700, textDecoration: 'none' } }, 'Export CSV 📥')
            ),
            el('div', { style: { display: 'flex', flexDirection: 'column', gap: 8 } },
              (data.recent_activity || []).length === 0
                ? el(Empty, { text: 'No recent activity', icon: 'activity' })
                : (data.recent_activity || []).map((l, i) =>
                    el('div', { key: l.id || i, className: 'card2', style: { padding: '12px 16px', display: 'flex', gap: 12, alignItems: 'flex-start' } },
                      el('div', { style: { width: 8, height: 8, borderRadius: '50%', background: 'var(--accent)', marginTop: 5, flexShrink: 0 } }),
                      el('div', { style: { flex: 1, minWidth: 0 } },
                        el('p', { style: { fontWeight: 700, fontSize: 14, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, l.action || l.type || 'Event'),
                        el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2 } }, l.details || l.time_ago || '')
                      )
                    )
                  )
            )
          )
        )
      );
    };

    window._XOFLIX.Dashboard = Dashboard;
  };
})();
