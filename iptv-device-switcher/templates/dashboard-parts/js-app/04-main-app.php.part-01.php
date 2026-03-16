// ==========================================
// 4. MAIN APP CONTAINER
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

// 2. Smart Copy Field (NEW)
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
            <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-1">{label}</label>
            <div className="relative flex items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden transition-all group-hover:border-blue-300 group-hover:shadow-sm group-hover:bg-white">
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
                    <button onClick={handleCopy} className={`p-2 rounded-lg text-xs font-bold flex items-center gap-1 transition-all ${copied ? 'bg-emerald-100 text-emerald-600' : 'bg-white border border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600'}`}>
                        {copied ? <Check size={14}/> : <Copy size={14}/>}
                        {copied ? 'Copied' : 'Copy'}
                    </button>
                </div>
            </div>
        </div>
    );
};

// 3. Modern Sub Card
// [FIX] Progress bar uses real totalDays from sub object instead of hardcoded 30
const ModernSubCard = ({ sub, onExtend }) => {
    const [view, setView] = useState('xtream');
    const isExpired = sub.status.toLowerCase() === 'expired';
    const isPending = sub.status.toLowerCase() === 'pending';
    const statusColor = isExpired ? 'rose' : (isPending ? 'amber' : 'emerald');
    
    const creds = sub.credentials || {};

    const totalDays = (sub.totalDays > 0)
        ? sub.totalDays
        : (sub.planName && /year|annual/i.test(sub.planName) ? 365
           : sub.planName && /quarter|3.?mo/i.test(sub.planName) ? 90
           : 30);

    const barPercent = Math.min(100, Math.max(0, (sub.daysLeft / totalDays) * 100));
    
    return (
        <div className="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100 relative overflow-hidden transition-all hover:-translate-y-1 hover:shadow-2xl">
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
                        {!isExpired && <span className="text-slate-300">|</span>}
                        {!isExpired && <span className="text-emerald-600 font-bold">{sub.daysLeft} days remaining</span>}
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
            
            {/* Visual Progress for Active Subs */}
            {!isExpired && !isPending && (
                <div className="w-full bg-slate-100 h-2 rounded-full mb-8 overflow-hidden">
                    <div
                        className="h-full bg-gradient-to-r from-emerald-400 to-blue-500 rounded-full transition-all duration-1000"
                        style={{width: `${barPercent}%`}}
                    ></div>
                </div>
            )}

            {/* Credentials Section */}
            <div className="bg-slate-50/50 rounded-[1.5rem] border border-slate-100 p-2">
                <div className="flex gap-2 p-1 bg-white rounded-2xl border border-slate-100 shadow-sm mb-6">
                    <button onClick={() => setView('xtream')} className={`flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all ${view === 'xtream' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'}`}>
                        <Server size={16} /> Xtream API
                    </button>
                    <button onClick={() => setView('m3u')} className={`flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all ${view === 'm3u' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'}`}>
                        <FileText size={16} /> M3U Playlist
                    </button>
                </div>

                <div className="px-4 pb-4 animate-fade-in">
                    {view === 'xtream' ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <SmartCopyField label="Username" value={creds.username} />
                            <SmartCopyField label="Password" value={creds.password} isSecret={true} />
                            <div className="md:col-span-2">
                                <SmartCopyField label="Host URL" value={creds.url} />
                            </div>
                            {/* Alternative Host if exists */}
                            {creds.hostAlt && creds.hostAlt !== creds.url && (
                                <div className="md:col-span-2 mt-2 pt-4 border-t border-slate-200 border-dashed">
                                    <span className="text-[10px] font-bold text-amber-500 uppercase mb-2 block">Alternative Host (Try if above fails)</span>
                                    <SmartCopyField label="Alt Host URL" value={creds.hostAlt} />
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <SmartCopyField label="M3U Playlist URL" value={creds.m3uUrl} />
                            
                            {/* Attachments */}
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

const ProofUploadModal = ({ invoice, onClose }) => {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-fade-in">
            <div className="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-slide-up">
                <div className="p-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 className="font-bold text-lg text-slate-900">Upload Proof</h3>