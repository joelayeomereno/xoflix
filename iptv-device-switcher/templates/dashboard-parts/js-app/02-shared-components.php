// ==========================================
// 2. SHARED COMPONENTS (Lumina Engine v2.4 - SCORE FIX)
// ==========================================
console.log("Lumina Engine: Shared Components Loaded");

// --- UTILS ---
const Pagination = ({ totalItems, itemsPerPage, currentPage, onPageChange }) => {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) return null;
    const pages = [];
    for (let i = 1; i <= totalPages; i++) pages.push(i);
    return (
        <div className="flex items-center justify-center gap-2 mt-6">
            <button onClick={() => onPageChange(Math.max(1, currentPage - 1))} disabled={currentPage === 1} className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 transition-colors"><ChevronDown size={16} className="rotate-90" /></button>
            {pages.map(p => (
                <button key={p} onClick={() => onPageChange(p)} className={`w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all ${currentPage === p ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'}`}>{p}</button>
            ))}
            <button onClick={() => onPageChange(Math.min(totalPages, currentPage + 1))} disabled={currentPage === totalPages} className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 transition-colors"><ChevronRight size={16} /></button>
        </div>
    );
};

// -----------------------------------------------------------------------
// FIX 1: AlertBanner
// - "Act Now" renamed to "Renew Now"
// - When clicked, finds matching sub by sub_id and calls handleExtend(sub)
// - Falls back to setActiveTab('shop') if sub not found
// - New prop handleExtend must be passed at all AlertBanner call sites
// -----------------------------------------------------------------------
const AlertBanner = ({ alerts, setActiveTab, handleExtend }) => {
    if (!alerts || alerts.length === 0) return null;
    return (
        <div className="space-y-3 mb-6 animate-fade-in">
            {alerts.map((alert, idx) => (
                <div key={idx} className="flex items-start gap-3 bg-amber-50/90 backdrop-blur-sm border border-amber-200 text-amber-900 px-5 py-4 rounded-2xl shadow-sm">
                    <AlertTriangle size={20} className="text-amber-600 mt-0.5 flex-shrink-0" />
                    <div className="flex-1"><p className="text-sm font-bold">{alert.message}</p></div>
                    {alert.action && (
                        <button
                            onClick={() => {
                                const subsList = typeof ACTIVE_SUBSCRIPTIONS !== 'undefined' ? ACTIVE_SUBSCRIPTIONS : [];
                                const matchedSub = alert.sub_id ? subsList.find(s => s.id == alert.sub_id) : subsList[0];
                                if (matchedSub && typeof handleExtend === 'function') {
                                    handleExtend(matchedSub);
                                } else {
                                    setActiveTab(alert.action || 'shop');
                                }
                            }}
                            className="text-xs font-bold bg-amber-200/50 text-amber-900 px-3 py-1.5 rounded-lg hover:bg-amber-300 transition-colors whitespace-nowrap"
                        >
                            Renew Now
                        </button>
                    )}
                </div>
            ))}
        </div>
    );
};

const CopyButton = ({ text }) => {
    const [copied, setCopied] = useState(false);
    const handleCopy = () => { navigator.clipboard.writeText(text); setCopied(true); setTimeout(() => setCopied(false), 2000); };
    return (
        <button onClick={handleCopy} className={`flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all border ${copied ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-white text-slate-500 border-slate-200 hover:text-indigo-600 hover:border-indigo-200'}`}>
            {copied ? <Check size={14}/> : <Copy size={14}/>} {copied ? 'Copied' : 'Copy'}
        </button>
    );
};

const CountdownTimer = ({ targetDate }) => {
    const [timeLeft, setTimeLeft] = useState(new Date(targetDate) - new Date());
    useEffect(() => { const timer = setInterval(() => { setTimeLeft(new Date(targetDate) - new Date()); }, 1000); return () => clearInterval(timer); }, [targetDate]);
    if (timeLeft <= 0) return timeLeft > -7200000 ? <span className="text-emerald-500 font-bold animate-pulse">LIVE NOW</span> : <span className="text-slate-400 font-medium">Ended</span>;
    const h = Math.floor((timeLeft / 3600000) % 24);
    const m = Math.floor((timeLeft / 60000) % 60);
    const s = Math.floor((timeLeft / 1000) % 60);
    const d = Math.floor(timeLeft / 86400000);
    return d > 0 ? <span className="text-indigo-600 font-bold">{d}d {h}h {m}m</span> : <div className={`font-mono font-bold tracking-widest ${h===0?'text-rose-500':'text-indigo-600'}`}>{String(h).padStart(2,'0')}:{String(m).padStart(2,'0')}:{String(s).padStart(2,'0')}</div>;
};

