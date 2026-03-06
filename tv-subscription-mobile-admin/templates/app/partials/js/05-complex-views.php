// ================================================================
        // PAY PAGE (NUCLEAR EDITION) -- Full A-Z Parity & Icon Fix
        // File: tv-subscription-mobile-admin/templates/app/partials/js/05-complex-views.php
        // ================================================================
        const Payments = () => {
            const [list,        setList]        = useState([]);
            const [page,        setPage]        = useState(1);
            const [meta,        setMeta]        = useState({total:0,pages:1});
            const [filter,      setFilter]      = useState('all');
            const [search,      setSearch]      = useState('');
            const [datePreset,  setDatePreset]  = useState('all');
            const [dateFrom,    setDateFrom]    = useState('');
            const [dateTo,      setDateTo]      = useState('');
            const [showCustom,  setShowCustom]  = useState(false);
            const [sheet,       setSheet]       = useState(null);
            const [wizMode,     setWizMode]     = useState(false);
            const [rejectMode,  setRejectMode]  = useState(false);
            const [creds,       setCreds]       = useState({ user:'', pass:'', m3u:'', url:'' });
            const [rejReason,   setRejReason]   = useState('unclear_proof');
            const [loading,     setLoading]     = useState(false);
            const [bulkMode,    setBulkMode]    = useState(false);
            const [selectedIds, setSelectedIds] = useState([]);
            const [panels,      setPanels]      = useState([]);
            const [panelMode,   setPanelMode]   = useState('override');

            // ---- Local Robust Icon Component (Nuclear Fix for Broken SVGs) ----
            const InternalIcon = ({ name, size=20, color, className="", style:st }) => {
                const lib = {
                    search: "M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.35-4.35",
                    check: "M20 6 9 17 4 12",
                    x: "M18 6 6 18 M6 6 18 18",
                    creditCard: "M1 4h22v16H1z M1 10h22",
                    edit: "M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z",
                    key: "M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4",
                    chevronLeft: "M15 18l-6-6 6-6",
                    chevronDown: "M6 9l6 6 6-6",
                    chevronUp: "M18 15l-6-6-6 6",
                    dollar: "M12 1v22 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                };
                const d = lib[name] || "M12 2v20 M2 12h20"; 
                return el('svg', { 
                    xmlns: "http://www.w3.org/2000/svg", width: size, height: size, 
                    viewBox: "0 0 24 24", fill: "none", stroke: color || "currentColor", 
                    strokeWidth: "2.5", strokeLinecap: "round", strokeLinejoin: "round", 
                    className, style: st,
                    dangerouslySetInnerHTML: { __html: d.split(' M').map((s,i) => `<path d="${i===0?s:'M'+s}" />`).join('') }
                });
            };

            // ---- Absolute Decoder (Nuclear Fix for Naira Symbol &#8358;) ----
            const decode = (str) => {
                if (!str) return "";
                let res = String(str);
                res = res.replace(/&#8358;/g, '\u20A6').replace(/&amp;#8358;/g, '\u20A6');
                try {
                    const txt = document.createElement("textarea");
                    for(let i=0; i<3; i++) {
                        txt.innerHTML = res;
                        if (txt.value === res) break;
                        res = txt.value;
                    }
                } catch(e) {}
                return res;
            };

            const todayStr  = () => new Date().toISOString().slice(0,10);
            const daysAgo   = n => { const d=new Date(); d.setDate(d.getDate()-n); return d.toISOString().slice(0,10); };

            const applyPreset = preset => {
                setDatePreset(preset); setShowCustom(false); setPage(1);
                if (preset==='today') { setDateFrom(todayStr()); setDateTo(todayStr()); }
                else if (preset==='week')  { setDateFrom(daysAgo(6));  setDateTo(todayStr()); }
                else if (preset==='month') { setDateFrom(daysAgo(29)); setDateTo(todayStr()); }
                else if (preset==='custom') { setShowCustom(true); }
                else { setDateFrom(''); setDateTo(''); }
            };

            const load = async () => {
                setLoading(true);
                try { 
                    const q = `payments?status=${filter}&page=${page}${search ? '&search='+encodeURIComponent(search) : ''}${dateFrom ? '&date_from='+dateFrom : ''}${dateTo ? '&date_to='+dateTo : ''}`;
                    const res = await api(q);
                    setList(res.data || []);
                    setMeta({ total: res.total, pages: res.pages });
                } finally { 
                    setLoading(false); 
                }
            };

            useEffect(() => { load(); }, [filter, dateFrom, dateTo, page]);
            useEffect(() => { api('settings').then(s => setPanels(s?.panels || [])); }, []);

            const doApprove = async () => {
                if (!confirmApprove()) return;
                await api(`payments/${sheet.id}/action`, 'POST', { action:'approve', creds:null });
                showToast('Payment Approved'); setSheet(null); load();
            };
            const doFulfill = async () => {
                await api(`payments/${sheet.id}/action`, 'POST', { action:'fulfill', creds });
                showToast('Subscription Activated'); setSheet(null); setWizMode(false); setCreds({user:'',pass:'',m3u:'',url:''}); load();
            };
            const doReject = async () => {
                await api(`payments/${sheet.id}/action`, 'POST', { action:'reject', reason_key: rejReason });
                showToast('Payment Rejected'); setSheet(null); setRejectMode(false); load();
            };
            const parseM3u = () => {
                const l=creds.m3u, u=l.match(/username=([^&]+)/), p=l.match(/password=([^&]+)/);
                if(u&&p) setCreds({...creds,user:u[1],pass:p[1]});
                try{setCreds(prev=>({...prev,url:new URL(l).origin}))}catch(e){}
            };

            const applyPanel = (pid) => {
                const p = panels.find(x => x.id === pid);
                if(!p) return;
                if(panelMode === 'override') {
                    setCreds(prev => ({...prev, url: p.xtream_url, m3u: p.smart_tv_url }));
                } else {
                    setCreds(prev => ({...prev, m3u: prev.m3u + `\n\n[Panel: ${p.smart_tv_url}]` }));
                }
            };

            const toggleSelect = id =>
                setSelectedIds(prev => prev.includes(id) ? prev.filter(x=>x!==id) : [...prev,id]);

            const bulkAction = async action => {
                if (!selectedIds.length) return alert('No items selected.');
                if (!window.confirm(`Confirm bulk ${action} on ${selectedIds.length} payment(s)?`)) return;
                await api('payments/bulk','POST',{ids:selectedIds, action});
                showToast(`${selectedIds.length} processed`);
                setBulkMode(false); setSelectedIds([]); load();
            };

            const StatusBadge = ({s}) => {
                const map = {
                    'APPROVED':'bg-emerald-100 text-emerald-700',
                    'completed':'bg-emerald-100 text-emerald-700',
                    'REJECTED':'bg-rose-100 text-rose-600',
                    'PENDING_ADMIN_REVIEW':'bg-amber-100 text-amber-700',
                    'AWAITING_PROOF':'bg-sky-100 text-sky-700',
                    'IN_PROGRESS':'bg-violet-100 text-violet-700',
                    'pending':'bg-amber-100 text-amber-700',
                };
                const label = { 'PENDING_ADMIN_REVIEW':'Review', 'AWAITING_PROOF':'Proof Needed', 'IN_PROGRESS':'In Progress' }[s] || s;
                return el('span',{className:`px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wide ${map[s]||'bg-slate-100 text-slate-500'}`},label);
            };

            const DR = ({label,value,mono}) => (value!=null&&value!=='')
                ? el('div',{className:'flex justify-between items-start py-3 border-b border-slate-50 last:border-0'},
                    el('span',{className:'text-[11px] font-bold text-slate-400 uppercase tracking-widest flex-shrink-0'},label),
                    el('span',{
                        className:`text-xs font-black text-slate-900 text-right ml-4 break-all ${mono?'font-mono':''}`,
                        dangerouslySetInnerHTML: { __html: decode(String(value)) }
                    }))
                : null;

            const sym  = p => decode(p?.currency_symbol || '$');
            const fmtN = (n,s) => n!=null ? `${decode(s||'')}${Number(n).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}` : null;

            return el('div',{className:'flex flex-col h-full bg-white'},
                el('div',{className:'px-5 pt-5 pb-0 bg-white border-b border-slate-100'},
                    el('div',{className:'flex items-center justify-between mb-4'},
                        el('h1',{className:'text-3xl font-black'},'Payments'),
                        el('button',{
                            onClick:()=>{setBulkMode(!bulkMode);setSelectedIds([]);haptic();},
                            className:`px-3.5 py-2 rounded-xl text-xs font-black transition-all ${bulkMode?'bg-slate-900 text-white':'bg-slate-100 text-slate-600'}`
                        }, bulkMode?'Cancel':'Select')
                    ),
                    bulkMode && selectedIds.length>0 && el('div',{className:'flex gap-2 mb-3'},
                        el('button',{onClick:()=>bulkAction('approve'),className:'flex-1 py-3.5 bg-emerald-500 text-white text-xs font-black rounded-xl'},`Approve (${selectedIds.length})`),
                        el('button',{onClick:()=>bulkAction('reject'), className:'flex-1 py-3.5 bg-rose-500   text-white text-xs font-black rounded-xl'},`Reject (${selectedIds.length})`)
                    ),
                    el('div',{className:'flex gap-2 mb-3 overflow-x-auto pb-1 no-scrollbar'},
                        ['all','pending','completed'].map(f=>el('button',{
                            key:f,onClick:()=>{setFilter(f);setPage(1);haptic();},
                            className:`flex-shrink-0 px-4 py-2 rounded-full text-xs font-black capitalize ${filter===f?'bg-slate-900 text-white':'bg-slate-100 text-slate-500'}`
                        },f))
                    ),
                    el('div',{className:'flex gap-2 mb-3'},
                        el('div',{className:'flex-1 flex items-center bg-slate-100 rounded-xl px-3.5 gap-2'},
                            el(InternalIcon,{name:'search',size:16,className:'text-slate-400 flex-shrink-0'}),
                            el('input',{
                                className:'w-full py-3 bg-transparent text-sm font-bold outline-none placeholder:text-slate-400',
                                placeholder:'Search customers or invoices...',
                                value:search, onChange:e=>{setSearch(e.target.value); setPage(1);},
                                onKeyDown:e=>e.key==='Enter'&&load()
                            })
                        ),
                        el('button',{onClick:load,className:'px-4.5 bg-slate-900 text-white rounded-xl font-black btn-press'},
                            el(InternalIcon,{name:'search',size:18}))
                    ),
                    el('div',{className:'flex gap-2 mb-4 overflow-x-auto pb-1 no-scrollbar'},
                        [['all','All Dates'],['today','Today'],['week','Week'],['month','Month'],['custom','Custom']].map(([p,l])=>
                            el('button',{key:p,onClick:()=>applyPreset(p),
                                className:`flex-shrink-0 px-3 py-1.5 rounded-full text-[10px] font-black uppercase ${datePreset===p?'bg-primary-600 text-white':'bg-slate-100 text-slate-500'}`
                            },l))
                    ),
                    showCustom && el('div',{className:'flex gap-2 mb-4 items-center animate-in'},
                        el('input',{type:'date',value:dateFrom,onChange:e=>{setDateFrom(e.target.value); setPage(1);},
                            className:'flex-1 p-3 bg-slate-100 rounded-xl text-sm font-bold border-none'}),
                        el('span',{className:'text-slate-400 font-bold'},'to'),
                        el('input',{type:'date',value:dateTo,onChange:e=>{setDateTo(e.target.value); setPage(1);},
                            className:'flex-1 p-3 bg-slate-100 rounded-xl text-sm font-bold border-none'})
                    )
                ),
                el('div',{className:'flex-1 overflow-y-auto p-5 pb-32 space-y-3 bg-slate-50'},
                    loading && [1,2,3].map(i=>el('div',{key:i,className:'h-24 bg-slate-200 rounded-2xl animate-pulse'})),
                    !loading && list.length===0 && el(Empty, { text:'No transactions found', icon:'creditCard' }),
                    !loading && list.map(p => {
                        const checked = selectedIds.includes(p.id);
                        return el('div',{
                            key:p.id,
                            onClick:()=>bulkMode?(toggleSelect(p.id),haptic()):(setSheet(p),setWizMode(false),setRejectMode(false),haptic()),
                            className:`bg-white p-4 rounded-2xl shadow-sm border transition-all btn-press cursor-pointer ${checked?'border-primary-500 ring-1 ring-primary-500':'border-slate-100'}`
                        },
                            el('div',{className:'flex justify-between items-start'},
                                el('div',{className:'flex-1 min-w-0 pr-3'},
                                    el('p',{className:'font-black text-slate-900 truncate'},p.display_name||p.user_login||`User #${p.user_id}`),
                                    el('p',{className:'text-[11px] font-bold text-slate-400 mt-1 truncate'},`#INV-${String(p.id).padStart(5,'0')} · ${p.plan_name||' '}`)
                                ),
                                el('div',{className:'text-right flex-shrink-0'},
                                    el('p',{
                                        className:'font-black text-[17px] text-slate-900',
                                        dangerouslySetInnerHTML: { __html: decode(p.amount_display) || `$${p.amount}` }
                                    }),
                                    el('p',{className:'text-[10px] font-bold text-slate-400 mt-1 uppercase'},p.time_ago||'')
                                )
                            ),
                            el('div',{className:'flex items-center justify-between mt-3'},
                                el('div',{className:'flex items-center gap-2'},
                                    el(StatusBadge,{s:p.status}),
                                    p.is_renewal ? el('span',{className:'pill pill-green text-[8px]'},'Renewal') : el('span',{className:'pill pill-slate text-[8px]'},'New')
                                ),
                                bulkMode && el('div',{className:`w-5.5 h-5.5 rounded-full border-2 flex items-center justify-center flex-shrink-0 ${checked?'border-primary-600 bg-primary-600 text-white' : 'border-slate-300'}`},
                                    checked && el(InternalIcon,{name:'check',size:12}))
                            )
                        );
                    }),
                    !loading && meta.pages > 1 && el('div', {className:'flex justify-center items-center gap-6 py-4'},
                        el('button', {onClick:()=>{setPage(p=>Math.max(1,p-1)); haptic();}, disabled:page===1, className:'btn btn-ghost btn-sm press'}, 'Prev'),
                        el('span', {className:'text-xs font-black text-slate-400 uppercase tracking-widest'}, `Page ${page} / ${meta.pages}`),
                        el('button', {onClick:()=>{setPage(p=>Math.min(meta.pages,p+1)); haptic();}, disabled:page===meta.pages, className:'btn btn-ghost btn-sm press'}, 'Next')
                    )
                ),
                el(Sheet,{
                    open:!!sheet,
                    onClose:()=>{setSheet(null);setWizMode(false);setRejectMode(false);setCreds({user:'',pass:'',m3u:'',url:''}); },
                    title: wizMode?'Fulfillment Wizard':(rejectMode?'Rejection Menu':(sheet?`Invoice #INV-${String(sheet.id).padStart(5,'0')}`:''))
                },
                    sheet && (wizMode
                        ? el('div',{className:'space-y-4 pb-10'},
                            sheet.credential_user && el('div',{className:'p-3 bg-emerald-50 text-emerald-700 text-[11px] rounded-xl font-black border border-emerald-100 flex items-center gap-2'},
                                el(InternalIcon,{name:'check',size:14}), 'Credentials Detected (Automatic Renewal)'),
                            el('div',{className:'p-3 bg-primary-50 text-primary-700 text-[11px] rounded-xl font-black border border-primary-100'},
                                'Paste M3U URL or select a panel below.'),
                            el('div',{className:'bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-4'},
                                el('div',null, el('label',{className:'lbl'},'IPTV Panel Selector'),
                                    el('div',{className:'flex p-1 bg-slate-200 rounded-lg mb-2.5'}, 
                                        ['override','attach'].map(m => el('button',{key:m, onClick:()=>setPanelMode(m), className:`flex-1 py-1.5 text-[9px] font-black uppercase rounded ${panelMode===m?'bg-white shadow-sm text-slate-900':'text-slate-500'}`},m))
                                    ),
                                    el('select',{className:'field', onChange:e=>applyPanel(e.target.value)}, 
                                        el('option',{value:''},'-- Manual / Custom Entry --'),
                                        panels.map(p => el('option',{key:p.id,value:p.id},p.name)))
                                ),
                                el('textarea',{
                                    className:'w-full p-3 bg-white border border-slate-200 rounded-xl text-xs font-mono',
                                    placeholder:'Paste full Xtream M3U link here...',
                                    rows:3, value:creds.m3u, onChange:e=>setCreds({...creds,m3u:e.target.value})
                                }),
                                el('button',{onClick:parseM3u,className:'text-[11px] font-black text-primary-600'},'Auto-Parse M3U Params')
                            ),
                            el('div',{className:'grid grid-cols-2 gap-3'},
                                el(Input,{label:'Username',value:creds.user||sheet.credential_user,onChange:v=>setCreds({...creds,user:v})}),
                                el(Input,{label:'Password',value:creds.pass||sheet.credential_pass,onChange:v=>setCreds({...creds,pass:v})})
                            ),
                            el(Input,{label:'DNS / Portal URL',value:creds.url||sheet.credential_url,onChange:v=>setCreds({...creds,url:v})}),
                            el('button',{onClick:doFulfill,className:'w-full py-4.5 bg-emerald-500 text-white font-black rounded-2xl shadow-lg shadow-emerald-200'},
                                'Complete Fulfillment')
                          )
                        : rejectMode
                        ? el('div',{className:'space-y-4 pb-8'},
                            el(Field,{type:'select',label:'Rejection Reason',value:rejReason,onChange:setRejReason, options:[
                                {value:'unclear_proof',label:'Proof image is unreadable'},
                                {value:'wrong_amount', label:'Amount does not match total'},
                                {value:'wrong_account',label:'Sent to wrong account'},
                                {value:'duplicate_or_used',label:'Proof used previously'},
                                {value:'invalid_reference',label:'Missing transaction ID'}
                            ]}),
                            el('button',{onClick:doReject, className:'w-full py-4 bg-rose-600 text-white font-black rounded-2xl shadow-xl'}, 'Confirm Rejection')
                        )
                        : el('div',{className:'space-y-3 pb-8'},
                            el('div',{className:'flex items-center justify-between p-4 bg-slate-50 rounded-2xl'},
                                el('span',{className:'text-xs font-black text-slate-400 uppercase tracking-widest'},'Status'),
                                el(StatusBadge,{s:sheet.status})
                            ),
                            sheet.proofs&&sheet.proofs.length>0&&el('div',null,
                                el('p',{className:'text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2.5'},'Verification Proof'),
                                el('div',{className:'grid grid-cols-2 gap-2.5'},
                                    sheet.proofs.map((u,i)=>el('a',{key:i,href:u,target:'_blank',
                                        className:'block aspect-video bg-slate-100 rounded-xl border border-slate-200 bg-cover',
                                        style:{backgroundImage:`url(${u})`}}))
                                )
                            ),
                            el('div',{className:'bg-slate-50 rounded-2xl p-4 space-y-0.5'},
                                el(DR,{label:'Customer',  value:sheet.display_name||sheet.user_login}),
                                el(DR,{label:'Email',     value:sheet.user_email}),
                                el(DR,{label:'Phone',     value:sheet.user_phone}),
                                el(DR,{label:'Plan',      value:sheet.plan_name}),
                                el(DR,{label:'Method',    value:sheet.method}),
                                el(DR,{label:'Ref ID',    value:sheet.transaction_id, mono:true}),
                                el(DR,{label:'Timestamp', value:sheet.date})
                            ),
                            el('div',{className:'bg-slate-50 rounded-2xl p-4 space-y-0.5'},
                                el(DR,{label:'Currency',  value:sheet.currency}),
                                el(DR,{label:'Net Local',  value:decode(sheet.amount_display)||fmtN(sheet.amount,sym(sheet))}),
                                sheet.locked_usd!=null&&el(DR,{label:'Net USD', value:fmtN(sheet.locked_usd,'$')})
                            ),
                            (sheet.status!=='APPROVED'&&sheet.status!=='completed'&&sheet.status!=='REJECTED')&&
                                el('div',{className:'space-y-3 pt-4'},
                                    el('div',{className:'grid grid-cols-2 gap-3'},
                                        el('button',{onClick:()=>setWizMode(true),
                                            className:'py-4 bg-slate-900 text-white font-black rounded-2xl shadow-xl btn-press flex items-center justify-center gap-2 text-sm'},
                                            el(InternalIcon,{name:'key',size:16}), 'Fulfill'),
                                        el('button',{onClick:doApprove,
                                            className:'py-4 bg-emerald-500 text-white font-black rounded-2xl shadow-lg shadow-emerald-100 btn-press flex items-center justify-center gap-2 text-sm'},
                                            el(InternalIcon,{name:'check',size:18}), 'Approve')
                                    ),
                                    el('button',{onClick:()=>setRejectMode(true),
                                        className:'w-full py-4 bg-white border-2 border-rose-100 text-rose-500 font-black rounded-2xl btn-press flex items-center justify-center gap-2 text-sm'},
                                        el(InternalIcon,{name:'x',size:16}), 'Reject Transaction')
                                )
                        )
                    )
                )
            );
        };