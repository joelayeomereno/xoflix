// ==========================================
// 4. MAIN APP CONTAINER - PART 1 (COMPONENTS & CONSTANTS)
// ==========================================

// --- CONSTANTS ---
const COUNTRIES = [
    {c:'US',n:'United States'},{c:'GB',n:'United Kingdom'},{c:'CA',n:'Canada'},{c:'AU',n:'Australia'},
    {c:'DE',n:'Germany'},{c:'FR',n:'France'},{c:'ES',n:'Spain'},{c:'IT',n:'Italy'},{c:'BR',n:'Brazil'},
    {c:'NG',n:'Nigeria'},{c:'ZA',n:'South Africa'},{c:'IN',n:'India'},{c:'CN',n:'China'},{c:'JP',n:'Japan'},
    {c:'NL',n:'Netherlands'},{c:'NZ',n:'New Zealand'},{c:'AE',n:'UAE'},{c:'SA',n:'Saudi Arabia'},
    {c:'MX',n:'Mexico'},{c:'AR',n:'Argentina'},{c:'CO',n:'Colombia'},{c:'PE',n:'Peru'},{c:'CL',n:'Chile'},
    {c:'SE',n:'Sweden'},{c:'NO',n:'Norway'},{c:'DK',n:'Denmark'},{c:'FI',n:'Finland'},{c:'IE',n:'Ireland'},
    {c:'PL',n:'Poland'},{c:'GR',n:'Greece'},{c:'PT',n:'Portugal'},{c:'TR',n:'Turkey'},{c:'RU',n:'Russia'},
    {c:'KR',n:'South Korea'},{c:'SG',n:'Singapore'},{c:'MY',n:'Malaysia'},{c:'ID',n:'Indonesia'},
    {c:'TH',n:'Thailand'},{c:'PH',n:'Philippines'},{c:'VN',n:'Vietnam'},{c:'PK',n:'Pakistan'},
    {c:'BD',n:'Bangladesh'},{c:'EG',n:'Egypt'},{c:'MA',n:'Morocco'},{c:'DZ',n:'Algeria'},{c:'KE',n:'Kenya'},
    {c:'GH',n:'Ghana'},{c:'UG',n:'Uganda'},{c:'TZ',n:'Tanzania'},{c:'IL',n:'Israel'},{c:'QA',n:'Qatar'},
    {c:'KW',n:'Kuwait'},{c:'BE',n:'Belgium'},{c:'CH',n:'Switzerland'},{c:'AT',n:'Austria'},{c:'CZ',n:'Czechia'},
    {c:'HU',n:'Hungary'},{c:'RO',n:'Romania'},{c:'BG',n:'Bulgaria'},{c:'RS',n:'Serbia'},{c:'HR',n:'Croatia'}
];

// --- UTILITIES ---

const getSmartStyles = (hex, isHex) => {
    const defaults = {
        title: 'text-white',
        desc: 'text-white/90',
        badge: 'bg-white/20 backdrop-blur-md border-white/10 text-white',
        overlay: 'bg-black/10 mix-blend-overlay'
    };

    if (!isHex || !hex || !hex.startsWith('#')) return defaults;

    let c = hex.substring(1);
    if (c.length === 3) c = c.split('').map(char => char + char).join('');
    if (c.length !== 6) return defaults;

    const rgb = parseInt(c, 16);
    const r = (rgb >> 16) & 0xff;
    const g = (rgb >>  8) & 0xff;
    const b = (rgb >>  0) & 0xff;

    const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b;

    if (luma > 160) {
        return {
            title: 'text-slate-900',
            desc: 'text-slate-600',
            badge: 'bg-slate-900/10 border-slate-900/10 text-slate-800',
            overlay: 'hidden'
        };
    } else {
        return defaults;
    }
};

// --- COMPONENTS ---

// 1. Locked Country Display
const LockedCountryDisplay = ({ countryCode }) => {
    const countryObj = COUNTRIES.find(c => c.c === countryCode || c.n === countryCode) || { c: '', n: countryCode || 'Unknown' };
    
    return (
        <div className="relative">
            <div className="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold text-slate-500 cursor-not-allowed opacity-80 flex items-center gap-3">
                {countryObj.c && (
                    <img 
                        src={`https://flagcdn.com/w40/${countryObj.c.toLowerCase()}.png`} 
                        alt={countryObj.c} 
                        className="w-6 h-auto rounded-sm grayscale opacity-70" 
                    />
                )}
                <span className="flex-1 truncate">{countryObj.n}</span>
                <Lock size={16} className="text-slate-400" />
            </div>
            <div className="text-[10px] text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                <Lock size={10} /> Location is locked. Contact support to change.
            </div>
        </div>
    );
};

