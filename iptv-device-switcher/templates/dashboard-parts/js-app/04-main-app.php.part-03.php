<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
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
                        <img src={userDefaults.avatar || 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjOTRhM2I4IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTIwIDIxdi0yYTQgNCAwIDAgMC00LTRIODRhNCA0IDAgMCAwLTQgNHYyIi8+PGNpcmNsZSBjeD0iMTIiIGN5PSI3IiByPSI0Ii8+PC9zdmc+'} className="w-10 h-10 rounded-full bg-slate-200 border-2 border-white shadow-sm object-cover" alt="Avatar"/>
                    </button>
                </header>

                <div className="flex-1 overflow-y-auto p-4 md:p-10 pb-24 md:pb-10 custom-scrollbar">
                    
                    {recentPayment && (
                        <div className="fixed bottom-6 right-6 z-40 bg-gradient-to-r from-slate-900 to-slate-800 text-white p-4 rounded-2xl shadow-2xl flex items-center justify-between gap-4 animate-slide-up w-[92vw] max-w-sm">
                            <div className="flex items-center gap-4 min-w-0">
                                <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center animate-pulse shrink-0"><CreditCard size={20} className="text-blue-300"/></div>
                                <div className="min-w-0">
                                    <p className="font-bold leading-tight">Finish your payment</p>
                                    <p className="text-xs text-slate-300 truncate">Order {recentPayment.id} - {recentPayment.amount}</p>
                                </div>
                            </div>
                            <button onClick={() => setUploadInvoice(recentPayment)} className="px-4 py-2 bg-white text-slate-900 font-bold rounded-lg text-xs hover:bg-blue-50 transition-colors shrink-0">Upload Proof</button>
                        </div>
                    )}

                    {/* 1. DASHBOARD VIEW */}
                    {activeTab === 'dashboard' && (
                        <div className="max-w-5xl mx-auto space-y-8 animate-slide-up">
                            <AlertBanner alerts={window.USER_ALERTS} setActiveTab={setActiveTab} handleExtend={handleExtend} />
                            
                            {newsItems.length > 0 && (
                            <div className="relative w-full overflow-hidden rounded-[1.5rem] md:rounded-[2rem] shadow-xl shadow-slate-200/50 bg-white group">
                                <div className="flex transition-transform duration-700 ease-in-out h-64 md:h-80" style={{ transform: `translateX(-${currentNewsIndex * 100}%)` }}>
                                    {newsItems.map((news) => {
                                        const styles = getSmartStyles(news.color, news.isHex);
                                        const bgStyle = news.isHex ? { backgroundColor: news.color } : {};
                                        const bgClass = news.isHex ? '' : `bg-gradient-to-br ${news.color}`;
                                        
                                        return (
                                            <div key={news.id} className={`w-full flex-shrink-0 h-full ${bgClass} p-6 md:p-14 relative flex items-center`} style={bgStyle}>
                                                <div className={`absolute inset-0 ${styles.overlay}`}></div>
                                                <div className="relative z-10 max-w-2xl">
                                                    <span className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border mb-4 inline-block ${styles.badge}`}>Update</span>
                                                    <h2 className={`text-2xl md:text-5xl font-black mb-3 md:mb-6 leading-tight ${styles.title}`}>{news.title}</h2>
                                                    <p className={`text-sm md:text-lg mb-6 leading-relaxed max-w-lg font-medium line-clamp-2 md:line-clamp-none ${styles.desc}`}>{news.description}</p>
                                                    <button onClick={() => handleNewsClick(news.action, news.link)} className="px-5 py-2.5 md:px-7 md:py-3.5 bg-white text-slate-900 rounded-xl font-bold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-lg active:scale-95 text-sm md:text-base">
                                                        {news.buttonText} <ArrowRight size={18} />
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="absolute bottom-6 left-0 right-0 flex justify-center gap-2 z-20">
                                    {newsItems.map((_, idx) => (
                                        <button 
                                            key={idx} 
                                            onClick={() => setCurrentNewsIndex(idx)}
                                            className={`h-2 rounded-full transition-all duration-300 ${currentNewsIndex === idx ? 'w-8 bg-white' : 'w-2 bg-white/40 hover:bg-white/70'}`}
                                            aria-label={`Go to slide ${idx + 1}`}
                                        />
                                    ))}
                                </div>
                            </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                                    <div className="flex justify-between items-start mb-6">
                                        <div className="flex items-center gap-4"><div className="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600"><Zap size={24} /></div><div><h3 className="text-lg font-bold text-slate-900">Active Plans</h3><p className="text-sm text-slate-500 font-medium">{activeSubsList.length} subscriptions</p></div></div>
                                        <button onClick={() => setActiveTab('subscription')} className="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:text-blue-600"><ChevronRight size={20} /></button>
                                    </div>
                                    {activeSubsList.length === 0 ? <p className="text-slate-400 text-sm py-4 text-center">No active plans.</p> : activeSubsList.slice(0,3).map(sub => (
                                        <div key={sub.id} className="mb-4 last:mb-0 p-3 bg-slate-50 rounded-xl">
                                            <div className="flex justify-between items-center mb-1"><span className="font-bold">{sub.planName}</span><StatusBadge status={sub.status}/></div>
                                            <p className="text-xs text-slate-500 mb-2">{sub.daysLeft} days remaining</p>
                                            <UsageBar daysLeft={sub.daysLeft} />
                                            
                                            {/* EXTEND BUTTON */}
                                            <button onClick={() => handleExtend(sub)} className={`mt-3 w-full py-2 border border-slate-200 text-slate-700 font-bold rounded-lg text-xs hover:border-blue-300 hover:text-blue-600 transition-all flex items-center justify-center gap-1 ${sub.daysLeft < 3 ? 'bg-rose-50 border-rose-200 text-rose-600 animate-pulse' : 'bg-white'}`}>
                                                <Zap size={14} className={sub.daysLeft < 3 ? 'text-rose-500' : 'text-blue-500'} /> 
                                                {sub.daysLeft < 3 ? 'Urgent: Renew Now' : 'Extend Plan'}
                                            </button>
                                        </div>
                                    ))}
                                    <button onClick={() => { setTargetSub(null); setActiveTab('shop'); }} className="mt-4 w-full py-3 border-2 border-dashed border-slate-200 rounded-xl text-slate-500 font-bold hover:border-blue-400 hover:text-blue-600 transition-all">+ Add Plan</button>
                                </div>
                                <div className="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                                    <h3 className="font-bold text-lg mb-4">Pending Actions</h3>
                                    {invoicesList.filter(i => ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(i.raw_status)).length === 0 ? <p className="text-slate-400 text-sm py-4 text-center">All caught up!</p> : invoicesList.filter(i => ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(i.raw_status)).map(inv => (
                                        <div key={inv.id} className="flex justify-between items-center p-3 bg-amber-50 rounded-xl border border-amber-100 mb-2">
                                            <div><p className="font-bold text-sm text-amber-900">{inv.plan}</p><p className="text-xs text-amber-700">{inv.amount}</p></div>
                                            {inv.needs_proof ? (
                                                <button onClick={() => setUploadInvoice(inv)} className="px-3 py-1.5 bg-amber-200 text-amber-900 text-xs font-bold rounded-lg hover:bg-amber-300">Upload Proof</button>
                                            ) : (inv.raw_status === 'PENDING_ADMIN_REVIEW' ? (
                                                <span className="text-xs font-bold text-amber-600">Reviewing...</span>
                                            ) : (inv.has_gateway_link ? (
                                                <a href={inv.payment_link} target="_blank" className="text-xs font-bold text-blue-600 hover:underline">Pay Now</a>
                                            ) : (
                                                <span className="text-xs font-bold text-amber-600">Pending</span>
                                            )))}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* 2. SHOP VIEW */}
                    {activeTab === 'shop' && <div className="max-w-5xl mx-auto"><ShopTab initialTargetSub={targetSub} /></div>}

                    {/* 3. SUBSCRIPTION VIEW (100x Upgrade) */}
                    {activeTab === 'subscription' && (
                        <div className="max-w-5xl mx-auto space-y-6 animate-slide-up">
                            {activeSubsList.length === 0 && <div className="text-center py-20 bg-white rounded-[2rem] border border-slate-100"><p className="text-slate-500 mb-4">No active subscriptions.</p><button onClick={() => setActiveTab('shop')} className="text-blue-600 font-bold hover:underline">Buy a Plan</button></div>}
                            
                            {displayedSubs.map(sub => (
                                <ModernSubCard key={sub.id} sub={sub} onExtend={handleExtend} />
                            ))}

                            {/* Pagination Controls */}
                            {totalSubPages > 1 && (
                                <div className="flex items-center justify-center gap-4 py-4 bg-white/50 backdrop-blur-sm rounded-xl border border-slate-100">
                                    <button 
                                        onClick={() => setSubPage(p => Math.max(1, p - 1))}
                                        disabled={subPage === 1}
                                        className="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-600 font-bold disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
                                    >
                                        <ArrowLeft size={16} /> Previous
                                    </button>
                                    
                                    <span className="text-xs font-black uppercase text-slate-400 tracking-wider">
                                        Page {subPage} of {totalSubPages}
                                    </span>
                                    
                                    <button 
                                        onClick={() => setSubPage(p => Math.min(totalSubPages, p + 1))}
                                        disabled={subPage === totalSubPages}
                                        className="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-600 font-bold disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
                                    >
                                        Next <ArrowRight size={16} />
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* 4. SPORTS VIEW */}
                    {activeTab === 'sports' && (
                        <div className="max-w-7xl mx-auto space-y-8 animate-slide-up">
                            <div className="flex flex-col md:flex-row justify-between items-end gap-6">
                                <div><h1 className="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-2">Live Sports Guide</h1><p className="text-slate-500 font-medium">Real-time schedules for top leagues.</p></div>
                                <div className="flex gap-2 overflow-x-auto pb-2 w-full md:w-auto no-scrollbar">
                                    {[ {id:'all',label:'All Events'}, {id:'live',label:'Live Now'}, {id:'soccer',label:'Football'}, {id:'nba',label:'Basketball'}, {id:'ufc',label:'Fighting'} ].map(f => (
                                        <button key={f.id} onClick={() => setFilterSport(f.id)} className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all border ${filterSport === f.id ? 'bg-slate-900 text-white border-slate-900 shadow-lg' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'}`}>{f.label}</button>
                                    ))}
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                {filteredSports.length === 0 && <div className="col-span-full py-20 text-center bg-white rounded-[2rem] border border-slate-200"><p className="text-slate-500">No scheduled events found.</p></div>}
                                {filteredSports.map(ev => (
                                    <SportsCard 
                                        key={ev.id} 
                                        event={ev} 
                                        onClick={() => setSelectedSport(ev)} 
                                    />
                                ))}
                            </div>
                            
                            {/* POPUP MODAL */}
                            {selectedSport && (
                                <SportsModal 
                                    event={selectedSport} 
                                    onClose={() => setSelectedSport(null)} 
                                />
                            )}
                        </div>
                    )}

                    {/* 5. BILLING VIEW */}
                    {activeTab === 'billing' && (
                        <div className="max-w-5xl mx-auto space-y-6 animate-slide-up">
                            <h1 className="text-3xl font-bold text-slate-900">Billing History</h1>
                            <div className="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                                <table className="w-full text-left">
                                    <thead className="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">ID</th>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">Date</th>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">Amount</th>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">Status</th>
                                            <th className="p-5"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {displayedInvoices.map(inv => (
                                            <tr key={inv.id} className="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                                                <td className="p-5 font-mono text-sm font-bold">{inv.id}</td>
                                                <td className="p-5 text-sm text-slate-500">{inv.date}</td>
                                                <td className="p-5 font-bold" dangerouslySetInnerHTML={{ __html: inv.amount }}></td>
                                                <td className="p-5"><StatusBadge status={inv.status}/></td>
                                                <td className="p-5 text-right">
                                                    {(inv.needs_proof || inv.has_gateway_link) && ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(inv.raw_status) ? (
                                                        inv.has_gateway_link && !inv.attempt_recent ? (
                                                            <a href={inv.payment_link} target="_blank" className="text-xs font-bold text-blue-600 hover:underline">Pay Now</a>
                                                        ) : (
                                                            <button onClick={() => setUploadInvoice(inv)} className="text-xs font-bold text-blue-600 hover:underline">
                                                                {inv.has_gateway_link ? "Paid? Upload Proof" : "Upload Proof"}
                                                            </button>
                                                        )
                                                    ) : null}
                                                </td>
                                            </tr>
                                        ))}
                                        {invoicesList.length === 0 && <tr><td colSpan="5" className="p-8 text-center text-slate-400">No invoices found.</td></tr>}
                                    </tbody>
                                </table>
                                
                                {totalBillingPages > 1 && (
                                    <div className="p-4 border-t border-slate-100 flex justify-between items-center bg-slate-50/30">
                                        <button 
                                            onClick={() => setBillingPage(p => Math.max(1, p - 1))}
                                            disabled={billingPage === 1}
                                            className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold disabled:opacity-50 hover:bg-slate-50 transition-colors"
                                        >
                                            Previous
                                        </button>
                                        <span className="text-xs font-bold text-slate-500">Page {billingPage} of {totalBillingPages}</span>
                                        <button 
                                            onClick={() => setBillingPage(p => Math.min(totalBillingPages, p + 1))}
                                            disabled={billingPage === totalBillingPages}
                                            className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold disabled:opacity-50 hover:bg-slate-50 transition-colors"
                                        >
                                            Next
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}