// --- MODERN CARD COMPONENTS ---

const StatusBadge = ({ status }) => {
    const raw = (status || '').toString();
    const upper = raw.toUpperCase();
    const labelMap = { 'APPROVED': 'Paid', 'COMPLETED': 'Paid', 'PENDING_ADMIN_REVIEW': 'Reviewing', 'AWAITING_PROOF': 'Needs Proof', 'REJECTED': 'Rejected' };
    const styleMap = { 
        'Active': 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-500/20', 
        'Pending': 'bg-amber-100 text-amber-700 ring-1 ring-amber-500/20', 
        'Expired': 'bg-rose-100 text-rose-700 ring-1 ring-rose-500/20', 
        'Paid': 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-500/20', 
        'Reviewing': 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-500/20', 
        'Needs Proof': 'bg-amber-100 text-amber-700 ring-1 ring-amber-500/20', 
        'Rejected': 'bg-rose-100 text-rose-700 ring-1 ring-rose-500/20' 
    };
    const friendly = labelMap[upper] || raw;
    
    return (
        <span className={`px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-widest ${styleMap[friendly] || styleMap[raw] || styleMap[upper] || 'bg-slate-100 text-slate-600'}`}>
            {friendly}
        </span>
    );
};

// 1. Sidebar Item
const SidebarItem = ({ icon: Icon, label, active, onClick }) => (
    <button onClick={onClick} className={`group flex items-center w-full p-3.5 mb-1.5 rounded-2xl transition-all duration-300 relative overflow-hidden ${active ? 'bg-indigo-50 text-indigo-700 shadow-sm ring-1 ring-indigo-100' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'}`}>
        {active && <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-600 rounded-r-full"></div>}
        <Icon size={20} className={`mr-4 transition-transform duration-300 ${active ? 'scale-110 text-indigo-600' : 'group-hover:scale-110 text-slate-400 group-hover:text-slate-600'}`} strokeWidth={active ? 2.5 : 2} />
        <span className={`font-bold text-sm tracking-wide ${active ? 'font-extrabold' : ''}`}>{label}</span>
    </button>
);

// 2. Credential Field (Simple)
const CredentialField = ({ label, value, isPassword = false }) => {
    const [revealed, setRevealed] = useState(false);
    const safeValue = (value === null || value === undefined) ? '' : String(value);
    const canCopy = !!safeValue;
    return (
        <div className="bg-slate-50/50 hover:bg-white p-3.5 rounded-2xl border border-slate-200 hover:border-indigo-200 hover:shadow-sm transition-all flex items-center gap-3 group">
            <div className="min-w-0 flex-1">
                <p className="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mb-1 group-hover:text-indigo-500 transition-colors">{label}</p>
                {isPassword ? <input type="text" value={revealed ? safeValue : '        '} readOnly className="w-full text-sm font-bold font-mono text-slate-700 bg-transparent border-none focus:ring-0 p-0" /> : <code className="block text-sm font-bold font-mono text-slate-700 select-all overflow-x-auto whitespace-nowrap no-scrollbar">{safeValue}</code>}
            </div>
            <div className="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                {isPassword && <button type="button" onClick={() => setRevealed(v => !v)} className="p-2 rounded-xl text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors"><Eye size={16} /></button>}
                <button type="button" onClick={() => { if (canCopy) navigator.clipboard.writeText(safeValue); }} className={`p-2 rounded-xl transition-colors ${canCopy ? 'text-slate-400 hover:text-indigo-600 hover:bg-indigo-50' : 'text-slate-300 cursor-not-allowed'}`}><Copy size={16}/></button>
            </div>
        </div>
    );
};

// 3. Smart Copy Field (Enhanced)
const SmartCopyField = ({ label, value, isSecret }) => {
    const [copied, setCopied] = useState(false);
    const [visible, setVisible] = useState(!isSecret);
    const handleCopy = () => { navigator.clipboard.writeText(value); setCopied(true); setTimeout(() => setCopied(false), 2000); };
    return (
        <div className="group">
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-1">{label}</label>
            <div className="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden transition-all group-hover:border-blue-300 group-hover:shadow-sm group-hover:bg-white">
                <div className="pl-4 text-slate-400 group-hover:text-blue-500 transition-colors">
                    {isSecret ? <Lock size={16}/> : (label.includes('URL') ? <Server size={16}/> : <User size={16}/>)}
                </div>
                <input type={visible ? "text" : "password"} value={value || ''} readOnly className="w-full bg-transparent border-none text-sm font-bold text-slate-700 py-3.5 px-3 focus:ring-0 outline-none"/>
                <div className="flex items-center pr-2 gap-1">
                    {isSecret && <button onClick={() => setVisible(!visible)} className="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors">{visible ? <EyeOff size={16}/> : <Eye size={16}/>}</button>}
                    <button onClick={handleCopy} className={`p-2 rounded-lg text-xs font-bold flex items-center gap-1 transition-all ${copied ? 'bg-emerald-100 text-emerald-600' : 'bg-white border border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600'}`}>
                        {copied ? <Check size={14}/> : <Copy size={14}/>} {copied ? 'Copied' : 'Copy'}
                    </button>
                </div>
            </div>
        </div>
    );
};

// 4. Subscription Credentials Block
const SubscriptionCredentials = ({ sub }) => {
    const [credTab, setCredTab] = useState('xtream');
    const c = (sub && sub.credentials) ? sub.credentials : {};
    const attachments = Array.isArray(c.attachments) ? c.attachments : [];
    return (
        <div className="space-y-4">
            <div className="inline-flex bg-slate-50 border border-slate-200 rounded-2xl p-1">
                <button type="button" onClick={() => setCredTab('xtream')} className={`px-4 py-2 rounded-xl text-sm font-bold transition-all ${credTab === 'xtream' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-900'}`}>Xtream</button>
                <button type="button" onClick={() => setCredTab('m3u')} className={`px-4 py-2 rounded-xl text-sm font-bold transition-all ${credTab === 'm3u' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-900'}`}>M3U</button>
            </div>
            {credTab === 'xtream' ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <CredentialField label="Name" value={c.name || 'XOFLIX'} />
                    <CredentialField label="Username" value={c.username} />
                    <CredentialField label="Password" value={c.password} isPassword={true} />
                    <CredentialField label="Host URL" value={c.url} />
                    {c.hostAlt && c.hostAlt !== c.url && (<CredentialField label="Alternative Host URL" value={c.hostAlt} />)}
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4"><CredentialField label="M3U Playlist" value={c.m3uUrl} /></div>
            )}
            {attachments.length > 0 && (
                <div className="pt-2">
                    <details className="group rounded-2xl border border-slate-200 bg-slate-50/60 overflow-hidden">
                        <summary className="cursor-pointer list-none select-none px-4 py-3 flex items-center justify-between"><span className="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Panel Attachments</span><span className="text-xs font-bold text-slate-400 group-open:hidden">Show</span><span className="text-xs font-bold text-slate-400 hidden group-open:inline">Hide</span></summary>
                        <div className="px-4 pb-4"><div className="grid grid-cols-1 gap-3">{attachments.map((u, idx) => (<CredentialField key={idx} label={`Panel Link ${idx + 1}`} value={u} />))}</div></div>
                    </details>
                </div>
            )}
        </div>
    );
};