// 2. Smart Copy Field (FIX: Increased Contrast)
const SmartCopyField = ({ label, value, isSecret }) => {
    const [copied, setCopied] = useState(false);
    const [visible, setVisible] = useState(!isSecret);
    
    const handleCopy = () => {
        navigator.clipboard.writeText(value);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div className="group">
            {/* FIXED: Text color darkened to slate-500 for better readability */}
            <label className="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-2 ml-1">{label}</label>
            <div className="relative flex items-center bg-white border border-slate-200 rounded-xl overflow-hidden transition-all group-hover:border-blue-300 group-hover:shadow-sm">
                <div className="pl-4 text-slate-400 group-hover:text-blue-500 transition-colors">
                    {isSecret ? <Lock size={16}/> : (label.includes('URL') ? <Server size={16}/> : <User size={16}/>)}
                </div>
                <input 
                    type={visible ? "text" : "password"} 
                    value={value || ''} 
                    readOnly 
                    className="w-full bg-transparent border-none text-sm font-bold text-slate-700 py-3.5 px-3 focus:ring-0 outline-none"
                />
                <div className="flex items-center pr-2 gap-1">
                    {isSecret && (
                        <button onClick={() => setVisible(!visible)} className="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors">
                            {visible ? <EyeOff size={16}/> : <Eye size={16}/>}
                        </button>
                    )}
                    <button onClick={handleCopy} className={`p-2 rounded-lg text-xs font-bold flex items-center gap-1 transition-all ${copied ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-blue-600'}`}>
                        {copied ? <Check size={14}/> : <Copy size={14}/>}
                        {copied ? 'Copied' : 'Copy'}
                    </button>
                </div>
            </div>
        </div>
    );
};

