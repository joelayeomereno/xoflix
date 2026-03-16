<?php if (!defined('ABSPATH')) exit; ?>
                    <button onClick={onClose} className="p-2 hover:bg-slate-50 rounded-full text-slate-400 hover:text-slate-600"><X size={20}/></button>
                </div>
                <div className="p-6">
                    <p className="text-sm text-slate-500 mb-6">
                        Please upload a screenshot of your payment for <strong>{invoice.id}</strong>.
                    </p>
                    <form method="post" encType="multipart/form-data">
                        <input type="hidden" name="payment_proof_submit" value="1" />
                        <input type="hidden" name="payment_id" value={invoice.raw_id} />
                        
                        <div className="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center mb-6 hover:border-blue-500 transition-colors bg-slate-50 relative">
                            <input type="file" name="payment_proof[]" className="absolute inset-0 w-full h-full opacity-0 cursor-pointer" id="proof_file" required multiple />
                            <div className="mx-auto w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3 pointer-events-none">
                                <UploadCloud size={24} />
                            </div>
                            <span className="block font-bold text-slate-700 pointer-events-none">Click to Select File</span>
                            <span className="text-xs text-slate-400 pointer-events-none">JPG, PNG, PDF allowed</span>
                        </div>
                        
                        <button type="submit" className="w-full py-3.5 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition-all">
                            Submit Proof
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
};

function IPTVDashboard() {
    const [activeTab, setActiveTab] = useState('dashboard');
    const [targetSub, setTargetSub] = useState(null); 
    const [isReloading, setIsReloading] = useState(false); 
    const [selectedSport, setSelectedSport] = useState(null); // [NEW] Sports Modal State

    useEffect(() => {
        try {
            const params = new URLSearchParams(window.location.search || '');
            const tab = params.get('tab');
            const finish = params.get('finish_payment');
            if (tab && ['dashboard','subscription','shop','sports','billing','profile','support'].includes(tab)) {
                setActiveTab(tab);
            } else if (finish) {
                setActiveTab('billing'); 
            }
            if (tab || finish) {
                params.delete('tab');
                params.delete('finish_payment');
                const newQs = params.toString();
                const newUrl = window.location.pathname + (newQs ? ('?' + newQs) : '') + window.location.hash;
                window.history.replaceState({}, document.title, newUrl);
            }
        } catch(e) {}
    }, []);

    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [uploadInvoice, setUploadInvoice] = useState(null); 
    const [filterSport, setFilterSport] = useState('all');
    const [currentNewsIndex, setCurrentNewsIndex] = useState(0);
    const [billingPage, setBillingPage] = useState(1); 
    
    const [subPage, setSubPage] = useState(1);

    const userDefaults = (typeof USER_DATA !== 'undefined' && USER_DATA) ? USER_DATA : { name: 'User', email: '', phone: '', country: 'US', currency: 'USD', avatar: '' };
    
    const [profileForm, setProfileForm] = useState({
        name: userDefaults.name || '',
        email: userDefaults.email || '',
        phone: userDefaults.phone || '',
        country: userDefaults.country || '',
        currency: userDefaults.currency || 'USD'
    });
    const [isSavingProfile, setIsSavingProfile] = useState(false);
    const [profileMsg, setProfileMsg] = useState('');

    useEffect(() => {
        if (typeof USER_DATA !== 'undefined' && USER_DATA) {
            setProfileForm({
                name: USER_DATA.name || '',
                email: USER_DATA.email || '',
                phone: USER_DATA.phone || '',
                country: USER_DATA.country || '',
                currency: USER_DATA.currency || 'USD'
            });
        }
    }, []);

    useEffect(() => {
        if (activeTab !== 'dashboard') return;
        if (!window.SERVER_NEWS || window.SERVER_NEWS.length === 0) return;
        
        const interval = setInterval(() => { 
            setCurrentNewsIndex(prev => (prev + 1) % window.SERVER_NEWS.length); 
        }, 6000); 
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
        if (link && (link.startsWith('http') || link.startsWith('//'))) {
            window.open(link, '_blank');
        } else {
            setActiveTab(action);
        }
    };

    const handleExtend = (sub) => {
        setTargetSub(sub);
        setActiveTab('shop');
    };

    const handleProfileSave = async () => {
        setIsSavingProfile(true);
        setProfileMsg('');
        
        try {
            const fd = new FormData();
            fd.append('action', 'streamos_update_profile');
            fd.append('display_name', profileForm.name);
            fd.append('phone', profileForm.phone);
            // NOTE: Country is disabled for updates on frontend
            // fd.append('country', profileForm.country); 
            fd.append('currency', profileForm.currency); 
            
            if (userDefaults.id && userDefaults.auth_sig) {
                fd.append('auth_id', userDefaults.id);
                fd.append('auth_sig', userDefaults.auth_sig);
            }
            
            if(window.TV_CHECKOUT_NONCE) fd.append('_wpnonce', window.TV_CHECKOUT_NONCE);

            const res = await fetch(window.TV_AJAX_URL, { method: 'POST', body: fd, credentials: 'include' });
            
            if (!res.ok) throw new Error(`Server returned ${res.status}`);

            const json = await res.json();
            
            if (json.success) {
                // [NEW] Check for currency change and force reload
                if (profileForm.currency !== userDefaults.currency) {
                     setProfileMsg('Currency updated. Reloading session...');
                     setIsReloading(true);
                     
                     // Allow UI to update msg before reload
                     setTimeout(() => {
                         window.location.reload();
                     }, 1500);
                     return; // Stop execution here to keep loader active and prevent race conditions
                }

                setProfileMsg('Changes saved successfully.');
                if (typeof USER_DATA !== 'undefined') {
                    USER_DATA.name = profileForm.name;
                    USER_DATA.phone = profileForm.phone;
                    // Country not updated locally
                    USER_DATA.currency = profileForm.currency;
                }
                setTimeout(() => setProfileMsg(''), 3000);
            } else {
                setProfileMsg(json.data && json.data.message ? 'Error: ' + json.data.message : 'Error saving changes.');
            }
        } catch(e) {
            console.error(e);
            setProfileMsg('Connection error.');
        }
        setIsSavingProfile(false);
    };

    const newsItems = (typeof window.SERVER_NEWS !== 'undefined' && window.SERVER_NEWS.length > 0) ? window.SERVER_NEWS : [];

    // [SMART] Usage Progress Bar
    const UsageBar = ({ daysLeft }) => {
        const percentage = Math.min(100, Math.max(0, (daysLeft / 30) * 100)); // Assume 30d baseline for visual
        let color = 'bg-emerald-500';
        if (daysLeft < 3) color = 'bg-rose-500';
        else if (daysLeft < 7) color = 'bg-amber-500';
        
        return (
            <div className="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
                <div className={`h-full ${color} transition-all duration-500`} style={{ width: `${percentage}%` }}></div>
            </div>
        );
    };