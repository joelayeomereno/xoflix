<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
                    {/* 5. BILLING VIEW */}
                    {activeTab === 'billing' && (
                        <div className="max-w-5xl mx-auto space-y-6 animate-slide-up">
                            <h1 className="text-3xl font-bold text-slate-900">Billing History</h1>
                            <div className="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                                <table className="w-full text-left">
                                    <thead className="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">ID</th>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">Date</th>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">Amount</th>
                                            <th className="p-5 text-xs font-bold text-slate-400 uppercase">Status</th>
                                            <th className="p-5"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {displayedInvoices.map(inv => (
                                            <tr key={inv.id} className="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                                                <td className="p-5 font-mono text-sm font-bold">{inv.id}</td>
                                                <td className="p-5 text-sm text-slate-500">{inv.date}</td>
                                                <td className="p-5 font-bold" dangerouslySetInnerHTML={{ __html: inv.amount }}></td>
                                                <td className="p-5"><StatusBadge status={inv.status}/></td>
                                                <td className="p-5 text-right">
                                                    {(inv.needs_proof || inv.has_gateway_link) && ['pending','AWAITING_PROOF','PENDING_ADMIN_REVIEW'].includes(inv.raw_status) ? (
                                                        inv.has_gateway_link && !inv.attempt_recent ? (
                                                            <a href={inv.payment_link} target="_blank" className="text-xs font-bold text-blue-600 hover:underline">Pay Now</a>
                                                        ) : (
                                                            <button onClick={() => setUploadInvoice(inv)} className="text-xs font-bold text-blue-600 hover:underline">
                                                                {inv.has_gateway_link ? "Paid? Upload Proof" : "Upload Proof"}
                                                            </button>
                                                        )
                                                    ) : null}
                                                </td>
                                            </tr>
                                        ))}
                                        {invoicesList.length === 0 && <tr><td colSpan="5" className="p-8 text-center text-slate-400">No invoices found.</td></tr>}
                                    </tbody>
                                </table>
                                
                                {totalBillingPages > 1 && (
                                    <div className="p-4 border-t border-slate-100 flex justify-between items-center bg-slate-50/30">
                                        <button 
                                            onClick={() => setBillingPage(p => Math.max(1, p - 1))}
                                            disabled={billingPage === 1}
                                            className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold disabled:opacity-50 hover:bg-slate-50 transition-colors"
                                        >
                                            Previous
                                        </button>
                                        <span className="text-xs font-bold text-slate-500">Page {billingPage} of {totalBillingPages}</span>
                                        <button 
                                            onClick={() => setBillingPage(p => Math.min(totalBillingPages, p + 1))}
                                            disabled={billingPage === totalBillingPages}
                                            className="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold disabled:opacity-50 hover:bg-slate-50 transition-colors"
                                        >
                                            Next
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* 6. PROFILE VIEW (UPDATED: Locked Location) */}
                    {activeTab === 'profile' && (
                        <div className="max-w-4xl mx-auto animate-slide-up pb-10 space-y-8">
                            
                            <div className="bg-gradient-to-r from-slate-900 to-slate-800 rounded-[2.5rem] p-8 md:p-12 text-white shadow-xl relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
                                <div className="relative z-10 flex flex-col md:flex-row items-center gap-8">
                                    <div className="relative group">
                                        <img src={userDefaults.avatar} alt={userDefaults.name} className="w-32 h-32 rounded-full border-4 border-white/20 shadow-2xl object-cover" />
                                        <div className="absolute bottom-2 right-2 bg-emerald-500 w-6 h-6 rounded-full border-4 border-slate-900"></div>
                                    </div>
                                    <div className="text-center md:text-left">
                                        <h1 className="text-3xl md:text-4xl font-black tracking-tight mb-2">{profileForm.name || userDefaults.name}</h1>
                                        <p className="text-slate-400 font-medium mb-4">{profileForm.email || userDefaults.email}</p>
                                        <div className="inline-flex gap-2">
                                            {hasActiveSub ? (
                                                <span className="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full text-xs font-bold uppercase tracking-wider">Premium Member</span>
                                            ) : (
                                                <span className="px-3 py-1 bg-slate-700 text-slate-300 border border-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">Free Account</span>
                                            )}
                                            <span className="px-3 py-1 bg-white/10 text-white rounded-full text-xs font-bold uppercase tracking-wider">Joined {userDefaults.joined || 'Recently'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-sm h-full">
                                    <h3 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><User size={18}/></div>
                                        Edit Personal Details
                                    </h3>
                                    <div className="space-y-5">
                                        <div>
                                            <label className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block ml-1">Full Name</label>
                                            <input 
                                                type="text" 
                                                value={profileForm.name} 
                                                onChange={(e) => setProfileForm({...profileForm, name: e.target.value})}
                                                className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
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

