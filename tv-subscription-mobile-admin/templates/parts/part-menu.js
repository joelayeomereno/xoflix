// part-menu.js
// Registers: _XOFLIX._registerMenu → MenuView
// Depends on: React, _XOFLIX.{ Icon }

(function () {
  'use strict';

  window._XOFLIX._registerMenu = function () {
    const { createElement: el } = React;
    const { Icon } = window._XOFLIX;

    const MenuView = ({ onNavigate }) => {
      const items = [
        { id: 'finance',  label: 'Finance',       icon: 'dollar',   color: '#6366f1' },
        { id: 'subs',     label: 'Subscriptions', icon: 'tv',       color: '#6366f1' },
        { id: 'plans',    label: 'Plans',          icon: 'tag',      color: '#22c55e' },
        { id: 'coupons',  label: 'Coupons',        icon: 'zap',      color: '#f59e0b' },
        { id: 'methods',  label: 'Pay Methods',    icon: 'link',     color: '#22c55e' },
        { id: 'sports',   label: 'Sports Guide',   icon: 'trophy',   color: '#ef4444' },
        { id: 'messages', label: 'Messages',       icon: 'message',  color: '#8b5cf6' },
        { id: 'settings', label: 'Settings',       icon: 'settings', color: 'var(--muted)' },
      ];
      return el('div', { className: 'page-wrap' },
        el('div', { className: 'page-header top-safe' },
          el('h1', { style: { fontSize: 26, fontWeight: 900 } }, 'More')
        ),
        el('div', { className: 'page-scroll' },
          el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, padding: '16px 16px 0' } },
            items.map(i =>
              el('button', { key: i.id, className: 'card press',
                style: { padding: 20, display: 'flex', flexDirection: 'column', alignItems: 'flex-start', gap: 12, border: '1px solid var(--border)', cursor: 'pointer', background: 'var(--surface)', textAlign: 'left' },
                onClick: () => onNavigate(i.id)
              },
                el('div', { style: { width: 44, height: 44, borderRadius: 12, background: i.color === 'var(--muted)' ? 'var(--surface3)' : `${i.color}22`, display: 'flex', alignItems: 'center', justifyContent: 'center', color: i.color } },
                  el(Icon, { name: i.icon, size: 22 })
                ),
                el('span', { style: { fontSize: 14, fontWeight: 700, color: 'var(--text)' } }, i.label)
              )
            )
          ),
          el('div', { style: { padding: 16 } },
            el('a', { href: TVMA.logoutUrl,
              style: { display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, width: '100%', padding: 16, borderRadius: 14, background: 'rgba(239,68,68,.1)', border: '1px solid rgba(239,68,68,.2)', color: 'var(--red)', fontWeight: 700, fontSize: 15, textDecoration: 'none' }
            },
              el(Icon, { name: 'logOut', size: 18 }), 'Sign Out'
            )
          )
        )
      );
    };

    window._XOFLIX.MenuView = MenuView;
  };
})();
