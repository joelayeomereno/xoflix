// part-finance.js
// Registers: _XOFLIX._registerFinance → Finance
// Depends on: React, _XOFLIX.{ apiFetch, Icon, BackHeader, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerFinance = function () {
    const { useState, useEffect, useCallback, useMemo, createElement: el } = React;
    const { apiFetch, Icon, BackHeader, Loader, Empty } = window._XOFLIX;

    const Finance = ({ onBack }) => {
      const [data, setData]       = useState(null);
      const [range, setRange]     = useState('4w');
      const [loading, setLoading] = useState(true);
      const [detail, setDetail]   = useState(null);

      const load = useCallback(async (r = range) => {
        setLoading(true);
        try {
          const res = await apiFetch(`finance?range=${r}`);
          setData(res);
        } catch (_) { setData(null); }
        finally { setLoading(false); }
      }, [range]);

      useEffect(() => { load(range); }, [range]);

      const summary = useMemo(() => {
        if (!data?.weeks) return null;
        const weeks = Object.values(data.weeks);
        return {
          total:       weeks.reduce((s, w) => s + parseFloat(w.total || 0), 0),
          count:       weeks.reduce((s, w) => s + parseInt(w.count || 0), 0),
          new_total:   weeks.reduce((s, w) => s + parseFloat(w.new_total || 0), 0),
          renew_total: weeks.reduce((s, w) => s + parseFloat(w.renew_total || 0), 0),
        };
      }, [data]);

      const fmt = n => {
        const num = parseFloat(n || 0);
        const sym = data?.currency_symbol || '$';
        return sym + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      };

      const weeks = data?.weeks ? Object.entries(data.weeks) : [];

      const RangeChips = () =>
        el('div', { className: 'chips-row' },
          [['4w', '4 Weeks'], ['8w', '8 Weeks'], ['12w', '12 Weeks']].map(([v, l]) =>
            el('button', { key: v, className: `chip press ${range === v ? 'active' : ''}`, onClick: () => setRange(v) }, l)
          )
        );

      return el('div', { className: 'page-wrap' },

        onBack
          ? el(BackHeader, {
              title: 'Finance', onBack,
              right: el('div', { style: { display: 'flex', gap: 8, alignItems: 'center' } },
                data?.csv_url && el('a', { href: data.csv_url, className: 'btn btn-ghost btn-sm press', style: { textDecoration: 'none' } }, el(Icon, { name: 'fileText', size: 14 }), 'CSV'),
                el('button', { className: 'btn btn-ghost btn-sm press', onClick: () => load(range) }, el(Icon, { name: 'refresh', size: 14 }))
              )
            })
          : el('div', { className: 'page-header top-safe' },
              el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 } },
                el('div', null,
                  el('h1', { style: { fontSize: 24, fontWeight: 900 } }, 'Finance'),
                  el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2 } }, 'Weekly revenue · Mon–Sun')
                ),
                el('div', { style: { display: 'flex', gap: 8, alignItems: 'center' } },
                  data?.csv_url && el('a', { href: data.csv_url, className: 'btn btn-ghost btn-sm press', style: { textDecoration: 'none' } }, el(Icon, { name: 'fileText', size: 14 }), 'CSV'),
                  el('button', { className: 'btn btn-ghost btn-sm press', onClick: () => load(range) }, el(Icon, { name: 'refresh', size: 14 }))
                )
              ),
              el(RangeChips)
            ),

        onBack && el('div', { style: { padding: '10px 12px', borderBottom: '1px solid var(--border)', flexShrink: 0 } },
          el(RangeChips)
        ),

        el('div', { className: onBack ? 'page-scroll no-nav' : 'page-scroll' },
          loading && el(Loader),

          !loading && summary && el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, padding: '12px 12px 0' } },
            [
              ['Total Revenue', fmt(summary.total),       'dollar',     '#6366f1', 'rgba(99,102,241,.12)'],
              ['Transactions',  summary.count,            'creditCard', '#22c55e', 'rgba(34,197,94,.12)'],
              ['New Subs',      fmt(summary.new_total),   'userPlus',   '#06b6d4', 'rgba(6,182,212,.12)'],
              ['Renewals',      fmt(summary.renew_total), 'refresh',    '#f59e0b', 'rgba(245,158,11,.12)'],
            ].map(([label, val, icon, color, bg]) =>
              el('div', { key: label, className: 'card stat-card' },
                el('div', { className: 'stat-glow', style: { background: `radial-gradient(circle at 80% 20%,${color},transparent 70%)` } }),
                el('div', { style: { width: 32, height: 32, borderRadius: 9, background: bg, display: 'flex', alignItems: 'center', justifyContent: 'center', color, flexShrink: 0 } },
                  el(Icon, { name: icon, size: 16 })
                ),
                el('div', { style: { minWidth: 0 } },
                  el('p', { style: { fontSize: 10, color: 'var(--muted)', fontWeight: 700, letterSpacing: '.06em', textTransform: 'uppercase' } }, label),
                  el('p', { style: { fontSize: 18, fontWeight: 900, lineHeight: 1.2, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', fontVariantNumeric: 'tabular-nums' } }, String(val))
                )
              )
            )
          ),

          !loading && weeks.length === 0 && el(Empty, { text: 'No transactions in this period', icon: 'dollar' }),

          !loading && weeks.map(([wk, w]) => {
            const isOpen       = detail === wk;
            const hasBreakdown = (w.by_method && Object.keys(w.by_method).length > 0) ||
                                 (w.by_plan   && Object.keys(w.by_plan).length   > 0);
            return el('div', { key: wk, className: 'card anim-up', style: { margin: '8px 12px 0' } },
              el('div', { style: { padding: '14px 16px', display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer' }, onClick: () => setDetail(isOpen ? null : wk) },
                el('div', { style: { flex: 1, minWidth: 0 } },
                  el('p', { style: { fontWeight: 800, fontSize: 15, marginBottom: 2 } }, `${w.week_start_fmt || wk} – ${w.week_end_fmt || ''}`),
                  el('p', { style: { fontSize: 11, color: 'var(--muted)', fontWeight: 600 } }, `${w.count || 0} txn${w.count !== 1 ? 's' : ''} · ${fmt(w.new_total)} new · ${fmt(w.renew_total)} renewal`)
                ),
                el('div', { style: { textAlign: 'right', flexShrink: 0 } },
                  el('p', { style: { fontSize: 20, fontWeight: 900, fontVariantNumeric: 'tabular-nums', color: 'var(--green)' } }, fmt(w.total)),
                  el('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 4, marginTop: 2 } },
                    el(Icon, { name: isOpen ? 'chevronUp' : 'chevronDown', size: 14, color: 'var(--muted)' })
                  )
                )
              ),
              isOpen && hasBreakdown && el('div', { className: 'anim-in', style: { borderTop: '1px solid var(--border)', padding: '12px 16px', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 } },
                el('div', null,
                  el('p', { style: { fontSize: 11, fontWeight: 800, color: 'var(--muted)', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 8 } }, 'By Method'),
                  w.by_method && Object.entries(w.by_method).length > 0
                    ? Object.entries(w.by_method).map(([m, amt]) =>
                        el('div', { key: m, style: { display: 'flex', justifyContent: 'space-between', padding: '5px 0', borderBottom: '1px solid var(--border)', fontSize: 13 } },
                          el('span', { style: { color: 'var(--muted)', fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '55%' } }, m),
                          el('span', { style: { fontWeight: 800, fontVariantNumeric: 'tabular-nums' } }, fmt(amt))
                        )
                      )
                    : el('p', { style: { fontSize: 12, color: 'var(--muted)' } }, '–')
                ),
                el('div', null,
                  el('p', { style: { fontSize: 11, fontWeight: 800, color: 'var(--muted)', textTransform: 'uppercase', letterSpacing: '.06em', marginBottom: 8 } }, 'By Plan'),
                  w.by_plan && Object.entries(w.by_plan).length > 0
                    ? Object.entries(w.by_plan).map(([pn, amt]) =>
                        el('div', { key: pn, style: { display: 'flex', justifyContent: 'space-between', padding: '5px 0', borderBottom: '1px solid var(--border)', fontSize: 13 } },
                          el('span', { style: { color: 'var(--muted)', fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '55%' } }, pn),
                          el('span', { style: { fontWeight: 800, fontVariantNumeric: 'tabular-nums' } }, fmt(amt))
                        )
                      )
                    : el('p', { style: { fontSize: 12, color: 'var(--muted)' } }, '–')
                )
              ),
              isOpen && !hasBreakdown && el('div', { className: 'anim-in', style: { borderTop: '1px solid var(--border)', padding: '12px 16px' } },
                el('p', { style: { fontSize: 13, color: 'var(--muted)', textAlign: 'center' } }, 'No method/plan breakdown available for this week.')
              )
            );
          })
        )
      );
    };

    window._XOFLIX.Finance = Finance;
  };
})();
