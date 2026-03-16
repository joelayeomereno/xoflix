<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
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

