<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
// --- NEW COMPACT PLAN SYSTEM ---

const CompactPlanCard = ({ plan, onOpen }) => {
    // Determine if this is a "Highlight" card (Premium/Recommended)
    const isHighlight = plan.recommended || plan.category === 'premium';
     
    return (
        <div 
            onClick={() => onOpen(plan)}
            className={`group relative flex flex-col h-full min-h-[380px] rounded-[2rem] p-6 cursor-pointer transition-all duration-500 border ${
                isHighlight 
                ? 'bg-slate-900 border-slate-800 shadow-2xl hover:shadow-slate-900/20 hover:-translate-y-2' 
                : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-2'
            }`}
        >
            {/* Hover Gradient Effect */}
            <div className={`absolute inset-0 rounded-[2rem] opacity-0 group-hover:opacity-100 transition-opacity duration-500 ${isHighlight ? 'bg-gradient-to-b from-white/5 to-transparent' : 'bg-gradient-to-b from-blue-50/30 to-transparent'}`}></div>

            {/* Badges */}
            <div className="absolute top-6 right-6 flex flex-col items-end gap-2 z-20">
                {plan.recommended && (
                    <div className="bg-gradient-to-r from-blue-600 to-violet-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg transform group-hover:scale-105 transition-transform">
                        Best Value
                    </div>
                )}
                {plan.category === 'premium' && !plan.recommended && (
                    <div className="bg-slate-800 border border-slate-700 text-slate-300 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest">
                        Premium
                    </div>
                )}
            </div>
             
            {/* Header */}
            <div className="mb-6 relative z-10">
                <h3 className={`text-sm font-black uppercase tracking-widest mb-3 ${isHighlight ? 'text-slate-400' : 'text-slate-400'}`}>
                    {plan.name}
                </h3>
                 
                <div className="flex items-baseline gap-1">
                    <span className={`text-5xl font-black tracking-tighter leading-none ${isHighlight ? 'text-white' : 'text-slate-900'}`}>
                        {plan.price}
                    </span>
                </div>
                <div className={`text-xs font-bold mt-2 ${isHighlight ? 'text-slate-500' : 'text-slate-400'}`}>
                    Billed every {plan.period}
                </div>
            </div>

            {/* Divider */}
            <div className={`h-px w-full my-4 ${isHighlight ? 'bg-white/10' : 'bg-slate-50'}`}></div>

            {/* Feature Highlights */}
            <div className="space-y-3 mb-8 flex-1 relative z-10">
                {plan.multi_device && (
                    <div className={`flex items-center gap-3 text-xs font-bold ${isHighlight ? 'text-emerald-400' : 'text-emerald-600'}`}>
                        <div className={`p-1.5 rounded-lg ${isHighlight ? 'bg-emerald-500/10' : 'bg-emerald-50'}`}>
                            <Zap size={14} strokeWidth={3}/> 
                        </div>
                        Multi-Screen Supported
                    </div>
                )}
                {plan.features.slice(0, 3).map((f, i) => (
                    <div key={i} className={`flex items-start gap-3 text-xs font-medium ${isHighlight ? 'text-slate-300' : 'text-slate-600'}`}>
                        <Check size={14} className={`mt-0.5 shrink-0 ${isHighlight ? 'text-slate-600' : 'text-slate-400'}`} strokeWidth={3} />
                        <span className="leading-tight line-clamp-2">{f}</span>
                    </div>
                ))}
            </div>

            {/* Action */}
            <div className="relative z-10 mt-auto">
                <button className={`w-full py-4 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 transition-all ${
                    isHighlight 
                    ? 'bg-white text-slate-900 hover:bg-slate-200' 
                    : 'bg-slate-900 text-white hover:bg-slate-800 shadow-lg shadow-slate-200'
                }`}>
                    Select Plan
                </button>
            </div>
        </div>
    );
};

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

                {/* Features (Scrollable) */}
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
                    {/* --- COMPACT HERO HEADER --- */}
                    <div className="relative mb-10 rounded-[2rem] p-6 md:px-8 md:py-6 bg-white border border-slate-100 shadow-sm overflow-hidden">
                        {/* Subtle Background Blobs */}
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
                                <p className="text-xs md:text-sm text-slate-500 font-medium">
                                    Unlock 4K streaming, multi-device support & 24/7 priority assistance.
                                </p>
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
                    {/* --- END HERO HEADER --- */}
                     
                    {/* NEW: SECTIONED LAYOUT - PROFESSIONAL UPGRADE */}
                    {(() => {
                        // Group Plans - Start with preferred order
                        // Note: PLANS are already sorted by display_order from PHP.
                         
                        const groups = {};
                        const sectionOrder = [];
                         
                        PLANS.forEach(p => {
                            const cat = (p.category || 'standard').toLowerCase();
                            if (!groups[cat]) {
                                groups[cat] = [];
                                sectionOrder.push(cat);
                            }
                            groups[cat].push(p);
                        });

                        // Render Logic
                        return sectionOrder.map((cat, index) => {
                            const groupPlans = groups[cat];
                            if (!groupPlans || groupPlans.length === 0) return null;
                             
                            const isPremium = cat === 'premium';
                            const title = cat === 'standard' ? 'Standard Access' : (cat === 'premium' ? 'Premium Experience' : cat.charAt(0).toUpperCase() + cat.slice(1));
                            const subtitle = cat === 'standard' ? 'Reliable streaming for everyday viewing.' : (cat === 'premium' ? 'The ultimate 4K experience with priority features.' : 'Flexible options for your needs.');
                            const icon = isPremium ? <Star size={24} className="text-violet-500" fill="currentColor" /> : <LayoutDashboard size={24} className="text-slate-400" />;
                            const isLast = index === sectionOrder.length - 1;

                            return (
                                <div key={cat} className="mb-4 last:mb-0">
                                    <div className={`relative ${isPremium ? 'bg-slate-50/50 rounded-[3rem] p-8 -mx-6 sm:mx-0 border border-slate-100' : ''}`}>
                                        {/* Section Header */}
                                        <div className="flex flex-col items-center text-center mb-10">
                                            <div className={`p-4 rounded-2xl mb-4 ${isPremium ? 'bg-white shadow-xl shadow-violet-100 text-violet-600' : 'bg-white shadow-sm border border-slate-100 text-slate-500'}`}>
                                                {icon}
                                            </div>
                                            <h3 className={`text-2xl font-black tracking-tight mb-2 ${isPremium ? 'text-slate-900' : 'text-slate-700'}`}>
                                                {title}
                                            </h3>
                                            <p className="text-sm font-medium text-slate-400 max-w-md mx-auto">
                                                {subtitle}
                                            </p>
                                        </div>
                                         
                                        {/* Grid */}
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                                            {groupPlans.map(plan => (
                                                <CompactPlanCard key={plan.id} plan={plan} onOpen={setDetailPlan} />
                                            ))}
                                        </div>
                                    </div>

                                    {!isLast && (
                                        <div className="py-16 flex items-center justify-center">
                                            <div className="w-full max-w-xs h-px bg-slate-200"></div>
                                        </div>
                                    )}
                                </div>
                            );
                        });
                    })()}
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
