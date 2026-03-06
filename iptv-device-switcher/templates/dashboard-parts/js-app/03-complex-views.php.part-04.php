</>
            ) : (
                <div className="max-w-2xl mx-auto bg-white rounded-[2rem] p-8 border border-slate-100 shadow-xl">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h2 className="text-xl font-bold">{selectedPlan.name} Config</h2>
                            {targetSub && <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Extending Subscription #{targetSub.id}</span>}
                        </div>
                        <button onClick={handleCloseConfig} className="p-2 bg-slate-100 rounded-full"><X size={20}/></button>
                    </div>
                    <div className="space-y-6 mb-8">
                        <div>
                            <div className="flex justify-between items-end mb-2">
                                <label className="block text-sm font-bold text-slate-500">Duration</label>
                                {totals.discountPercent > 0 && (
                                    <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg animate-fade-in">
                                        {totals.discountPercent}% Saved
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center gap-4 bg-slate-50 p-2 rounded-xl">
                                <button onClick={() => setMonths(Math.max(1, months-1))} className="w-10 h-10 bg-white shadow rounded-lg font-bold">-</button>
                                <span className="flex-1 text-center font-bold">{months} Month{months>1?'s':''}</span>
                                <button onClick={() => setMonths(months+1)} className="w-10 h-10 bg-white shadow rounded-lg font-bold">+</button>
                            </div>
                             
                            {selectedPlan.discounts && selectedPlan.discounts.length > 0 && (
                                <div className="flex gap-2 mt-2 overflow-x-auto no-scrollbar pb-1">
                                    {selectedPlan.discounts.map((t, idx) => (
                                        <span key={idx} className={`text-[10px] px-2 py-1 rounded border whitespace-nowrap ${months >= parseInt(t.months) ? 'bg-emerald-100 border-emerald-200 text-emerald-700 font-bold' : 'bg-white border-slate-200 text-slate-400'}`}>
                                            {t.months}mo: -{t.percent}%
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-bold text-slate-500 mb-2">Connections</label>
                            {selectedPlan.multi_device ? (
                                <select value={connections} onChange={(e) => setConnections(Number(e.target.value))} className="w-full p-3 bg-slate-50 rounded-xl font-bold outline-none">
                                    {[1,2,3,4,5].map(n => <option key={n} value={n}>{n} Device{n>1?'s':''}</option>)}
                                </select>
                            ) : (
                                <div className="w-full p-3 bg-slate-100 rounded-xl font-bold text-slate-500 border border-slate-200 flex items-center justify-between">
                                    <span>1 Device</span>
                                    <span className="text-[10px] bg-slate-200 px-2 py-1 rounded uppercase tracking-wider">Locked</span>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-col sm:flex-row justify-between items-end border-t border-slate-100 pt-6 gap-4">
                        <div className="w-full sm:w-auto">
                            <p className="text-xs font-bold text-slate-400 mb-1">Total</p>
                            <div className="flex items-baseline gap-2">
                                {totals.discountPercent > 0 && (
                                    <span className="text-sm font-bold text-slate-400 line-through decoration-slate-300">
                                        {currencySymbol}{totals.original.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}
                                    </span>
                                )}
                                <p className="text-3xl font-black text-slate-900 leading-none">
                                    {currencySymbol}{totals.total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}
                                </p>
                            </div>
                        </div>
                        <button onClick={() => setView('checkout')} className="w-full sm:w-auto px-8 py-4 bg-slate-900 text-white font-bold rounded-xl shadow-lg hover:bg-black hover:shadow-xl hover:-translate-y-0.5 transition-all active:scale-95 flex items-center justify-center gap-2">
                            {targetSub ? 'Extend Now' : 'Checkout'} <ArrowRight size={18} />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};