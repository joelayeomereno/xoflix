<?php if (!defined('ABSPATH')) { exit; } ?>
        // --- 4. SHARED UI COMPONENTS ---
        const Input = ({ label, value, onChange, type="text", p="", className="" }) => el('div', { className: 'space-y-1 ' + className },
            el('label', { className: 'text-xs font-bold text-slate-400 uppercase tracking-wider' }, label),
            el(type==='textarea'?'textarea':'input', { 
                type, value, onChange: e=>onChange(e.target.value), placeholder:p, 
                className: 'w-full p-3 bg-slate-100 border-none rounded-xl text-sm font-bold text-slate-900 transition-all focus:bg-white focus:ring-2 focus:ring-primary-500/20' 
            })
        );

        const Sheet = ({ open, onClose, children, title, actions }) => {
            if(!open) return null;
            return el('div', { className: 'fixed inset-0 z-50 flex items-end justify-center' },
                el('div', { className: 'absolute inset-0 sheet-backdrop animate-in', onClick: onClose }),
                el('div', { className: 'bg-white w-full rounded-t-3xl shadow-2xl relative z-10 animate-slide-up max-h-[92vh] flex flex-col' },
                    el('div', { className: 'w-12 h-1.5 bg-slate-200 rounded-full mx-auto mt-3 mb-2 shrink-0' }),
                    title && el('div', { className: 'px-6 pb-4 border-b border-slate-100 shrink-0 flex justify-between items-center' }, 
                        el('h3', { className: 'text-lg font-black' }, title),
                        el('button', { onClick: onClose, className: 'p-2 bg-slate-50 rounded-full btn-press' }, el(Icon, {name:'x', size:18}))
                    ),
                    el('div', { className: 'overflow-y-auto p-6 space-y-4 pb-safe' }, children),
                    actions && el('div', { className: 'p-4 border-t border-slate-100 pb-safe shrink-0' }, actions)
                )
            );
        };

        const Repeater = ({ label, items, onChange, fields }) => {
            const add = () => onChange([...items, fields.reduce((acc,f)=>({...acc,[f.k]:''}), {})]);
            const update = (idx, k, v) => { const n = [...items]; n[idx][k] = v; onChange(n); };
            const remove = (idx) => onChange(items.filter((_,i) => i!==idx));

            return el('div', { className: 'space-y-2 border border-slate-100 p-3 rounded-xl' },
                el('div', { className: 'flex justify-between items-center' },
                    el('label', { className: 'text-xs font-bold text-slate-400 uppercase' }, label),
                    el('button', { onClick: add, className: 'text-primary-600 text-xs font-bold' }, '+ Add Item')
                ),
                items.map((item, idx) => el('div', { key: idx, className: 'p-3 bg-slate-50 rounded-lg relative space-y-2' },
                    el('button', { onClick:()=>remove(idx), className: 'absolute top-2 right-2 text-rose-400' }, el(Icon, {name:'x', size:14})),
                    fields.map(f => el(Input, { key:f.k, label:f.l, value:item[f.k], onChange:v=>update(idx,f.k,v) }))
                ))
            );
        };

