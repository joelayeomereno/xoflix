            const bulkAction = async (action) => {
                if(action==='delete_user'){ if(!confirmDeleteWithCode('delete these users')) return; } else { if(!window.confirm('Confirm?')) return; }
                await api('users/bulk', 'POST', {ids:selectedIds, action}); setMulti(false); setSelectedIds([]); api('users').then(setList); };

            return el('div', { className: 'flex flex-col h-full' },
                el('div', { className: 'p-5 pb-2 space-y-4' },
                    el('div', {className:'flex justify-between items-center'}, el('h1', { className: 'text-3xl font-black' }, 'Customers'), el('button', {onClick:()=>{setMulti(!multi);setSelectedIds([]);}, className:`text-xs font-bold px-3 py-1 rounded-lg ${multi?'bg-primary-600 text-white':'bg-slate-200'}`}, multi?'Cancel':'Bulk')),
                    multi && selectedIds.length>0 && el('div', {className:'flex gap-2'}, el('button', {onClick:()=>bulkAction('activate_sub'), className:'flex-1 py-2 bg-emerald-500 text-white rounded-lg text-xs font-bold'}, 'Activate'), el('button', {onClick:()=>bulkAction('delete_user'), className:'flex-1 py-2 bg-rose-500 text-white rounded-lg text-xs font-bold'}, 'Delete')),
                    el(Input, { p:'Search Users...', value:search, onChange:setSearch })
                ),
                el('div', { className: 'flex-1 overflow-y-auto p-5 pb-32 space-y-3' },
                    list.map(u => el('div', { key: u.id, onClick:()=>multi ? toggleId(u.id) : setSel(u.id), className: `bg-white p-4 rounded-2xl shadow-sm border ${selectedIds.includes(u.id)?'border-primary-500 ring-1 ring-primary-500':'border-slate-100'} flex items-center gap-4 btn-press` },
                        el('div', { className: 'w-12 h-12 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center font-bold text-lg' }, u.name[0]),
                        el('div', null, el('p', { className: 'font-bold' }, u.name), el('p', { className: 'text-xs text-slate-500' }, u.email))
                    ))
                ),
                el(Sheet, { open: !!sel, onClose: ()=>{setSel(null);setData(null);}, title: data?.profile?.display_name || 'Loading...' },
                    data ? el('div', null,
                        el('div', { className: 'flex p-1 bg-slate-100 rounded-xl mb-6' }, ['profile', 'subs'].map(t => el('button', { key: t, onClick:()=>setTab(t), className: `flex-1 py-2 text-xs font-bold uppercase rounded-lg transition-all ${tab===t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-400'}` }, t))),
                        tab === 'profile' ? el('div', { className: 'space-y-4' },
                            el(Input, { label:'Name', value:pForm.display_name, onChange:v=>setPForm({...pForm, display_name:v}) }),
                            el(Input, { label:'Email', value:pForm.email, onChange:v=>setPForm({...pForm, email:v}) }),
                            el(Input, { label:'Phone', value:pForm.phone, onChange:v=>setPForm({...pForm, phone:v}) }),
                            el(Input, { label:'Password', value:pForm.password||'', onChange:v=>setPForm({...pForm, password:v}), p:'Leave blank to keep' }),
                            el('button', { onClick:saveProfile, className: 'w-full py-4 bg-slate-900 text-white font-bold rounded-2xl shadow-lg mt-4' }, 'Save Profile'),
                            el('a', { href:data.impersonate_url, target:'_blank', className: 'block w-full py-4 bg-primary-50 text-primary-600 font-bold rounded-2xl text-center' }, 'Login As User')
                        ) : el('div', { className: 'space-y-6' },
                            data.subscriptions.map(s => el('div', { key: s.id, className: 'p-4 bg-slate-50 rounded-xl border border-slate-100' }, el('div', { className: 'flex justify-between font-bold mb-1' }, s.plan_name, el('span', { className: 'text-xs uppercase bg-white px-2 py-1 rounded' }, s.status)), el('p', { className: 'text-xs text-slate-500' }, `${s.start_date}  ${s.end_date}`), el('button', { onClick:()=>{setSForm({...s, sub_id:s.id})}, className: 'text-xs font-bold text-primary-600 mt-2' }, 'Edit'))),
                            el('div', { className: 'pt-4 border-t border-slate-100' },
                                el('h4', { className: 'font-bold mb-3' }, sForm.sub_id ? 'Edit Subscription' : 'Add Subscription'),
                                el('div', { className: 'grid grid-cols-2 gap-3 mb-3' }, el(Input, { label:'Start', value:sForm.start_date, onChange:v=>setSForm({...sForm, start_date:v}) }), el(Input, { label:'End', value:sForm.end_date, onChange:v=>setSForm({...sForm, end_date:v}) })),
                                el('div', { className: 'mb-4' }, el('label', { className: 'text-xs font-bold text-slate-400 uppercase' }, 'Status'), el('select', { className: 'w-full p-3 bg-slate-100 rounded-xl text-sm font-bold', value:sForm.status, onChange:e=>setSForm({...sForm, status:e.target.value}) }, ['active','pending','expired'].map(o => el('option', { value:o }, o)))),
                                el('button', { onClick:saveSub, className: 'w-full py-4 bg-emerald-500 text-white font-bold rounded-2xl shadow-lg' }, 'Save Subscription')
                            )
                        )
                    ) : el('div', { className: 'h-64 flex items-center justify-center' }, 'Loading...')
                )
            );
        };


        // --- SUBSCRIPTIONS HUB (Desktop parity: bulk actions + list) ---
        const SubscriptionsHub = () => {
            const [list, setList] = useState([]);
            const [selectedIds, setSelectedIds] = useState([]);
            const [multi, setMulti] = useState(false);
            const [status, setStatus] = useState('all');
            const [search, setSearch] = useState('');
            const [plans, setPlans] = useState([]);
            const [edit, setEdit] = useState(null);

            const load = async () => {
                const qs = `subscriptions?status=${encodeURIComponent(status)}&search=${encodeURIComponent(search)}`;
                const rows = await api(qs);
                setList(rows || []);
            };

            useEffect(() => { api('plans').then(setPlans); load(); }, []);
            useEffect(() => { load(); }, [status]);

            const toggleSelect = (id) => {
                if (!multi) { setEdit(list.find(s => s.id === id) || null); return; }
                setSelectedIds(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
            };

            const bulkAction = async (action) => {
                if (selectedIds.length === 0) return alert('No items selected');
                if (action==='delete'){ if(!confirmDeleteWithCode('delete these subscriptions')) return; } else { if(!window.confirm('Confirm bulk action?')) return; }
                await api('subscriptions/bulk', 'POST', { ids: selectedIds, action });
                setSelectedIds([]);
                setMulti(false);
                load();
            };

            const saveEdit = async () => {
                if (!edit) return;
                await api(`users/${edit.user_id}/subscription`, 'POST', {
                    sub_id: edit.id,
                    plan_id: edit.plan_id,
                    status: edit.status,
                    start_date: edit.start_date,
                    end_date: edit.end_date,
                    connections: edit.connections || 1
                });
                setEdit(null);
                load();
            };

            const StatusBadge = ({ s }) => {
                const cls = s === 'active' ? 'bg-emerald-100 text-emerald-700' : (s === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500');
                return el('span', { className:`px-3 py-1 rounded-full text-[10px] font-black uppercase ${cls}` }, s || '');
            };

            return el('div', { className: 'flex flex-col h-full bg-white' },
                el('div', { className: 'flex items-center p-4 border-b border-slate-100' },
                    el('button', { onClick:()=>window.dispatchEvent(new CustomEvent('nav-back')), className:'p-2' }, el(Icon, {name:'chevronLeft'})),
                    el('h2', { className:'text-lg font-black ml-2' }, 'Subscriptions'),
                    el('div', { className:'flex-1'} ),
                    el('button', { onClick:()=>{setMulti(!multi); setSelectedIds([]);}, className:`px-3 py-2 rounded-xl text-xs font-black ${multi?'bg-slate-900 text-white':'bg-slate-100 text-slate-600'}` }, multi ? 'Done' : 'Select')
                ),
                el('div', { className:'p-4 space-y-3 border-b border-slate-100' },
                    el('div', { className:'flex gap-2' },
                        el('select', { className:'flex-1 p-3 bg-slate-100 rounded-xl text-sm font-bold', value:status, onChange:e=>setStatus(e.target.value) },
                            ['all','active','pending','inactive','expired'].map(s => el('option', { key:s, value:s }, s))
                        ),
                        el('button', { onClick:load, className:'px-4 bg-slate-900 text-white rounded-xl font-black btn-press' }, 'Go')
                    ),
                    el('div', { className:'flex gap-2' },
                        el('input', { className:'flex-1 p-3 bg-slate-100 rounded-xl text-sm font-bold', placeholder:'Search user / plan / sub id', value:search, onChange:e=>setSearch(e.target.value) }),
                        el('button', { onClick:load, className:'px-4 bg-primary-600 text-white rounded-xl font-black btn-press' }, el(Icon,{name:'search',size:18}))
                    ),
                    multi && el('div', { className:'flex gap-2' },
                        el('button', { onClick:()=>bulkAction('activate'), className:'flex-1 py-3 bg-emerald-600 text-white rounded-xl font-black' }, 'Activate'),
                        el('button', { onClick:()=>bulkAction('pending'), className:'flex-1 py-3 bg-amber-500 text-white rounded-xl font-black' }, 'Pending'),
                        el('button', { onClick:()=>bulkAction('delete'), className:'flex-1 py-3 bg-rose-600 text-white rounded-xl font-black' }, 'Delete')
                    )
                ),
                el('div', { className:'flex-1 overflow-y-auto p-4 space-y-3 pb-32' },
                    list.map(s => {
                        const checked = selectedIds.includes(s.id);
                        return el('button', { key:s.id, onClick:()=>toggleSelect(s.id), className:`w-full text-left p-4 border rounded-2xl shadow-sm flex items-center justify-between btn-press ${checked?'border-primary-500 bg-primary-50':'border-slate-100 bg-white'}` },
                            el('div', { className:'space-y-1' },
                                el('div', { className:'flex items-center gap-2' },
                                    el('p', { className:'font-black' }, `#${s.id}  ${s.user_login || s.display_name || 'User'}`),
                                    el(StatusBadge, { s: s.status })
                                ),
                                el('p', { className:'text-xs font-bold text-slate-500' }, `${s.plan_name || ''}  Ends: ${s.end_date || ''}`)
                            ),
                            multi ? el('div', { className:`w-6 h-6 rounded-full border-2 flex items-center justify-center ${checked?'border-primary-600 bg-primary-600 text-white':'border-slate-300'}` }, checked && el(Icon,{name:'check',size:14})) : el(Icon,{name:'edit',size:18,className:'text-slate-300'})
                        );
                    })
                ),
                el(Sheet, { open:!!edit, onClose:()=>setEdit(null), title: edit ? `Edit Subscription #${edit.id}` : '' , actions: edit && el('button',{onClick:saveEdit,className:'w-full py-4 bg-slate-900 text-white font-black rounded-2xl'}, 'Save') },
                    edit && el('div', { className:'space-y-4' },
                        el('div', { className:'grid grid-cols-2 gap-3' },
                            el('div', null, el('label',{className:'text-xs font-bold text-slate-400 uppercase'}, 'Plan'),
                                el('select',{className:'w-full p-3 bg-slate-100 rounded-xl text-sm font-bold', value:edit.plan_id, onChange:e=>setEdit({...edit, plan_id:parseInt(e.target.value)})},
                                    plans.map(p=>el('option',{key:p.id,value:p.id}, `${p.name} ($${p.price})`))
                                )
                            ),
                            el('div', null, el('label',{className:'text-xs font-bold text-slate-400 uppercase'}, 'Status'),
                                el('select',{className:'w-full p-3 bg-slate-100 rounded-xl text-sm font-bold', value:edit.status, onChange:e=>setEdit({...edit, status:e.target.value})},
                                    ['active','pending','inactive','expired'].map(x=>el('option',{key:x,value:x}, x))
                                )
                            )
                        ),
                        el(Input, { label:'Start Date (YYYY-MM-DD HH:MM:SS)', value:edit.start_date||'', onChange:v=>setEdit({...edit,start_date:v}) }),
                        el(Input, { label:'End Date (YYYY-MM-DD HH:MM:SS)', value:edit.end_date||'', onChange:v=>setEdit({...edit,end_date:v}) }),
                        el(Input, { label:'Connections', value:String(edit.connections||1), onChange:v=>setEdit({...edit,connections:parseInt(v||'1')||1}) })
                    )
                )
            );
        };
        const ResourceManager = ({ type, title, fields, endpoint }) => {
            const [list, setList] = useState([]);
            const [form, setForm] = useState({});
            const [sheet, setSheet] = useState(false);
            const [broadcast, setBroadcast] = useState(false);

            useEffect(() => { api(endpoint).then(setList) }, [sheet]);
            const save = async () => { await api(form.id ? `${endpoint}/${form.id}` : `${endpoint}/new`, 'POST', form); setSheet(false); setForm({}); api(endpoint).then(setList); };
            const del = async (id) => { if(!confirmDeleteWithCode('delete')) return; await api(`${endpoint}/${id}/delete`, 'DELETE'); api(endpoint).then(setList); };
            const sendMail = async () => { await api('messages/broadcast', 'POST', form); alert('Sent'); setBroadcast(false); };

            if(broadcast) return el('div', { className:'flex flex-col h-full bg-white'}, el('div', {className:'p-4 border-b'}, el('button',{onClick:()=>setBroadcast(false)}, 'Cancel')), el('div', {className:'p-4 space-y-4'}, el('h2', {className:'text-xl font-bold'}, 'Bulk Email'), el(Input, {label:'Subject', value:form.subject, onChange:v=>setForm({...form,subject:v})}), el('div', null, el('label', {className:'text-xs font-bold text-slate-400'}, 'Body (HTML)'), el('textarea', {className:'w-full p-3 bg-slate-100 rounded-xl', rows:6, value:form.body, onChange:e=>setForm({...form,body:e.target.value})})), el('button', {onClick:sendMail, className:'w-full py-4 bg-primary-600 text-white font-bold rounded-2xl'}, 'Send Broadcast')));

            return el('div', { className: 'flex flex-col h-full bg-white' },
                el('div', { className: 'flex items-center p-4 border-b border-slate-100' }, el('button', { onClick:()=>window.dispatchEvent(new CustomEvent('nav-back')), className:'p-2' }, el(Icon, {name:'chevronLeft'})), el('h2', { className:'text-lg font-black ml-2' }, title), el('div', { className:'flex-1'}), type==='messages' && el('button', { onClick:()=>{setForm({});setBroadcast(true);}, className:'p-2 mr-2 bg-primary-100 text-primary-600 rounded-full' }, el(Icon, {name:'mail',size:18})), el('button', { onClick:()=>{setForm({});setSheet(true);}, className:'p-2 bg-slate-900 text-white rounded-full' }, el(Icon, {name:'plus',size:18}))),
                el('div', { className:'flex-1 overflow-y-auto p-4 space-y-3' }, list.map(i => el('div', { key:i.id, className:'p-4 border border-slate-100 rounded-xl flex justify-between items-center' }, el('div', null, el('p', {className:'font-bold'}, i.name || i.code || i.title), el('p', {className:'text-xs text-slate-500'}, i.price ? '$'+i.price : (i.status||''))), el('div', {className:'flex gap-2'}, el('button', {onClick:()=>{setForm(i);setSheet(true);}, className:'p-2 text-slate-400'}, el(Icon,{name:'edit',size:16})), el('button', {onClick:()=>del(i.id), className:'p-2 text-rose-400'}, el(Icon,{name:'trash',size:16})))))),
                el(Sheet, { open:sheet, onClose:()=>{setSheet(false);}, title: form.id ? 'Edit '+title : 'Add '+title }, el('div', { className:'space-y-4' }, fields.map(f => { if(f.type==='repeater') return el(Repeater, {label:f.label, items:form[f.k]||[], onChange:v=>setForm({...form,[f.k]:v}), fields:f.fields}); if(f.type==='select') return el('div', {key:f.k}, el('label',{className:'text-xs font-bold text-slate-400'}, f.label), el('select', {className:'w-full p-3 bg-slate-100 rounded-xl', value:form[f.k]||'', onChange:e=>setForm({...form,[f.k]:e.target.value})}, f.opts.map(o=>el('option',{value:o},o)))); return el(Input, { key:f.k, label:f.label, value:form[f.k]||'', onChange:v=>setForm({...form,[f.k]:v}), type:f.type }); }), el('button', { onClick:save, className:'w-full py-4 bg-slate-900 text-white font-bold rounded-2xl' }, 'Save')))
            );
        };

        const Menu = ({ onNavigate }) => el('div', { className: 'p-6' },
            el('h1', { className: 'text-3xl font-black mb-6' }, 'Menu'),
            el('div', { className: 'grid grid-cols-2 gap-4' },
                [{id:'subs',l:'Subs',i:'tv',c:'cyan'},{id:'plans',l:'Plans',i:'tag',c:'blue'},{id:'coupons',l:'Coupons',i:'zap',c:'amber'},{id:'methods',l:'Methods',i:'creditCard',c:'emerald'},{id:'sports',l:'Sports',i:'trophy',c:'rose'},{id:'messages',l:'Msg',i:'message',c:'violet'},{id:'settings',l:'System',i:'settings',c:'slate'}].map(i => el('button', { key:i.id, onClick:()=>onNavigate(i.id), className:'p-6 bg-white border border-slate-100 rounded-[20px] shadow-sm flex flex-col items-center justify-center gap-3 btn-press' }, el('div', { className:`w-12 h-12 rounded-full bg-${i.c}-50 text-${i.c}-600 flex items-center justify-center` }, el(Icon, {name:i.i, size:24})), el('span', { className:'font-bold text-slate-900' }, i.l)))
            ),
            el('a', { href: window.TVMA.logoutUrl, className: 'block w-full py-4 bg-rose-50 text-rose-600 font-bold text-center rounded-2xl flex items-center justify-center gap-2 mt-8' }, el(Icon, {name:'logOut',size:18}), 'Log Out')
        );

        const Settings = () => {
            const [conf, setConf] = useState(null); const [tab, setTab] = useState('general');
            useEffect(() => { api('settings').then(setConf) }, []);
            const save = async () => { await api('settings/update', 'POST', conf); alert('Saved'); };

            if(!conf) return el('div', {className:'p-10'}, 'Loading...');

            return el('div', { className: 'p-5 space-y-6 pb-32 h-full overflow-y-auto' },
                el('div', { className: 'flex items-center mb-4' }, el('button', {onClick:()=>window.dispatchEvent(new CustomEvent('nav-back')), className:'p-2 -ml-2'}, el(Icon,{name:'chevronLeft'})), el('h1', { className: 'text-3xl font-black' }, 'System')),
                el('div', { className: 'flex p-1 bg-slate-100 rounded-xl mb-4' }, ['general', 'notif', 'panels', 'pages'].map(t => el('button', { key: t, onClick:()=>setTab(t), className: `flex-1 py-2 text-xs font-bold uppercase rounded-lg transition-all ${tab===t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-400'}` }, t))),
                tab === 'general' && el('div', { className:'space-y-4' }, el(Input, { label:'WhatsApp', value:conf.support.whatsapp, onChange:v=>setConf({...conf, support:{...conf.support, whatsapp:v}}) }), el(Input, { label:'Email', value:conf.support.email, onChange:v=>setConf({...conf, support:{...conf.support, email:v}}) })),
                tab === 'notif' && el('div', { className:'space-y-4' }, el(Repeater, { label:'Templates', items:conf.templates||[], onChange:v=>setConf({...conf, templates:v}), fields:[{k:'subject',l:'Subject'},{k:'body',l:'Body'}] })),
                tab === 'panels' && el('div', { className:'space-y-4' }, el(Repeater, { label:'Xtream Panels', items:conf.panels||[], onChange:v=>setConf({...conf, panels:v}), fields:[{k:'name',l:'Name'},{k:'xtream_url',l:'DNS URL'},{k:'smart_tv_url',l:'M3U URL'}] })),
                tab === 'pages' && el('div', { className:'space-y-4' }, el(Input, { label:'Plans Page ID', value:conf.pages.plans, onChange:v=>setConf({...conf, pages:{...conf.pages, plans:v}}) }), el(Input, { label:'Method Page ID', value:conf.pages.method, onChange:v=>setConf({...conf, pages:{...conf.pages, method:v}}) })),
                el('button', { onClick:save, className: 'w-full py-4 bg-slate-900 text-white font-bold rounded-2xl shadow-lg mt-4' }, 'Save Configuration')
            );
        };

        const Spotlight = ({ open, onClose }) => {
            const [q, setQ] = useState(''); const [res, setRes] = useState([]); const inputRef = useRef(null);
            useEffect(() => { if(open && inputRef.current) setTimeout(()=>inputRef.current.focus(), 100); }, [open]);
            useEffect(() => { if(q.length > 2) { const t = setTimeout(() => api('search?q='+q).then(setRes), 300); return () => clearTimeout(t); } else setRes([]); }, [q]);
            if(!open) return null;
            return el('div', { className: 'fixed inset-0 z-50 flex flex-col bg-slate-50/95 backdrop-blur-xl animate-in' }, el('div', { className: 'pt-safe p-4 flex gap-3 border-b border-slate-200' }, el('div', { className: 'flex-1 bg-white rounded-xl flex items-center px-3 shadow-sm' }, el(Icon, {name:'search',className:'text-slate-400'}), el('input', { ref:inputRef, className:'w-full p-3 outline-none', placeholder:'Search...', value:q, onChange:e=>setQ(e.target.value) })), el('button', { onClick:onClose, className:'font-bold text-slate-500' }, 'Cancel')), el('div', { className: 'flex-1 overflow-y-auto p-4 space-y-2' }, res.map(r => el('div', { key:r.type+r.id, className:'p-4 bg-white rounded-xl shadow-sm flex items-center gap-4' }, el('div', { className:'w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600' }, el(Icon,{name:r.type==='user'?'users':'creditCard'})), el('div', null, el('p',{className:'font-bold'},r.title), el('p',{className:'text-xs text-slate-500'},r.subtitle))))));
        };

        const App = () => {
            const [tab, setTab] = useState('home'); const [subView, setSubView] = useState(null); const [searchOpen, setSearchOpen] = useState(false);
            useEffect(() => { const h = () => setSubView(null); window.addEventListener('nav-back', h); return () => window.removeEventListener('nav-back', h); }, []);
            
            const renderContent = () => {
                if (subView) {
                    if (subView === 'subs') return el(SubscriptionsHub);
                    if (subView === 'plans') return el(ResourceManager, { type:'plans', title:'Plans', endpoint:'plans', fields:[{k:'name',label:'Name'},{k:'price',label:'Price'},{k:'duration_days',label:'Days'},{k:'description',label:'Features',type:'textarea'},{k:'tiers',label:'Discounts',type:'repeater',fields:[{k:'months',l:'Months'},{k:'percent',l:'Percent'}]}] });
                    if (subView === 'coupons') return el(ResourceManager, { type:'coupons', title:'Coupons', endpoint:'coupons', fields:[{k:'code',label:'Code'},{k:'amount',label:'Amount'},{k:'limit',label:'Limit'}] });
                    if (subView === 'methods') return el(ResourceManager, { type:'methods', title:'Methods', endpoint:'methods', fields:[{k:'name',label:'Name'},{k:'link',label:'Link'},{k:'instructions',label:'Instructions',type:'textarea'},{k:'bank_name',label:'Bank Name'},{k:'account_number',label:'Account No'},{k:'fw_key',label:'FW Public Key'}] });
                    if (subView === 'sports') return el(ResourceManager, { type:'sports', title:'Sports', endpoint:'sports', fields:[{k:'title',label:'Title'},{k:'date',label:'Date'},{k:'channel',label:'Channel'}] });
                    if (subView === 'messages') return el(ResourceManager, { type:'messages', title:'Messages', endpoint:'messages', fields:[{k:'title',label:'Title'},{k:'message',label:'Body',type:'textarea'}] });
                    if (subView === 'settings') return el(Settings);
                }
                const views = { home: Dashboard, creditCard: Payments, users: Users, menu: () => el(Menu, { onNavigate: setSubView }) };
                return el(views[tab] || Dashboard);
            };

            const NavBtn = ({ id, icon, label }) => el('button', { onClick: () => { setTab(id); setSubView(null); haptic(); }, className: `flex flex-col items-center gap-1 w-full btn-press ${tab === id ? 'text-primary-600' : 'text-slate-400'}` }, el(Icon, { name: icon, size: 24 }), el('span', { className: 'text-[10px] font-bold' }, label));

            return el('div', { className: 'h-full flex flex-col' },
                el('main', { className: 'flex-1 overflow-y-auto overflow-x-hidden relative' }, renderContent()),
                el('button', { onClick: () => { setSearchOpen(true); haptic(); }, className: 'fixed bottom-24 right-5 w-14 h-14 bg-slate-900 text-white rounded-full shadow-xl flex items-center justify-center btn-press z-20' }, el(Icon, { name: 'search', size: 24 })),
                el('div', { className: 'glass pb-safe pt-2 fixed bottom-0 w-full z-30' }, el('div', { className: 'flex justify-around items-center h-16' }, ['home','creditCard','users','menu'].map(t => el(NavBtn, { key:t, id:t, icon:t==='menu'?'menu':(t==='creditCard'?'creditCard':t), label:t==='creditCard'?'Pay':t.charAt(0).toUpperCase()+t.slice(1) })))),
                el(Spotlight, { open: searchOpen, onClose: () => setSearchOpen(false) })
            );
        };

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(el(ErrorBoundary, null, el(App)));
    }
  </script>
</body>
</html>
