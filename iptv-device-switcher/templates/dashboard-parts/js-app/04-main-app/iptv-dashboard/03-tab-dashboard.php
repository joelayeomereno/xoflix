<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
                    {/* 1. DASHBOARD VIEW (PREMIUM) */}
                    {activeTab === 'dashboard' && (
                        <div className="max-w-6xl mx-auto space-y-8 animate-slide-up pb-10">
                            <AlertBanner alerts={window.USER_ALERTS} setActiveTab={setActiveTab} handleExtend={handleExtend} />
                            
                            {newsItems.length > 0 && (
                            <div className="relative w-full overflow-hidden rounded-[2.5rem] shadow-2xl shadow-indigo-200/40 bg-white group ring-1 ring-slate-100">
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
                                                    <button onClick={() => handleNewsClick(news.action, news.link)} className="px-8 py-4 bg-white text-slate-900 rounded-2xl font-bold hover:bg-slate-50 hover:scale-105 transition-all flex items-center gap-2 shadow-xl shadow-black/5 active:scale-95 text-sm md:text-base group">
                                                        {news.buttonText} <ArrowRight size={18} className="group-hover:translate-x-1 transition-transform" />
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="absolute bottom-8 left-0 right-0 flex justify-center gap-3 z-20">
                                    {newsItems.map((_, idx) => (
                                        <button 
                                            key={idx} 
                                            onClick={() => setCurrentNewsIndex(idx)}
                                            className={`h-1.5 rounded-full transition-all duration-500 backdrop-blur-sm ${currentNewsIndex === idx ? 'w-12 bg-white shadow-lg' : 'w-2 bg-white/30 hover:bg-white/60'}`}
                                            aria-label={`Go to slide ${idx + 1}`}
                                        />
                                    ))}
                                </div>
                            </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                {/* ACTIVE PLANS HERO CARD */}
                                <div className="bg-white rounded-[2.5rem] p-10 md:p-12 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] hover:shadow-[0_20px_50px_-12px_rgba(79,70,229,0.15)] relative overflow-hidden group transition-all duration-500 border border-slate-200 flex flex-col h-full">
                                    
                                    {/* Dynamic Mesh Background */}
                                    <div className="absolute top-0 right-0 w-[400px] h-[400px] bg-gradient-to-br from-indigo-500/5 to-purple-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 group-hover:scale-110 transition-transform duration-1000"></div>

                                    <div className="flex justify-between items-start mb-10 relative z-10">
                                        <div>
                                            <h3 className="text-3xl font-black text-slate-900 tracking-tighter mb-2">My Plan</h3>
                                            <p className="text-slate-500 font-medium text-sm">{activeSubsList.length > 0 ? 'Premium Access Active' : 'No active subscription'}</p>
                                        </div>
                                        
                                        <div className={`w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-xl transition-transform group-hover:rotate-12 ${activeSubsList.length > 0 ? 'bg-slate-900 text-white shadow-slate-900/20' : 'bg-slate-50 text-slate-300'}`}>
                                            {activeSubsList.length > 0 ? <Zap size={30} fill="currentColor"/> : <Lock size={30}/>}
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
                                                {activeSubsList.slice(0, 1).map(sub => {
                                                    const totalDays = (sub.totalDays > 0)
                                                        ? sub.totalDays
                                                        : (sub.planName && /year|annual/i.test(sub.planName) ? 365
                                                           : sub.planName && /quarter|3.?mo/i.test(sub.planName) ? 90
                                                           : 30);
                                                    const barPercent = Math.min(100, Math.max(0, (sub.daysLeft / totalDays) * 100));
                                                    return (
                                                    <div key={sub.id}>
                                                        <div className="flex items-center gap-3 mb-8">
                                                            <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-[11px] font-black uppercase tracking-widest border border-indigo-100 shadow-sm">
                                                                <span className="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span> Active
                                                            </div>
                                                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">#{sub.id}</span>
                                                        </div>
                                                        
                                                        <div className="grid grid-cols-2 gap-4 mb-8">
                                                            <div className="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                                                <div className="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Package</div>
                                                                <div className="text-slate-900 font-black text-xl leading-tight">{sub.planName}</div>
                                                            </div>
                                                            <div className="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                                                <div className="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Time Left</div>
                                                                <div className={`font-black text-xl leading-tight ${sub.daysLeft < 3 ? 'text-rose-500' : 'text-emerald-600'}`}>{sub.daysLeft} Days</div>
                                                            </div>
                                                        </div>

                                                        <div className="mb-10">
                                                            <div className="flex justify-between items-end mb-3">
                                                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Usage Period</span>
                                                                <span className="text-xs font-bold text-slate-600">{barPercent.toFixed(0)}% Remaining</span>
                                                            </div>
                                                            <div className="w-full h-4 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                                                <div className={`h-full rounded-full transition-all duration-1000 ${sub.daysLeft < 3 ? 'bg-rose-500' : 'bg-gradient-to-r from-emerald-400 to-indigo-500'}`} style={{width: `${barPercent}%`}}></div>
                                                            </div>
                                                        </div>

                                                        <button onClick={() => handleExtend(sub)} className="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold shadow-xl shadow-slate-900/20 hover:bg-indigo-600 hover:shadow-indigo-500/30 transition-all active:scale-95 flex items-center justify-center gap-2 group text-sm uppercase tracking-wide">
                                                            {sub.daysLeft < 3 ? 'Renew Now' : 'Extend Subscription'} <ArrowRight size={18} className="group-hover:translate-x-1 transition-transform"/>
                                                        </button>
                                                    </div>
                                                    );
                                                })}
                                                {activeSubsList.length > 1 && (
                                                    <button onClick={() => setActiveTab('subscription')} className="w-full text-center text-xs font-bold text-slate-400 hover:text-indigo-600 mt-6">
                                                        + {activeSubsList.length - 1} other subscriptions
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* PENDING ACTIONS CARD */}
                                <div className="bg-white p-10 md:p-12 rounded-[2.5rem] border border-slate-200 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] hover:shadow-[0_20px_50px_-12px_rgba(245,158,11,0.15)] h-full flex flex-col relative overflow-hidden transition-all duration-500">
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
                                                <div className="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6 shadow-inner">
                                                    <Check size={32} className="text-slate-300"/>
                                                </div>
                                                <p className="text-sm font-bold text-slate-400">All caught up!</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-4">
                                                {invoicesList.slice(0, 4).map(inv => {
                                                    const isPending = ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(inv.raw_status);
                                                    return (
                                                        <div key={inv.id} className={`flex justify-between items-center p-5 rounded-3xl border transition-all ${isPending ? 'bg-amber-50/50 border-amber-100 hover:bg-amber-50 shadow-sm' : 'bg-white border-slate-100 hover:border-indigo-100 hover:shadow-md'}`}>
                                                            <div className="flex items-center gap-5">
                                                                <div className={`w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-black shadow-sm ${isPending ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'}`}>
                                                                    {isPending ? '!' : <Check size={18} strokeWidth={4}/>}
                                                                </div>
                                                                <div>
                                                                    <p className="font-bold text-sm text-slate-900">{inv.plan}</p>
                                                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">{inv.date} · {inv.amount}</p>
                                                                </div>
                                                            </div>
                                                            
                                                            {isPending ? (
                                                                inv.needs_proof ? (
                                                                    <button onClick={() => setUploadInvoice(inv)} className="px-5 py-2.5 bg-amber-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all transform hover:-translate-y-0.5">Upload</button>
                                                                ) : (
                                                                    <span className="text-[10px] font-bold text-amber-600 bg-amber-100 px-3 py-1.5 rounded-full uppercase tracking-wider">Pending</span>
                                                                )
                                                            ) : (
                                                                <span className="text-xs font-bold text-slate-300">Paid</span>
                                                            )}
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