// 3. Modern Sub Card (FIX: Visual Separation & Progress)
const ModernSubCard = ({ sub, onExtend }) => {
    const [view, setView] = useState('xtream');
    const isExpired = sub.status.toLowerCase() === 'expired';
    const isPending = sub.status.toLowerCase() === 'pending';
    const statusColor = isExpired ? 'rose' : (isPending ? 'amber' : 'emerald');
    
    const creds = sub.credentials || {};
    
    return (
        <div className="bg-gradient-to-br from-white to-slate-50 rounded-[2.5rem] p-8 shadow-[0_20px_40px_-10px_rgba(0,0,0,0.1)] border border-slate-200 relative overflow-hidden transition-all hover:-translate-y-1 hover:shadow-2xl">
            {/* Status Indicator Line */}
            <div className={`absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-${statusColor}-500 to-${statusColor}-300`}></div>
            
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                <div>
                    <div className="flex items-center gap-3 mb-2">
                        <h2 className="text-2xl font-black text-slate-900 tracking-tight">{sub.planName}</h2>
                        <span className={`px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-${statusColor}-50 text-${statusColor}-600 border border-${statusColor}-100 flex items-center gap-1.5`}>
                            <span className={`w-1.5 h-1.5 rounded-full bg-${statusColor}-500 animate-pulse`}></span>
                            {sub.status}
                        </span>
                    </div>
                    <p className="text-slate-400 font-medium text-sm flex items-center gap-2">
                        <Clock size={16} className="text-slate-300" /> 
                        {isExpired ? 'Expired on' : 'Renews on'} <span className="text-slate-600 font-bold">{sub.nextBillingDate}</span>
                    </p>
                </div>
                
                <div className="flex items-center gap-4 w-full md:w-auto">
                    <div className="hidden md:block text-right">
                        <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Price</div>
                        <div className="text-xl font-black text-slate-900">{sub.price}</div>
                    </div>
                    <button 
                        onClick={() => onExtend(sub)}
                        className={`flex-1 md:flex-none py-3.5 px-6 rounded-2xl font-bold text-sm shadow-lg shadow-${statusColor}-500/20 text-white flex items-center justify-center gap-2 transition-all active:scale-95 bg-slate-900 hover:bg-slate-800`}
                    >
                        {isExpired ? <Zap size={18} className="text-yellow-400" /> : <CreditCard size={18} className="text-blue-300" />}
                        {isExpired ? 'Reactivate Now' : 'Extend Plan'}
                    </button>
                </div>
            </div>
            
            {/* FIXED: Progress Bar with Label */}
            {!isExpired && !isPending && (
                <div className="mb-6">
                     <div className="flex justify-between items-center mb-2 px-1">
                        <span className="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Usage Period</span>
                        <span className="text-[10px] font-bold text-slate-400">{sub.daysLeft} Days Remaining</span>
                     </div>
                     <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden shadow-inner">
                          <div className="h-full bg-gradient-to-r from-emerald-400 to-blue-500 rounded-full transition-all duration-1000" style={{width: `${Math.min(100, (sub.daysLeft / 30) * 100)}%`}}></div>
                     </div>
                </div>
            )}

            {/* FIXED: Section Separation (Background & Border) */}
            <div className="bg-slate-50 rounded-[1.5rem] border border-slate-200 p-5 shadow-sm mt-4">
                <div className="flex gap-2 p-1 bg-white rounded-2xl border border-slate-100 shadow-sm mb-6">
                    <button onClick={() => setView('xtream')} className={`flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all ${view === 'xtream' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'}`}>
                        <Server size={16} /> Xtream API
                    </button>
                    <button onClick={() => setView('m3u')} className={`flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all ${view === 'm3u' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'}`}>
                        <FileText size={16} /> M3U Playlist
                    </button>
                </div>

                <div className="px-1 pb-2 animate-fade-in">
                    {view === 'xtream' ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <SmartCopyField label="Username" value={creds.username} />
                            <SmartCopyField label="Password" value={creds.password} isSecret={true} />
                            <div className="md:col-span-2">
                                <SmartCopyField label="Host URL" value={creds.url} />
                            </div>
                            {creds.hostAlt && creds.hostAlt !== creds.url && (
                                <div className="md:col-span-2 mt-2 pt-4 border-t border-slate-200 border-dashed">
                                    <span className="text-[10px] font-bold text-amber-500 uppercase mb-2 block">Alternative Host</span>
                                    <SmartCopyField label="Alt Host URL" value={creds.hostAlt} />
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <SmartCopyField label="M3U Playlist URL" value={creds.m3uUrl} />
                            
                            {creds.attachments && creds.attachments.length > 0 && (
                                <div className="mt-4 bg-blue-50/50 rounded-xl p-4 border border-blue-100">
                                    <h4 className="text-xs font-black text-blue-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <Copy size={14} /> Attached Playlists
                                    </h4>
                                    <div className="space-y-3">
                                        {creds.attachments.map((url, idx) => (
                                            <SmartCopyField key={idx} label={`Panel #${idx + 1}`} value={url} />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
            
            {/* Setup Help */}
            <div className="mt-6 flex justify-center">
                 <a href="?tab=support" className="text-xs font-bold text-slate-400 hover:text-blue-600 flex items-center gap-1 transition-colors">
                     <HelpCircle size={14} /> Need help setting up? Visit Support Center
                 </a>
            </div>
        </div>
    );
};

// 4. Sports Card (Visual Upgrade: Contrast & Depth)
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

    return (
        <div 
            onClick={onClick}
            // UPGRADE: Flat white background, stronger shadow, stronger border
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
                                    // UPGRADE: Heavier VS, darker color
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
                            // UPGRADE: Darker pill background and border, darker icon
                            <div className="flex items-center gap-2 bg-slate-100/50 border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">
                                <Clock size={14} className="text-slate-500"/>
                                <CountdownTimer targetDate={event.startTime}/>
                            </div>
                        ) : (
                            <span className={`text-xs font-bold px-2 py-1 rounded-lg ${isLive ? 'text-emerald-600 bg-emerald-50' : 'text-slate-500 bg-slate-100'}`}>
                                {isLive && hasScore ? 'In Progress' : new Date(event.startTime).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}
                            </span>
                        )}
                    </div>
                </div>
                {/* UPGRADE: Action Button Contrast */}
                <button className={`w-10 h-10 rounded-2xl flex items-center justify-center transition-all ${isLive || hasChannels ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-100 border border-slate-200 text-slate-600 shadow-sm hover:bg-slate-900 hover:text-white hover:border-slate-900'}`}>
                    {isLive ? <Tv size={16}/> : <Star size={16} strokeWidth={2.5}/>}
                </button>
            </div>
        </div>
    );
};

// 4. Proof Upload Modal
const ProofUploadModal = ({ invoice, onClose }) => {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-fade-in">
            <div className="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-slide-up">
                <div className="p-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 className="font-bold text-lg text-slate-900">Upload Proof</h3>
                    <button onClick={onClose} className="p-2 hover:bg-slate-50 rounded-full text-slate-400 hover:text-slate-600"><X size={20}/></button>
                </div>
                <div className="p-6">
                    <p className="text-sm text-slate-500 mb-6">
                        Please upload a screenshot of your payment for <strong>{invoice.id}</strong>.
                    </p>
                    <form method="post" encType="multipart/form-data">
                        <input type="hidden" name="payment_proof_submit" value="1" />
                        <input type="hidden" name="payment_id" value={invoice.raw_id} />
                        
                        <div className="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center mb-6 hover:border-blue-500 transition-colors bg-slate-50 relative">
                            <input type="file" name="payment_proof[]" className="absolute inset-0 w-full h-full opacity-0 cursor-pointer" id="proof_file" required multiple />
                            <div className="mx-auto w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3 pointer-events-none">
                                <UploadCloud size={24} />
                            </div>
                            <span className="block font-bold text-slate-700 pointer-events-none">Click to Select File</span>
                            <span className="text-xs text-slate-400 pointer-events-none">JPG, PNG, PDF allowed</span>
                        </div>
                        
                        <button type="submit" className="w-full py-3.5 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition-all">
                            Submit Proof
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
};

// 5. Sidebar Item
const SidebarItem = ({ icon: Icon, label, active, onClick }) => (
    <button onClick={onClick} className={`flex items-center w-full p-3.5 mb-2 rounded-2xl transition-all duration-300 group relative overflow-hidden ${active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'}`}>
        <Icon size={20} className={`mr-3 ${active ? 'text-white' : 'group-hover:text-indigo-600 transition-colors'}`} />
        <span className="font-medium text-sm tracking-wide">{label}</span>
    </button>
);

// 6. Mobile Drawer
const Drawer = ({ isOpen, onClose, activeTab, setActiveTab, logoutUrl }) => {
    useEffect(() => { 
        document.body.style.overflow = isOpen ? 'hidden' : ''; 
        return () => { document.body.style.overflow = ''; }; 
    }, [isOpen]);
    
    if (!isOpen) return null;
    
    const menuItems = [
        {id:'dashboard',icon:LayoutDashboard,label:'Home'},
        {id:'subscription',icon:FileText,label:'My Subscriptions'},
        {id:'billing',icon:CreditCard,label:'Billing'},
        {id:'shop',icon:ShoppingBag,label:'Upgrade'},
        {id:'sports',icon:Trophy,label:'Sports Guide'},
        {id:'profile',icon:User,label:'My Profile'},
        {id:'support',icon:HelpCircle,label:'Support'}
    ];
    
    return (
        <div className="fixed inset-0 z-[100] md:hidden">
            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm animate-fade-in" onClick={onClose}></div>
            <div className="absolute right-0 top-0 bottom-0 w-72 bg-white shadow-2xl animate-slide-in flex flex-col">
                <div className="p-6 flex items-center justify-between border-b border-slate-100">
                    <span className="font-bold text-lg text-slate-900">Menu</span>
                    <button onClick={onClose} className="p-2 -mr-2 text-slate-400 hover:text-slate-900 rounded-full hover:bg-slate-50 transition-colors">
                        <X size={24}/>
                    </button>
                </div>
                <div className="flex-1 overflow-y-auto py-4">
                    <nav className="space-y-1 px-3">
                        {menuItems.map(item => (
                            <button key={item.id} onClick={()=>{setActiveTab(item.id);onClose();}} className={`flex items-center w-full p-3.5 rounded-xl transition-all ${activeTab===item.id?'bg-indigo-50 text-indigo-700 font-bold':'text-slate-600 font-medium hover:bg-slate-50'}`}>
                                <item.icon size={20} className={`mr-3.5 ${activeTab===item.id?'text-indigo-600':'text-slate-400'}`}/>
                                {item.label}
                            </button>
                        ))}
                    </nav>
                </div>
                <div className="p-4 border-t border-slate-100 bg-slate-50/50 pb-safe">
                    <a href={logoutUrl} className="flex items-center justify-center w-full p-3.5 rounded-xl border border-slate-200 bg-white text-rose-600 font-bold hover:bg-rose-50 transition-all shadow-sm">
                        <LogOut size={18} className="mr-2"/> Sign Out
                    </a>
                </div>
            </div>
        </div>
    );
};