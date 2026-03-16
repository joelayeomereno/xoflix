// part-subs.js
// Registers: _XOFLIX._registerSubs → SubsHub
// Depends on: React, _XOFLIX.{ apiFetch, showToast, Icon, BackHeader, Sheet, Field, StatusBadge, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerSubs = function () {
    const { useState, useEffect, useCallback, createElement: el } = React;
    const { apiFetch, showToast, Icon, BackHeader, Sheet, Field, StatusBadge, Loader, Empty } = window._XOFLIX;

    const SubsHub = ({ onBack }) => {
      const [list, setList]     = useState([]);
      const [status, setStatus] = useState('all');
      const [search, setSearch] = useState('');
      const [loading, setLoading] = useState(true);
      const [plans, setPlans]   = useState([]);
      const [edit, setEdit]     = useState(null);
      const [multi, setMulti]   = useState(false);
      const [ids, setIds]       = useState([]);

      const load = useCallback(async () => {
        setLoading(true);
        try {
          const r = await apiFetch(`subscriptions?status=${status}&search=${encodeURIComponent(search)}`);
          setList(Array.isArray(r) ? r : (r.data || []));
        } catch (_) { setList([]); }
        finally { setLoading(false); }
      }, [status, search]);

      useEffect(() => {
        apiFetch('plans').then(p => setPlans(Array.isArray(p) ? p : (p.data || []))).catch(() => {});
        load();
      }, []);

      useEffect(() => { const t = setTimeout(load, 300); return () => clearTimeout(t); }, [status, search]);

      const saveEdit = async () => {
        try {
          await apiFetch(`users/${edit.user_id}/subscription`, 'POST', { sub_id: edit.id, plan_id: edit.plan_id, status: edit.status, start_date: edit.start_date, end_date: edit.end_date, connections: edit.connections || 1 });
          showToast('Subscription saved ✅'); setEdit(null); load();
        } catch (_) {}
      };

      const bulkDo = async action => {
        if (!ids.length) return showToast('Select items first', 'err');
        const code = Math.floor(1000 + Math.random() * 9000);
        const ok = await window._XOFLIX._confirm(`Bulk: ${action}`, `Apply to ${ids.length} subscriptions?`, { danger: action === 'delete', codeRequired: action === 'delete', code, confirmLabel: 'Confirm' });
        if (!ok) return;
        try { await apiFetch('subscriptions/bulk', 'POST', { ids, action }); showToast('Done ✅'); setIds([]); setMulti(false); load(); } catch (_) {}
      };

      const toggleId = id => setIds(p => p.includes(id) ? p.filter(i => i !== id) : [...p, id]);

      return el('div', { className: 'page-wrap' },
        el(BackHeader, { title: 'Subscriptions', onBack,
          right: el('button', { className: `btn btn-sm press ${multi ? 'btn-primary' : 'btn-ghost'}`, onClick: () => { setMulti(!multi); setIds([]); } }, multi ? 'Done' : 'Select')
        }),
        el('div', { style: { padding: '10px 14px 0', borderBottom: '1px solid var(--border)', display: 'flex', flexDirection: 'column', gap: 8 } },
          el('div', { className: 'chips-row' },
            ['all', 'active', 'pending', 'inactive', 'expired'].map(s =>
              el('button', { key: s, className: `chip press ${status === s ? 'active' : ''}`, onClick: () => setStatus(s) }, s)
            )
          ),
          el('input', { className: 'field', style: { marginBottom: 10 }, placeholder: 'Search…', value: search, onChange: e => setSearch(e.target.value) }),
          multi && ids.length > 0 && el('div', { style: { display: 'flex', gap: 8, paddingBottom: 10 } },
            el('button', { className: 'btn btn-success btn-sm press btn-full', onClick: () => bulkDo('activate') }, '🚀 Activate Selected'),
            el('button', { className: 'btn btn-danger btn-sm press', onClick: () => bulkDo('delete') }, el(Icon, { name: 'trash', size: 14 }))
          )
        ),
        el('div', { className: 'page-scroll no-nav' },
          loading && el(Loader),
          !loading && list.length === 0 && el(Empty, { text: 'No subscriptions found', icon: 'tv' }),
          !loading && list.map(s =>
            el('div', { key: s.id, className: 'card press anim-up',
              style: { margin: '8px 12px 0', padding: '14px 16px', display: 'flex', alignItems: 'center', gap: 12, cursor: 'pointer', border: ids.includes(s.id) ? '1px solid var(--accent)' : '1px solid var(--border)' },
              onClick: () => multi ? toggleId(s.id) : setEdit(s)
            },
              multi && el('div', { style: { width: 22, height: 22, borderRadius: '50%', border: `2px solid ${ids.includes(s.id) ? 'var(--accent)' : 'var(--muted)'}`, background: ids.includes(s.id) ? 'var(--accent)' : 'none', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 } },
                ids.includes(s.id) && el(Icon, { name: 'check', size: 12, color: '#fff' })
              ),
              el('div', { style: { flex: 1, minWidth: 0 } },
                el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 } },
                  el('span', { style: { fontWeight: 800, fontSize: 15 } }, `#${s.id}`),
                  el('span', { style: { fontWeight: 600, fontSize: 14, color: 'var(--muted)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', flex: 1, margin: '0 8px' } }, s.user_login || ''),
                  el(StatusBadge, { s: s.status })
                ),
                el('p', { style: { fontSize: 12, color: 'var(--muted)' } }, `${s.plan_name || '–'} · ends ${s.end_date || '–'}`)
              ),
              !multi && el(Icon, { name: 'edit', size: 16, color: 'var(--muted)' })
            )
          )
        ),
        el(Sheet, {
          open: !!edit, onClose: () => setEdit(null),
          title: `Edit Sub #${edit?.id || ''}`,
          footer: edit && el('button', { className: 'btn btn-primary btn-full press', onClick: saveEdit }, el(Icon, { name: 'check', size: 16 }), 'Save Changes')
        },
          edit && el('div', { className: 'col g14' },
            el(Field, { label: 'Plan', value: String(edit.plan_id || ''), onChange: v => setEdit({ ...edit, plan_id: parseInt(v) }), type: 'select', options: plans.map(p => ({ value: String(p.id), label: `${p.name} ($${p.price})` })) }),
            el(Field, { label: 'Status', value: edit.status || 'active', onChange: v => setEdit({ ...edit, status: v }), type: 'select', options: ['active', 'pending', 'inactive', 'expired'] }),
            el(Field, { label: 'Start Date', value: edit.start_date || '', onChange: v => setEdit({ ...edit, start_date: v }), placeholder: 'YYYY-MM-DD' }),
            el(Field, { label: 'End Date', value: edit.end_date || '', onChange: v => setEdit({ ...edit, end_date: v }), placeholder: 'YYYY-MM-DD' }),
            el(Field, { label: 'Connections', value: String(edit.connections || 1), onChange: v => setEdit({ ...edit, connections: parseInt(v) || 1 }) })
          )
        )
      );
    };

    window._XOFLIX.SubsHub = SubsHub;
  };
})();
