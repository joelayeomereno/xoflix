<script type="text/babel">
/**
 * JS Components
 * Contains reusable React components for the Dashboard.
 * Relies on: js-icons.php (for Icons)
 */

const NEWS_UPDATES = [
  { 
      id: 1, 
      title: "New 4K Sports Channels", 
      description: "Experience the thrill in Ultra HD. We've added 12 new premium sports channels to your lineup.", 
      buttonText: "Explore", 
      action: "sports", 
      color: "from-indigo-600 to-violet-600" 
  },
  { 
      id: 2, 
      title: "Server Maintenance", 
      description: "Optimization scheduled for Oct 30th (03:00 AM UTC). Brief interruptions may occur.", 
      buttonText: "Status", 
      action: "support", 
      color: "from-rose-500 to-orange-500" 
  }
];

const { useState, useEffect } = React;

// --- A. COUNTDOWN TIMER ---
const CountdownTimer = ({ targetDate }) => {
    const calculateTimeLeft = () => {
        const diff = new Date(targetDate) - new Date();
        return diff;
    };

    const [timeLeft, setTimeLeft] = useState(calculateTimeLeft());

    useEffect(() => {
        const timer = setInterval(() => { setTimeLeft(calculateTimeLeft()); }, 1000);
        return () => clearInterval(timer);
    }, [targetDate]);

    if (timeLeft <= 0) {
        // If event started less than 2 hours ago, show LIVE
        if (timeLeft > -7200000) return <span className="text-emerald-500 font-bold animate-pulse">LIVE NOW</span>;
        return <span className="text-slate-400 font-medium">Ended</span>;
    }

    const h = Math.floor((timeLeft / (1000 * 60 * 60)) % 24);
    const m = Math.floor((timeLeft / 1000 / 60) % 60);
    const s = Math.floor((timeLeft / 1000) % 60);
    const d = Math.floor(timeLeft / (1000 * 60 * 60 * 24));

    if (d > 0) return <span className="text-blue-600 font-bold">{d}d {h}h {m}m</span>;
    
    // Critical time (< 1 hour)
    const isCritical = h === 0;
    return (
        <div className={`font-mono font-bold tracking-widest ${isCritical ? 'text-rose-500' : 'text-blue-600'}`}>
            {String(h).padStart(2, '0')}:{String(m).padStart(2, '0')}:{String(s).padStart(2, '0')}
        </div>
    );
};

