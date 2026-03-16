<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
                    {activeTab === 'support' && (
                        <div className="flex flex-col items-center justify-center h-[60vh] text-center animate-slide-up">
                            <div className="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-6"><HelpCircle className="text-slate-300" size={32} /></div>
                            <h3 className="text-xl font-bold text-slate-900 mb-2">Support Center</h3>
                            <p className="text-slate-500 font-medium max-w-sm mb-8">Need help? Our team is available 24/7 via WhatsApp and Email.</p>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 w-full max-w-md">
                                {typeof window.SUPPORT_CONFIG !== 'undefined' && window.SUPPORT_CONFIG.email && (
                                    <a href={`mailto:${window.SUPPORT_CONFIG.email}`} className="flex items-center justify-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl hover:border-blue-500 hover:shadow-md transition-all group">
                                        <div className="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center text-blue-600"><FileText size={18}/></div><div className="text-left"><p className="font-bold text-slate-900">Email Support</p><p className="text-xs text-slate-500">Response in 2h</p></div>
                                    </a>
                                )}
                                {typeof window.SUPPORT_CONFIG !== 'undefined' && window.SUPPORT_CONFIG.whatsapp && (
                                    <a href={`https://wa.me/${window.SUPPORT_CONFIG.whatsapp}`} target="_blank" className="flex items-center justify-center gap-3 p-4 bg-white border border-slate-200 rounded-2xl hover:border-emerald-500 hover:shadow-md transition-all group">
                                        <div className="w-10 h-10 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600"><div className="w-4 h-4 bg-current rounded-full"></div></div><div className="text-left"><p className="font-bold text-slate-900">Live Chat</p><p className="text-xs text-slate-500">WhatsApp Online</p></div>
                                    </a>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <BottomNav activeTab={activeTab} setActiveTab={setActiveTab} />
                <Drawer isOpen={isDrawerOpen} onClose={() => setIsDrawerOpen(false)} activeTab={activeTab} setActiveTab={setActiveTab} logoutUrl="<?php echo wp_logout_url(home_url()); ?>" />
                {uploadInvoice && <ProofUploadModal invoice={uploadInvoice} onClose={() => setUploadInvoice(null)} />}
            </main>
        </div>
    );
}

// Mobile Nav Component
