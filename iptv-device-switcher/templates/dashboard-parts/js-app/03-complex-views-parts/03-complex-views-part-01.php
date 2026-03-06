// ==========================================
// 3. COMPLEX VIEWS (ADVANCED CHECKOUT ENGINE)
// ==========================================

// --- Helper Components ---

const StepIndicator = ({ current }) => {
    const steps = ['Config', 'Method', 'Payment'];
    return (
        <div className="relative flex items-center justify-between mb-12 max-w-sm mx-auto px-4 select-none">
            <div className="absolute top-5 left-0 right-0 h-1 bg-slate-100 -z-0 rounded-full overflow-hidden mx-4">
                <div className="h-full bg-emerald-500 transition-all duration-500 ease-out" style={{width: `${((current-1)/(steps.length-1))*100}%`}}></div>
            </div>
            {steps.map((label, idx) => {
                const stepNum = idx + 1;
                const isActive = stepNum === current;
                const isPast = stepNum < current;
                return (
                    <div key={label} className="relative z-10 flex flex-col items-center">
                        <div className={`w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold border-4 transition-all duration-300 ${isActive ? 'bg-blue-600 border-white text-white shadow-xl scale-110 ring-2 ring-blue-100' : (isPast ? 'bg-emerald-500 border-white text-white shadow-sm' : 'bg-slate-50 border-white text-slate-400')}`}>
                            {isPast ? <Check size={14} strokeWidth={3}/> : stepNum}
                        </div>
                        <span className={`absolute top-12 text-[10px] font-bold uppercase tracking-wider whitespace-nowrap transition-all duration-300 ${isActive ? 'text-slate-900 translate-y-0 opacity-100' : 'text-slate-400 -translate-y-1 opacity-70'}`}>{label}</span>
                    </div>
                );
            })}
        </div>
    );
};

const TrustStrip = () => (
    <div className="flex items-center justify-center gap-4 text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-6 opacity-70">
        <span className="flex items-center gap-1"><Lock size={12}/> 256-bit SSL</span>
        <span className="w-1 h-1 bg-slate-300 rounded-full"></span>
        <span className="flex items-center gap-1"><Check size={12}/> Money-back Guarantee</span>
    </div>
);

const FullScreenLoader = () => (
    <div className="fixed inset-0 z-[100] bg-slate-900/20 backdrop-blur-sm flex flex-col items-center justify-center animate-fade-in touch-none cursor-wait">
        <div className="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center transform transition-all scale-100">
            <div className="w-12 h-12 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin mb-4"></div>
            <h3 className="text-lg font-bold text-slate-900">Processing Securely</h3>
            <p className="text-slate-500 text-xs font-medium mt-1">Starting your transaction...</p>
        </div>
    </div>
);