// 5. Modern Sub Card
const ModernSubCard = ({ sub, onExtend }) => {
    const [view, setView] = useState('xtream');
    const isExpired = sub.status.toLowerCase() === 'expired';
    const isPending = sub.status.toLowerCase() === 'pending';
    const statusColor = isExpired ? 'rose' : (isPending ? 'amber' : 'emerald');
    const creds = sub.credentials || {};
    
    return (
        <div className="bg-white rounded-[2.5rem] p-10 shadow-xl shadow-slate-300/40 border border-slate-200 relative overflow-hidden transition-all hover:-translate-y-1 hover:shadow-2xl">
            <div className={`absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-${statusColor}-500 to-${statusColor}-300`}></div>
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                <div>
                    <div className="flex items-center gap-3 mb-2">
                        <h2 className="text-2xl font-black text-slate-900 tracking-tight">{sub.planName}</h2>
                        <span className={`px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-${statusColor}-50 text-${statusColor}-600 border border-${statusColor}-100 flex items-center gap-1.5`}><span className={`w-1.5 h-1.5 rounded-full bg-${statusColor}-500 animate-pulse`}></span>{sub.status}</span>
                    </div>
                    <p className="text-slate-400 font-medium text-sm flex items-center gap-2"><Clock size={16} className="text-slate-300" /> {isExpired ? 'Expired on' : 'Renews on'} <span className="text-slate-600 font-bold">{sub.nextBillingDate}</span> {!isExpired && <span className="text-slate-300">|</span>} {!isExpired && <span className="text-emerald-600 font-bold">{sub.daysLeft} days remaining</span>}</p>
                </div>
                <div className="flex items-center gap-4 w-full md:w-auto">
                    <div className="hidden md:block text-right"><div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Price</div><div className="text-xl font-black text-slate-900">{sub.price}</div></div>
                    <button onClick={() => onExtend(sub)} className={`flex-1 md:flex-none py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg shadow-${statusColor}-500/20 text-white flex items-center justify-center gap-2 transition-all active:scale-95 bg-slate-900 hover:bg-slate-800`}>{isExpired ? <Zap size={18} className="text-yellow-400" /> : <CreditCard size={18} className="text-blue-300" />} {isExpired ? 'Reactivate Now' : 'Extend Plan'}</button>
                </div>
            </div>
            {!isExpired && !isPending && (
                <div className="w-full bg-slate-100 h-2 rounded-full mb-8 overflow-hidden">
                    <div
                        className="h-full bg-gradient-to-r from-emerald-400 to-blue-500 rounded-full transition-all duration-1000"
                        style={{width: `${Math.min(100, Math.max(0, (sub.daysLeft / (sub.totalDays > 0 ? sub.totalDays : (sub.planName && /year|annual/i.test(sub.planName) ? 365 : sub.planName && /quarter|3.?mo/i.test(sub.planName) ? 90 : 30))) * 100))}%`}}
                    ></div>
                </div>
            )}
            <div className="bg-slate-50/50 rounded-[1.5rem] border border-slate-100 p-2">
                <div className="flex gap-2 p-1 bg-white rounded-2xl border border-slate-100 shadow-sm mb-6">
                    <button onClick={() => setView('xtream')} className={`flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all ${view === 'xtream' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'}`}><Server size={16} /> Xtream API</button>
                    <button onClick={() => setView('m3u')} className={`flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all ${view === 'm3u' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'}`}><FileText size={16} /> M3U Playlist</button>
                </div>
                <div className="px-4 pb-4 animate-fade-in">
                    {view === 'xtream' ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <SmartCopyField label="Username" value={creds.username} />
                            <SmartCopyField label="Password" value={creds.password} isSecret={true} />
                            <div className="md:col-span-2"><SmartCopyField label="Host URL" value={creds.url} /></div>
                            {creds.hostAlt && creds.hostAlt !== creds.url && (<div className="md:col-span-2 mt-2 pt-4 border-t border-slate-200 border-dashed"><span className="text-[10px] font-bold text-amber-500 uppercase mb-2 block">Alternative Host (Try if above fails)</span><SmartCopyField label="Alt Host URL" value={creds.hostAlt} /></div>)}
                        </div>
                    ) : (
                        <div className="space-y-4"><SmartCopyField label="M3U Playlist URL" value={creds.m3uUrl} />
                        {creds.attachments && creds.attachments.length > 0 && (<div className="mt-4 bg-blue-50/50 rounded-xl p-4 border border-blue-100"><h4 className="text-xs font-black text-blue-800 uppercase tracking-wider mb-3 flex items-center gap-2"><Copy size={14} /> Attached Playlists</h4><div className="space-y-3">{creds.attachments.map((url, idx) => (<SmartCopyField key={idx} label={`Panel #${idx + 1}`} value={url} />))}</div></div>)}
                        </div>
                    )}
                </div>
            </div>
            <div className="mt-6 flex justify-center"><a href="?tab=support" className="text-xs font-bold text-slate-400 hover:text-blue-600 flex items-center gap-1 transition-colors"><HelpCircle size={14} /> Need help setting up? Visit Support Center</a></div>
        </div>
    );
};