// --- B. SPORTS CARD ---
const SportsCard = ({ event }) => {
    const startTime = new Date(event.startTime);
    const now = new Date();
    const isLive = (now >= startTime && now <= new Date(startTime.getTime() + 2 * 60 * 60 * 1000));
    const isFuture = now < startTime;
    
    const getSportIcon = (type) => {
        if (!type) return <Trophy size={20} />;
        switch(type.toLowerCase()) {
            case 'soccer': return <IconFootball size={20} />;
            case 'nba':
            case 'basketball': return <IconBasketball size={20} />;
            case 'f1':
            case 'racing': return <IconF1 size={20} />;
            case 'nfl':
            case 'american football': return <IconNFL size={20} />;
            case 'ufc':
            case 'mma':
            case 'boxing':
            case 'fighting': return <IconFighting size={20} />;
            case 'tennis': return <IconTennis size={20} />;
            case 'cricket': return <IconCricket size={20} />;
            default: return <Trophy size={20} />;
        }
    };

    return (
        <div className={`relative bg-white rounded-[1.5rem] border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all group overflow-hidden ${isLive ? 'ring-1 ring-emerald-500 shadow-emerald-100' : ''}`}>
            {isLive && <div className="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl shadow-sm animate-pulse">LIVE</div>}
            
            <div className="flex justify-between items-start mb-4">
                <div className="flex items-center gap-3">
                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-sm ${isLive ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500'}`}>
                        {getSportIcon(event.type)}
                    </div>
                    <div>
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{event.league}</p>
                        <h3 className="font-bold text-slate-900 leading-tight">{event.title}</h3>
                    </div>
                </div>
            </div>
            
            <div className="flex justify-between items-end">
                <div>
                    <p className="text-xs text-slate-500 font-medium mb-1 flex items-center gap-1.5"><Tv size={12} /> {event.channel}</p>
                    <div className="text-sm">
                        {isFuture ? ( 
                            <div className="flex items-center gap-2 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">
                                <Clock size={12} className="text-slate-400" />
                                <CountdownTimer targetDate={event.startTime} />
                            </div> 
                        ) : ( 
                            <span className="text-xs text-slate-400">{startTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span> 
                        )}
                    </div>
                </div>
                <button className={`w-8 h-8 rounded-full flex items-center justify-center transition-colors ${isLive ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 hover:bg-emerald-600' : 'bg-slate-100 text-slate-400 hover:bg-blue-50 hover:text-blue-600'}`}>
                    {isLive ? <Tv size={14} /> : <Star size={14} />}
                </button>
            </div>
        </div>
    );
};

// --- C. BOTTOM NAVIGATION (MOBILE) ---
const BottomNav = ({ activeTab, setActiveTab }) => {
    const navItems = [
        { id: 'dashboard', icon: LayoutDashboard, label: 'Home' },
        { id: 'subscription', icon: FileText, label: 'My Subs' },
        { id: 'shop', icon: ShoppingBag, label: 'Store' },
        { id: 'profile', icon: User, label: 'Profile' },
    ];
    return (
        <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t border-slate-200 z-40 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <div className="flex justify-around items-center h-16">
                {navItems.map(item => {
                    const isActive = activeTab === item.id;
                    const Icon = item.icon;
                    return (
                        <button key={item.id} onClick={() => setActiveTab(item.id)} className={`flex flex-col items-center justify-center w-full h-full space-y-1 transition-colors duration-200 ${isActive ? 'text-blue-600' : 'text-slate-400 active:text-slate-600'}`}>
                            <div className={`relative p-1 rounded-full transition-all ${isActive ? 'bg-blue-50' : ''}`}>
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

// --- D. MOBILE DRAWER ---
const Drawer = ({ isOpen, onClose, activeTab, setActiveTab, logoutUrl }) => {
    useEffect(() => { 
        document.body.style.overflow = isOpen ? 'hidden' : ''; 
        return () => { document.body.style.overflow = ''; }; 
    }, [isOpen]);

    if (!isOpen) return null;
    
    const menuItems = [
        { id: 'dashboard', icon: LayoutDashboard, label: 'Home' },
        { id: 'subscription', icon: FileText, label: 'My Subscriptions' },
        { id: 'billing', icon: CreditCard, label: 'Billing & Invoices' },
        { id: 'shop', icon: ShoppingBag, label: 'Upgrade Plan' },
        { id: 'sports', icon: Trophy, label: 'Sports Guide' },
        { id: 'profile', icon: User, label: 'My Profile' },
        { id: 'support', icon: HelpCircle, label: 'Support Center' },
    ];

    return (
        <div className="fixed inset-0 z-50 md:hidden">
            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm animate-fade-in" onClick={onClose}></div>
            <div className="absolute right-0 top-0 bottom-0 w-72 bg-white shadow-2xl animate-slide-in flex flex-col">
                <div className="p-6 flex items-center justify-between border-b border-slate-100">
                    <span className="font-bold text-lg text-slate-900">Menu</span>
                    <button onClick={onClose} className="p-2 -mr-2 text-slate-400 hover:text-slate-900 rounded-full hover:bg-slate-50 transition-colors">
                        <X size={24} />
                    </button>
                </div>
                <div className="flex-1 overflow-y-auto py-4">
                    <nav className="space-y-1 px-3">
                        {menuItems.map(item => (
                            <button key={item.id} onClick={() => { setActiveTab(item.id); onClose(); }} className={`flex items-center w-full p-3.5 rounded-xl transition-all duration-200 ${activeTab === item.id ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 font-medium hover:bg-slate-50'}`}>
                                <item.icon size={20} className={`mr-3.5 ${activeTab === item.id ? 'text-blue-600' : 'text-slate-400'}`} />
                                {item.label}
                            </button>
                        ))}
                    </nav>
                </div>
                <div className="p-4 border-t border-slate-100 bg-slate-50/50 pb-safe">
                    <a href={logoutUrl} className="flex items-center justify-center w-full p-3.5 rounded-xl border border-slate-200 bg-white text-rose-600 font-bold hover:bg-rose-50 hover:border-rose-200 transition-all shadow-sm">
                        <LogOut size={18} className="mr-2" /> Sign Out
                    </a>
                </div>
            </div>
        </div>
    );
};

// --- E. SIDEBAR ITEM ---
const SidebarItem = ({ icon: Icon, label, active, onClick }) => (
    <button onClick={onClick} className={`flex items-center w-full p-3.5 mb-2 rounded-2xl transition-all duration-300 group relative overflow-hidden ${active ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'}`}>
        <Icon size={20} className={`mr-3 ${active ? 'text-white' : 'group-hover:text-blue-600 transition-colors'}`} />
        <span className="font-medium text-sm tracking-wide">{label}</span>
    </button>
);

// --- F. STATUS BADGE ---
const StatusBadge = ({ status }) => {
    const styles = { 
        'Active': 'bg-emerald-100 text-emerald-700 border border-emerald-200', 
        'Paid': 'bg-emerald-100 text-emerald-700 border border-emerald-200', 
        'Pending': 'bg-amber-100 text-amber-700 border border-amber-200', 
        'Expiring Soon': 'bg-rose-100 text-rose-700 border border-rose-200', 
        'Expired': 'bg-slate-100 text-slate-500 border border-slate-200' 
    };
    return <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider whitespace-nowrap ${styles[status] || 'bg-slate-100 text-slate-600'}`}>{status}</span>;
};

// --- G. CREDENTIAL FIELD ---
const CredentialField = ({ label, value, isPassword = false }) => {
    const [copied, setCopied] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const handleCopy = () => { 
        navigator.clipboard.writeText(value); 
        setCopied(true); 
        setTimeout(() => setCopied(false), 2000); 
    };
    
    const displayValue = isPassword && !showPassword ? '��������' : value;
    
    return (
        <div className="w-full">
           <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 ml-1">{label}</p>
           <div className="flex items-center gap-2 bg-white hover:border-blue-300 p-3 rounded-xl border border-slate-200 transition-all group shadow-sm">
             <div className="flex-1 min-w-0 px-1"><code className="text-sm font-mono text-slate-700 truncate block select-all">{displayValue}</code></div>
             <div className="flex items-center gap-1">
                 {isPassword && <button onClick={() => setShowPassword(!showPassword)} className="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">{showPassword ? <EyeOff size={16} /> : <Eye size={16} />}</button>}
                 <button onClick={handleCopy} className="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors relative">{copied ? <Check size={16} className="text-emerald-600" /> : <Copy size={16} />}</button>
             </div>
           </div>
        </div>
    );
};

// --- H. PLAN CARD ---
const PlanCard = ({ plan, onSelect }) => (
  <div className={`relative bg-white rounded-[2rem] p-6 lg:p-8 transition-all duration-300 flex flex-col h-full group ${plan.recommended ? 'ring-2 ring-violet-500 shadow-xl shadow-violet-200' : 'ring-1 ring-slate-200 shadow-sm hover:ring-blue-300 hover:shadow-md'}`}>
    
    {plan.recommended && <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-gradient-to-r from-violet-600 to-indigo-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-widest shadow-lg whitespace-nowrap">Best Value</div>}
    
    <div className="text-center mb-6">
        <h3 className="text-slate-500 font-bold mb-2 uppercase tracking-wide text-xs">{plan.name}</h3>
        <div className="flex items-center justify-center gap-1 text-slate-900">
            <span className="text-4xl font-black tracking-tighter">{plan.price}</span>
            <span className="text-slate-400 text-sm font-medium">/{plan.period}</span>
        </div>
    </div>
    
    <ul className="space-y-4 mb-8 flex-1">
        {plan.features.map((feature, idx) => (
            <li key={idx} className="flex items-start gap-3 text-sm text-slate-600">
                <div className={`w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 ${plan.recommended ? 'bg-violet-50 text-violet-600' : 'bg-blue-50 text-blue-600'}`}>
                    <Check size={12} strokeWidth={3} />
                </div>
                <span className="leading-tight">{feature}</span>
            </li>
        ))}
    </ul>
    
    <button type="button" onClick={() => onSelect(plan)} className={`w-full py-4 rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95 ${plan.recommended ? 'bg-violet-600 text-white hover:bg-violet-700 shadow-violet-200 hover:shadow-violet-300' : 'bg-slate-900 text-white hover:bg-slate-800 shadow-slate-200 hover:shadow-slate-300'}`}>
        Get Started
    </button>
  </div>
);
</script>
