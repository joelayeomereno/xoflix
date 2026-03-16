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

function IPTVDashboard() {
    const [activeTab, setActiveTab] = useState('dashboard');
    const [targetSub, setTargetSub] = useState(null); // [NEW] Extension Targeting State
    const [isReloading, setIsReloading] = useState(false); // [NEW] Session Reload State

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

    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [uploadInvoice, setUploadInvoice] = useState(null); 
    const [filterSport, setFilterSport] = useState('all');
    const [currentNewsIndex, setCurrentNewsIndex] = useState(0);
    const [billingPage, setBillingPage] = useState(1); 
    
    const [subPage, setSubPage] = useState(1);

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

    useEffect(() => {
        if (typeof USER_DATA !== 'undefined' && USER_DATA) {
            setProfileForm({
                name: USER_DATA.name || '',
                email: USER_DATA.email || '',
                phone: USER_DATA.phone || '',
                country: USER_DATA.country || '',
                currency: USER_DATA.currency || 'USD'
            });
        }
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

    const filteredSports = (typeof SPORTS_RAW !== 'undefined' ? SPORTS_RAW : []).filter(ev => {
        if (filterSport === 'all') return true;
        if (filterSport === 'live') { const s = new Date(ev.startTime); const n = new Date(); return (n >= s && n <= new Date(s.getTime() + 2*3600*1000)); }
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

    const handleNewsClick = (action, link) => {
        if (link && (link.startsWith('http') || link.startsWith('//'))) {
            window.open(link, '_blank');
        } else {
            setActiveTab(action);
        }
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
            // NOTE: Country is disabled for updates on frontend
            // fd.append('country', profileForm.country); 
            fd.append('currency', profileForm.currency); 
            
            if (userDefaults.id && userDefaults.auth_sig) {
                fd.append('auth_id', userDefaults.id);
                fd.append('auth_sig', userDefaults.auth_sig);
            }
            
            if(window.TV_CHECKOUT_NONCE) fd.append('_wpnonce', window.TV_CHECKOUT_NONCE);

            const res = await fetch(window.TV_AJAX_URL, { method: 'POST', body: fd, credentials: 'include' });
            
            if (!res.ok) throw new Error(`Server returned ${res.status}`);

            const json = await res.json();
            
            if (json.success) {
                // [NEW] Check for currency change and force reload
                if (profileForm.currency !== userDefaults.currency) {
                     setProfileMsg('Currency updated. Reloading session...');
                     setIsReloading(true);
                     
                     // Allow UI to update msg before reload
                     setTimeout(() => {
                         window.location.reload();
                     }, 1500);
                     return; // Stop execution here to keep loader active and prevent race conditions
                }

                setProfileMsg('Changes saved successfully.');
                if (typeof USER_DATA !== 'undefined') {
                    USER_DATA.name = profileForm.name;
                    USER_DATA.phone = profileForm.phone;
                    // Country not updated locally
                    USER_DATA.currency = profileForm.currency;
                }
                setTimeout(() => setProfileMsg(''), 3000);
            } else {
                setProfileMsg(json.data && json.data.message ? 'Error: ' + json.data.message : 'Error saving changes.');
            }
        } catch(e) {
            console.error(e);
            setProfileMsg('Connection error.');
        }
        setIsSavingProfile(false);
    };

    const newsItems = (typeof window.SERVER_NEWS !== 'undefined' && window.SERVER_NEWS.length > 0) ? window.SERVER_NEWS : [];

    // [FIX] Usage Progress Bar — uses real totalDays from sub object instead of hardcoded 30
    const UsageBar = ({ sub }) => {
        const totalDays = (sub && sub.totalDays > 0)
            ? sub.totalDays
            : (sub && sub.planName && /year|annual/i.test(sub.planName) ? 365
               : sub && sub.planName && /quarter|3.?mo/i.test(sub.planName) ? 90
               : 30);
        const daysLeft = (sub && sub.daysLeft) ? sub.daysLeft : 0;
        const percentage = Math.min(100, Math.max(0, (daysLeft / totalDays) * 100));
        let color = 'bg-emerald-500';
        if (daysLeft < 3) color = 'bg-rose-500';
        else if (daysLeft < 7) color = 'bg-amber-500';
        
        return (
            <div className="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
                <div className={`h-full ${color} transition-all duration-500`} style={{ width: `${percentage}%` }}></div>
            </div>
        );
    };

    return (
        <div className="flex h-screen bg-slate-50 text-slate-900 font-sans overflow-hidden">
            
            {/* [NEW] GLOBAL OVERLAY FOR SESSION RELOAD */}
            {isReloading && (
                <div className="fixed inset-0 z-[1000] bg-slate-900/50 backdrop-blur-md flex flex-col items-center justify-center animate-fade-in touch-none cursor-wait">
                    <div className="bg-white p-10 rounded-[2rem] shadow-2xl flex flex-col items-center transform transition-all scale-100 border border-slate-100 max-w-sm w-full mx-6 text-center">
                        <div className="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin mb-6"></div>
                        <h3 className="text-xl font-black text-slate-900 tracking-tight mb-2">Updating Session</h3>
                        <p className="text-slate-500 text-sm font-medium leading-relaxed">Applying your new currency settings and refreshing payment methods...</p>
                    </div>
                </div>
            )}

            <aside className="hidden md:flex flex-col w-72 bg-white border-r border-slate-200 z-30 h-full p-4">
                <div className="px-4 py-6 mb-4"><span className="text-xl font-black text-slate-900">XOFLIX<span className="text-blue-600"> TV</span></span></div>
                <nav className="space-y-1">
                    <SidebarItem icon={LayoutDashboard} label="Overview" active={activeTab==='dashboard'} onClick={() => setActiveTab('dashboard')} />
                    <SidebarItem icon={FileText} label="Subscriptions" active={activeTab==='subscription'} onClick={() => setActiveTab('subscription')} />
                    <SidebarItem icon={ShoppingBag} label="Purchase Plan" active={activeTab==='shop'} onClick={() => { setTargetSub(null); setActiveTab('shop'); }} />
                    <SidebarItem icon={Trophy} label="Sports Guide" active={activeTab==='sports'} onClick={() => setActiveTab('sports')} />
                    <SidebarItem icon={CreditCard} label="Billing" active={activeTab==='billing'} onClick={() => setActiveTab('billing')} />
                    <SidebarItem icon={User} label="Profile" active={activeTab==='profile'} onClick={() => setActiveTab('profile')} />
                    <SidebarItem icon={HelpCircle} label="Support" active={activeTab==='support'} onClick={() => setActiveTab('support')} />
                </nav>
            </aside>

            <main className="flex-1 flex flex-col relative overflow-hidden">
                <header className="h-16 md:h-20 px-4 md:px-10 flex items-center justify-between bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20">
                    <div className="md:hidden">
                        <button onClick={() => setIsDrawerOpen(true)} className="p-2 text-slate-600 hover:text-blue-600 transition-colors rounded-lg active:bg-slate-100">
                            <Menu size={28} />
                        </button>
                    </div>
                    <h2 className="text-xl font-bold capitalize md:block hidden">{activeTab}</h2>
                    <h2 className="text-xl font-bold capitalize md:hidden">XOFLIX TV</h2>
                    
                    <button onClick={() => setActiveTab('profile')} className="flex items-center gap-3 hover:bg-slate-50 p-2 rounded-xl transition-all group border border-transparent hover:border-slate-200" title="Go to Profile">
                        <span className="text-sm font-bold text-slate-900 hidden md:inline group-hover:text-blue-600 transition-colors">{profileForm.name || userDefaults.name}</span>
                        <img src={userDefaults.avatar || 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTIwIDIxdi0yYTQgNCAwIDAgMC00LTRIOGE0IDQgMCAwIDAtNCA0djIiLz48Y2lyY2xlIGN4PSIxMiIgY3k9IjciIHI9IjQiLz48L3N2Zz4='} className="w-10 h-10 rounded-full bg-slate-200 border-2 border-white shadow-sm object-cover" alt="Avatar"/>
                    </button>
                </header>

                <div className="flex-1 overflow-y-auto p-4 md:p-10 pb-24 md:pb-10 custom-scrollbar">
                    
                    {recentPayment && (
                        <div className="fixed bottom-6 right-6 z-40 bg-gradient-to-r from-slate-900 to-slate-800 text-white p-4 rounded-2xl shadow-2xl flex items-center justify-between gap-4 animate-slide-up w-[92vw] max-w-sm">
                            <div className="flex items-center gap-4 min-w-0">
                                <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center animate-pulse shrink-0"><CreditCard size={20} className="text-blue-300"/></div>
                                <div className="min-w-0">