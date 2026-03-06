<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
function IPTVDashboard() {
    const [activeTab, setActiveTab] = useState('dashboard');
    const [targetSub, setTargetSub] = useState(null); // [NEW] Extension Targeting State
    const [isReloading, setIsReloading] = useState(false); // [NEW] Session Reload State
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