<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
// ==========================================
// 4. BOTTOM NAV & BOOTSTRAP (Complete)
// ==========================================

const BottomNav = ({ activeTab, setActiveTab }) => {
    const items = [
        {id:'dashboard',icon:LayoutDashboard,label:'Home'},
        {id:'subscription',icon:FileText,label:'Subs'},
        {id:'shop',icon:ShoppingBag,label:'Store'},
        {id:'sports',icon:Trophy,label:'Sports'},
        {id:'billing',icon:CreditCard,label:'Bill'}
    ];
    return (
        <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t border-slate-200 z-40 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <div className="flex justify-around items-center h-16">
                {items.map(item => {
                    const isActive = activeTab === item.id;
                    const Icon = item.icon;
                    return (
                        <button key={item.id} onClick={() => setActiveTab(item.id)} className={`flex flex-col items-center justify-center w-full h-full space-y-1 transition-colors duration-200 ${isActive ? 'text-indigo-600' : 'text-slate-400 active:text-slate-600'}`}>
                            <div className={`relative p-1 rounded-full transition-all ${isActive ? 'bg-indigo-50' : ''}`}>
                                <Icon size={22} strokeWidth={isActive ? 2.5 : 2} className={isActive ? 'transform scale-105' : ''} />
                            </div>
                            <span className="text-[10px] font-semibold tracking-tight">{item.label}</span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
};

// --- INITIALIZATION ---
const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<IPTVDashboard />);