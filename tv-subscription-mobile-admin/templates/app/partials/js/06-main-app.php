<?php
/**
 * File: tv-subscription-mobile-admin/templates/app/partials/js/06-main-app.php
 * Path: tv-subscription-mobile-admin/templates/app/partials/js/06-main-app.php
 *
 * Adds full parity with desktop TV Manager user-manage page:
 * Tab 1  Profile      — display_name, email, first/last name, phone, country,
 * admin notes, password reset, joined date, impersonate
 * Tab 2  Subscription — IPTV credentials (user/pass/url/m3u), plan, status,
 * start/end dates, connections, subscription history
 * Tab 3  Transactions — full payment history table (A to Z all columns)
 * Tab 4  Logs         — activity log (date, action, details, IP)
 *
 * [PAGINATION FIX] Added page/meta state and navigation controls.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

        // ================================================================
        // USERS COMPONENT — Full A-Z Parity with Desktop TV Manager
        // ================================================================
        const Users = () => {
            const [list,        setList]        = useState([]);
            const [page,        setPage]        = useState(1);
            const [meta,        setMeta]        = useState({total:0,pages:1});
            const [sel,         setSel]         = useState(null);  // selected user ID
            const [data,        setData]        = useState(null);  // full user payload
            const [tab,         setTab]         = useState('profile');
            const [search,      setSearch]      = useState('');
            const [pForm,       setPForm]       = useState({});    // profile form
            const [sForm,       setSForm]       = useState({});    // subscription form
            const [multi,       setMulti]       = useState(false);
            const [selectedIds, setSelectedIds] = useState([]);
            const [plans,       setPlans]       = useState([]);
            const [saving,      setSaving]      = useState(false);

            useEffect(() => { api('plans').then(setPlans); }, []);
            useEffect(() => {
                api(`users?page=${page}` + (search ? '&search=' + encodeURIComponent(search) : '')).then(res => {
                    setList(res.data || []);
                    setMeta({ total: res.total, pages: res.pages });
                });
            }, [search, page]);

            const loadUser = uid => {
                setData(null);
                api(`users/${uid}`).then(d => {
                    setData(d);
                    setPForm(d.profile || {});
                    const latest = (d.subscriptions || [])[0];
                    setSForm(latest
                        ? { ...latest, sub_id: latest.id }
                        : { plan_id: plans[0]?.id || 1, status:'active',
                            start_date: new Date().toISOString().slice(0,10),
                            end_date:'', connections:1,
                            credential_user:'', credential_pass:'', credential_url:'', credential_m3u:'' }
                    );
                });
            };

            useEffect(() => { if (sel) { setTab('profile'); loadUser(sel); } }, [sel]);

            const saveProfile = async () => {
                setSaving(true);
                try { await api(`users/${sel}/update`, 'POST', pForm); alert('Profile saved.'); }
                finally { setSaving(false); }
            };

            const saveSub = async () => {
                setSaving(true);
                try {
                    await api(`users/${sel}/subscription`, 'POST', sForm);
                    alert('Subscription saved.');
                    loadUser(sel);
                } finally { setSaving(false); }
            };

            const toggleId = id => setSelectedIds(prev =>
                prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);

            const bulkAction = async action => {
                if (action === 'delete_user') {
                    if (!confirmDeleteWithCode('delete these users')) return;
                } else {
                    if (!window.confirm('Confirm bulk action?')) return;
                }
                await api('users/bulk', 'POST', { ids: selectedIds, action });
                setMulti(false); setSelectedIds([]);
                setPage(1); loadUser(); 
            };

            const SubBadge = ({ s }) => {
                const cls = s === 'active' ? 'bg-emerald-100 text-emerald-700'
                    : s === 'pending' ? 'bg-amber-100 text-amber-700'
                    : 'bg-slate-100 text-slate-500';
                return el('span', { className: `px-2 py-0.5 rounded-full text-[9px] font-black uppercase ${cls}` }, s);
            };

            const DR = ({ label, value, mono }) => value != null && value !== '' && value !== '-'
                ? el('div', { className: 'flex justify-between items-start py-2 border-b border-slate-50 last:border-0' },
                    el('span', { className: 'text-xs font-bold text-slate-400 uppercase tracking-wide flex-shrink-0' }, label),
                    el('span', { className: `text-xs font-black text-slate-900 text-right ml-4 break-all ${mono ? 'font-mono' : ''}` }, value))
                : null;

            const sym = (ccOrPayment) => {
                const cc = (typeof ccOrPayment === 'object' && ccOrPayment !== null)
                    ? (ccOrPayment.currency || 'USD')
                    : (ccOrPayment || 'USD');
                const map = {
                    'USD': '$', 'EUR': '\u20AC', 'GBP': '\u00A3', 'NGN': '\u20A6',
                    'GHS': '\u20B5', 'KES': 'KSh', 'ZAR': 'R', 'INR': '\u20B9',
                    'TRY': '\u20BA', 'JPY': '\u00A5', 'AUD': 'A$', 'CAD': 'C$',
                    'SGD': 'S$', 'AED': '\u062F.\u0625', 'SAR': '\u0631.\u0633',
                };
                return map[cc.toUpperCase()] || (cc.toUpperCase() + ' ');
            };

            const renderProfile = () => data && el('div', { className: 'space-y-4' },
                el('div', { className: 'flex gap-3' },
                    el('div', { className: 'flex-1 bg-slate-50 rounded-2xl p-4 text-center' },
                        el('p', { className: 'text-xs font-black text-slate-400 uppercase' }, 'Joined'),
                        el('p', { className: 'font-black text-slate-900 mt-1 text-sm' },
                            data.profile.user_registered
                                ? new Date(data.profile.user_registered).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})
                                : '—')
                    ),
                    el('div', { className: 'flex-1 bg-emerald-50 rounded-2xl p-4 text-center' },
                        el('p', { className: 'text-xs font-black text-emerald-500 uppercase' }, 'Lifetime Value'),
                        el('p', { className: 'font-black text-emerald-700 mt-1 text-lg' },
                            data.ltv != null ? `$${Number(data.ltv).toLocaleString(undefined,{maximumFractionDigits:2})}` : '$0')
                    )
                ),
                el('div', { className: 'bg-slate-50 rounded-2xl p-4 space-y-3' },
                    el('p', { className: 'text-xs font-black text-slate-400 uppercase mb-2' }, 'Account Information'),
                    el(Input, { label:'Display Name', value: pForm.display_name || '', onChange: v => setPForm({...pForm, display_name: v}) }),
                    el('div', { className: 'grid grid-cols-2 gap-3' },
                        el(Input, { label:'First Name', value: pForm.first_name || '', onChange: v => setPForm({...pForm, first_name: v}) }),
                        el(Input, { label:'Last Name',  value: pForm.last_name  || '', onChange: v => setPForm({...pForm, last_name: v}) })
                    ),
                    el(Input, { label:'Email', value: pForm.email || '', onChange: v => setPForm({...pForm, email: v}), type:'email' }),
                    el('div', { className: 'grid grid-cols-2 gap-3' },
                        el(Input, { label:'Phone', value: pForm.phone || '', onChange: v => setPForm({...pForm, phone: v}) }),
                        el(Input, { label:'Country', value: pForm.billing_country || '', onChange: v => setPForm({...pForm, billing_country: v}), p:'e.g. NG' })
                    )
                ),
                el('div', { className: 'bg-slate-50 rounded-2xl p-4 space-y-3' },
                    el('p', { className: 'text-xs font-black text-slate-400 uppercase mb-2' }, 'Security'),
                    el(Input, { label:'New Password (leave blank to keep)', value: pForm.password || '', onChange: v => setPForm({...pForm, password: v}), p:'Enter to override...' }),
                ),
                el('div', { className: 'bg-slate-50 rounded-2xl p-4 space-y-2' },
                    el('p', { className: 'text-xs font-black text-slate-400 uppercase mb-2' }, 'Admin Notes'),
                    el('textarea', {
                        className: 'w-full p-3 bg-white rounded-xl text-sm font-medium border border-slate-200 resize-none',
                        rows: 3, placeholder: 'Internal notes...',
                        value: pForm.admin_notes || '',
                        onChange: e => setPForm({...pForm, admin_notes: e.target.value})
                    })
                ),
                el('button', {
                    onClick: saveProfile, disabled: saving,
                    className: 'w-full py-4 bg-slate-900 text-white font-black rounded-2xl shadow-xl btn-press mt-4'
                }, saving ? 'Saving…' : 'Save Profile'),
                data.impersonate_url && el('a', {
                    href: data.impersonate_url, target: '_blank',
                    className: 'block w-full py-4 bg-primary-50 text-primary-600 font-black rounded-2xl text-center'
                }, '?? Login As User')
            );

            const renderSubscription = () => data && el('div', { className: 'space-y-4' },
                el('div', { className: 'bg-slate-50 rounded-2xl p-4 space-y-3' },
                    el('div', { className: 'flex items-center justify-between mb-2' },
                        el('p', { className: 'text-xs font-black text-slate-400 uppercase' },
                            sForm.sub_id ? `Editing Sub #${sForm.sub_id}` : 'New Subscription'),
                        el('button', {
                            onClick: () => {
                                const rnd = s => {
                                    const c = 'abcdefghjkmnpqrstuvwxyz23456789';
                                    return Array.from({length:s},()=>c[Math.floor(Math.random()*c.length)]).join('');
                                };
                                setSForm(prev => ({...prev, credential_user: 'user' + rnd(6), credential_pass: rnd(10)}));
                            },
                            className: 'text-xs font-black text-primary-600'
                        }, '? Magic Creds')
                    ),
                    el('div', { className: 'grid grid-cols-2 gap-3' },
                        el('div', null,
                            el('label', { className: 'text-xs font-bold text-slate-400 uppercase' }, 'Plan'),
                            el('select', {
                                className: 'w-full p-3 bg-white rounded-xl text-sm font-bold border border-slate-200 mt-1',
                                value: sForm.plan_id || '', onChange: e => setSForm({...sForm, plan_id: parseInt(e.target.value)})
                            }, plans.map(p => el('option', { key: p.id, value: p.id }, p.name)))
                        ),
                        el('div', null,
                            el('label', { className: 'text-xs font-bold text-slate-400 uppercase' }, 'Status'),
                            el('select', {
                                className: 'w-full p-3 bg-white rounded-xl text-sm font-bold border border-slate-200 mt-1',
                                value: sForm.status || 'active', onChange: e => setSForm({...sForm, status: e.target.value})
                            }, ['active','pending','expired','inactive'].map(s => el('option', { key:s, value:s }, s)))
                        )
                    ),
                    el('div', { className: 'grid grid-cols-2 gap-3' },
                        el(Input, { label:'Start Date', value: sForm.start_date || '', onChange: v => setSForm({...sForm, start_date: v}), p:'YYYY-MM-DD HH:MM:SS' }),
                        el(Input, { label:'End Date',   value: sForm.end_date   || '', onChange: v => setSForm({...sForm, end_date: v}),   p:'YYYY-MM-DD HH:MM:SS' })
                    ),
                    el(Input, { label:'Max Connections', value: String(sForm.connections || 1), onChange: v => setSForm({...sForm, connections: parseInt(v||'1')||1}), type:'number' }),
                    el('div', { className: 'pt-3 border-t border-slate-200 mt-2 space-y-3' },
                        el('p', { className: 'text-xs font-black text-slate-400 uppercase' }, 'Line Credentials'),
                        el('div', { className: 'grid grid-cols-2 gap-3' },
                            el(Input, { label:'Username', value: sForm.credential_user || '', onChange: v => setSForm({...sForm, credential_user: v}) }),
                            el(Input, { label:'Password', value: sForm.credential_pass || '', onChange: v => setSForm({...sForm, credential_pass: v}) })
                        ),
                        el(Input, { label:'Host / DNS URL', value: sForm.credential_url || '', onChange: v => setSForm({...sForm, credential_url: v}), p:'http://domain.com:8080' }),
                        el('div', { className: 'space-y-1' },
                            el('label', { className: 'text-xs font-bold text-slate-400 uppercase' }, 'M3U / Smart TV URL'),
                            el('textarea', {
                                className: 'w-full p-3 bg-white rounded-xl text-sm font-mono border border-slate-200',
                                rows: 3, value: sForm.credential_m3u || '', placeholder: 'http://host/get.php?username=...&password=...',
                                onChange: e => setSForm({...sForm, credential_m3u: e.target.value})
                            })
                        )
                    ),
                    el('button', {
                        onClick: saveSub, disabled: saving,
                        className: 'w-full py-4 bg-emerald-500 text-white font-black rounded-2xl shadow-lg btn-press mt-2'
                    }, saving ? 'Saving…' : (sForm.sub_id ? 'Update Subscription' : 'Create Subscription'))
                ),
                data.subscriptions && data.subscriptions.length > 0 && el('div', { className: 'space-y-2' },
                    el('p', { className: 'text-xs font-black text-slate-400 uppercase px-1' }, 'All Subscriptions'),
                    data.subscriptions.map(s =>
                        el('div', { key: s.id,
                            onClick: () => setSForm({...s, sub_id: s.id}),
                            className: `bg-white border rounded-2xl p-4 btn-press ${sForm.sub_id === s.id ? 'border-primary-400 ring-1 ring-primary-400' : 'border-slate-100'}`
                        },
                            el('div', { className: 'flex justify-between items-center' },
                                el('div', null,
                                    el('p', { className: 'font-black text-sm' }, s.plan_name || `Sub #${s.id}`),
                                    el('p', { className: 'text-xs text-slate-400 mt-0.5' },
                                        `${s.start_date ? s.start_date.slice(0,10) : '?'} ? ${s.end_date ? s.end_date.slice(0,10) : '?'}`)
                                ),
                                el(SubBadge, { s: s.status })
                            ),
                            (s.credential_user || s.credential_pass || s.credential_url) &&
                                el('div', { className: 'mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2' },
                                    s.credential_user && el('div', null,
                                        el('p', { className: 'text-[9px] font-black text-slate-400 uppercase' }, 'Username'),
                                        el('p', { className: 'text-xs font-mono font-bold' }, s.credential_user)),
                                    s.credential_pass && el('div', null,
                                        el('p', { className: 'text-[9px] font-black text-slate-400 uppercase' }, 'Password'),
                                        el('p', { className: 'text-xs font-mono font-bold' }, s.credential_pass)),
                                    s.credential_url && el('div', { className: 'col-span-2' },
                                        el('p', { className: 'text-[9px] font-black text-slate-400 uppercase' }, 'Host URL'),
                                        el('a', { href: s.credential_url, target:'_blank',
                                            className: 'text-xs font-mono text-primary-600 break-all' }, s.credential_url))
                                )
                        )
                    )
                )
            );

            const renderTransactions = () => data && el('div', { className: 'space-y-3' },
                el('div', { className: 'bg-emerald-50 rounded-2xl p-4 flex justify-between items-center' },
                    el('span', { className: 'text-xs font-black text-emerald-600 uppercase' }, 'Total Paid (LTV)'),
                    el('span', { className: 'text-xl font-black text-emerald-700' },
                        `$${Number(data.ltv||0).toLocaleString(undefined,{maximumFractionDigits:2})}`)
                ),
                (data.payments || []).length === 0 && el('div', { className: 'text-center py-10 text-slate-400' },
                    el('p', { className: 'font-bold' }, 'No payment records found')),
                (data.payments || []).map(p => {
                    const stMap = { 'APPROVED':'bg-emerald-100 text-emerald-700', 'completed':'bg-emerald-100 text-emerald-700', 'REJECTED':'bg-rose-100 text-rose-600', 'pending':'bg-amber-100 text-amber-700' };
                    const stCls = stMap[p.status] || 'bg-slate-100 text-slate-500';
                    return el('div', { key: p.id, className: 'bg-white border border-slate-100 rounded-2xl p-4 space-y-2' },
                        el('div', { className: 'flex justify-between items-start' },
                            el('div', null,
                                el('p', { className: 'font-black text-lg', dangerouslySetInnerHTML: { __html: decodeHtml(p.amount_display) || (sym(p) + Number(p.amount).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})) } }),
                                el('p', { className: 'text-xs text-slate-500 font-mono mt-0.5' }, p.transaction_id || `#${p.id}`)
                            ),
                            el('div', { className: 'text-right' },
                                el('span', { className: `px-2 py-1 rounded-full text-[9px] font-black uppercase ${stCls}` }, p.status),
                                el('p', { className: 'text-xs text-slate-400 mt-1' }, p.date ? p.date.slice(0,16) : '')
                            )
                        )
                    );
                })
            );

            const renderLogs = () => data && el('div', { className: 'space-y-2' },
                (data.logs || []).length === 0 && el('div', { className: 'text-center py-10 text-slate-400' }, el('p', { className: 'font-bold' }, 'No activity recorded')),
                (data.logs || []).map((l, i) =>
                    el('div', { key: i, className: 'bg-white border border-slate-100 rounded-xl p-3' },
                        el('div', { className: 'flex justify-between items-start gap-2' },
                            el('span', { className: 'text-xs font-black text-slate-700' }, l.action),
                            el('span', { className: 'text-[10px] text-slate-400 font-mono flex-shrink-0' }, l.date ? l.date.slice(0,16) : '')
                        ),
                        l.details && el('p', { className: 'text-xs text-slate-500 mt-1' }, l.details)
                    )
                )
            );

            return el('div', { className: 'flex flex-col h-full' },
                el('div', { className: 'p-5 pb-2 space-y-4' },
                    el('div', { className: 'flex justify-between items-center' },
                        el('h1', { className: 'text-3xl font-black' }, 'Customers'),
                        el('button', { onClick: () => { setMulti(!multi); setSelectedIds([]); haptic(); }, className: `text-xs font-bold px-3 py-2 rounded-xl btn-press ${multi ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'}` }, multi ? 'Cancel' : 'Bulk')
                    ),
                    multi && selectedIds.length > 0 && el('div', { className: 'flex gap-2' },
                        el('button', { onClick: () => bulkAction('activate_sub'), className: 'flex-1 py-3 bg-emerald-500 text-white rounded-xl text-xs font-black' }, `Activate (${selectedIds.length})`),
                        el('button', { onClick: () => bulkAction('delete_user'), className: 'flex-1 py-3 bg-rose-500 text-white rounded-xl text-xs font-black' }, `Delete (${selectedIds.length})`)
                    ),
                    el(Input, { p: 'Search customers…', value: search, onChange: v => { setSearch(v); setPage(1); } })
                ),
                el('div', { className: 'flex-1 overflow-y-auto p-5 pt-2 pb-32 space-y-3 bg-slate-50' },
                    list.map(u => {
                        const checked = selectedIds.includes(u.id);
                        return el('div', {
                            key: u.id,
                            onClick: () => multi ? (toggleId(u.id), haptic()) : (setSel(u.id), haptic()),
                            className: `bg-white p-4 rounded-2xl shadow-sm border btn-press cursor-pointer flex items-center gap-4 ${checked ? 'border-primary-500 ring-1 ring-primary-500' : 'border-slate-100'}`
                        },
                            el('div', { className: 'w-11 h-11 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center font-black text-lg flex-shrink-0' }, (u.name || u.login || '?')[0].toUpperCase()),
                            el('div', { className: 'flex-1 min-w-0' },
                                el('p', { className: 'font-black truncate' }, u.name || u.login),
                                el('p', { className: 'text-xs text-slate-500 truncate' }, u.email)
                            ),
                            multi && el('div', { className: `w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 ${checked ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-300'}` }, checked && el(Icon, { name: 'check', size: 12 }))
                        );
                    }),
                    meta.pages > 1 && el('div', {className:'flex justify-center items-center gap-6 py-4'},
                        el('button', {onClick:()=>{setPage(p=>Math.max(1,p-1)); haptic();}, disabled:page===1, className:'btn btn-ghost btn-sm press'}, 'Prev'),
                        el('span', {className:'text-xs font-black text-slate-400 uppercase tracking-widest'}, `Page ${page} / ${meta.pages}`),
                        el('button', {onClick:()=>{setPage(p=>Math.min(meta.pages,p+1)); haptic();}, disabled:page===meta.pages, className:'btn btn-ghost btn-sm press'}, 'Next')
                    )
                ),
                el(Sheet, { open: !!sel, onClose: () => { setSel(null); setData(null); }, title: data?.profile?.display_name || 'Loading…' },
                    !data ? el(Loader) : el('div', { className: 'flex flex-col h-full' },
                        el('div', { className: 'flex items-center gap-4 pb-4 mb-4 border-b border-slate-100' },
                            el('div', { className: 'w-14 h-14 bg-gradient-to-br from-primary-500 to-violet-500 text-white rounded-2xl flex items-center justify-center font-black text-2xl shadow-lg' }, (data.profile.display_name || '?')[0].toUpperCase()),
                            el('div', null, el('p', { className: 'font-black text-lg text-slate-900' }, data.profile.display_name), el('p', { className: 'text-xs text-slate-500' }, data.profile.email))
                        ),
                        el('div', { className: 'flex p-1 bg-slate-100 rounded-xl mb-5 gap-0.5' },
                            [['profile','Profile'], ['subscription','Sub & Creds'], ['transactions','Payments'], ['logs','Logs']].map(([t,l]) => el('button', { key: t, onClick: () => setTab(t), className: `flex-1 py-2 text-[10px] font-black uppercase rounded-lg transition-all ${tab === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-400'}` }, l))
                        ),
                        el('div', { className: 'flex-1 overflow-y-auto space-y-4 pb-8' }, tab === 'profile' && renderProfile(), tab === 'subscription' && renderSubscription(), tab === 'transactions' && renderTransactions(), tab === 'logs' && renderLogs())
                    )
                )
            );
        };