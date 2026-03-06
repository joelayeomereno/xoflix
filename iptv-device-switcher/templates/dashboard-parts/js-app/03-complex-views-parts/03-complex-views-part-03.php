<h4 className="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                        <div className="w-1 h-4 bg-slate-200 rounded-full"></div> Included Features
                    </h4>
                    <ul className="space-y-4">
                        {plan.features.map((feature, idx) => (
                            <li key={idx} className="flex items-start gap-4 text-sm font-medium text-slate-600 group">
                                <div className={`w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm transition-colors ${plan.recommended ? 'bg-violet-100 text-violet-600 group-hover:bg-violet-200' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'}`}>
                                    <Check size={12} strokeWidth={4} />
                                </div>
                                <span className="leading-tight pt-0.5">{feature}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Footer */}
                <div className="p-6 border-t border-slate-100 bg-slate-50/50 pb-safe backdrop-blur-md">
                    <button 
                        onClick={() => onSelect(plan)} 
                        className={`w-full py-5 rounded-2xl font-black text-base transition-all shadow-xl flex items-center justify-center gap-3 transform active:scale-95 ${plan.recommended ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-violet-200' : 'bg-slate-900 hover:bg-slate-800 text-white shadow-slate-200'}`}
                    >
                        Select Plan <ArrowRight size={20} strokeWidth={3} />
                    </button>
                    <button onClick={onClose} className="w-full mt-4 py-3 text-slate-400 font-bold text-xs uppercase tracking-widest hover:text-slate-600 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
};

// Shop Tab (Updated Wrapper)
const ShopTab = ({ initialTargetSub }) => {
    const [view, setView] = useState('browsing');
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [detailPlan, setDetailPlan] = useState(null); // For modal
    const [connections, setConnections] = useState(1);
    const [months, setMonths] = useState(1);
    const [targetSub, setTargetSub] = useState(null);

    // Initial Load Effect for Extensions
    useEffect(() => {
        if (initialTargetSub) {
            // Find plan object from ID
            const plan = PLANS.find(p => p.name === initialTargetSub.planName) || PLANS[0];
            if (plan) {
                setTargetSub(initialTargetSub);
                setSelectedPlan(plan);
                setConnections(1); 
                setMonths(1);
            }
        }
    }, [initialTargetSub]);

    const calculateTotals = () => { 
        if(!selectedPlan) return { total: 0, original: 0, discountPercent: 0 }; 
        
        const base = (selectedPlan.local_price_raw !== undefined) 
                     ? parseFloat(selectedPlan.local_price_raw) 
                     : parseFloat(selectedPlan.raw_price); 
        
        const gross = base * months * connections;
        let final = gross;
        let appliedPercent = 0;

        if (selectedPlan.discounts && Array.isArray(selectedPlan.discounts)) {
            let maxDisc = 0;
            selectedPlan.discounts.forEach(t => {
                if (months >= parseInt(t.months)) {
                    maxDisc = Math.max(maxDisc, parseFloat(t.percent));
                }
            });
            if (maxDisc > 0) {
                appliedPercent = maxDisc;
                final = gross * (1 - (maxDisc / 100));
            }
        }

        return {
            total: final,
            original: gross,
            discountPercent: appliedPercent
        };
    };

    const totals = calculateTotals();
    const currencySymbol = selectedPlan?.currency?.symbol || '$';

    // Clear target if user closes plan config
    const handleCloseConfig = () => {
        setSelectedPlan(null);
        setTargetSub(null);
    };

    const handlePlanSelect = (plan) => {
        setDetailPlan(null); // Close modal
        setSelectedPlan(plan);
        setConnections(1);
        setMonths(1);
    };

    if (view === 'checkout') {
        return (
            <CheckoutView config={{ 
                plan: selectedPlan, 
                months, 
                connections, 
                total: totals.total, 
                originalTotal: totals.original,
                discountPercent: totals.discountPercent,
                currencySymbol,
                targetSubId: targetSub ? targetSub.id : null 
            }} onBack={() => setView('browsing')} />
        );
    }

    return (
        <div className="animate-slide-up">
            <PlanDetailModal 
                plan={detailPlan} 
                onClose={() => setDetailPlan(null)} 
                onSelect={handlePlanSelect} 
            />

            {!selectedPlan ? (
                <>
                    <h1 className="text-3xl font-black mb-2 text-center text-slate-900 tracking-tight">Choose Your Plan</h1>
                    <p className="text-slate-500 font-medium text-center mb-10">Premium access to 25,000+ channels and VOD.</p>
                    
                    {/* RESTORED GRID LAYOUT */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-4">
                        {PLANS.map(plan => (
                            <CompactPlanCard key={plan.id} plan={plan} onOpen={setDetailPlan} />
                        ))}
                    </div>
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