// 6. Sports Card (Visual Upgrade + SMART TIME)
const SportsCard = ({ event, onClick }) => {
    const now = new Date();
    const start = new Date(event.startTime);
    const isTimeLive = (now >= start && now <= new Date(start.getTime() + 7200000));
    const statusRaw = (event.status || '').toUpperCase();
    const isApiLive = ['1H', '2H', 'HT', 'ET', 'P', 'LIVE', 'IN PLAY'].some(s => statusRaw.includes(s));
    const isLive = isApiLive || isTimeLive;
    const isFuture = now < start;
    const hasLogos = event.home_logo && event.away_logo;
    const hasScore = (event.home_score !== null && event.home_score !== undefined && event.home_score !== '') && 
                     (event.away_score !== null && event.away_score !== undefined && event.away_score !== '');
    const hasChannels = event.channels && event.channels.length > 0;

    const getSportIcon = (type) => { 
        if (!type || typeof type !== 'string') return <Trophy size={20}/>;
        switch(type.toLowerCase()) { 
            case 'soccer': return <IconFootball size={20}/>; 
            case 'nba': case 'basketball': return <IconBasketball size={20}/>; 
            case 'f1': case 'motorsport': return <IconF1 size={20}/>; 
            case 'nfl': case 'american football': return <IconNFL size={20}/>; 
            case 'ufc': case 'mma': case 'fighting': return <IconFighting size={20}/>; 
            case 'tennis': return <IconTennis size={20}/>; 
            case 'cricket': return <IconCricket size={20}/>; 
            default: return <Trophy size={20}/>; 
        } 
    };
    
    // --- SMART TIME LOGIC ---
    const getSmartTime = () => {
        if (isLive && hasScore) return 'In Progress';
        
        const dateObj = new Date(event.startTime);
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const eventDay = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
        const diffDays = Math.round((eventDay - today) / (1000 * 60 * 60 * 24));
        
        const timeStr = dateObj.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});
        
        if (diffDays === 0) return timeStr;
        if (diffDays === 1) return 'Tom ' + timeStr;
        if (diffDays === -1) return 'Yest ' + timeStr;
        
        const dayName = new Intl.DateTimeFormat(undefined, { weekday: 'short' }).format(dateObj);
        return dayName + ' ' + timeStr;
    };

    return (
        <div 
            onClick={onClick}
            className={`relative bg-white rounded-[1.8rem] border border-slate-200 p-5 shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:shadow-[0_20px_40px_rgb(0,0,0,0.12)] transition-all group overflow-hidden cursor-pointer ${isLive ? 'ring-1 ring-emerald-500 shadow-emerald-100' : 'hover:border-indigo-300'}`}
        >
            <div className="absolute top-0 right-0 z-10 flex flex-col items-end">
                {isLive && (
                    <div className="bg-emerald-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm animate-pulse mb-1 flex items-center gap-1">
                        <div className="w-1.5 h-1.5 bg-white rounded-full"></div> LIVE
                    </div>
                )}
                {hasChannels && (
                    <div className={`flex items-center gap-1.5 bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 ${isLive ? 'rounded-l-lg' : 'rounded-bl-xl'} shadow-sm`}>
                        <span className="relative flex h-2 w-2">
                          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                          <span className="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        <span>Stream</span>
                    </div>
                )}
            </div>
            
            <div className="flex justify-between items-start mb-4">
                <div className="flex items-center gap-3 w-full">
                    {!hasLogos ? (
                        <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-sm ${isLive ? 'bg-emerald-100 text-emerald-600' : 'bg-white border border-slate-100 text-slate-400'}`}>
                            {getSportIcon(event.type)}
                        </div>
                    ) : null}
                    
                    <div className="flex-1 min-w-0">
                        <p className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider truncate mb-1">{event.league}</p>
                        
                        {hasLogos ? (
                             <div className="flex items-center gap-3">
                                 <img src={event.home_logo} className="w-8 h-8 object-contain drop-shadow-sm" alt="Home" loading="lazy" />
                                 {hasScore ? (
                                    <div className={`text-sm font-black px-2 py-0.5 rounded-lg ${isLive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>
                                        {event.home_score}-{event.away_score}
                                    </div>
                                 ) : (
                                    <span className="text-sm font-black text-slate-600">VS</span>
                                 )}
                                 <img src={event.away_logo} className="w-8 h-8 object-contain drop-shadow-sm" alt="Away" loading="lazy" />
                             </div>
                        ) : (
                             <h3 className="font-bold text-slate-900 leading-tight truncate">
                                {hasScore ? `${event.title} (${event.home_score}-${event.away_score})` : event.title}
                             </h3>
                        )}
                    </div>
                </div>
            </div>
            
            <div className="flex justify-between items-end">
                <div>
                    <div className="text-sm">
                        {isFuture ? (
                            <div className="flex items-center gap-2 bg-slate-100/50 border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">
                                <Clock size={14} className="text-slate-500"/>
                                <CountdownTimer targetDate={event.startTime}/>
                            </div>
                        ) : (
                            <span className={`text-xs font-bold px-2 py-1 rounded-lg ${isLive ? 'text-emerald-600 bg-emerald-50' : 'text-slate-500 bg-slate-100'}`}>
                                {getSmartTime()}
                            </span>
                        )}
                    </div>
                </div>
                <button className={`w-10 h-10 rounded-2xl flex items-center justify-center transition-all ${isLive || hasChannels ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-100 border border-slate-200 text-slate-600 shadow-sm hover:bg-slate-900 hover:text-white hover:border-slate-900'}`}>
                    {isLive ? <Tv size={16}/> : <Star size={16} strokeWidth={2.5}/>}
                </button>
            </div>
        </div>
    );
};

