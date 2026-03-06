// part-users.js
// Registers: _XOFLIX._registerUsers → Users
// Depends on: React, _XOFLIX.{ apiFetch, showToast, Icon, Sheet, Field, StatusBadge, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerUsers = function () {
    const { useState, useEffect, useCallback, createElement: el } = React;
    const { apiFetch, showToast, Icon, Sheet, Field, StatusBadge, Loader, Empty } = window._XOFLIX;

    const SORT_OPTIONS = [
      { key: 'join_date',   label: 'Join Date',   icon: 'arrowDown' },
      { key: 'name',        label: 'Name',         icon: 'sort'     },
      { key: 'email',       label: 'Email',        icon: 'sort'     },
      { key: 'last_active', label: 'Last Active',  icon: 'activity' },
      { key: 'revenue',     label: 'Revenue',      icon: 'dollar'   },
      { key: 'sub_status',  label: 'Sub Status',   icon: 'tv'       },
    ];
    const PER_PAGE = 20;

    const Users = () => {
      const [list, setList]         = useState([]);
      const [total, setTotal]       = useState(0);
      const [page, setPage]         = useState(1);
      const [search, setSearch]     = useState('');
      const [sortKey, setSortKey]   = useState('join_date');
      const [sortDir, setSortDir]   = useState('desc');
      const [statusFilter, setStatusFilter] = useState('all');
      const [loading, setLoading]   = useState(false);
      const [sel, setSel]           = useState(null);
      const [detail, setDetail]     = useState(null);
      const [tab, setTab]           = useState('profile');
      const [pForm, setPForm]       = useState({});
      const [sForm, setSForm]       = useState({});
      const [multi, setMulti]       = useState(false);
      const [ids, setIds]           = useState([]);
      const [createOpen, setCreateOpen] = useState(false);
      const [newUser, setNewUser]   = useState({ display_name: '', email: '', login: '', phone: '', password: '' });
      const [showSort, setShowSort] = useState(false);

      const load = useCallback(async (pg = page) => {
        setLoading(true);
        try {
          const params = new URLSearchParams({ search: search || '', sort: sortKey, order: sortDir, status: statusFilter, page: pg, per_page: PER_PAGE });
          const r = await apiFetch('users?' + params.toString());
          if (Array.isArray(r)) {
            setList(r); setTotal(r.length);
          } else {
            setList(r.data || r.users || []); setTotal(r.total || r.data?.length || 0);
          }
        } catch (_) { setList([]); setTotal(0); }
        finally { setLoading(false); }
      }, [search, sortKey, sortDir, statusFilter, page]);

      useEffect(() => {
        setPage(1);
        const t = setTimeout(() => load(1), 300);
        return () => clearTimeout(t);
      }, [search, sortKey, sortDir, statusFilter]);

      useEffect(() => { load(page); }, [page]);

      useEffect(() => {
        if (!sel) { setDetail(null); return; }
        setDetail(null);
        apiFetch(`users/${sel}`).then(d => {
          setDetail(d);
          setPForm(d.profile || {});
          setSForm({ plan_id: '', status: 'active', start_date: new Date().toISOString().slice(0, 10), end_date: '', connections: 1 });
          setTab('profile');
        }).catch(() => {});
      }, [sel]);

      const saveProfile = async () => {
        try { await apiFetch(`users/${sel}/update`, 'POST', pForm); showToast('Profile saved ✅'); } catch (_) {}
      };
      const saveSub = async () => {
        try {
          await apiFetch(`users/${sel}/subscription`, 'POST', sForm);
          showToast('Subscription saved ✅');
          apiFetch(`users/${sel}`).then(d => setDetail(d)).catch(() => {});
        } catch (_) {}
      };
      const createUser = async () => {
        try {
          const r = await apiFetch('users/create', 'POST', newUser);
          showToast('User created ✅');
          if (r.generated_password) showToast('🔑 ' + r.generated_password, 'info');
          setCreateOpen(false);
          setNewUser({ display_name: '', email: '', login: '', phone: '', password: '' });
          load(1);
        } catch (_) {}
      };
      const toggleId = id => setIds(p => p.includes(id) ? p.filter(i => i !== id) : [...p, id]);
      const bulkDo = async action => {
        if (!ids.length) return showToast('Select users first', 'err');
        const code = Math.floor(1000 + Math.random() * 9000);
        const ok = await window._XOFLIX._confirm(`Bulk: ${action}`, `Apply to ${ids.length} users?`, { danger: action === 'delete_user', codeRequired: action === 'delete_user', code, confirmLabel: action === 'delete_user' ? 'Delete' : 'Confirm' });
        if (!ok) return;
        try { await apiFetch('users/bulk', 'POST', { ids, action }); showToast('Done ✅'); setMulti(false); setIds([]); load(page); } catch (_) {}
      };

      const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
      const cycleSortDir = () => setSortDir(d => d === 'desc' ? 'asc' : 'desc');
      const pickSort = k => { if (sortKey === k) { cycleSortDir(); } else { setSortKey(k); setSortDir('desc'); } };

      return el('div', { className: 'page-wrap' },
        el('div', { className: 'page-header top-safe' },
          el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 } },
            el('h1', { style: { fontSize: 24, fontWeight: 900 } }, 'Customers'),
            el('div', { style: { display: 'flex', gap: 8 } },
              multi && ids.length > 0 && el('div', { style: { display: 'flex', gap: 6 } },
                el('button', { className: 'btn btn-success btn-sm press', onClick: () => bulkDo('activate_sub') }, '🚀 Activate'),
                el('button', { className: 'btn btn-danger btn-sm press', onClick: () => bulkDo('delete_user') }, el(Icon, { name: 'trash', size: 14 }))
              ),
              el('button', { className: `btn btn-sm press ${multi ? 'btn-primary' : 'btn-ghost'}`, onClick: () => { setMulti(!multi); setIds([]); } }, multi ? 'Done' : 'Select'),
              el('button', { className: 'btn btn-ghost btn-sm press', onClick: () => setCreateOpen(true) }, el(Icon, { name: 'userPlus', size: 16 }))
            )
          ),
          el('input', { className: 'field', style: { marginBottom: 10 }, placeholder: 'Search by name, email, phone…', value: search, onChange: e => setSearch(e.target.value) }),
          el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 8 } },
            el('div', { className: 'chips-row', style: { flex: 1 } },
              ['all', 'active', 'inactive', 'pending', 'expired'].map(s =>
                el('button', { key: s, className: `chip press ${statusFilter === s ? 'active' : ''}`, onClick: () => setStatusFilter(s), style: { padding: '5px 12px', fontSize: 12 } }, s)
              )
            ),
            el('button', { className: `sort-pill press ${showSort ? 'active' : ''}`, onClick: () => setShowSort(v => !v) },
              el(Icon, { name: 'sort', size: 13 }),
              SORT_OPTIONS.find(o => o.key === sortKey)?.label || 'Sort',
              el(Icon, { name: sortDir === 'desc' ? 'arrowDown' : 'arrowUp', size: 11 })
            )
          ),
          showSort && el('div', { className: 'anim-up', style: { marginTop: 10, background: 'var(--surface2)', borderRadius: 12, border: '1px solid var(--border)', overflow: 'hidden' } },
            SORT_OPTIONS.map(o =>
              el('button', { key: o.key,
                style: { width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10, padding: '11px 14px', background: 'none', border: 'none', borderBottom: '1px solid var(--border)', cursor: 'pointer', color: sortKey === o.key ? 'var(--accent)' : 'var(--text)', fontFamily: 'inherit', fontSize: 14, fontWeight: sortKey === o.key ? 700 : 500 },
                onClick: () => { pickSort(o.key); setShowSort(false); }
              },
                el('span', null, o.label),
                sortKey === o.key && el('span', { style: { fontSize: 11, color: 'var(--accent)', fontWeight: 700 } }, sortDir === 'desc' ? '🔽 Desc' : '🔼 Asc')
              )
            )
          )
        ),

        el('div', { className: 'page-scroll' },
          !loading && el('div', { style: { padding: '8px 16px 4px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
            el('p', { style: { fontSize: 12, color: 'var(--muted)', fontWeight: 600 } },
              total > 0 ? `${total} user${total !== 1 ? 's' : ''} · Page ${page} of ${totalPages}` : ''
            )
          ),
          loading && el(Loader),
          !loading && list.length === 0 && el(Empty, { text: 'No users found', icon: 'users' }),
          !loading && list.map(u =>
            el('div', { key: u.id, className: 'card press anim-up',
              style: { margin: '8px 12px 0', padding: '14px 16px', display: 'flex', alignItems: 'center', gap: 12, cursor: 'pointer', border: ids.includes(u.id) ? '1px solid var(--accent)' : '1px solid var(--border)' },
              onClick: () => multi ? toggleId(u.id) : setSel(u.id)
            },
              multi && el('div', { style: { width: 22, height: 22, borderRadius: '50%', border: `2px solid ${ids.includes(u.id) ? 'var(--accent)' : 'var(--muted)'}`, background: ids.includes(u.id) ? 'var(--accent)' : 'none', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 } },
                ids.includes(u.id) && el(Icon, { name: 'check', size: 12, color: '#fff' })
              ),
              el('div', { style: { width: 40, height: 40, borderRadius: '50%', background: 'var(--surface3)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16, fontWeight: 800, color: 'var(--accent)', flexShrink: 0 } },
                ((u.name || u.display_name || '?')[0] || '?').toUpperCase()
              ),
              el('div', { style: { flex: 1, minWidth: 0 } },
                el('p', { style: { fontWeight: 700, fontSize: 15, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, u.name || u.display_name || 'Unknown'),
                el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, u.email || '')
              ),
              !multi && el('div', { style: { display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4, flexShrink: 0 } },
                u.sub_status && el(StatusBadge, { s: u.sub_status }),
                el(Icon, { name: 'chevronRight', size: 16, color: 'var(--muted)' })
              )
            )
          ),
          !loading && totalPages > 1 && el('div', { className: 'page-ctrl' },
            el('button', { className: 'page-num press', onClick: () => setPage(1), disabled: page === 1, style: { fontSize: 11 } }, '«'),
            el('button', { className: 'page-num press', onClick: () => setPage(p => Math.max(1, p - 1)), disabled: page === 1 }, el(Icon, { name: 'chevronLeft', size: 14 })),
            ...[...Array(Math.min(5, totalPages))].map((_, i) => {
              let pg;
              if (totalPages <= 5) { pg = i + 1; }
              else if (page <= 3) { pg = i + 1; }
              else if (page >= totalPages - 2) { pg = totalPages - 4 + i; }
              else { pg = page - 2 + i; }
              return el('button', { key: pg, className: `page-num press ${pg === page ? 'active' : ''}`, onClick: () => setPage(pg) }, pg);
            }),
            el('button', { className: 'page-num press', onClick: () => setPage(p => Math.min(totalPages, p + 1)), disabled: page === totalPages }, el(Icon, { name: 'chevronRight', size: 14 })),
            el('button', { className: 'page-num press', onClick: () => setPage(totalPages), disabled: page === totalPages, style: { fontSize: 11 } }, '»')
          )
        ),

        el(Sheet, { open: !!sel, onClose: () => setSel(null), title: detail?.profile?.display_name || 'Loading…' },
          detail
            ? el('div', null,
                el('div', { className: 'tab-row', style: { marginBottom: 20 } },
                  ['profile', 'subs', 'impersonate'].map(t =>
                    el('button', { key: t, className: `tab-btn ${tab === t ? 'active' : ''}`, onClick: () => setTab(t) },
                      t === 'impersonate' ? 'Login As' : (t[0].toUpperCase() + t.slice(1))
                    )
                  )
                ),
                tab === 'profile' && el('div', { className: 'col g14' },
                  el(Field, { label: 'Display Name', value: pForm.display_name || '', onChange: v => setPForm({ ...pForm, display_name: v }) }),
                  el(Field, { label: 'Email', value: pForm.email || '', onChange: v => setPForm({ ...pForm, email: v }), type: 'email' }),
                  el(Field, { label: 'Phone', value: pForm.phone || '', onChange: v => setPForm({ ...pForm, phone: v }) }),
                  el(Field, { label: 'New Password (leave blank to keep)', value: pForm.password || '', onChange: v => setPForm({ ...pForm, password: v }), placeholder: '••••••••' }),
                  el('button', { className: 'btn btn-primary btn-full press', style: { marginTop: 6 }, onClick: saveProfile }, el(Icon, { name: 'check', size: 16 }), 'Save Profile')
                ),
                tab === 'subs' && el('div', { className: 'col g12' },
                  (detail.subscriptions || []).length === 0 && el(Empty, { text: 'No subscriptions yet', icon: 'tv' }),
                  (detail.subscriptions || []).map(s =>
                    el('div', { key: s.id, className: 'card2', style: { padding: 14 } },
                      el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 } },
                        el('span', { style: { fontWeight: 700 } }, s.plan_name || 'Plan'),
                        el(StatusBadge, { s: s.status })
                      ),
                      el('p', { style: { fontSize: 12, color: 'var(--muted)' } }, `${s.start_date || '–'} ➔ ${s.end_date || '–'}`),
                      el('button', { className: 'btn btn-ghost btn-sm press', style: { marginTop: 8 }, onClick: () => setSForm({ ...s, sub_id: s.id }) }, el(Icon, { name: 'edit', size: 13 }), 'Edit')
                    )
                  ),
                  el('div', { style: { borderTop: '1px solid var(--border)', paddingTop: 16, marginTop: 4 } },
                    el('p', { style: { fontWeight: 800, fontSize: 15, marginBottom: 12 } }, sForm.sub_id ? '📝 Editing Subscription' : '+ Add Subscription'),
                    el('div', { className: 'col g12' },
                      el(Field, { label: 'Start Date', value: sForm.start_date || '', onChange: v => setSForm({ ...sForm, start_date: v }) }),
                      el(Field, { label: 'End Date', value: sForm.end_date || '', onChange: v => setSForm({ ...sForm, end_date: v }) }),
                      el(Field, { label: 'Status', value: sForm.status || 'active', onChange: v => setSForm({ ...sForm, status: v }), type: 'select', options: ['active', 'pending', 'inactive', 'expired'] }),
                      el(Field, { label: 'Connections', value: String(sForm.connections || 1), onChange: v => setSForm({ ...sForm, connections: parseInt(v) || 1 }) }),
                      el('button', { className: 'btn btn-success btn-full press', onClick: saveSub }, el(Icon, { name: 'check', size: 16 }), sForm.sub_id ? 'Save Changes' : 'Add Subscription')
                    )
                  )
                ),
                tab === 'impersonate' && el('div', { className: 'col g14' },
                  el('div', { style: { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 12, padding: '20px 0' } },
                    el('div', { style: { width: 64, height: 64, borderRadius: '50%', background: 'var(--surface2)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 26, fontWeight: 900, color: 'var(--accent)' } },
                      ((detail.profile?.display_name || '?')[0] || '?').toUpperCase()
                    ),
                    el('h3', { style: { fontWeight: 800, fontSize: 18 } }, detail.profile?.display_name || 'User'),
                    el('p', { style: { color: 'var(--muted)', fontSize: 14 } }, detail.profile?.email || ''),
                    el('p', { style: { fontSize: 13, color: 'var(--amber)', background: 'rgba(245,158,11,.1)', border: '1px solid rgba(245,158,11,.2)', borderRadius: 10, padding: '10px 16px', textAlign: 'center', lineHeight: 1.5 } },
                      '⚠️ You will be logged in as this user in a new tab. Session is time-limited.'
                    ),
                    detail.impersonate_url && el('a', { href: detail.impersonate_url, target: '_blank', className: 'btn btn-primary press', style: { width: '100%', textDecoration: 'none', textAlign: 'center' } },
                      el(Icon, { name: 'eye', size: 16 }), 'Login As This User'
                    )
                  )
                )
              )
            : el(Loader)
        ),

        el(Sheet, {
          open: createOpen, onClose: () => setCreateOpen(false), title: 'Create New User',
          footer: el('button', { className: 'btn btn-primary btn-full press', onClick: createUser }, el(Icon, { name: 'userPlus', size: 16 }), 'Create User')
        },
          el('div', { className: 'col g14' },
            el(Field, { label: 'Display Name', value: newUser.display_name, onChange: v => setNewUser({ ...newUser, display_name: v }) }),
            el(Field, { label: 'Email *', value: newUser.email, onChange: v => setNewUser({ ...newUser, email: v }), type: 'email' }),
            el(Field, { label: 'Username (login)', value: newUser.login, onChange: v => setNewUser({ ...newUser, login: v }), placeholder: 'Defaults to email' }),
            el(Field, { label: 'Phone', value: newUser.phone, onChange: v => setNewUser({ ...newUser, phone: v }) }),
            el(Field, { label: 'Password', value: newUser.password, onChange: v => setNewUser({ ...newUser, password: v }), placeholder: 'Auto-generated if blank' })
          )
        )
      );
    };

    window._XOFLIX.Users = Users;
  };
})();