const LockdownOverlay = ({ payId, methodDetails, onCancel }) => {
    const [status, setStatus] = useState('checking');
    const [showBankModal, setShowBankModal] = useState(false);
    
    const invoice = INVOICES.find(i => i.raw_id == payId);
    const isManual = (!methodDetails?.is_gateway) || (invoice?.bank_details && Object.keys(invoice.bank_details).length > 0 && !invoice.payment_link);

    useEffect(() => {
        if(!payId) return;
        const interval = setInterval(async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'tv_check_transaction_status');
                formData.append('pay_id', payId);
                const res = await fetch(window.TV_AJAX_URL, { method: 'POST', body: formData });
                const json = await res.json();
                
                if(json.success) {
                    if(json.data.is_completed) {
                        clearInterval(interval);
                        setStatus('success');
                        setTimeout(() => window.location.href = json.data.redirect_url, 1500);
                    } else if(json.data.is_cancelled) {
                        clearInterval(interval);
                        window.location.reload();
                    }
                }
            } catch(e) {}
        }, 4000); 
        return () => clearInterval(interval);
    }, [payId]);

    const openGateway = async () => {
        if(methodDetails?.is_gateway && methodDetails?.payment_link) {
             window.open(methodDetails.payment_link, '_blank');
        } else if (methodDetails?.slug && methodDetails?.slug.includes('flutterwave')) {
             try {
                const fd = new FormData();
                fd.append('action', 'tv_flutterwave_init_checkout');
                fd.append('pay_id', payId);
                if (window.TV_CHECKOUT_NONCE) fd.append('_wpnonce', window.TV_CHECKOUT_NONCE);
                const res = await fetch(window.TV_AJAX_URL, { method: 'POST', body: fd });
                const json = await res.json();
                if(json.success && json.data.link) window.open(json.data.link, '_blank');
             } catch(e) {}
        }
    };

    if (status === 'success') {
        return (
            <div className="fixed inset-0 z-[999999] bg-emerald-50 flex flex-col items-center justify-center text-slate-900 animate-fade-in">
                <div className="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center shadow-xl shadow-emerald-200 mb-6 animate-bounce text-white">
                    <Check size={40} strokeWidth={3} />
                </div>
                <h3 className="text-2xl font-bold mb-2">Payment Received!</h3>
                <p className="text-emerald-700">Redirecting to dashboard...</p>
            </div>
        );
    }

    return (
        <div className="fixed inset-0 z-[999999] bg-white flex flex-col items-center justify-center text-slate-900 overflow-y-auto p-6 font-sans">
            <div className="max-w-md w-full text-center">
                <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-400">
                    <Lock size={28} />
                </div>
                <h1 className="text-3xl font-extrabold text-slate-900 mb-3 tracking-tight">Action Required</h1>
                <p className="text-slate-500 text-lg mb-10 leading-relaxed">
                    {isManual ? "Please complete the bank transfer using the details below." : "Your payment is pending. Please complete the transfer using the secure link below."}
                </p>

                <div className="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-xl shadow-slate-100 mb-8 text-left">
                    <div className="flex justify-between items-center mb-6 pb-6 border-b border-slate-100">
                        <div>
                            <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Amount</span>
                            <span className="block text-2xl font-black text-slate-900">{INVOICES.find(i => i.raw_id == payId)?.amount || '---'}</span>
                        </div>
                        <div className="text-right">
                            <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reference</span>
                            <span className="block text-sm font-bold text-slate-600 bg-slate-50 px-3 py-1 rounded-lg">#INV-{String(payId).padStart(5,'0')}</span>
                        </div>
                    </div>

                    {methodDetails?.is_gateway ? (
                        <button onClick={openGateway} className="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2 mb-4 text-base">
                            Retry / Open Payment Link <ExternalLink size={18}/>
                        </button>
                    ) : isManual ? (
                        <button onClick={() => setShowBankModal(true)} className="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-lg shadow-slate-200 transition-all flex items-center justify-center gap-2 mb-4 text-base">
                            See Payment Details <Eye size={18}/>
                        </button>
                    ) : null}

                    <button onClick={() => window.location.href = USER_DATA.tv_flow_urls.upload_proof + '?pay_id=' + payId} className="w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-200 transition-all flex items-center justify-center gap-2 mb-4 text-base">
                        <Check size={18} strokeWidth={3}/> I Have Paid  Upload Proof
                    </button>

                    <button onClick={onCancel} className="w-full py-4 bg-white border-2 border-slate-100 hover:border-rose-100 hover:bg-rose-50 text-slate-400 hover:text-rose-50 font-bold rounded-xl transition-all flex items-center justify-center gap-2 text-sm">
                        Cancel Payment
                    </button>
                </div>

                <p className="text-xs text-slate-400 font-medium">
                    <span className="inline-block w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                    Do not close this tab  return here after payment.
                </p>
            </div>

            {showBankModal && invoice?.bank_details && (
                <div className="fixed inset-0 z-[1000000] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in">
                    <div className="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl relative">
                        <button onClick={() => setShowBankModal(false)} className="absolute top-4 right-4 p-2 bg-slate-100 rounded-full text-slate-500 hover:bg-slate-200 transition-colors"><X size={20}/></button>
                        <h3 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2"><Building size={20} className="text-blue-600"/> Bank Transfer</h3>
                        
                        <div className="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            {invoice.bank_details.bank_name && (
                                <div><p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bank Name</p><p className="font-bold text-slate-900 text-lg">{invoice.bank_details.bank_name}</p></div>
                            )}
                            {invoice.bank_details.account_number && (
                                <div>
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</p>
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="font-mono text-xl font-bold text-slate-900 tracking-wider">{invoice.bank_details.account_number}</p>
                                        <CopyButton text={invoice.bank_details.account_number} />
                                    </div>
                                </div>
                            )}
                            {invoice.bank_details.account_name && (
                                <div><p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Name</p><p className="font-medium text-slate-700">{invoice.bank_details.account_name}</p></div>
                            )}
                        </div>
                        
                        {invoice.bank_details.instructions && (
                            <div className="mt-4 text-xs text-slate-500 bg-blue-50 p-4 rounded-xl border border-blue-100 leading-relaxed" dangerouslySetInnerHTML={{__html: invoice.bank_details.instructions}}></div>
                        )}

                        <button onClick={() => setShowBankModal(false)} className="w-full mt-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">Done</button>
                    </div>
                </div>
            )}
        </div>
    );
};

// --- Main Checkout View ---
const CheckoutView = ({ config, onBack }) => {
    const [step, setStep] = useState(2);
    const [selectedMethod, setSelectedMethod] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    
    const [isLocked, setIsLocked] = useState(false);
    const [activePayId, setActivePayId] = useState(null);
    
    const [couponCode, setCouponCode] = useState('');
    const [appliedCoupon, setAppliedCoupon] = useState(null);
    const [couponMessage, setCouponMessage] = useState({ type: '', text: '' });
    const [isCheckingCoupon, setIsCheckingCoupon] = useState(false);

    const summaryRef = useRef(null);

    const subtotal = Number(config.total); 
    const discountAmount = appliedCoupon ? parseFloat(appliedCoupon.discount) : 0;
    const finalTotalRaw = Math.max(0, subtotal - discountAmount);
    
    const finalTotal = finalTotalRaw.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    const currencySym = config.currencySymbol || '$';

    const handleMethodSelect = (method) => {
        setSelectedMethod(method);
        setTimeout(() => {
            if (summaryRef.current) {
                summaryRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 50);
    };

    const handleApplyCoupon = async () => {
        if (!couponCode) return;
        setIsCheckingCoupon(true);
        setCouponMessage({ type: '', text: '' });
        const formData = new FormData();
        formData.append('action', 'tv_validate_coupon');
        formData.append('code', couponCode);
        formData.append('plan_id', config.plan.id);
        formData.append('current_total', subtotal);
        if (window.TV_CHECKOUT_NONCE) formData.append('_wpnonce', window.TV_CHECKOUT_NONCE);

        try {
            const response = await fetch(window.TV_AJAX_URL, { method: 'POST', body: formData, credentials: 'include' });
            const result = await response.json();
            if (result.success) {
                setAppliedCoupon({ code: result.data.code, discount: result.data.discount_amount });
                setCouponMessage({ type: 'success', text: result.data.message });
            } else {
                setAppliedCoupon(null);
                setCouponMessage({ type: 'error', text: result.data.message || 'Invalid coupon' });
            }
        } catch (e) { setCouponMessage({ type: 'error', text: 'Validation failed' }); }
        setIsCheckingCoupon(false);
    };

    const handleContinueToPayment = async () => {