// 7. Sports Modal (Restored & Fixed Layout)
const SportsModal = ({ event, onClose }) => {
    if (!event) return null;
    const now = new Date();
    const start = new Date(event.startTime);
    const statusRaw = (event.status || '').toUpperCase();
    const liveStatusCodes = ['1H', '2H', 'HT', 'ET', 'P', 'LIVE', 'IN PLAY'];
    const isLive = liveStatusCodes.some(s => statusRaw.includes(s)) || (now >= start && now <= new Date(start.getTime() + 7200000));
    const hasScore = (event.home_score !== null && event.home_score !== undefined && event.home_score !== '');
    
    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-md animate-fade-in" onClick={onClose}></div>
            <div className="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl relative z-10 animate-slide-up overflow-hidden flex flex-col ring-1 ring-white/50">
                <div className="bg-slate-900 text-white p-8 pb-10 text-center relative overflow-hidden">
                    <div className="absolute top-0 right-0 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div className="flex justify-between items-center mb-8 relative z-10"><span className="text-[10px] font-black uppercase tracking-widest text-slate-300 bg-white/10 px-3 py-1.5 rounded-full backdrop-blur-md">{event.league}</span><button onClick={onClose} className="w-8 h-8 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors backdrop-blur-md"><X size={16}/></button></div>
                    <div className="flex items-center justify-center gap-8 relative z-10">
                        <div className="flex flex-col items-center w-24">{event.home_logo ? (<img src={event.home_logo} className="w-20 h-20 object-contain mb-3 drop-shadow-lg" alt="Home" />) : (<div className="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center text-3xl font-black mb-3">{event.home_team?event.home_team[0]:'H'}</div>)}<span className="text-xs font-bold leading-tight line-clamp-2 text-slate-200">{event.home_team || 'Home'}</span></div>
                        
                        {/* SCORE DISPLAY FIX: Horizontal layout, gaps, and nowrap */}
                        <div className="flex flex-col items-center">
                            {hasScore ? (
                                <div className={`flex flex-row items-center justify-center gap-3 px-6 py-2 rounded-2xl text-2xl font-black mb-2 shadow-xl backdrop-blur-md whitespace-nowrap min-w-[120px] ${isLive ? 'bg-emerald-500 text-white shadow-emerald-500/30' : 'bg-white/10 text-white'}`}>
                                    <span>{event.home_score}</span>
                                    <span className="text-white/80 opacity-70">-</span>
                                    <span>{event.away_score}</span>
                                </div>
                            ) : (
                                <span className="text-3xl font-black text-slate-600 mb-2">VS</span>
                            )}
                            {isLive ? <span className="text-[10px] font-bold text-emerald-400 animate-pulse uppercase tracking-widest">LIVE NOW</span> : <span className="text-[10px] font-bold text-slate-400 bg-slate-800/50 px-2 py-1 rounded">{new Date(event.startTime).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</span>}
                        </div>
                        
                        <div className="flex flex-col items-center w-24">{event.away_logo ? (<img src={event.away_logo} className="w-20 h-20 object-contain mb-3 drop-shadow-lg" alt="Away" />) : (<div className="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center text-3xl font-black mb-3">{event.away_team?event.away_team[0]:'A'}</div>)}<span className="text-xs font-bold leading-tight line-clamp-2 text-slate-200">{event.away_team || 'Away'}</span></div>
                    </div>
                </div>
                <div className="p-8 overflow-y-auto custom-scrollbar bg-slate-50 h-[380px]">
                    <h4 className="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><Tv size={14} className="text-indigo-500" /> Available Channels</h4>
                    {(!event.channels || event.channels.length === 0) ? (<div className="text-center py-12 text-slate-400"><div className="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm"><Tv size={24} className="opacity-20"/></div><p className="text-sm font-medium">No channels listed.</p></div>) : (<div className="space-y-3">{event.channels.map((ch, i) => (<div key={i} className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex justify-between items-center group hover:border-indigo-300 transition-all cursor-default"><div className="flex items-center gap-4"><div className="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-black text-sm group-hover:scale-110 transition-transform">{i + 1}</div><div><div className="font-bold text-slate-900 text-sm group-hover:text-indigo-700 transition-colors">{ch.name}</div>{ch.region && <div className="text-[10px] font-bold text-slate-400 uppercase mt-0.5">{ch.region}</div>}</div></div><div className="text-[10px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-100">HD</div></div>))}</div>)}
                </div>
                <div className="p-6 bg-white border-t border-slate-100"><button onClick={onClose} className="w-full py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl transition-colors">Close Guide</button></div>
            </div>
        </div>
    );
};

// 8. Plan Card (Restored)
const PlanCard = ({ plan, onSelect }) => (
  <div className={`relative bg-white rounded-[2rem] p-6 lg:p-8 transition-all duration-300 flex flex-col h-full group ${plan.recommended ? 'ring-2 ring-violet-500 shadow-xl shadow-violet-200' : 'ring-1 ring-slate-200 shadow-sm hover:ring-blue-300 hover:shadow-md'}`}>
    {plan.recommended && <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-gradient-to-r from-violet-600 to-indigo-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-widest shadow-lg whitespace-nowrap">Best Value</div>}
    <div className="text-center mb-6"><h3 className="text-slate-500 font-bold mb-2 uppercase tracking-wide text-xs">{plan.name}</h3><div className="flex items-center justify-center gap-1 text-slate-900"><span className="text-4xl font-black tracking-tighter">{plan.price}</span><span className="text-slate-400 text-sm font-medium">/{plan.period}</span></div></div>
    <ul className="space-y-4 mb-8 flex-1">{plan.features.map((feature, idx) => (<li key={idx} className="flex items-start gap-3 text-sm text-slate-600"><div className={`w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 ${plan.recommended ? 'bg-violet-50 text-violet-600' : 'bg-blue-50 text-blue-600'}`}><Check size={12} strokeWidth={3} /></div><span className="leading-tight">{feature}</span></li>))}</ul>
    <button type="button" onClick={() => onSelect(plan)} className={`w-full py-4 rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95 ${plan.recommended ? 'bg-violet-600 text-white hover:bg-violet-700 shadow-violet-200' : 'bg-slate-900 text-white hover:bg-slate-800 shadow-slate-200'}`}>Get Started</button>
  </div>
);

// 9. Mobile Drawer (Restored)
const Drawer = ({ isOpen, onClose, activeTab, setActiveTab, logoutUrl }) => {
    useEffect(() => { document.body.style.overflow = isOpen ? 'hidden' : ''; return () => { document.body.style.overflow = ''; }; }, [isOpen]);
    if (!isOpen) return null;
    const menuItems = [{id:'dashboard',icon:LayoutDashboard,label:'Home'},{id:'subscription',icon:FileText,label:'My Subscriptions'},{id:'billing',icon:CreditCard,label:'Billing'},{id:'shop',icon:ShoppingBag,label:'Upgrade'},{id:'sports',icon:Trophy,label:'Sports Guide'},{id:'profile',icon:User,label:'My Profile'},{id:'support',icon:HelpCircle,label:'Support'}];
    return (
        <div className="fixed inset-0 z-[100] md:hidden">
            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-md animate-fade-in" onClick={onClose}></div>
            <div className="absolute right-0 top-0 bottom-0 w-[80%] max-w-sm bg-white shadow-2xl animate-slide-in flex flex-col rounded-l-[2rem]">
                <div className="p-8 flex items-center justify-between border-b border-slate-100"><span className="font-black text-xl text-slate-900 tracking-tight">Menu</span><button onClick={onClose} className="p-2 -mr-2 text-slate-400 hover:text-slate-900 rounded-full hover:bg-slate-50 transition-colors"><X size={24}/></button></div>
                <div className="flex-1 overflow-y-auto py-6 px-6"><nav className="space-y-2">{menuItems.map(item => (<button key={item.id} onClick={()=>{setActiveTab(item.id);onClose();}} className={`flex items-center w-full p-4 rounded-2xl transition-all ${activeTab===item.id?'bg-indigo-50 text-indigo-700 font-extrabold shadow-sm ring-1 ring-indigo-100':'text-slate-600 font-bold hover:bg-slate-50'}`}><item.icon size={20} className={`mr-4 ${activeTab===item.id?'text-indigo-600':'text-slate-400'}`} strokeWidth={activeTab===item.id?2.5:2}/>{item.label}</button>))}</nav></div>
                <div className="p-6 border-t border-slate-100 bg-slate-50/50 pb-safe rounded-bl-[2rem]"><a href={logoutUrl} className="flex items-center justify-center w-full p-4 rounded-2xl border border-slate-200 bg-white text-rose-600 font-bold hover:bg-rose-50 hover:border-rose-200 transition-all shadow-sm"><LogOut size={18} className="mr-2"/> Sign Out</a></div>
            </div>
        </div>
    );
};

// 10. Proof Upload Modal (Restored)
const ProofUploadModal = ({ invoice, onClose }) => {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-fade-in">
            <div className="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-slide-up">
                <div className="p-4 border-b border-slate-100 flex justify-between items-center"><h3 className="font-bold text-lg text-slate-900">Upload Proof</h3><button onClick={onClose} className="p-2 hover:bg-slate-50 rounded-full text-slate-400 hover:text-slate-600"><X size={20}/></button></div>
                <div className="p-6">
                    <p className="text-sm text-slate-500 mb-6">Please upload a screenshot of your payment for <strong>{invoice.id}</strong>.</p>
                    <form method="post" encType="multipart/form-data"><input type="hidden" name="payment_proof_submit" value="1" /><input type="hidden" name="payment_id" value={invoice.raw_id} /><div className="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center mb-6 hover:border-blue-500 transition-colors bg-slate-50 relative"><input type="file" name="payment_proof[]" className="absolute inset-0 w-full h-full opacity-0 cursor-pointer" id="proof_file" required multiple /><div className="mx-auto w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3 pointer-events-none"><UploadCloud size={24} /></div><span className="block font-bold text-slate-700 pointer-events-none">Click to Select File</span><span className="text-xs text-slate-400 pointer-events-none">JPG, PNG, PDF allowed</span></div><button type="submit" className="w-full py-3.5 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition-all">Submit Proof</button></form>
                </div>
            </div>
        </div>
    );
};