<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
// ==========================================
// 3. MAIN DASHBOARD LOGIC (Lumina V2 - 100X Polished & Complete)
// ==========================================

function IPTVDashboard() {
    // --- STATE MANAGEMENT ---
    const [activeTab, setActiveTab] = useState('dashboard');
    const [targetSub, setTargetSub] = useState(null);
    const [isReloading, setIsReloading] = useState(false);
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [uploadInvoice, setUploadInvoice] = useState(null);
    const [filterSport, setFilterSport] = useState('all');
    const [currentNewsIndex, setCurrentNewsIndex] = useState(0);
    const [billingPage, setBillingPage] = useState(1);
    const [subPage, setSubPage] = useState(1);
    const [selectedSport, setSelectedSport] = useState(null);
    
    // Profile State
    const userDefaults = (typeof USER_DATA !== 'undefined' && USER_DATA) ? USER_DATA : { name: 'User', email: '', phone: '', country: 'US', currency: 'USD', avatar: '' };
    const [profileForm, setProfileForm] = useState({
        name: userDefaults.name || '',
        email: userDefaults.email || '',
        phone: userDefaults.phone || '',
        country: userDefaults.country || '',
        currency: userDefaults.currency || 'USD'
    });
    const [isSavingProfile, setIsSavingProfile] = useState(false);
    const [profileMsg, setProfileMsg] = useState('');

    // --- EFFECTS ---
    useEffect(() => {
        try {
            const params = new URLSearchParams(window.location.search || '');
            const tab = params.get('tab');
            const finish = params.get('finish_payment');
            if (tab && ['dashboard','subscription','shop','sports','billing','profile','support'].includes(tab)) {
                setActiveTab(tab);
            } else if (finish) {
                setActiveTab('billing'); 
            }
            if (tab || finish) {
                params.delete('tab');
                params.delete('finish_payment');
                const newQs = params.toString();
                const newUrl = window.location.pathname + (newQs ? ('?' + newQs) : '') + window.location.hash;
                window.history.replaceState({}, document.title, newUrl);
            }
        } catch(e) {}
    }, []);

    useEffect(() => {
        if (activeTab !== 'dashboard') return;
        if (!window.SERVER_NEWS || window.SERVER_NEWS.length === 0) return;
        const interval = setInterval(() => { 
            setCurrentNewsIndex(prev => (prev + 1) % window.SERVER_NEWS.length); 
        }, 6000); 
        return () => clearInterval(interval);
    }, [activeTab]);

    useEffect(() => {
        if (typeof INVOICES === 'undefined') return;
        const pending = INVOICES.find(inv => inv.needs_proof);
        if(pending && window.location.search.includes('payment_status=initiated')) {
            setUploadInvoice(pending);
        }
    }, []);

    // --- DATA ---
    const filteredSports = (typeof SPORTS_RAW !== 'undefined' ? SPORTS_RAW : []).filter(ev => {
        if (filterSport === 'all') return true;
        if (filterSport === 'live') { 
            const s = new Date(ev.startTime); 
            const n = new Date(); 
            return (n >= s && n <= new Date(s.getTime() + 2*3600*1000)); 
        }
        return ev.type && ev.type.toLowerCase() === filterSport;
    });

    const recentPayment = (typeof INVOICES !== 'undefined' ? INVOICES : []).find(inv => inv.attempt_recent && ['pending', 'AWAITING_PROOF'].includes(inv.raw_status));
    const invoicesList = typeof INVOICES !== 'undefined' ? INVOICES : [];
    const billingItemsPerPage = 10;
    const totalBillingPages = Math.ceil(invoicesList.length / billingItemsPerPage);
    const displayedInvoices = invoicesList.slice((billingPage - 1) * billingItemsPerPage, billingPage * billingItemsPerPage);
    const activeSubsList = typeof ACTIVE_SUBSCRIPTIONS !== 'undefined' ? ACTIVE_SUBSCRIPTIONS : [];
    const hasActiveSub = activeSubsList.length > 0;
    const subsPerPage = 1;
    const totalSubPages = Math.ceil(activeSubsList.length / subsPerPage);
    const displayedSubs = activeSubsList.slice((subPage - 1) * subsPerPage, subPage * subsPerPage);
    const newsItems = (typeof window.SERVER_NEWS !== 'undefined' && window.SERVER_NEWS.length > 0) ? window.SERVER_NEWS : [];

    // --- HANDLERS ---
    const handleNewsClick = (action, link) => {
        if (link && (link.startsWith('http') || link.startsWith('//'))) { window.open(link, '_blank'); } 
        else { setActiveTab(action); }
    };

    const handleExtend = (sub) => {
        setTargetSub(sub);
        setActiveTab('shop');
    };

    const handleProfileSave = async () => {
        setIsSavingProfile(true);
        setProfileMsg('');
        try {
            const fd = new FormData();
            fd.append('action', 'streamos_update_profile');
            fd.append('display_name', profileForm.name);
            fd.append('phone', profileForm.phone);
            fd.append('currency', profileForm.currency); 
            if (userDefaults.id && userDefaults.auth_sig) { fd.append('auth_id', userDefaults.id); fd.append('auth_sig', userDefaults.auth_sig); }
            if(window.TV_CHECKOUT_NONCE) fd.append('_wpnonce', window.TV_CHECKOUT_NONCE);

            const res = await fetch(window.TV_AJAX_URL, { method: 'POST', body: fd, credentials: 'include' });
            const json = await res.json();
            if (json.success) {
                if (profileForm.currency !== userDefaults.currency) {
                     setProfileMsg('Currency updated. Reloading session...');
                     setIsReloading(true);
                     setTimeout(() => { window.location.reload(); }, 1500);
                     return;
                }
                setProfileMsg('Changes saved successfully.');
                setTimeout(() => setProfileMsg(''), 3000);
            } else {
                setProfileMsg(json.data && json.data.message ? 'Error: ' + json.data.message : 'Error saving changes.');
            }
        } catch(e) {
            setProfileMsg('Connection error.');
        }
        setIsSavingProfile(false);
    };

    const UsageBar = ({ daysLeft }) => {
        const percentage = Math.min(100, Math.max(0, (daysLeft / 30) * 100)); 
        let color = 'bg-emerald-500'; if (daysLeft < 3) color = 'bg-rose-500'; else if (daysLeft < 7) color = 'bg-amber-500';
        return (<div className="w-full h-1.5 bg-slate-200 rounded-full mt-3 overflow-hidden shadow-inner"><div className={`h-full ${color} transition-all duration-500`} style={{ width: `${percentage}%` }}></div></div>);
    };

    return (
        <div className="flex h-screen bg-[#F0F1F5] text-slate-900 font-sans overflow-hidden selection:bg-indigo-500 selection:text-white">
            
            {/* Session Loader */}
            {isReloading && (
                <div className="fixed inset-0 z-[1000] bg-slate-900/50 backdrop-blur-md flex flex-col items-center justify-center animate-fade-in touch-none cursor-wait">
                    <div className="bg-white p-10 rounded-[2.5rem] shadow-2xl flex flex-col items-center transform transition-all scale-100 border border-slate-100 max-w-sm w-full mx-6 text-center relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                        <div className="w-16 h-16 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin mb-6 relative z-10"></div>
                        <h3 className="text-xl font-black text-slate-900 tracking-tight mb-2 relative z-10">Updating Session</h3>
                        <p className="text-slate-500 text-sm font-medium leading-relaxed relative z-10">Applying your new settings...</p>
                    </div>
                </div>
            )}

            {/* FLOATING SIDEBAR (Desktop) */}
            <aside className="hidden md:flex flex-col w-72 h-[96vh] my-[2vh] ml-[2vh] bg-white rounded-[2rem] shadow-[0_20px_50px_-12px_rgba(0,0,0,0.15)] z-30 p-6 relative overflow-hidden ring-1 ring-slate-200/60">
                <div className="absolute -top-20 -right-20 w-56 h-56 bg-gradient-to-br from-blue-400/5 to-indigo-400/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div className="relative z-10 mb-10 px-2 flex items-center gap-3">
                     {/* Tightened Icon: w-9 h-9 */}
                     <div className="w-9 h-9 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 shrink-0">
                        <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                     </div>
                     {/* Heavier Font */}
                     <span className="text-2xl font-black tracking-tighter text-slate-900 leading-none mt-0.5">XOFLIX<span className="text-indigo-600">TV</span></span>
                </div>
                
                <nav className="space-y-1.5 flex-1 relative z-10">
                    <SidebarItem icon={LayoutDashboard} label="Overview" active={activeTab==='dashboard'} onClick={() => setActiveTab('dashboard')} />
                    <SidebarItem icon={FileText} label="Subscriptions" active={activeTab==='subscription'} onClick={() => setActiveTab('subscription')} />
                    <SidebarItem icon={ShoppingBag} label="Purchase Plan" active={activeTab==='shop'} onClick={() => { setTargetSub(null); setActiveTab('shop'); }} />
                    <SidebarItem icon={Trophy} label="Sports Guide" active={activeTab==='sports'} onClick={() => setActiveTab('sports')} />
                    <SidebarItem icon={CreditCard} label="Billing" active={activeTab==='billing'} onClick={() => setActiveTab('billing')} />
                    <SidebarItem icon={User} label="Profile" active={activeTab==='profile'} onClick={() => setActiveTab('profile')} />
                    <SidebarItem icon={HelpCircle} label="Support" active={activeTab==='support'} onClick={() => setActiveTab('support')} />
                </nav>
                
                {/* User Mini Profile Footer */}
                <div className="mt-auto pt-6 border-t border-slate-100 relative z-10">
                    <div className="flex items-center gap-3 p-2.5 bg-slate-50 rounded-2xl hover:bg-slate-100 transition-colors cursor-pointer group border border-slate-100" onClick={() => setActiveTab('profile')}>
                         <img src={userDefaults.avatar} className="w-10 h-10 rounded-full bg-white shadow-sm border border-white object-cover" alt="User" />
                         <div className="min-w-0">
                             <p className="text-sm font-bold text-slate-900 truncate group-hover:text-indigo-700 transition-colors">{userDefaults.name}</p>
                             <p className="text-xs text-slate-500 truncate font-medium">{hasActiveSub ? 'Premium Member' : 'Free Account'}</p>
                         </div>
                    </div>
                </div>
            </aside>

            <main className="flex-1 flex flex-col relative overflow-hidden h-full">
                
                {/* Floating Header - TIGHTENED (py-2 md:py-3) */}
                <header className="px-6 md:px-10 py-2 md:py-3 flex items-center justify-between z-20 shrink-0">
                    <div className="md:hidden">
                        <button onClick={() => setIsDrawerOpen(true)} className="p-2 bg-white text-slate-600 shadow-sm border border-slate-200/50 hover:text-indigo-600 transition-all rounded-xl active:scale-95">
                            <Menu size={24} />
                        </button>
                    </div>
                    
                    <div className="hidden md:block">
                        <h2 className="text-2xl font-black text-slate-900 tracking-tight capitalize leading-none mb-0.5">{activeTab === 'shop' ? 'Premium Store' : activeTab}</h2>
                        {/* Perfect Casing */}
                        <p className="text-slate-500 text-xs font-bold uppercase tracking-widest">Welcome to Xoflix Reborn</p>
                    </div>
                    <div className="md:hidden">
                         <span className="text-lg font-black tracking-tight text-slate-900">XOFLIX<span className="text-indigo-600"> TV</span></span>
                    </div>
                    
                    <div className="flex items-center gap-3">
                        <div className="hidden md:flex items-center gap-2 px-3 py-1.5 bg-white rounded-full shadow-sm border border-slate-200 text-[10px] font-bold text-slate-600">
                             <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div> Operational
                        </div>
                        {/* Smaller Header Icon - Changed to Profile */}
                        <button onClick={() => setActiveTab('profile')} className="w-9 h-9 rounded-full bg-white shadow-sm border border-slate-200/50 text-slate-400 hover:text-indigo-600 hover:shadow-md transition-all flex items-center justify-center">
                            <User size={18} />
                        </button>
                    </div>
                </header>

                <div className="flex-1 overflow-y-auto px-4 md:px-10 pb-24 md:pb-10 custom-scrollbar scroll-smooth">
                    
                    {/* Recent Payment Floating Action */}
                    {recentPayment && (
                        <div className="fixed bottom-6 right-6 z-50 bg-slate-900 text-white p-5 rounded-[1.5rem] shadow-2xl flex items-center justify-between gap-5 animate-slide-up w-[92vw] max-w-sm ring-1 ring-white/10">
                            <div className="flex items-center gap-4 min-w-0">
                                <div className="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center animate-pulse shrink-0 backdrop-blur-md"><CreditCard size={24} className="text-indigo-300"/></div>
                                <div className="min-w-0">
                                    <p className="font-bold leading-tight text-sm">Action Required</p>
                                    <p className="text-xs text-slate-400 truncate mt-0.5">Order #{recentPayment.id} Pending</p>
                                </div>
                            </div>
                            <button onClick={() => setUploadInvoice(recentPayment)} className="px-4 py-2.5 bg-white text-slate-900 font-bold rounded-xl text-xs hover:bg-indigo-50 transition-colors shrink-0 shadow-lg">Upload Proof</button>
                        </div>
                    )}

                    {/* === VIEW 1: DASHBOARD (PREMIUM 100X) === */}
                    {activeTab === 'dashboard' && (
                        <div className="max-w-6xl mx-auto space-y-10 animate-slide-up pb-10">
                            <AlertBanner alerts={window.USER_ALERTS} setActiveTab={setActiveTab} handleExtend={handleExtend} />
                            
                            {newsItems.length > 0 && (
                            <div className="relative w-full overflow-hidden rounded-[2.5rem] shadow-[0_25px_50px_-12px_rgba(79,70,229,0.3)] bg-white group ring-1 ring-slate-100/50">
                                <div className="flex transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] h-72 md:h-80" style={{ transform: `translateX(-${currentNewsIndex * 100}%)` }}>
                                    {newsItems.map((news) => {
                                        const styles = getSmartStyles(news.color, news.isHex);
                                        const bgStyle = news.isHex ? { backgroundColor: news.color } : {};
                                        const bgClass = news.isHex ? '' : `bg-gradient-to-br ${news.color}`;
                                        
                                        return (
                                            <div key={news.id} className={`w-full flex-shrink-0 h-full ${bgClass} p-8 md:p-16 relative flex items-center`} style={bgStyle}>
                                                <div className={`absolute inset-0 ${styles.overlay}`}></div>
                                                <div className="relative z-10 max-w-3xl">
                                                    <span className={`px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border mb-6 inline-block backdrop-blur-md shadow-sm ${styles.badge}`}>New Update</span>
                                                    <h2 className={`text-3xl md:text-5xl font-black mb-4 leading-tight tracking-tight ${styles.title}`}>{news.title}</h2>
                                                    <p className={`text-base md:text-xl mb-8 leading-relaxed max-w-xl font-medium line-clamp-2 md:line-clamp-none ${styles.desc}`}>{news.description}</p>
                                                    {/* MOBILE OPTIMIZED BUTTON: Reduced width, padding and size for mobile */}
                                                    <button onClick={() => handleNewsClick(news.action, news.link)} className="w-fit px-4 py-2 md:px-8 md:py-4 bg-white text-slate-900 rounded-xl md:rounded-2xl font-bold hover:bg-slate-50 hover:scale-105 transition-all flex items-center gap-2 shadow-xl shadow-black/10 active:scale-95 text-xs md:text-base group">
                                                        {news.buttonText} <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="absolute bottom-8 left-0 right-0 flex justify-center gap-3 z-20">
                                    {newsItems.map((_, idx) => (
                                        <button key={idx} onClick={() => setCurrentNewsIndex(idx)} className={`h-1.5 rounded-full transition-all duration-500 backdrop-blur-sm ${currentNewsIndex === idx ? 'w-12 bg-white shadow-lg' : 'w-2 bg-white/30 hover:bg-white/60'}`} aria-label={`Go to slide ${idx + 1}`} />
                                    ))}
                                </div>
                            </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
                                {/* ACTIVE PLANS HERO CARD (Shadow Depth Upgrade) */}
                                <div className="bg-white rounded-[2.5rem] p-10 md:p-12 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.08)] hover:shadow-[0_25px_70px_-15px_rgba(79,70,229,0.15)] relative overflow-hidden group transition-all duration-500 border border-slate-200 flex flex-col h-full">
                                    
                                    <div className="absolute top-0 right-0 w-[400px] h-[400px] bg-gradient-to-br from-indigo-500/5 to-purple-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 group-hover:scale-110 transition-transform duration-1000"></div>

                                    <div className="flex justify-between items-start mb-10 relative z-10">
                                        <div>
                                            <h3 className="text-3xl font-black text-slate-900 tracking-tighter mb-2">My Plan</h3>
                                            <p className="text-slate-500 font-medium text-sm">{activeSubsList.length > 0 ? 'Premium Access Active' : 'No active subscription'}</p>
                                        </div>
                                        
                                        {/* STRAIGHT ICON: Removed group-hover:rotate-12 */}
                                        <div className={`w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-xl transition-transform ${activeSubsList.length > 0 ? 'bg-slate-900 text-white shadow-slate-900/20' : 'bg-slate-50 text-slate-300'}`}>
                                            {activeSubsList.length > 0 ? <Zap size={28} fill="currentColor"/> : <Lock size={28}/>}
                                        </div>
                                    </div>

                                    <div className="flex-1 relative z-10">
                                        {activeSubsList.length === 0 ? (
                                            <div className="text-center py-8">
                                                <p className="text-slate-400 text-sm font-medium mb-8">Unlock 25,000+ channels today.</p>
                                                <button onClick={() => { setTargetSub(null); setActiveTab('shop'); }} className="w-full py-4 border-2 border-dashed border-slate-200 rounded-2xl text-slate-500 font-bold hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all flex items-center justify-center gap-2"><ShoppingBag size={18}/> View Plans</button>
                                            </div>
                                        ) : (
                                            <div className="space-y-4">
                                                {activeSubsList.slice(0, 1).map(sub => (
                                                    <div key={sub.id}>
                                                        <div className="flex items-center gap-3 mb-8">
                                                            <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-[11px] font-black uppercase tracking-widest border border-indigo-100 shadow-sm">
                                                                <span className="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span> Active
                                                            </div>
                                                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">#{sub.id}</span>
                                                        </div>
                                                        
                                                        <div className="grid grid-cols-2 gap-4 mb-8">
                                                            {/* High Contrast Inner Cards */}
                                                            <div className="p-6 bg-slate-100/80 rounded-3xl border border-slate-200">
                                                                <div className="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Package</div>
                                                                <div className="text-slate-900 font-black text-xl leading-tight">{sub.planName}</div>
                                                            </div>
                                                            <div className="p-6 bg-slate-100/80 rounded-3xl border border-slate-200">
                                                                <div className="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Time Left</div>
                                                                <div className={`font-black text-xl leading-tight ${sub.daysLeft < 3 ? 'text-rose-500' : 'text-emerald-600'}`}>{sub.daysLeft} Days</div>
                                                            </div>
                                                        </div>

                                                        <div className="mb-10">
                                                            <div className="flex justify-between items-end mb-3">
                                                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Usage Period</span>
                                                                <span className="text-xs font-bold text-slate-600">{Math.min(100, Math.max(0, (sub.daysLeft / 30) * 100)).toFixed(0)}% Remaining</span>
                                                            </div>
                                                            <div className="w-full h-4 bg-slate-100 rounded-full overflow-hidden shadow-inner"><div className={`h-full rounded-full transition-all duration-1000 ${sub.daysLeft < 3 ? 'bg-rose-500' : 'bg-gradient-to-r from-emerald-400 to-indigo-500'}`} style={{width: `${Math.min(100, (sub.daysLeft / 30) * 100)}%`}}></div></div>
                                                        </div>
                                                        <button onClick={() => handleExtend(sub)} className="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold shadow-xl shadow-slate-900/20 hover:bg-indigo-600 hover:shadow-indigo-500/30 transition-all active:scale-95 flex items-center justify-center gap-2 group text-sm uppercase tracking-wide">
                                                            {sub.daysLeft < 3 ? 'Renew Now' : 'Extend Subscription'} <ArrowRight size={18} className="group-hover:translate-x-1 transition-transform"/>
                                                        </button>
                                                    </div>
                                                ))}
                                                
                                                {/* UPGRADED: +13 other subscriptions (Higher Contrast & Opacity) */}
                                                {activeSubsList.length > 1 && (
                                                    <button onClick={() => setActiveTab('subscription')} className="w-full text-center text-xs font-bold text-slate-500 hover:text-indigo-600 mt-6 opacity-90 transition-opacity hover:opacity-100">
                                                        + {activeSubsList.length - 1} other subscriptions
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* RECENT ACTIVITY HERO CARD */}
                                <div className="bg-white p-10 md:p-12 rounded-[2.5rem] border border-slate-200/80 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.12)] hover:shadow-[0_25px_70px_-15px_rgba(245,158,11,0.15)] h-full flex flex-col relative overflow-hidden transition-all duration-500">
                                    <div className="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl"></div>
                                    
                                    <div className="flex justify-between items-center mb-10 relative z-10">
                                        <h3 className="text-2xl font-black text-slate-900 tracking-tighter">Recent Activity</h3>
                                        <button onClick={() => setActiveTab('billing')} className="w-10 h-10 flex items-center justify-center rounded-2xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors shadow-sm border border-slate-200/50">
                                            <ArrowRight size={20} />
                                        </button>
                                    </div>

                                    <div className="flex-1 relative z-10">
                                        {invoicesList.length === 0 ? (
                                            <div className="h-full flex flex-col items-center justify-center text-center py-10 opacity-50">
                                                <div className="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6 shadow-inner"><Check size={32} className="text-slate-300"/></div>
                                                <p className="text-sm font-bold text-slate-400">All caught up!</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-4">
                                                {invoicesList.slice(0, 4).map(inv => {
                                                    const isPending = ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(inv.raw_status);
                                                    return (
                                                        <div key={inv.id} className={`flex justify-between items-center p-5 rounded-3xl border transition-all ${isPending ? 'bg-amber-50/50 border-amber-100 hover:bg-amber-50 shadow-sm' : 'bg-white border-slate-100 hover:border-indigo-100 hover:shadow-md'}`}>
                                                            <div className="flex items-center gap-5">
                                                                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center text-sm font-black shadow-sm ${isPending ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'}`}>{isPending ? '!' : <Check size={18} strokeWidth={4}/>}</div>
                                                                <div>
                                                                    <p className="font-bold text-sm text-slate-900">{inv.plan}</p>
                                                                    {/* FIXED: Replaced literal bullet with HTML entity & Safe amount render */}
                                                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1 flex items-center gap-2">
                                                                        {inv.date} 
                                                                        <span className="text-slate-300">&bull;</span>
                                                                        <span dangerouslySetInnerHTML={{ __html: inv.amount }}></span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            {isPending ? (
                                                                inv.needs_proof ? ( <button onClick={() => setUploadInvoice(inv)} className="px-5 py-2.5 bg-amber-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all transform hover:-translate-y-0.5">Upload</button> ) : ( <span className="text-[10px] font-bold text-amber-600 bg-amber-100 px-3 py-1.5 rounded-full uppercase tracking-wider">Pending</span> )
                                                            ) : ( <span className="text-xs font-bold text-slate-300">Paid</span> )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* 2. SHOP VIEW */}
                    {activeTab === 'shop' && <div className="max-w-6xl mx-auto"><ShopTab initialTargetSub={targetSub} /></div>}

                    {/* 3. SUBSCRIPTIONS VIEW */}
                    {activeTab === 'subscription' && (
                        <div className="max-w-5xl mx-auto space-y-6 animate-slide-up pt-8">
                            {activeSubsList.length === 0 && <div className="text-center py-20 bg-white rounded-[2rem] border border-slate-100"><p className="text-slate-500 mb-4">No active subscriptions.</p><button onClick={() => setActiveTab('shop')} className="text-blue-600 font-bold hover:underline">Buy a Plan</button></div>}
                            {displayedSubs.map(sub => (<ModernSubCard key={sub.id} sub={sub} onExtend={handleExtend} />))}
                            {totalSubPages > 1 && (<div className="flex items-center justify-center gap-4 py-4 bg-white/50 backdrop-blur-sm rounded-xl border border-slate-100"><button onClick={() => setSubPage(p => Math.max(1, p - 1))} disabled={subPage === 1} className="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-600 font-bold disabled:opacity-50 transition-colors"><ArrowLeft size={16} /> Previous</button><span className="text-xs font-black uppercase text-slate-400 tracking-wider">Page {subPage} of {totalSubPages}</span><button onClick={() => setSubPage(p => Math.min(totalSubPages, p + 1))} disabled={subPage === totalSubPages} className="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-600 font-bold disabled:opacity-50 transition-colors">Next <ArrowRight size={16} /></button></div>)}
                        </div>
                    )}

                    {/* 4. SPORTS VIEW */}
                    {activeTab === 'sports' && (
                        <div className="max-w-7xl mx-auto space-y-8 animate-slide-up">
                            {/* Updated Header: Items Center to ensure horizontal alignment on mobile */}
                            <div className="flex flex-col md:flex-row justify-between items-center md:items-end gap-6">
                                <div className="text-center md:text-left">
                                    <h1 className="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-2">Live Sports Guide</h1>
                                    <p className="text-slate-500 font-medium">Real-time schedules for top leagues.</p>
                                </div>
                                <div className="flex gap-2 overflow-x-auto pb-2 w-full md:w-auto no-scrollbar justify-center md:justify-end">
                                    {[ {id:'all',label:'All Events'}, {id:'live',label:'Live Now'}, {id:'soccer',label:'Football'}, {id:'nba',label:'Basketball'}, {id:'ufc',label:'Fighting'} ].map(f => (
                                        <button key={f.id} onClick={() => setFilterSport(f.id)} className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all border ${filterSport === f.id ? 'bg-slate-900 text-white border-slate-900 shadow-lg' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'}`}>{f.label}</button>
                                    ))}
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                {filteredSports.length === 0 && <div className="col-span-full py-20 text-center bg-white rounded-[2rem] border border-slate-200"><p className="text-slate-500">No scheduled events found.</p></div>}
                                {filteredSports.map(ev => (<SportsCard key={ev.id} event={ev} onClick={() => setSelectedSport(ev)} />))}
                            </div>
                            {selectedSport && (<SportsModal event={selectedSport} onClose={() => setSelectedSport(null)} />)}
                        </div>
                    )}

                    {/* 5. BILLING VIEW */}
                    {activeTab === 'billing' && (
                        <div className="max-w-5xl mx-auto space-y-6 animate-slide-up">
                            <h1 className="text-3xl font-bold text-slate-900">Billing History</h1>
                            <div className="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                                <table className="w-full text-left">
                                    <thead className="bg-slate-50 border-b border-slate-100">
                                        <tr><th className="p-5 text-xs font-bold text-slate-400 uppercase">ID</th><th className="p-5 text-xs font-bold text-slate-400 uppercase">Date</th><th className="p-5 text-xs font-bold text-slate-400 uppercase">Amount</th><th className="p-5 text-xs font-bold text-slate-400 uppercase">Status</th><th className="p-5"></th></tr>
                                    </thead>
                                    <tbody>
                                        {displayedInvoices.map(inv => (
                                            <tr key={inv.id} className="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                                                <td className="p-5 font-mono text-sm font-bold">{inv.id}</td>
                                                <td className="p-5 text-sm text-slate-500">{inv.date}</td>
                                                <td className="p-5 font-bold" dangerouslySetInnerHTML={{ __html: inv.amount }}></td>
                                                <td className="p-5"><StatusBadge status={inv.status}/></td>
                                                <td className="p-5 text-right">{(inv.needs_proof || inv.has_gateway_link) && ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(inv.raw_status) ? (inv.has_gateway_link && !inv.attempt_recent ? (<a href={inv.payment_link} target="_blank" className="text-xs font-bold text-blue-600 hover:underline">Pay Now</a>) : (<button onClick={() => setUploadInvoice(inv)} className="text-xs font-bold text-blue-600 hover:underline">{inv.has_gateway_link ? "Paid? Upload Proof" : "Upload Proof"}</button>)) : null}</td>
                                            </tr>
                                        ))}
                                        {invoicesList.length === 0 && <tr><td colSpan="5" className="p-8 text-center text-slate-400">No invoices found.</td></tr>}
                                    </tbody>
                                </table>
                                {totalBillingPages > 1 && (<div className="p-4 border-t border-slate-100 flex justify-between items-center bg-slate-50/30"><button onClick={() => setBillingPage(p => Math.max(1, p - 1))} disabled={billingPage === 1} className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold disabled:opacity-50 hover:bg-slate-50 transition-colors">Previous</button><span className="text-xs font-bold text-slate-500">Page {billingPage} of {totalBillingPages}</span><button onClick={() => setBillingPage(p => Math.min(totalBillingPages, p + 1))} disabled={billingPage === totalBillingPages} className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold disabled:opacity-50 hover:bg-slate-50 transition-colors">Next</button></div>)}
                            </div>
                        </div>
                    )}

                    {/* 6. PROFILE VIEW */}
                    {activeTab === 'profile' && (
                        <div className="max-w-4xl mx-auto animate-slide-up pb-10 space-y-8">
                            <div className="bg-gradient-to-r from-slate-900 to-slate-800 rounded-[2.5rem] p-8 md:p-12 text-white shadow-xl relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
                                <div className="relative z-10 flex flex-col md:flex-row items-center gap-8">
                                    <div className="relative group"><img src={userDefaults.avatar} alt={userDefaults.name} className="w-32 h-32 rounded-full border-4 border-white/20 shadow-2xl object-cover" /><div className="absolute bottom-2 right-2 bg-emerald-500 w-6 h-6 rounded-full border-4 border-slate-900"></div></div>
                                    <div className="text-center md:text-left"><h1 className="text-3xl md:text-4xl font-black tracking-tight mb-2">{profileForm.name || userDefaults.name}</h1><p className="text-slate-400 font-medium mb-4">{profileForm.email || userDefaults.email}</p><div className="inline-flex gap-2">{hasActiveSub ? (<span className="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full text-xs font-bold uppercase tracking-wider">Premium Member</span>) : (<span className="px-3 py-1 bg-slate-700 text-slate-300 border border-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">Free Account</span>)}<span className="px-3 py-1 bg-white/10 text-white rounded-full text-xs font-bold uppercase tracking-wider">Joined {userDefaults.joined || 'Recently'}</span></div></div>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm h-full">
                                    <h3 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2"><div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><User size={18}/></div>Edit Personal Details</h3>
                                    <div className="space-y-5">
                                        <div><label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Full Name</label><input type="text" value={profileForm.name} onChange={(e) => setProfileForm({...profileForm, name: e.target.value})} className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" placeholder="Your full name" /></div>
                                        <div><label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Email (Read Only)</label><div className="relative"><input type="email" value={profileForm.email} readOnly style={{ color: '#000000', backgroundColor: '#e2e8f0', opacity: 1, fontWeight: '700' }} className="w-full px-4 py-3 bg-gray-200 border border-slate-200 rounded-xl text-sm font-bold text-black cursor-not-allowed opacity-100" /><div className="absolute right-3 top-3 text-emerald-500 text-xs font-bold bg-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1"><Check size={10} strokeWidth={4} /> Verified</div></div></div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div><label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Phone</label><input type="tel" value={profileForm.phone} onChange={(e) => setProfileForm({...profileForm, phone: e.target.value})} className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" placeholder="+1..." /></div>
                                            <div><label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Location</label><LockedCountryDisplay countryCode={profileForm.country} /></div>
                                        </div>
                                        <div><label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Preferred Currency</label><select className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" value={profileForm.currency} onChange={(e) => setProfileForm({...profileForm, currency: e.target.value})}>{['USD','EUR','GBP','NGN','GHS','KES','ZAR','INR','AED','BRL','CAD','AUD'].map(c => <option key={c} value={c}>{c}</option>)}</select></div>
                                        {profileMsg && <p className={`text-xs font-bold text-center ${profileMsg.includes('Error') || profileMsg.includes('Connection') ? 'text-rose-500' : 'text-emerald-500'}`}>{profileMsg}</p>}
                                        <button onClick={handleProfileSave} disabled={isSavingProfile} className="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-lg transition-all disabled:opacity-70 flex justify-center items-center gap-2">{isSavingProfile ? <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> : 'Save Changes'}</button>
                                    </div>
                                </div>
                                <div className="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm h-full flex flex-col">
                                    <h3 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2"><div className="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center"><Lock size={18}/></div>Security</h3>
                                    <div className="space-y-4 mb-8 flex-1">
                                        <div className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl"><div><p className="font-bold text-sm">Email Notifications</p><p className="text-xs text-slate-500">Order updates & news</p></div><div className="w-10 h-6 bg-emerald-500 rounded-full relative"><div className="w-4 h-4 bg-white rounded-full absolute top-1 right-1"></div></div></div>
                                        <div className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl"><div><p className="font-bold text-sm">Password</p><p className="text-xs text-slate-500">Managed via logout</p></div><button onClick={() => window.location.href='/forgot-password'} className="text-xs font-bold text-blue-600 hover:underline">Reset</button></div>
                                    </div>
                                    <a href="<?php echo wp_logout_url(home_url()); ?>" className="w-full py-4 bg-rose-50 text-rose-600 font-bold rounded-xl text-sm hover:bg-rose-100 transition-colors flex items-center justify-center gap-2"><LogOut size={18}/> Sign Out of Account</a>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'support' && (
                        <div className="flex flex-col items-center justify-center h-[60vh] text-center animate-slide-up">
                            <div className="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-6"><HelpCircle className="text-slate-300" size={32} /></div>
                            <h3 className="text-xl font-bold text-slate-900 mb-2">Support Center</h3>
                            <p className="text-slate-500 font-medium max-w-sm mb-8">Need help? Our team is available 24/7 via WhatsApp and Email.</p>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 w-full max-w-md">
                                {typeof window.SUPPORT_CONFIG !== 'undefined' && window.SUPPORT_CONFIG.email && (<a href={`mailto:${window.SUPPORT_CONFIG.email}`} className="flex items-center justify-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl hover:border-blue-500 hover:shadow-md transition-all group"><div className="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center text-blue-600"><FileText size={18}/></div><div className="text-left"><p className="font-bold text-slate-900">Email Support</p><p className="text-xs text-slate-500">Response in 2h</p></div></a>)}
                                {typeof window.SUPPORT_CONFIG !== 'undefined' && window.SUPPORT_CONFIG.whatsapp && (<a href={`https://wa.me/${window.SUPPORT_CONFIG.whatsapp}`} target="_blank" className="flex items-center justify-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl hover:border-emerald-500 hover:shadow-md transition-all group"><div className="w-10 h-10 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600"><div className="w-4 h-4 bg-current rounded-full"></div></div><div className="text-left"><p className="font-bold text-slate-900">Live Chat</p><p className="text-xs text-slate-500">WhatsApp Online</p></div></a>)}
                            </div>
                        </div>
                    )}
                </div>

                <BottomNav activeTab={activeTab} setActiveTab={setActiveTab} />
                <Drawer isOpen={isDrawerOpen} onClose={() => setIsDrawerOpen(false)} activeTab={activeTab} setActiveTab={setActiveTab} logoutUrl="<?php echo wp_logout_url(home_url()); ?>" />
                {uploadInvoice && <ProofUploadModal invoice={uploadInvoice} onClose={() => setUploadInvoice(null)} />}
            </main>
        </div>
    );
}