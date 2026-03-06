const PlanDetailModal = ({ plan, onClose, onSelect }) => {
    if (!plan) return null;
    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm animate-fade-in" onClick={onClose}></div>
            <div className="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl relative z-10 animate-slide-up overflow-hidden flex flex-col max-h-[90vh]">
                
                {/* Header */}
                <div className={`p-10 text-center bg-slate-50 border-b border-slate-100 relative overflow-hidden ${plan.recommended ? 'bg-gradient-to-b from-violet-50 to-white' : ''}`}>
                    {plan.recommended && <div className="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-violet-500 to-indigo-500"></div>}
                    
                    <h2 className="text-3xl font-black text-slate-900 mb-2 tracking-tight">{plan.name}</h2>
                    <div className="flex flex-col items-center justify-center gap-1 text-slate-900 mb-4">
                        <span className="text-4xl font-black tracking-tighter leading-none text-transparent bg-clip-text bg-gradient-to-br from-slate-900 to-slate-700">{plan.price}</span>
                        <span className="text-slate-400 text-sm font-bold uppercase tracking-wider">/ {plan.period}</span>
                    </div>
                    {plan.recommended && <span className="inline-flex items-center gap-1.5 mt-2 text-[10px] font-black bg-violet-100 text-violet-700 px-4 py-1.5 rounded-full uppercase tracking-widest shadow-sm border border-violet-200">
                        <Star size={10} fill="currentColor" /> Recommended Choice
                    </span>}
                </div>

                {/* Features */}
                <div className="p-8 overflow-y-auto custom-scrollbar bg-white">
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

// Shop Tab Container
const ShopTab = ({ initialTargetSub }) => {
    const [view, setView] = useState('browsing');
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [detailPlan, setDetailPlan] = useState(null); 
    const [connections, setConnections] = useState(1);
    const [months, setMonths] = useState(1);
    const [targetSub, setTargetSub] = useState(null);

    useEffect(() => {
        if (initialTargetSub) {
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
        const base = (selectedPlan.local_price_raw !== undefined) ? parseFloat(selectedPlan.local_price_raw) : parseFloat(selectedPlan.raw_price); 
        const gross = base * months * connections;
        let final = gross;
        let appliedPercent = 0;

        if (selectedPlan.discounts && Array.isArray(selectedPlan.discounts)) {
            let maxDisc = 0;
            selectedPlan.discounts.forEach(t => {
                if (months >= parseInt(t.months)) { maxDisc = Math.max(maxDisc, parseFloat(t.percent)); }
            });
            if (maxDisc > 0) {
                appliedPercent = maxDisc;
                final = gross * (1 - (maxDisc / 100));
            }
        }
        return { total: final, original: gross, discountPercent: appliedPercent };
    };

    const totals = calculateTotals();
    const currencySymbol = selectedPlan?.currency?.symbol || '$';

    const handleCloseConfig = () => { setSelectedPlan(null); setTargetSub(null); };
    const handlePlanSelect = (plan) => { setDetailPlan(null); setSelectedPlan(plan); setConnections(1); setMonths(1); };

    if (view === 'checkout') {
        return (
            <CheckoutView config={{ 
                plan: selectedPlan, months, connections, total: totals.total, 
                originalTotal: totals.original, discountPercent: totals.discountPercent,
                currencySymbol, targetSubId: targetSub ? targetSub.id : null 
            }} onBack={() => setView('browsing')} />
        );
    }

    return (
        <div className="animate-slide-up">
            <PlanDetailModal plan={detailPlan} onClose={() => setDetailPlan(null)} onSelect={handlePlanSelect} />

            {!selectedPlan ? (
                <>
                    {/* Hero Header */}
                    <div className="relative mb-12 rounded-[2rem] p-6 md:px-8 md:py-6 bg-white border border-slate-100 shadow-sm overflow-hidden">
                        <div className="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-50 to-violet-50 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 opacity-60 pointer-events-none"></div>
                        <div className="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                            <div className="flex-1">
                                <div className="inline-flex items-center gap-2 mb-2 justify-center md:justify-start">
                                    <span className="relative flex h-2 w-2">
                                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-500 opacity-75"></span>
                                      <span className="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                                    </span>
                                    <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">Official Store</span>
                                </div>
                                <h1 className="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight mb-1">
                                    Premium Access. <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-violet-600">Unlimited Fun.</span>
                                </h1>
                                <p className="text-xs md:text-sm text-slate-500 font-medium">Unlock 4K streaming, multi-device support & 24/7 priority assistance.</p>
                            </div>
                            <div className="flex items-center gap-3 shrink-0">
                                <div className="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-xl border border-slate-100 text-[10px] font-bold text-slate-600 uppercase tracking-wide">
                                    <Zap size={14} className="text-amber-500" fill="currentColor"/> Instant
                                </div>
                                <div className="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-xl border border-slate-100 text-[10px] font-bold text-slate-600 uppercase tracking-wide">
                                    <Lock size={14} className="text-emerald-500" fill="currentColor"/> Secure
                                </div>
                            </div>
                        </div>
                    </div>
                     
                    {/* Plans Grid */}
                    {(() => {
                        const groups = {};
                        const sectionOrder = [];
                        PLANS.forEach(p => {
                            const cat = (p.category || 'standard').toLowerCase();
                            if (!groups[cat]) { groups[cat] = []; sectionOrder.push(cat); }
                            groups[cat].push(p);
                        });

                        return sectionOrder.map((cat, index) => {
                            const groupPlans = groups[cat];
                            if (!groupPlans || groupPlans.length === 0) return null;
                            const isPremium = cat === 'premium';
                            const isLast = index === sectionOrder.length - 1;
                            
                            // Visual Config: Changed standard background to slate-50 to contrast with white cards
                            const containerClass = isPremium 
                                ? 'bg-gradient-to-b from-indigo-50/50 to-blue-50/50 border border-indigo-100 rounded-[3rem] p-8 md:p-12 shadow-sm relative overflow-hidden' 
                                : 'bg-slate-50 border border-slate-200 rounded-[3rem] p-8 md:p-12 shadow-sm relative overflow-hidden';
                                
                            const title = isPremium ? 'Premium Experience' : (cat === 'standard' ? 'Standard Access' : cat.charAt(0).toUpperCase() + cat.slice(1));
                            const subtitle = isPremium ? 'The ultimate 4K experience with priority features.' : 'Reliable streaming for everyday viewing.';
                            const icon = isPremium ? <Star size={24} className="text-violet-500" fill="currentColor" /> : <LayoutDashboard size={24} className="text-slate-400" />;

                            return (
                                <div key={cat} className="mb-12 last:mb-0">
                                    <div className={containerClass}>
                                        {isPremium && <div className="absolute top-0 right-0 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>}
                                        <div className="flex flex-col items-center text-center mb-10 relative z-10">
                                            <div className={`p-4 rounded-2xl mb-4 ${isPremium ? 'bg-white shadow-xl shadow-indigo-100 text-indigo-600' : 'bg-slate-50 border border-slate-100 text-slate-500'}`}>{icon}</div>
                                            <h3 className={`text-2xl font-black tracking-tight mb-2 ${isPremium ? 'text-slate-900' : 'text-slate-800'}`}>{title}</h3>
                                            <p className="text-sm font-medium text-slate-400 max-w-md mx-auto">{subtitle}</p>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                                            {groupPlans.map(plan => (<CompactPlanCard key={plan.id} plan={plan} onOpen={setDetailPlan} />))}
                                        </div>
                                    </div>
                                    {!isLast && (<div className="py-12 flex items-center justify-center"><div className="bg-slate-50 px-4 py-1.5 rounded-full border border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Explore More Options</div></div>)}
                                </div>
                            );
                        });
                    })()}