// part-payments.js
// Registers: _XOFLIX._registerPayments → Payments
// Depends on: React, _XOFLIX.{ apiFetch, apiRaw, showToast, Icon, BackHeader, Sheet, Field, StatusBadge, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerPayments = function () {
    const { useState, useEffect, useCallback, createElement: el } = React;
    const { apiFetch, apiRaw, showToast, Icon, BackHeader, Sheet, Field, StatusBadge, Loader, Empty } = window._XOFLIX;

    const Payments = ({ onBack }) => {
      const [list, setList]       = useState([]);
      const [page, setPage]       = useState(1);
      const [meta, setMeta]       = useState({ total: 0, pages: 1 });
      const [filter, setFilter]   = useState('pending');
      const [loading, setLoading] = useState(true);
      const [sheet, setSheet]     = useState(null);
      const [wizMode, setWizMode] = useState(false);
      const [creds, setCreds]     = useState({ user: '', pass: '', m3u: '', url: '' });

      const load = useCallback(async () => {
        setLoading(true);
        try {
          const r = await apiFetch(`payments?status=${filter}&page=${page}`);
          setList(r.data || []);
          setMeta({ total: r.total, pages: r.pages });
        } catch (_) { setList([]); }
        finally { setLoading(false); }
      }, [filter, page]);

      useEffect(() => { load(); }, [filter, page]);

      const approve = async (withCreds = false) => {
        const ok = await window._XOFLIX._confirm('Approve Payment', `Approve invoice #${sheet.id}?`, { confirmLabel: 'Approve', confirmStyle: 'primary' });
        if (!ok) return;
        try {
          await apiFetch(`payments/${sheet.id}/action`, 'POST', { action: 'approve', creds: withCreds ? creds : null });
          showToast('Payment approved ✅'); setSheet(null); setWizMode(false); load();
        } catch (_) {}
      };

      const reject = async () => {
        const code = Math.floor(1000 + Math.random() * 9000);
        const ok = await window._XOFLIX._confirm('Reject Payment', `Reject invoice #${sheet.id}? Cannot be undone.`, { danger: true, codeRequired: true, code, confirmLabel: 'Reject' });
        if (!ok) return;
        try {
          await apiFetch(`payments/${sheet.id}/action`, 'POST', { action: 'reject' });
          showToast('Payment rejected'); setSheet(null); load();
        } catch (_) {}
      };

      const parseM3U = () => {
        const l = creds.m3u;
        const u = l.match(/username=([^&\s]+)/), p = l.match(/password=([^&\s]+)/);
        if (u && p) setCreds(c => ({ ...c, user: u[1], pass: p[1] }));
        try { setCreds(c => ({ ...c, url: new URL(l).origin })); } catch (_) {}
      };

      const FilterChips = ({ labels }) =>
        el('div', { className: 'chips-row' },
          ['pending', 'all', 'completed', 'rejected'].map(f =>
            el('button', { key: f, className: `chip press ${filter === f ? 'active' : ''}`, onClick: () => { setFilter(f); setPage(1); } },
              labels[f]
            )
          )
        );

      return el('div', { className: 'page-wrap' },

        onBack
          ? el(BackHeader, { title: 'Payments', onBack, right: el('button', { className: 'btn btn-ghost btn-sm press', onClick: load }, el(Icon, { name: 'refresh', size: 15 })) })
          : el('div', { className: 'page-header top-safe' },
              el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 } },
                el('div', null,
                  el('h1', { style: { fontSize: 24, fontWeight: 900 } }, 'Payments'),
                  el('p',  { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2 } }, 'Transaction history')
                ),
                el('button', { className: 'btn btn-ghost btn-sm press', onClick: load }, el(Icon, { name: 'refresh', size: 15 }))
              ),
              el(FilterChips, { labels: { pending: '🕒 Pending', all: 'All', completed: '✅ Done', rejected: '❌ Rejected' } })
            ),

        onBack && el('div', { style: { padding: '10px 14px 0', borderBottom: '1px solid var(--border)', flexShrink: 0 } },
          el(FilterChips, { labels: { pending: '🕒 Pending', all: 'All', completed: '✅ Completed', rejected: '❌ Rejected' } })
        ),

        el('div', { className: onBack ? 'page-scroll no-nav' : 'page-scroll' },
          loading && el(Loader),
          !loading && list.length === 0 && el(Empty, { text: 'No payments found', icon: 'creditCard' }),
          !loading && list.map(p =>
            el('div', { key: p.id, className: 'card press anim-up', style: { margin: '12px 12px 0', padding: '14px 16px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', cursor: 'pointer' },
              onClick: () => { setSheet(p); setWizMode(false); } },
              el('div', { style: { display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 } },
                el('div', { style: { width: 42, height: 42, borderRadius: 12, background: 'var(--surface2)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 14, fontWeight: 800, color: 'var(--accent)', flexShrink: 0 } }, '#' + p.id),
                el('div', { style: { minWidth: 0 } },
                  el('p', { style: { fontWeight: 700, fontSize: 15, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, p.user_login || 'Unknown'),
                  el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2 } }, p.plan_name || p.time_ago || '')
                )
              ),
              el('div', { style: { textAlign: 'right', flexShrink: 0, marginLeft: 12 } },
                el('p', { style: { fontWeight: 900, fontSize: 17, fontVariantNumeric: 'tabular-nums' } }, (p.currency_symbol || '$') + Number(p.amount || 0).toLocaleString()),
                el(StatusBadge, { s: p.status })
              )
            )
          ),
          !loading && meta.pages > 1 && el('div', { className: 'page-ctrl' },
            el('button', { className: 'page-num press', onClick: () => setPage(1), disabled: page === 1, style: { fontSize: 11 } }, '«'),
            el('button', { className: 'page-num press', onClick: () => setPage(p => Math.max(1, p - 1)), disabled: page === 1 }, el(Icon, { name: 'chevronLeft', size: 14 })),
            el('span', { className: 'text-xs font-bold text-slate-400' }, `Page ${page} / ${meta.pages}`),
            el('button', { className: 'page-num press', onClick: () => setPage(p => Math.min(meta.pages, p + 1)), disabled: page === meta.pages }, el(Icon, { name: 'chevronRight', size: 14 })),
            el('button', { className: 'page-num press', onClick: () => setPage(meta.pages), disabled: page === meta.pages, style: { fontSize: 11 } }, '»')
          )
        ),

        el(Sheet, {
          open: !!sheet, onClose: () => { setSheet(null); setWizMode(false); },
          title: wizMode ? '🪄 Fulfillment Wizard' : `Invoice #${sheet?.id || ''}`,
          footer: sheet && !wizMode && el('div', { style: { display: 'flex', gap: 10 } },
            el('button', { className: 'btn btn-ghost btn-full press', onClick: () => setWizMode(true) }, el(Icon, { name: 'key', size: 16 }), 'Fulfill'),
            el('button', { className: 'btn btn-danger btn-full press', onClick: reject }, el(Icon, { name: 'x', size: 16 }), 'Reject')
          )
        },
          wizMode
            ? el('div', { className: 'col g14' },
                el('div', { style: { padding: 12, borderRadius: 10, background: 'rgba(99,102,241,.1)', border: '1px solid rgba(99,102,241,.2)', fontSize: 13, color: '#a5b4fc', lineHeight: 1.5 } },
                  '🪄 Paste M3U URL to auto-fill credentials, then activate.'
                ),
                el(Field, { label: 'M3U URL', value: creds.m3u, onChange: v => setCreds({ ...creds, m3u: v }), type: 'textarea', rows: 2 }),
                el('button', { className: 'btn btn-ghost btn-sm press', onClick: parseM3U }, '⚡ Auto-Parse ⚡'),
                el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 } },
                  el(Field, { label: 'Username', value: creds.user, onChange: v => setCreds({ ...creds, user: v }) }),
                  el(Field, { label: 'Password', value: creds.pass, onChange: v => setCreds({ ...creds, pass: v }) })
                ),
                el(Field, { label: 'Host URL', value: creds.url, onChange: v => setCreds({ ...creds, url: v }) }),
                el('button', { className: 'btn btn-success btn-full press', style: { marginTop: 4 }, onClick: () => approve(true) },
                  el(Icon, { name: 'check', size: 18 }), 'Activate & Approve'
                )
              )
            : (sheet && el('div', { className: 'col g12' },
                el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '12px 16px', background: 'var(--surface2)', borderRadius: 12 } },
                  el('span', { style: { fontSize: 13, color: 'var(--muted)', fontWeight: 600 } }, 'Status'),
                  el(StatusBadge, { s: sheet.status })
                ),
                el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 } },
                  [['User', sheet.user_login], ['Amount', (sheet.currency_symbol || '$') + Number(sheet.amount || 0).toLocaleString()], ['Plan', sheet.plan_name], ['Date', sheet.date?.slice?.(0, 10)]].map(([k, v]) =>
                    el('div', { key: k, style: { padding: '10px 14px', background: 'var(--surface2)', borderRadius: 10 } },
                      el('p', { style: { fontSize: 11, color: 'var(--muted)', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '.06em' } }, k),
                      el('p', { style: { fontWeight: 700, marginTop: 4, fontSize: 15, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, v || '–')
                    )
                  )
                ),
                (sheet.proofs || []).length > 0
                  ? el('div', null,
                      el('p', { className: 'lbl', style: { marginBottom: 8 } }, 'Payment Proofs'),
                      el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 } },
                        sheet.proofs.map(u => el('a', { key: u, href: u, target: '_blank',
                          style: { aspectRatio: '16/9', display: 'block', borderRadius: 10, overflow: 'hidden', background: 'var(--surface3)', backgroundImage: `url(${u})`, backgroundSize: 'cover', backgroundPosition: 'center', border: '1px solid var(--border)' } }))
                      )
                    )
                  : el('p', { style: { textAlign: 'center', color: 'var(--muted)', fontSize: 13, padding: '16px 0' } }, 'No proof uploaded'),
                (sheet.status !== 'APPROVED' && sheet.status !== 'REJECTED' && sheet.status !== 'completed') &&
                  el('button', { className: 'btn btn-success btn-full press', onClick: () => approve(false) },
                    el(Icon, { name: 'check', size: 18 }), 'Quick Approve (no creds)'
                  )
              ))
        )
      );
    };

    window._XOFLIX.Payments = Payments;
  };
})();
