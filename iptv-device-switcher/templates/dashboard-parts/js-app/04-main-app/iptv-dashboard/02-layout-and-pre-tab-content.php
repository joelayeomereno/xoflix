<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
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
        if (link && (link.startsWith('http') || link.startsWith('//'))) { window.open(link, '_blank'); } else { setActiveTab(action); }
    };
    const handleExtend = (sub) => { setTargetSub(sub); setActiveTab('shop'); };
    const handleProfileSave = async () => {
        setIsSavingProfile(true); setProfileMsg('');
        try {
            const fd = new FormData();
            fd.append('action', 'streamos_update_profile');
            fd.append('display_name', profileForm.name); fd.append('phone', profileForm.phone); fd.append('currency', profileForm.currency); 
            if (userDefaults.id && userDefaults.auth_sig) { fd.append('auth_id', userDefaults.id); fd.append('auth_sig', userDefaults.auth_sig); }
            if(window.TV_CHECKOUT_NONCE) fd.append('_wpnonce', window.TV_CHECKOUT_NONCE);
            const res = await fetch(window.TV_AJAX_URL, { method: 'POST', body: fd, credentials: 'include' });
            if (!res.ok) throw new Error(`Server returned ${res.status}`);
            const json = await res.json();
            if (json.success) {
                if (profileForm.currency !== userDefaults.currency) { setProfileMsg('Currency updated. Reloading session...'); setIsReloading(true); setTimeout(() => { window.location.reload(); }, 1500); return; }
                setProfileMsg('Changes saved successfully.');
                if (typeof USER_DATA !== 'undefined') { USER_DATA.name = profileForm.name; USER_DATA.phone = profileForm.phone; USER_DATA.currency = profileForm.currency; }
                setTimeout(() => setProfileMsg(''), 3000);
            } else { setProfileMsg(json.data && json.data.message ? 'Error: ' + json.data.message : 'Error saving changes.'); }
        } catch(e) { console.error(e); setProfileMsg('Connection error.'); }
        setIsSavingProfile(false);
    };

    const newsItems = (typeof window.SERVER_NEWS !== 'undefined' && window.SERVER_NEWS.length > 0) ? window.SERVER_NEWS : [];
    const UsageBar = ({ daysLeft }) => {
        const percentage = Math.min(100, Math.max(0, (daysLeft / 30) * 100)); 
        let color = 'bg-emerald-500'; if (daysLeft < 3) color = 'bg-rose-500'; else if (daysLeft < 7) color = 'bg-amber-500';
        return (<div className="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden"><div className={`h-full ${color} transition-all duration-500`} style={{ width: `${percentage}%` }}></div></div>);
    };

    return (
        <div className="flex h-screen bg-[#F3F4F6] text-slate-900 font-sans overflow-hidden selection:bg-indigo-500 selection:text-white">
            
            {isReloading && (
                <div className="fixed inset-0 z-[1000] bg-slate-900/50 backdrop-blur-md flex flex-col items-center justify-center animate-fade-in touch-none cursor-wait">
                    <div className="bg-white p-10 rounded-[2.5rem] shadow-2xl flex flex-col items-center transform transition-all scale-100 border border-slate-100 max-w-sm w-full mx-6 text-center relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                        <div className="w-16 h-16 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin mb-6 relative z-10"></div>
                        <h3 className="text-xl font-black text-slate-900 tracking-tight mb-2 relative z-10">Updating Session</h3>
                        <p className="text-slate-500 text-sm font-medium leading-relaxed relative z-10">Applying your new currency settings...</p>
                    </div>
                </div>
            )}

            {/* FLOATING SIDEBAR (Desktop) */}
            <aside className="hidden md:flex flex-col w-72 h-[96vh] my-[2vh] ml-[2vh] bg-white rounded-[2rem] shadow-2xl shadow-slate-200/50 z-30 p-6 relative overflow-hidden ring-1 ring-slate-100/50">
                {/* Decorative Blob */}
                <div className="absolute -top-20 -right-20 w-56 h-56 bg-gradient-to-br from-blue-400/10 to-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div className="relative z-10 mb-10 px-2 flex items-center gap-3">
                     <div className="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                        {/* ICON PADDING REDUCED: Increased SVG size from 20 to 24 */}
                        <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                     </div>
                     {/* LOGO WEIGHT INCREASED: text-2xl and font-black */}
                     <span className="text-2xl font-black tracking-tight text-slate-900">XOFLIX<span className="text-indigo-600">TV</span></span>
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
                <div className="mt-auto pt-6 border-t border-slate-50 relative z-10">
                    <div className="flex items-center gap-3 p-2.5 bg-slate-50/80 rounded-2xl hover:bg-slate-100 transition-colors cursor-pointer group" onClick={() => setActiveTab('profile')}>
                         <img src={userDefaults.avatar || 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTIwIDIxdi0yYTQgNCAwIDAgMC00LTRIODRhNCA0IDAgMCAwLTQgNHYyIi8+PGNpcmNsZSBjeD0iMTIiIGN5PSI3IiByPSI0Ii8+PC9zdmc+'} className="w-10 h-10 rounded-full bg-white shadow-sm border border-white object-cover" alt="User" />
                         <div className="min-w-0">
                             <p className="text-sm font-bold text-slate-900 truncate group-hover:text-indigo-700 transition-colors">{userDefaults.name}</p>
                             <p className="text-xs text-slate-500 truncate font-medium">{hasActiveSub ? 'Premium Member' : 'Free Account'}</p>
                         </div>
                    </div>
                </div>
            </aside>

            <main className="flex-1 flex flex-col relative overflow-hidden h-full">
                
                {/* Floating Header - HEIGHT REDUCED (py-6 md:py-8 -> py-5 md:py-6) */}
                <header className="px-6 md:px-10 py-5 md:py-6 flex items-center justify-between z-20 shrink-0">
                    <div className="md:hidden">
                        <button onClick={() => setIsDrawerOpen(true)} className="p-2.5 bg-white text-slate-600 shadow-sm border border-slate-200/50 hover:text-indigo-600 transition-all rounded-xl active:scale-95">
                            <Menu size={24} />
                        </button>
                    </div>
                    
                    <div className="hidden md:block">
                        <h2 className="text-2xl md:text-3xl font-black text-slate-900 tracking-tight capitalize leading-none mb-1">{activeTab === 'shop' ? 'Premium Store' : activeTab}</h2>
                        <p className="text-slate-500 text-sm font-medium">Welcome back to your portal</p>
                    </div>
                    <div className="md:hidden">
                         <span className="text-lg font-black tracking-tight text-slate-900">XO<span className="text-indigo-600">TV</span></span>
                    </div>
                    
                    <div className="flex items-center gap-4">
                        <div className="hidden md:flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm border border-slate-200/50 text-xs font-bold text-slate-600">
                             <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div> System Operational
                        </div>
                        <button onClick={() => setActiveTab('support')} className="w-10 h-10 rounded-full bg-white shadow-sm border border-slate-200/50 text-slate-400 hover:text-indigo-600 hover:shadow-md transition-all flex items-center justify-center">
                            <HelpCircle size={20} />
                        </button>
                    </div>
                </header>

                <div className="flex-1 overflow-y-auto px-4 md:px-10 pb-24 md:pb-10 custom-scrollbar scroll-smooth">
                    
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