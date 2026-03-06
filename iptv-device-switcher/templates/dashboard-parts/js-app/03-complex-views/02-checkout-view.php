<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
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
        if (!selectedMethod) {
            alert("Please select a payment method.");
            return;
        }
        
        setIsSubmitting(true);

        const formData = new FormData();
        formData.append('action', 'tv_save_checkout_session');
        formData.append('plan_id', config.plan.id);
        formData.append('payment_method_id', selectedMethod.id);
        formData.append('connections', config.connections);
        formData.append('custom_months', config.months);
        
        // Pass Subscription Targeting if active
        if (config.targetSubId) {
            formData.append('target_subscription_id', config.targetSubId);
        }

        if (appliedCoupon) formData.append('coupon_code', appliedCoupon.code);
        if (window.TV_CHECKOUT_NONCE) formData.append('_wpnonce', window.TV_CHECKOUT_NONCE);

        try {
            const response = await fetch(window.TV_AJAX_URL, { method: 'POST', body: formData, credentials: 'include' });
            const result = await response.json();
             
            if (result.success) {
                 if (result.data.redirect_url) {
                      window.location.href = result.data.redirect_url;
                      return;
                 }
                 if(result.data.pay_id) setActivePayId(result.data.pay_id);
                 setIsLocked(true); 
                 
                 if(selectedMethod.is_gateway && selectedMethod.payment_link) {
                      window.open(selectedMethod.payment_link, '_blank');
                 }
            } else {
                 let msg = result.data?.message || result.message || "Unknown error";
                 if (msg.includes('session')) window.location.href = '/login';
                 else alert('Error: ' + msg);
                 setIsSubmitting(false);
            }
        } catch (e) {
            alert('Connection failed.');
            setIsSubmitting(false);
        }
    };
     
    const handleCancelLockdown = async () => {
        if(!confirm('Cancel transaction?')) return;
        try {
            const fd = new FormData();
            fd.append('action', 'tv_cancel_payment');
            fd.append('pay_id', activePayId);
            await fetch(window.TV_AJAX_URL, { method: 'POST', body: fd });
            setIsLocked(false);
            setIsSubmitting(false);
        } catch(e) { window.location.reload(); }
    };

    return (
        <div className="animate-slide-up max-w-4xl mx-auto relative">
            {isSubmitting && <FullScreenLoader />}
            {isLocked && <LockdownOverlay payId={activePayId} methodDetails={selectedMethod} onCancel={handleCancelLockdown} />}

            <button onClick={onBack} className="absolute top-0 left-0 -ml-12 p-3 text-slate-400 hover:text-slate-900 hidden md:block transition-colors"><ArrowLeft size={24}/></button>
            <div className="text-center mb-8">
                <h1 className="text-3xl font-black text-slate-900 tracking-tight mb-2">Secure Checkout</h1>
                <p className="text-slate-500 font-medium">Complete your subscription setup.</p>
            </div>
            <StepIndicator current={step} />
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-white rounded-[2rem] border border-slate-200 p-6 md:p-8 shadow-sm">
                        <h3 className="font-bold text-lg text-slate-900 mb-6 flex items-center gap-2"><Wallet size={20} className="text-blue-600"/> Payment Method</h3>
                        <div className="space-y-4">
                            {PAYMENT_METHODS.length === 0 && <div className="text-center py-10 text-slate-400">No payment methods available.</div>}
                            {PAYMENT_METHODS.map(method => (
                                <div key={method.id} onClick={() => handleMethodSelect(method)} className={`relative p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 group hover:shadow-md flex items-center gap-4 ${selectedMethod && selectedMethod.id === method.id ? "border-blue-600 bg-blue-50/50 ring-1 ring-blue-600" : "border-slate-100 hover:border-blue-300 bg-white"}`}>
                                    <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors ${selectedMethod && selectedMethod.id === method.id ? "border-blue-600 bg-blue-600 text-white" : "border-slate-300 bg-white"}`}>{selectedMethod && selectedMethod.id === method.id && <div className="w-2.5 h-2.5 bg-white rounded-full"></div>}</div>
                                    <div className="w-14 h-10 flex items-center justify-center bg-white rounded-lg border border-slate-200 p-1">{method.logo_url ? <img src={method.logo_url} alt={method.name} className="h-full object-contain" /> : <CreditCard size={20} className="text-slate-400"/>}</div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1"><h4 className="font-bold text-slate-900 truncate">{method.name}</h4>{method.is_recommended && <span className="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase rounded-md tracking-wide">Recommended</span>}</div>
                                        <p className="text-xs text-slate-500 truncate">{method.description ? method.description.replace(/<[^>]*>?/gm, '').substring(0, 60) : 'Secure payment'}</p>
                                    </div>
                                    <div className="hidden sm:flex flex-col items-end gap-1">{method.tags && method.tags.map((tag, i) => (<span key={i} className="text-[10px] font-bold bg-slate-100 text-slate-500 px-2 py-1 rounded-md uppercase tracking-wider">{tag}</span>))}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
                 
                <div className="md:col-span-1" ref={summaryRef} style={{ scrollMarginTop: '100px' }}>
                    <div className="bg-slate-900 text-white rounded-[2rem] p-6 shadow-2xl sticky top-24">
                        <h3 className="font-bold text-sm uppercase tracking-widest text-slate-400 mb-6">Order Summary</h3>
                         
                        {config.targetSubId && (
                            <div className="mb-4 bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                                <Check size={14} strokeWidth={3} />
                                Extending Subscription #{config.targetSubId}
                            </div>
                        )}

                        <div className="space-y-4 mb-6 border-b border-slate-700 pb-6">
                            <div className="flex justify-between items-center"><span className="text-sm font-medium text-slate-300">Plan</span><span className="font-bold">{config.plan.name}</span></div>
                            <div className="flex justify-between items-center"><span className="text-sm font-medium text-slate-300">Duration</span><span className="font-bold">{config.months} Month{config.months>1?'s':''}</span></div>
                             
                            {config.discountPercent > 0 && (
                                <div className="flex justify-between items-center text-emerald-400 animate-fade-in">
                                    <span className="text-sm font-bold flex items-center gap-1"><Zap size={12}/> Volume Discount</span>
                                    <span className="font-bold">-{config.discountPercent}%</span>
                                </div>
                            )}

                            {appliedCoupon && (<div className="flex justify-between items-center text-emerald-400 animate-fade-in"><span className="text-sm font-bold flex items-center gap-1"><Zap size={12}/> Coupon ({appliedCoupon.code})</span><span className="font-bold">-{currencySym}{Number(appliedCoupon.discount).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits:0})}</span></div>)}
                        </div>
                        <div className="mb-6">
                            <label className="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2 block">Discount Code</label>
                            <div className="flex gap-2">
                                <input type="text" value={couponCode} onChange={(e) => setCouponCode(e.target.value.toUpperCase())} placeholder="Enter Code" className="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500 uppercase placeholder-slate-600"/>
                                <button onClick={handleApplyCoupon} disabled={!couponCode || isCheckingCoupon || appliedCoupon} className="bg-slate-700 hover:bg-slate-600 disabled:opacity-50 text-white text-xs font-bold px-3 py-2 rounded-lg transition-colors">{isCheckingCoupon ? '...' : (appliedCoupon ? '?' : 'Apply')}</button>
                            </div>
                            {couponMessage.text && (<p className={`text-[10px] mt-2 font-bold ${couponMessage.type === 'error' ? 'text-rose-400' : 'text-emerald-400'}`}>{couponMessage.text}</p>)}
                        </div>
                         
                        <div className="flex justify-between items-end mb-8">
                            <span className="text-slate-400 font-medium">Total</span>
                            <div className="text-right">
                                {config.originalTotal > subtotal && (
                                    <span className="block text-xs text-slate-500 line-through mb-1">
                                        {currencySym}{config.originalTotal.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}
                                    </span>
                                )}
                                <span className="text-4xl font-black tracking-tighter text-white">{currencySym}{finalTotal}</span>
                            </div>
                        </div>
                        <button onClick={handleContinueToPayment} disabled={!selectedMethod || isSubmitting} className={`w-full py-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 shadow-lg ${!selectedMethod || isSubmitting ? 'bg-slate-700 text-slate-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-500 text-white hover:shadow-blue-500/25 active:scale-95'}`}>{isSubmitting ? 'Processing...' : 'Pay Now Securely'} <ArrowRight size={18}/></button>
                        <p className="text-center text-xs text-slate-500 mt-4 font-medium"><Lock size={10} className="inline mr-1"/> SSL Encrypted Transaction</p>
                    </div>
                </div>
            </div>
            <TrustStrip />
        </div>
    );
};

