<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
                    {/* 4. SPORTS VIEW */}
                    {activeTab === 'sports' && (
                        <div className="max-w-7xl mx-auto space-y-8 animate-slide-up">
                            {/* Header & Filter */}
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                                <div className="shrink-0">
                                    <h1 className="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-2">Live Sports Guide</h1>
                                    <p className="text-slate-500 font-medium">Real-time schedules for top leagues.</p>
                                </div>
                                
                                {/* Filter Tabs - Scrollable Container */}
                                <div className="w-full md:w-auto min-w-0 overflow-x-auto no-scrollbar">
                                    <div className="flex flex-nowrap gap-3 items-center pb-2 pr-6 md:pr-0">
                                        {[ {id:'all',label:'All Events'}, {id:'live',label:'Live Now'}, {id:'soccer',label:'Football'}, {id:'nba',label:'Basketball'}, {id:'ufc',label:'Fighting'} ].map(f => (
                                            <button 
                                                key={f.id} 
                                                onClick={() => setFilterSport(f.id)} 
                                                className={`flex-shrink-0 flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all border ${filterSport === f.id ? 'bg-slate-900 text-white border-slate-900 shadow-lg' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300'}`}
                                            >
                                                {f.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                            
                            {/* Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                {filteredSports.length === 0 && (
                                    <div className="col-span-full py-20 text-center bg-white rounded-[2rem] border border-slate-200">
                                        <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                                            <Trophy size={24} />
                                        </div>
                                        <p className="text-slate-500 font-medium">No scheduled events found.</p>
                                    </div>
                                )}
                                {filteredSports.map(ev => (
                                    <SportsCard key={ev.id} event={ev} onClick={() => setSelectedSport(ev)} />
                                ))}
                            </div>

                            {/* Modal */}
                            {selectedSport && (
                                <SportsModal event={selectedSport} onClose={() => setSelectedSport(null)} />
                            )}
                        </div>
                    )}