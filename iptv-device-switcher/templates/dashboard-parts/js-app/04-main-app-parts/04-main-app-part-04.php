placeholder="Your full name"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Email (Read Only)</label>
                                            <div className="relative">
                                                <input 
                                                    type="email" 
                                                    value={profileForm.email} 
                                                    readOnly
                                                    style={{ color: '#000000', backgroundColor: '#e2e8f0', opacity: 1, fontWeight: '700' }}
                                                    className="w-full px-4 py-3 bg-gray-200 border border-slate-200 rounded-xl text-sm font-bold text-black cursor-not-allowed opacity-100" 
                                                />
                                                <div className="absolute right-3 top-3 text-emerald-500 text-xs font-bold bg-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                                    <Check size={10} strokeWidth={4} /> Verified
                                                </div>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Phone</label>
                                                <input 
                                                    type="tel" 
                                                    value={profileForm.phone} 
                                                    onChange={(e) => setProfileForm({...profileForm, phone: e.target.value})}
                                                    className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                                                    placeholder="+1..."
                                                />
                                            </div>
                                            {/* REPLACED: Location now Read-Only */}
                                            <div>
                                                <label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Location</label>
                                                <LockedCountryDisplay countryCode={profileForm.country} />
                                            </div>
                                        </div>

                                        <div>
                                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Preferred Currency</label>
                                            <select 
                                                className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                                                value={profileForm.currency}
                                                onChange={(e) => setProfileForm({...profileForm, currency: e.target.value})}
                                            >
                                                {['USD','EUR','GBP','NGN','GHS','KES','ZAR','INR','AED','BRL','CAD','AUD'].map(c => <option key={c} value={c}>{c}</option>)}
                                            </select>
                                        </div>
                                        
                                        {profileMsg && <p className={`text-xs font-bold text-center ${profileMsg.includes('Error') || profileMsg.includes('Connection') ? 'text-rose-500' : 'text-emerald-500'}`}>{profileMsg}</p>}
                                        
                                        <button 
                                            onClick={handleProfileSave}
                                            disabled={isSavingProfile}
                                            className="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-lg transition-all disabled:opacity-70 flex justify-center items-center gap-2"
                                        >
                                            {isSavingProfile ? <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> : 'Save Changes'}
                                        </button>
                                    </div>
                                </div>

                                <div className="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm h-full flex flex-col">
                                    <h3 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center"><Lock size={18}/></div>
                                        Security
                                    </h3>
                                    
                                    <div className="space-y-4 mb-8 flex-1">
                                        <div className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                                            <div><p className="font-bold text-sm">Email Notifications</p><p className="text-xs text-slate-500">Order updates & news</p></div>
                                            <div className="w-10 h-6 bg-emerald-500 rounded-full relative"><div className="w-4 h-4 bg-white rounded-full absolute top-1 right-1"></div></div>
                                        </div>
                                        <div className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                                            <div><p className="font-bold text-sm">Password</p><p className="text-xs text-slate-500">Managed via logout</p></div>
                                            <button onClick={() => window.location.href='/forgot-password'} className="text-xs font-bold text-blue-600 hover:underline">Reset</button>
                                        </div>
                                    </div>

                                    <a href="<?php echo wp_logout_url(home_url()); ?>" className="w-full py-4 bg-rose-50 text-rose-600 font-bold rounded-xl text-sm hover:bg-rose-100 transition-colors flex items-center justify-center gap-2">
                                        <LogOut size={18}/> Sign Out of Account
                                    </a>
                                </div>
                            </div>
                        </div>
                    )}

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
const BottomNav = ({ activeTab, setActiveTab }) => {
    const items = [{id:'dashboard',icon:LayoutDashboard,label:'Home'},{id:'shop',icon:ShoppingBag,label:'Store'},{id:'subscription',icon:FileText,label:'Subs'},{id:'sports',icon:Trophy,label:'Sports'},{id:'billing',icon:CreditCard,label:'Bill'}];
    return <div className="fixed bottom-0 w-full bg-white border-t border-slate-200 flex justify-around py-3 pb-safe z-40">{items.map(i => <button key={i.id} onClick={()=>setActiveTab(i.id)} className={`flex flex-col items-center ${activeTab===i.id?'text-blue-600':'text-slate-400'}`}><i.icon size={20}/><span className="text-[10px] font-bold mt-1">{i.label}</span></button>)}</div>;
};

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<IPTVDashboard />);