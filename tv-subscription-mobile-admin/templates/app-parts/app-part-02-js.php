<script>
    window.TVMA = {
      api: <?php echo wp_json_encode(rest_url('tv-admin/v2')); ?>,
      nonce: <?php echo wp_json_encode($rest_nonce); ?>,
      user: <?php echo wp_json_encode($display_name); ?>,
      logoutUrl: <?php echo wp_json_encode(home_url('/admin/logout')); ?>
    };

    // Defer Execution until React loads
    document.addEventListener('DOMContentLoaded', () => {
        const interval = setInterval(() => {
            if (window.React && window.ReactDOM) {
                clearInterval(interval);
                initApp();
            }
        }, 50);
    });

    function initApp() {
        const { useState, useEffect, useRef, createElement: el, Component } = React;

        // --- 1. ERROR BOUNDARY ---
        class ErrorBoundary extends Component {
            constructor(props) { super(props); this.state = { hasError: false, error: null }; }
            static getDerivedStateFromError(error) { return { hasError: true, error }; }
            render() {
                if (this.state.hasError) {
                    return el('div', {className: 'flex flex-col items-center justify-center h-full p-10 text-center'}, 
                        el('div', {className: 'w-16 h-16 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center mb-4 text-2xl font-bold'}, '!'),
                        el('h2', {className:'text-xl font-bold text-slate-900'}, 'App Crashed'),
                        el('p', {className:'text-sm text-slate-500 mt-2 mb-6'}, this.state.error.toString()),
                        el('button', {onClick:()=>window.location.reload(), className:'px-6 py-3 bg-slate-900 text-white rounded-xl font-bold'}, 'Reload')
                    );
                }
                return this.props.children;
            }
        }

        // --- 2. ICON LIBRARY (Inline SVG) ---
        const Icon = ({ name, size=20, className="" }) => {
            const paths = {
                home: "M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z",
                users: "M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M16 3.13a4 4 0 0 1 0 7.75 M23 21v-2a4 4 0 0 0-3-3.87",
                creditCard: "M1 4h22v16H1z M1 10h22",
                settings: "M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.47a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z",
                search: "M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.35-4.35",
                check: "M20 6 9 17 4 12",
                x: "M18 6 6 18 M6 6 18 18",
                activity: "M22 12h-4l-3 9L9 3l-3 9H2",
                logOut: "M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17 21 12 16 7 M21 12H9",
                zap: "M13 2 3 14h9l-1 8 10-12h-9l1-8z",
                trendingUp: "M23 6 13.5 15.5 8.5 10.5 1 18 M17 6h6v6",
                server: "M2 2h20v8H2z M2 14h20v8H2z M6 6h.01 M6 18h.01",
                key: "M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4",
                edit: "M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z",
                save: "M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8",
                menu: "M3 12h18 M3 6h18 M3 18h18",
                plus: "M12 5v14 M5 12h14",
                trash: "M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2",
                tag: "M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z M7 7h.01",
                tv: "M2 7h20v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7 M17 2l-5 5-5-5",
                message: "M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z",
                chevronLeft: "M15 18l-6-6 6-6",
                trophy: "M6 9H4.5a2.5 2.5 0 0 1 0-5H6 M18 9h1.5a2.5 2.5 0 0 0 0-5H18 M4 22h16 M2 12h20 M12 2a5 5 0 0 0-5 5v2h10V7a5 5 0 0 0-5-5z",
                mail: "M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z M22 6l-10 7L2 6"
            };
            const d = paths[name] || paths.home;
            const html = d.startsWith('<') ? d : `<path d="${d}" />`;

            return el('svg', { 
                xmlns: "http://www.w3.org/2000/svg", 
                width: size, height: size, 
                viewBox: "0 0 24 24", 
                fill: "none", stroke: "currentColor", 
                strokeWidth: "2", strokeLinecap: "round", strokeLinejoin: "round", 
                className, 
                dangerouslySetInnerHTML: { __html: html }
            });
        };

        // --- 3. UTILITIES & API ---
        const api = async (ep, m='GET', b=null) => {
            try {
                const res = await fetch(window.TVMA.api + '/' + ep, {
                    method: m, headers: { 'X-WP-Nonce': window.TVMA.nonce, 'Content-Type': 'application/json' },
                    body: b ? JSON.stringify(b) : null
                });
                const j = await res.json();
                if(!res.ok) throw new Error(j.message || 'Server Error');
                return j;
            } catch(e) { 
                alert(e.message); 
                throw e; 
            }
        };

        /**
         * FIX (Fix 3): decodeHtml utility — converts HTML entities to real Unicode
         * characters. Handles both named entities (&euro;) and numeric entities
         * (&#8358;, &#x20A6;). Uses textarea trick for browser-based decoding with
         * direct substitution fallback for the most common currency entities.
         */
        const decodeHtml = (str) => {
            if (!str || typeof str !== 'string') return str;
            let res = str;
            // Direct swap for known currency entities (fast path, no DOM needed)
            res = res
                .replace(/&#8358;/g,  '\u20A6')  // ? Naira
                .replace(/&#8373;/g,  '\u20B5')  // ? Cedi
                .replace(/&#8377;/g,  '\u20B9')  // ? Rupee
                .replace(/&#8362;/g,  '\u20AA')  // ? Shekel
                .replace(/&#8369;/g,  '\u20B1')  // ? Peso
                .replace(/&#8363;/g,  '\u20AB')  // ? Dong
                .replace(/&#8361;/g,  '\u20A9')  // ? Won
                .replace(/&#8378;/g,  '\u20BA')  // ? Lira
                .replace(/&#3647;/g,  '\u0E3F')  // ? Baht
                .replace(/&euro;/g,   '\u20AC')  // €
                .replace(/&pound;/g,  '\u00A3')  // £
                .replace(/&yen;/g,    '\u00A5')  // ¥
                .replace(/&amp;#(\d+);/g, (_, n) => String.fromCodePoint(parseInt(n, 10)));
            // Generic numeric entity fallback
            res = res.replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(parseInt(n, 10)));
            res = res.replace(/&#x([0-9a-fA-F]+);/g, (_, h) => String.fromCodePoint(parseInt(h, 16)));
            return res;
        };

        // Sensitive action guards
        const verify4DigitCode = (label) => {
            const code = String(Math.floor(1000 + Math.random()*9000));
            const entered = window.prompt(`Type ${code} to confirm ${label}:`, '');
            return String(entered||'').trim() === code;
        };
        const confirmApprove = () => window.confirm('Are you sure you want to approve this transaction?');
        const confirmReject = () => window.confirm('Are you sure you want to reject this transaction?');
        const confirmDeleteWithCode = (label='delete') => {
            if(!window.confirm(`Are you sure you want to ${label}?`)) return false;
            return verify4DigitCode(label);
        };

        const haptic = () => { if(navigator.vibrate) navigator.vibrate(10); };

        // --- 4. SHARED UI COMPONENTS ---
        const Input = ({ label, value, onChange, type="text", p="", className="" }) => el('div', { className: 'space-y-1 ' + className },
            el('label', { className: 'text-xs font-bold text-slate-400 uppercase tracking-wider' }, label),
            el(type==='textarea'?'textarea':'input', { 
                type, value, onChange: e=>onChange(e.target.value), placeholder:p, 
                className: 'w-full p-3 bg-slate-100 border-none rounded-xl text-sm font-bold text-slate-900 transition-all focus:bg-white focus:ring-2 focus:ring-primary-500/20' 
            })
        );

        const Sheet = ({ open, onClose, children, title, actions }) => {
            if(!open) return null;
            return el('div', { className: 'fixed inset-0 z-50 flex items-end justify-center' },
                el('div', { className: 'absolute inset-0 sheet-backdrop animate-in', onClick: onClose }),
                el('div', { className: 'bg-white w-full rounded-t-3xl shadow-2xl relative z-10 animate-slide-up max-h-[92vh] flex flex-col' },
                    el('div', { className: 'w-12 h-1.5 bg-slate-200 rounded-full mx-auto mt-3 mb-2 shrink-0' }),
                    title && el('div', { className: 'px-6 pb-4 border-b border-slate-100 shrink-0 flex justify-between items-center' }, 
                        el('h3', { className: 'text-lg font-black' }, title),
                        el('button', { onClick: onClose, className: 'p-2 bg-slate-50 rounded-full btn-press' }, el(Icon, {name:'x', size:18}))
                    ),
                    el('div', { className: 'overflow-y-auto p-6 space-y-4 pb-safe' }, children),
                    actions && el('div', { className: 'p-4 border-t border-slate-100 pb-safe shrink-0' }, actions)
                )
            );
        };

        const Repeater = ({ label, items, onChange, fields }) => {
            const add = () => onChange([...items, fields.reduce((acc,f)=>({...acc,[f.k]:''}), {})]);
            const update = (idx, k, v) => { const n = [...items]; n[idx][k] = v; onChange(n); };
            const remove = (idx) => onChange(items.filter((_,i) => i!==idx));

            return el('div', { className: 'space-y-2 border border-slate-100 p-3 rounded-xl' },
                el('div', { className: 'flex justify-between items-center' },
                    el('label', { className: 'text-xs font-bold text-slate-400 uppercase' }, label),
                    el('button', { onClick: add, className: 'text-primary-600 text-xs font-bold' }, '+ Add Item')
                ),
                items.map((item, idx) => el('div', { key: idx, className: 'p-3 bg-slate-50 rounded-lg relative space-y-2' },
                    el('button', { onClick:()=>remove(idx), className: 'absolute top-2 right-2 text-rose-400' }, el(Icon, {name:'x', size:14})),
                    fields.map(f => el(Input, { key:f.k, label:f.l, value:item[f.k], onChange:v=>update(idx,f.k,v) }))
                ))
            );
        };

        // --- 5. VIEWS ---

        const Dashboard = () => {
            const [data, setData] = useState(null);
            useEffect(() => { api('dashboard').then(setData) }, []);

            if(!data) return el('div', { className: 'p-6 space-y-4' }, [1,2,3].map(i => el('div', { key: i, className: 'h-32 bg-slate-200 rounded-2xl animate-pulse' })));

            const Stat = ({t,v,s,i,c}) => el('div', {className:'bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between h-32 relative overflow-hidden'},
                el('div', {className:'flex justify-between items-start z-10'}, el('span',{className:'text-xs font-bold text-slate-400 uppercase'},t), el(Icon,{name:i,size:18,className:`text-${c}-500`})),
                el('div', {className:'z-10'},
                    // FIX (Fix 3): Revenue stat uses dangerouslySetInnerHTML + decodeHtml to render
                    // multi-currency values like "?8,084\n€120" correctly. Plain text would show
                    // raw HTML entities from the JSON response.
                    el('span', {
                        className:'text-3xl font-black text-slate-900',
                        dangerouslySetInnerHTML: { __html: decodeHtml(String(v)) }
                    }),
                    s&&el('div',{className:`text-xs font-bold mt-1 text-${s.includes('+')?'emerald':'slate'}-500`},s)
                ),
                el('div', {className:`absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-${c}-50`})
            );

            return el('div', { className: 'p-5 space-y-6 pb-32' },
                el('div', { className: 'flex justify-between items-center' },
                    el('div', null, el('h1', { className: 'text-2xl font-black text-slate-900' }, 'Good Morning,'), el('p', { className: 'text-slate-500 font-medium' }, window.TVMA.user)),
                    el('div', { className: 'w-10 h-10 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-bold' }, window.TVMA.user[0])
                ),
                el('div', { className: 'grid grid-cols-2 gap-4' },
                    el(Stat, {t:'Revenue',v:data.stats.revenue.value,s:data.stats.revenue.trend+'%',i:'trendingUp',c:'emerald'}),
                    el(Stat, {t:'Active',v:data.stats.active_subs,i:'users',c:'blue'}),
                    el(Stat, {t:'Pending',v:data.stats.pending_tasks,i:'activity',c:'amber'}),
                    el(Stat, {t:'Users',v:data.stats.users,i:'users',c:'violet'})
                ),
                el('div', null, el('h3', { className: 'font-bold text-lg mb-3' }, 'Recent Activity'),
                    el('div', { className: 'space-y-3' }, data.recent_activity.map(l => el('div', { key: l.id, className: 'flex gap-4 p-4 bg-white rounded-2xl border border-slate-100 items-center' },
                        el('div', { className: 'w-2 h-2 rounded-full bg-blue-500' }),
                        el('div', null, el('p', { className: 'font-bold text-sm' }, l.action), el('p', { className: 'text-xs text-slate-500' }, l.details))
                    )))
                ),
                data.csv_url && el('a', { href: data.csv_url, className:'block w-full py-4 bg-slate-900 text-white font-bold rounded-2xl text-center shadow-lg' }, 'Export Finance CSV')
            );
        };

        const Payments = () => {
            const [list, setList] = useState([]);
            const [filter, setFilter] = useState('all');
            const [sheet, setSheet] = useState(null);
            const [wizMode, setWizMode] = useState(false);
            const [creds, setCreds] = useState({ user:'', pass:'', m3u:'', url:'' });

            useEffect(() => { api(`payments?status=${filter}`).then(setList) }, [filter]);

            const approve = async (withCreds=false) => {
                if(!window.confirm('Are you sure you want to approve this transaction?')) return;
                await api(`payments/${sheet.id}/action`, 'POST', { action:'approve', creds:withCreds?creds:null });
                setSheet(null); setWizMode(false); api(`payments?status=${filter}`).then(setList);
            };
            const reject = async () => {
                if(!window.confirm('Are you sure you want to reject this transaction?')) return;
                await api(`payments/${sheet.id}/action`, 'POST', { action:'reject' });
                setSheet(null); api(`payments?status=${filter}`).then(setList);
            };
            const parse = () => {
                const l=creds.m3u, u=l.match(/username=([^&]+)/), p=l.match(/password=([^&]+)/);
                if(u&&p) setCreds({...creds,user:u[1],pass:p[1]});
                try{setCreds(prev=>({...prev,url:new URL(l).origin}))}catch(e){}
            };

            return el('div', { className: 'flex flex-col h-full' },
                el('div', { className: 'p-5 pb-2 bg-white border-b border-slate-100 z-10' },
                    el('h1', { className: 'text-3xl font-black mb-4' }, 'Payments'),
                    el('div', { className: 'flex gap-2' }, ['all','pending','completed'].map(f=>el('button',{key:f,onClick:()=>setFilter(f),className:`px-4 py-2 rounded-full text-xs font-bold capitalize transition-all ${filter===f?'bg-slate-900 text-white':'bg-slate-100 text-slate-500'}`},f)))
                ),
                el('div', { className: 'flex-1 overflow-y-auto p-5 pb-32 space-y-3 bg-slate-50' },
                    list.map(p => el('div', { key:p.id, onClick:()=>setSheet(p), className: 'bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-center btn-press' },
                        el('div', null, el('p',{className:'font-bold text-slate-900'},p.user_login), el('p',{className:'text-xs text-slate-500'},`#${p.id} · ${p.plan_name}`)),
                        // FIX (Fix 4): was '$'+p.amount (hardcoded $, no entity decoding).
                        // Now uses amount_display from API (which has real Unicode symbol via Fix 1),
                        // decoded through decodeHtml as a safety net, rendered via dangerouslySetInnerHTML.
                        el('div', {className:'text-right'},
                            el('p', {
                                className:'font-black text-lg',
                                dangerouslySetInnerHTML: { __html: decodeHtml(p.amount_display) || ('$' + p.amount) }
                            }),
                            el('span',{className:`text-[10px] font-bold uppercase ${p.status==='APPROVED'?'text-emerald-600':'text-amber-600'}`},p.status)
                        )
                    ))
                ),
                el(Sheet, { open:!!sheet, onClose:()=>{setSheet(null);setWizMode(false);}, title: wizMode?'Fulfillment':'Invoice Details' },
                    wizMode ? el('div', { className:'space-y-4' },
                        el('div', {className:'p-3 bg-primary-50 text-primary-700 text-xs rounded-xl'}, 'Paste M3U to auto-fill.'),
                        el('textarea', {className:'w-full p-3 bg-slate-100 rounded-xl text-sm', placeholder:'http://...', rows:3, value:creds.m3u, onChange:e=>setCreds({...creds,m3u:e.target.value})}),
                        el('button', {onClick:parse, className:'text-xs font-bold text-primary-600'}, 'Auto-Parse'),
                        el('div', {className:'grid grid-cols-2 gap-3'}, el(Input,{label:'User',value:creds.user,onChange:v=>setCreds({...creds,user:v})}), el(Input,{label:'Pass',value:creds.pass,onChange:v=>setCreds({...creds,pass:v})})),
                        el(Input, {label:'Host',value:creds.url,onChange:v=>setCreds({...creds,url:v})}),
                        el('button', {onClick:()=>approve(true), className:'w-full py-4 bg-emerald-500 text-white font-bold rounded-2xl shadow-lg'}, 'Activate')
                    ) : (sheet && el('div', { className:'space-y-6' },
                        el('div', {className:'flex justify-between p-4 bg-slate-50 rounded-2xl'}, el('span',{className:'text-sm font-bold text-slate-500'},'Status'), el('span',{className:'px-3 py-1 bg-white rounded-lg text-xs font-bold uppercase'},sheet.status)),
                        // FIX (Fix 5): Invoice detail amount was (sheet.currency_symbol||'$')+sheet.amount
                        // (direct concatenation — entity not decoded). Now uses decoded amount_display.
                        el('div', {className:'flex justify-between p-4 bg-slate-50 rounded-2xl'},
                            el('span',{className:'text-sm font-bold text-slate-500'},'Amount'),
                            el('span', {
                                className:'font-black text-slate-900',
                                dangerouslySetInnerHTML: { __html: decodeHtml(sheet.amount_display) || (decodeHtml(sheet.currency_symbol||'$') + sheet.amount) }
                            })
                        ),
                        sheet.proofs.length>0 && el('div', {className:'grid grid-cols-2 gap-2'}, sheet.proofs.map(u=>el('a',{key:u,href:u,target:'_blank',className:'block aspect-video bg-slate-100 rounded-xl bg-cover border',style:{backgroundImage:`url(${u})`}}))),
                        (sheet.status!=='APPROVED'&&sheet.status!=='REJECTED') && el('div', {className:'grid grid-cols-2 gap-4'}, el('button',{onClick:()=>setWizMode(true),className:'py-4 bg-slate-900 text-white font-bold rounded-2xl shadow-xl'},'Fulfill'), el('button',{onClick:reject,className:'py-4 bg-white border-2 border-rose-100 text-rose-500 font-bold rounded-2xl'},'Reject'))
                    ))
                )
            );
        };

        const Users = () => {
            const [list, setList] = useState([]);
            const [sel, setSel] = useState(null);
            const [data, setData] = useState(null);
            const [tab, setTab] = useState('profile');
            const [search, setSearch] = useState('');
            const [pForm, setPForm] = useState({});
            const [sForm, setSForm] = useState({});
            const [multi, setMulti] = useState(false);
            const [selectedIds, setSelectedIds] = useState([]);

            useEffect(() => { api('users' + (search ? '?search='+search:'')).then(setList) }, [search]);
            useEffect(() => { if(sel) api(`users/${sel}`).then(d => { setData(d); setPForm(d.profile); setSForm({ plan_id: 1, status: 'active', start_date: new Date().toISOString().slice(0,10), end_date: '', connections: 1 }); }); }, [sel]);

            const saveProfile = async () => { await api(`users/${sel}/update`, 'POST', pForm); alert('Saved'); };
            const saveSub = async () => { await api(`users/${sel}/subscription`, 'POST', sForm); alert('Saved'); api(`users/${sel}`).then(setData); };
            
            const toggleId = (id) => setSelectedIds(prev => prev.includes(id) ? prev.filter(i=>i!==id) : [...prev, id]);