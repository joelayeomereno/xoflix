<?php if (!defined('ABSPATH')) { exit; } ?>
    /* ---------------------------------------------
        FINANCE / TRANSACTIONS  (the PAY tab)
        Mirrors the main admin view-finance.php:
        weekly grouping, total/new/renewals, by method, by plan
    --------------------------------------------- */
    const Finance = ({ onBack }) => {
      const [data, setData]       = useState(null);
      const [range, setRange]     = useState('4w');
      const [loading, setLoading] = useState(true);
      const [detail, setDetail]   = useState(null); // expanded week row

      const load = useCallback(async (r=range) => {
        setLoading(true);
        try {
          const res = await apiFetch(`finance?range=${r}`);
          setData(res);
        }
        catch(_){ setData(null); }
        finally { setLoading(false); }
      }, [range]);

      useEffect(() => { load(range); }, [range]);

      const summary = useMemo(() => {
        if (!data?.weeks) return null;
        const weeks = Object.values(data.weeks);
        return {
          total:       weeks.reduce((s,w) => s + parseFloat(w.total||0), 0),
          count:       weeks.reduce((s,w) => s + parseInt(w.count||0), 0),
          new_total:   weeks.reduce((s,w) => s + parseFloat(w.new_total||0), 0),
          renew_total: weeks.reduce((s,w) => s + parseFloat(w.renew_total||0), 0),
        };
      }, [data]);

      const fmt = n => {
        const num = parseFloat(n||0);
        const sym = data?.currency_symbol || '$';
        return sym + num.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
      };

      const weeks = data?.weeks ? Object.entries(data.weeks) : [];

      return el('div', { className:'page-wrap' },
        onBack
          ? el(BackHeader, { title:'Finance', onBack,
              right: el('div', { style:{display:'flex',gap:8,alignItems:'center'} },
                data?.csv_url && el('a', { href:data.csv_url, className:'btn btn-ghost btn-sm press', style:{textDecoration:'none'} }, el(Icon,{name:'fileText',size:14}), 'CSV'),
                el('button', { className:'btn btn-ghost btn-sm press', onClick:()=>load(range) }, el(Icon,{name:'refresh',size:14}))
              )
            })
          : el('div', { className:'page-header top-safe' },
              el('div', { style:{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12} },
                el('div', null,
                  el('h1', { style:{fontSize:24,fontWeight:900} }, 'Finance'),
                  el('p', { style:{fontSize:12,color:'var(--muted)',marginTop:2} }, 'Weekly revenue \u00b7 Mon\u2013Sun')
                ),
                el('div', { style:{display:'flex',gap:8,alignItems:'center'} },
                  data?.csv_url && el('a', { href:data.csv_url, className:'btn btn-ghost btn-sm press', style:{textDecoration:'none'} },
                    el(Icon,{name:'fileText',size:14}), 'CSV'
                  ),
                  el('button', { className:'btn btn-ghost btn-sm press', onClick:()=>load(range) },
                    el(Icon,{name:'refresh',size:14})
                  )
                )
              ),
              el('div', { className:'chips-row' },
                [['4w','4 Weeks'],['8w','8 Weeks'],['12w','12 Weeks']].map(([v,l]) =>
                  el('button', { key:v, className:`chip press ${range===v?'active':''}`, onClick:()=>setRange(v) }, l)
                )
              )
            ),
        onBack && el('div', { style:{padding:'10px 12px',borderBottom:'1px solid var(--border)',flexShrink:0} },
          el('div', { className:'chips-row' },
            [['4w','4 Weeks'],['8w','8 Weeks'],['12w','12 Weeks']].map(([v,l]) =>
              el('button', { key:v, className:`chip press ${range===v?'active':''}`, onClick:()=>setRange(v) }, l)
            )
          )
        ),
        el('div', { className: onBack ? 'page-scroll no-nav' : 'page-scroll' },
          loading && el(Loader),
          !loading && summary && el('div', { style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10,padding:'12px 12px 0'} },
            [
              ['Total Revenue', fmt(summary.total), 'dollar', '#6366f1', 'rgba(99,102,241,.12)'],
              ['Transactions',  summary.count,       'creditCard','#22c55e','rgba(34,197,94,.12)'],
              ['New Subs',      fmt(summary.new_total),'userPlus','#06b6d4','rgba(6,182,212,.12)'],
              ['Renewals',      fmt(summary.renew_total),'refresh','#f59e0b','rgba(245,158,11,.12)'],
            ].map(([label,val,icon,color,bg]) =>
              el('div', { key:label, className:'card stat-card' },
                el('div', { className:'stat-glow', style:{background:`radial-gradient(circle at 80% 20%,${color},transparent 70%)`} }),
                el('div', { style:{width:32,height:32,borderRadius:9,background:bg,display:'flex',alignItems:'center',justifyContent:'center',color,flexShrink:0} },
                  el(Icon,{name:icon,size:16})
                ),
                el('div', { style:{minWidth:0} },
                  el('p', { style:{fontSize:10,color:'var(--muted)',fontWeight:700,letterSpacing:'.06em',textTransform:'uppercase'} }, label),
                  el('p', { style:{fontSize:18,fontWeight:900,lineHeight:1.2,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',fontVariantNumeric:'tabular-nums'} },
                    String(val)
                  )
                )
              )
            )
          ),
          !loading && weeks.length === 0 && el(Empty, { text:'No transactions in this period', icon:'dollar' }),
          !loading && weeks.map(([wk, w]) => {
            const isOpen = detail === wk;
            const hasBreakdown = (w.by_method && Object.keys(w.by_method).length > 0) ||
                                 (w.by_plan   && Object.keys(w.by_plan).length   > 0);
            return el('div', { key:wk, className:'card anim-up', style:{margin:'8px 12px 0'} },
              el('div', { style:{padding:'14px 16px',display:'flex',alignItems:'center',gap:10,cursor:'pointer'},
                onClick:()=>setDetail(isOpen?null:wk) },
                el('div', { style:{flex:1,minWidth:0} },
                  el('p', { style:{fontWeight:800,fontSize:15,marginBottom:2} },
                    `${w.week_start_fmt||wk} – ${w.week_end_fmt||''}`
                  ),
                  el('p', { style:{fontSize:11,color:'var(--muted)',fontWeight:600} },
                    `${w.count||0} txn${w.count!==1?'s':''} · ${fmt(w.new_total)} new · ${fmt(w.renew_total)} renewal`
                  )
                ),
                el('div', { style:{textAlign:'right',flexShrink:0} },
                  el('p', { style:{fontSize:20,fontWeight:900,fontVariantNumeric:'tabular-nums',color:'var(--green)'} }, fmt(w.total)),
                  el('div', { style:{display:'flex',alignItems:'center',justifyContent:'flex-end',gap:4,marginTop:2} },
                    el(Icon,{name:isOpen?'chevronUp':'chevronDown',size:14,color:'var(--muted)'})
                  )
                )
              ),
              isOpen && hasBreakdown && el('div', { className:'anim-in', style:{borderTop:'1px solid var(--border)',padding:'12px 16px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:12} },
                el('div', null,
                  el('p', { style:{fontSize:11,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.06em',marginBottom:8} }, 'By Method'),
                  w.by_method && Object.entries(w.by_method).length > 0
                    ? Object.entries(w.by_method).map(([m,amt]) =>
                        el('div', { key:m, style:{display:'flex',justifyContent:'space-between',padding:'5px 0',borderBottom:'1px solid var(--border)',fontSize:13} },
                          el('span', { style:{color:'var(--muted)',fontWeight:600,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',maxWidth:'55%'} }, m),
                          el('span', { style:{fontWeight:800,fontVariantNumeric:'tabular-nums'} }, fmt(amt))
                        )
                      )
                    : el('p', { style:{fontSize:12,color:'var(--muted)'} }, '–')
                ),
                el('div', null,
                  el('p', { style:{fontSize:11,fontWeight:800,color:'var(--muted)',textTransform:'uppercase',letterSpacing:'.06em',marginBottom:8} }, 'By Plan'),
                  w.by_plan && Object.entries(w.by_plan).length > 0
                    ? Object.entries(w.by_plan).map(([pn,amt]) =>
                        el('div', { key:pn, style:{display:'flex',justifyContent:'space-between',padding:'5px 0',borderBottom:'1px solid var(--border)',fontSize:13} },
                          el('span', { style:{color:'var(--muted)',fontWeight:600,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',maxWidth:'55%'} }, pn),
                          el('span', { style:{fontWeight:800,fontVariantNumeric:'tabular-nums'} }, fmt(amt))
                        )
                      )
                    : el('p', { style:{fontSize:12,color:'var(--muted)'} }, '–')
                )
              ),
              isOpen && !hasBreakdown && el('div', { className:'anim-in', style:{borderTop:'1px solid var(--border)',padding:'12px 16px'} },
                el('p', { style:{fontSize:13,color:'var(--muted)',textAlign:'center'} }, 'No method/plan breakdown available for this week.')
              )
            );
          })
        )
      );
    };

    const Payments = ({ onBack }) => {
      const [list, setList]       = useState([]);
      const [page, setPage]       = useState(1);
      const [meta, setMeta]       = useState({total:0,pages:1});
      const [filter, setFilter]   = useState('pending');
      const [loading, setLoading] = useState(true);
      const [sheet, setSheet]     = useState(null);
      const [wizMode, setWizMode] = useState(false);
      const [creds, setCreds]     = useState({ user:'', pass:'', m3u:'', url:'' });

      const load = useCallback(async () => {
        setLoading(true);
        try {
          const r = await apiFetch(`payments?status=${filter}&page=${page}`);
          setList(r.data||[]);
          setMeta({ total: r.total, pages: r.pages });
        }
        catch(_){ setList([]); }
        finally { setLoading(false); }
      }, [filter, page]);

      useEffect(() => { load(); }, [filter, page]);

      const approve = async (withCreds=false) => {
        const ok = await confirm('Approve Payment', `Approve invoice #${sheet.id}?`, { confirmLabel:'Approve', confirmStyle:'primary' });
        if (!ok) return;
        try {
          await apiFetch(`payments/${sheet.id}/action`, 'POST', { action:'approve', creds: withCreds?creds:null });
          showToast('Payment approved ?'); setSheet(null); setWizMode(false); load();
        } catch(_){}
      };

      const reject = async () => {
        const code = Math.floor(1000+Math.random()*9000);
        const ok = await confirm('Reject Payment', `Reject invoice #${sheet.id}? Cannot be undone.`, { danger:true, codeRequired:true, code, confirmLabel:'Reject' });
        if (!ok) return;
        try {
          await apiFetch(`payments/${sheet.id}/action`, 'POST', { action:'reject' });
          showToast('Payment rejected'); setSheet(null); load();
        } catch(_){}
      };

      const parseM3U = () => {
        const l = creds.m3u;
        const u = l.match(/username=([^&\s]+)/), p = l.match(/password=([^&\s]+)/);
        if (u && p) setCreds(c => ({...c, user:u[1], pass:p[1]}));
        try { setCreds(c => ({...c, url:new URL(l).origin})); } catch(_){}
      };

      return el('div', { className:'page-wrap' },
        onBack
          ? el(BackHeader, { title:'Payments', onBack,
              right: el('button', { className:'btn btn-ghost btn-sm press', onClick:load }, el(Icon,{name:'refresh',size:15}))
            })
          : el('div', { className:'page-header top-safe' },
              el('div', { style:{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12} },
                el('div', null,
                  el('h1', { style:{fontSize:24,fontWeight:900} }, 'Payments'),
                  el('p',  { style:{fontSize:12,color:'var(--muted)',marginTop:2} }, 'Transaction history')
                ),
                el('button', { className:'btn btn-ghost btn-sm press', onClick:load }, el(Icon,{name:'refresh',size:15}))
              ),
              el('div', { className:'chips-row' },
                ['pending','all','completed','rejected'].map(f =>
                  el('button', { key:f, className:`chip press ${filter===f?'active':''}`, onClick:()=>{setFilter(f); setPage(1);} },
                    { pending:'?? Pending', all:'All', completed:'? Done', rejected:'? Rejected' }[f]
                  )
                )
              )
            ),
        onBack && el('div', { style:{padding:'10px 14px 0',borderBottom:'1px solid var(--border)',flexShrink:0} },
          el('div', { className:'chips-row' },
            ['pending','all','completed','rejected'].map(f =>
              el('button', { key:f, className:`chip press ${filter===f?'active':''}`, onClick:()=>{setFilter(f); setPage(1);} },
                { pending:'?? Pending', all:'All', completed:'? Completed', rejected:'? Rejected' }[f]
              )
            )
          )
        ),
        el('div', { className: onBack ? 'page-scroll no-nav' : 'page-scroll' },
          loading && el(Loader),
          !loading && list.length===0 && el(Empty, { text:'No payments found', icon:'creditCard' }),
          !loading && list.map(p =>
            el('div', { key:p.id, className:'card press anim-up', style:{margin:'12px 12px 0',padding:'14px 16px',display:'flex',justifyContent:'space-between',alignItems:'center',cursor:'pointer'},
              onClick:()=>{setSheet(p);setWizMode(false);} },
              el('div', { style:{display:'flex',alignItems:'center',gap:12,minWidth:0} },
                el('div', { style:{width:42,height:42,borderRadius:12,background:'var(--surface2)',display:'flex',alignItems:'center',justifyContent:'center',fontSize:14,fontWeight:800,color:'var(--accent)',flexShrink:0} }, '#'+p.id),
                el('div', { style:{minWidth:0} },
                  el('p', { style:{fontWeight:700,fontSize:15,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'} }, p.user_login||'Unknown'),
                  el('p', { style:{fontSize:12,color:'var(--muted)',marginTop:2} }, p.plan_name||p.time_ago||'')
                )
              ),
              el('div', { style:{textAlign:'right',flexShrink:0,marginLeft:12} },
                el('p', { style:{fontWeight:900,fontSize:17,fontVariantNumeric:'tabular-nums'} }, (p.currency_symbol||'$')+Number(p.amount||0).toLocaleString()),
                el(StatusBadge, { s:p.status })
              )
            )
          ),
          !loading && meta.pages > 1 && el('div', { className:'page-ctrl' },
            el('button', { className:'page-num press', onClick:()=>setPage(1), disabled:page===1, style:{fontSize:11} }, '«'),
            el('button', { className:'page-num press', onClick:()=>setPage(p=>Math.max(1,p-1)), disabled:page===1 }, el(Icon,{name:'chevronLeft',size:14})),
            el('span', { className:'text-xs font-bold text-slate-400' }, `Page ${page} / ${meta.pages}`),
            el('button', { className:'page-num press', onClick:()=>setPage(p=>Math.min(meta.pages,p+1)), disabled:page===meta.pages }, el(Icon,{name:'chevronRight',size:14})),
            el('button', { className:'page-num press', onClick:()=>setPage(meta.pages), disabled:page===meta.pages, style:{fontSize:11} }, '»')
          )
        ),
        el(Sheet, {
          open:!!sheet, onClose:()=>{setSheet(null);setWizMode(false);},
          title: wizMode ? '?? Fulfillment Wizard' : `Invoice #${sheet?.id||''}`,
          footer: sheet && !wizMode && el('div', { style:{display:'flex',gap:10} },
            el('button', { className:'btn btn-ghost btn-full press', onClick:()=>setWizMode(true) }, el(Icon,{name:'key',size:16}), 'Fulfill'),
            el('button', { className:'btn btn-danger btn-full press', onClick:reject }, el(Icon,{name:'x',size:16}), 'Reject')
          )
        },
          wizMode
            ? el('div', { className:'col g14' },
                el('div', { style:{padding:12,borderRadius:10,background:'rgba(99,102,241,.1)',border:'1px solid rgba(99,102,241,.2)',fontSize:13,color:'#a5b4fc',lineHeight:1.5} },
                  '?? Paste M3U URL to auto-fill credentials, then activate.'
                ),
                el(Field, { label:'M3U URL', value:creds.m3u, onChange:v=>setCreds({...creds,m3u:v}), type:'textarea', rows:2 }),
                el('button', { className:'btn btn-ghost btn-sm press', onClick:parseM3U }, '? Auto-Parse ?'),
                el('div', { style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10} },
                  el(Field, { label:'Username', value:creds.user, onChange:v=>setCreds({...creds,user:v}) }),
                  el(Field, { label:'Password', value:creds.pass, onChange:v=>setCreds({...creds,pass:v}) })
                ),
                el(Field, { label:'Host URL', value:creds.url, onChange:v=>setCreds({...creds,url:v}) }),
                el('button', { className:'btn btn-success btn-full press', style:{marginTop:4}, onClick:()=>approve(true) },
                  el(Icon,{name:'check',size:18}), 'Activate & Approve'
                )
              )
            : (sheet && el('div', { className:'col g12' },
                el('div', { style:{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'12px 16px',background:'var(--surface2)',borderRadius:12} },
                  el('span', { style:{fontSize:13,color:'var(--muted)',fontWeight:600} }, 'Status'),
                  el(StatusBadge, { s:sheet.status })
                ),
                el('div', { style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:10} },
                  [['User',sheet.user_login],['Amount',(sheet.currency_symbol||'$')+Number(sheet.amount||0).toLocaleString()],['Plan',sheet.plan_name],['Date',sheet.date?.slice?.(0,10)]].map(([k,v]) =>
                    el('div', { key:k, style:{padding:'10px 14px',background:'var(--surface2)',borderRadius:10} },
                      el('p', { style:{fontSize:11,color:'var(--muted)',fontWeight:700,textTransform:'uppercase',letterSpacing:'.06em'} }, k),
                      el('p', { style:{fontWeight:700,marginTop:4,fontSize:15,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'} }, v||'–')
                    )
                  )
                ),
                (sheet.proofs||[]).length > 0
                  ? el('div', null,
                      el('p', { className:'lbl', style:{marginBottom:8} }, 'Payment Proofs'),
                      el('div', { style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:8} },
                        sheet.proofs.map(u => el('a', { key:u, href:u, target:'_blank',
                          style:{aspectRatio:'16/9',display:'block',borderRadius:10,overflow:'hidden',background:'var(--surface3)',backgroundImage:`url(${u})`,backgroundSize:'cover',backgroundPosition:'center',border:'1px solid var(--border)'} }))
                      )
                    )
                  : el('p', { style:{textAlign:'center',color:'var(--muted)',fontSize:13,padding:'16px 0'} }, 'No proof uploaded'),
                (sheet.status!=='APPROVED'&&sheet.status!=='REJECTED'&&sheet.status!=='completed') &&
                  el('button', { className:'btn btn-success btn-full press', onClick:()=>approve(false) },
                    el(Icon,{name:'check',size:18}), 'Quick Approve (no creds)'
                  )
              ))
        )
      );
    };

    const SubsHub = ({ onBack }) => {
      const [list, setList]       = useState([]);
      const [status, setStatus]   = useState('all');
      const [search, setSearch]   = useState('');
      const [loading, setLoading] = useState(true);
      const [plans, setPlans]     = useState([]);
      const [edit, setEdit]       = useState(null);
      const [multi, setMulti]      = useState(false);
      const [ids, setIds]          = useState([]);

      const load = useCallback(async () => {
        setLoading(true);
        try {
          const r = await apiFetch(`subscriptions?status=${status}&search=${encodeURIComponent(search)}`);
          setList(Array.isArray(r)?r:(r.data||[]));
        }
        catch(_){ setList([]); }
        finally { setLoading(false); }
      }, [status, search]);

      useEffect(() => { apiFetch('plans').then(p=>setPlans(Array.isArray(p)?p:(p.data||[]))).catch(()=>{}); load(); }, []);
      useEffect(() => { const t=setTimeout(load,300); return()=>clearTimeout(t); }, [status, search]);

      const saveEdit = async () => {
        try {
          await apiFetch(`users/${edit.user_id}/subscription`, 'POST', { sub_id:edit.id, plan_id:edit.plan_id, status:edit.status, start_date:edit.start_date, end_date:edit.end_date, connections:edit.connections||1 });
          showToast('Subscription saved ?'); setEdit(null); load();
        } catch(_){}
      };
      const bulkDo = async action => {
        if (!ids.length) return showToast('Select items first','err');
        const code = Math.floor(1000+Math.random()*9000);
        const ok = await confirm(`Bulk: ${action}`, `Apply to ${ids.length} subscriptions?`, { danger:action==='delete', codeRequired:action==='delete', code, confirmLabel:'Confirm' });
        if (!ok) return;
        try { await apiFetch('subscriptions/bulk','POST',{ids,action}); showToast('Done ?'); setIds([]); setMulti(false); load(); }
        catch(_){}
      };
      const toggleId = id => setIds(p => p.includes(id)?p.filter(i=>i!==id):[...p,id]);

      return el('div', { className:'page-wrap' },
        el(BackHeader, { title:'Subscriptions', onBack,
          right: el('button', { className:`btn btn-sm press ${multi?'btn-primary':'btn-ghost'}`, onClick:()=>{setMulti(!multi);setIds([]);} }, multi?'Done':'Select')
        }),
        el('div', { style:{padding:'10px 14px 0',borderBottom:'1px solid var(--border)',display:'flex',flexDirection:'column',gap:8} },
          el('div', { className:'chips-row' },
            ['all','active','pending','inactive','expired'].map(s =>
              el('button', { key:s, className:`chip press ${status===s?'active':''}`, onClick:()=>setStatus(s) }, s)
            )
          ),
          el('input', { className:'field', style:{marginBottom:10}, placeholder:'Search…', value:search, onChange:e=>setSearch(e.target.value) }),
          multi && ids.length>0 && el('div', { style:{display:'flex',gap:8,paddingBottom:10} },
            el('button', { className:'btn btn-success btn-sm press btn-full', onClick:()=>bulkDo('activate') }, '?? Activate Selected'),
            el('button', { className:'btn btn-danger btn-sm press', onClick:()=>bulkDo('delete') }, el(Icon,{name:'trash',size:14}))
          )
        ),
        el('div', { className:'page-scroll no-nav' },
          loading && el(Loader),
          !loading && list.length===0 && el(Empty, { text:'No subscriptions found', icon:'tv' }),
          !loading && list.map(s =>
            el('div', { key:s.id, className:'card press anim-up',
              style:{margin:'8px 12px 0',padding:'14px 16px',display:'flex',alignItems:'center',gap:12,cursor:'pointer',
                     border:ids.includes(s.id)?'1px solid var(--accent)':'1px solid var(--border)'},
              onClick:()=>multi?toggleId(s.id):setEdit(s)
            },
              multi && el('div', { style:{width:22,height:22,borderRadius:'50%',border:`2px solid ${ids.includes(s.id)?'var(--accent)':'var(--muted)'}`,background:ids.includes(s.id)?'var(--accent)':'none',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0} },
                ids.includes(s.id) && el(Icon,{name:'check',size:12,color:'#fff'})
              ),
              el('div', { style:{flex:1,minWidth:0} },
                el('div', { style:{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:4} },
                  el('span', { style:{fontWeight:800,fontSize:15} }, `#${s.id}`),
                  el('span', { style:{fontWeight:600,fontSize:14,color:'var(--muted)',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',flex:1,margin:'0 8px'} }, s.user_login||''),
                  el(StatusBadge, { s:s.status })
                ),
                el('p', { style:{fontSize:12,color:'var(--muted)'} }, `${s.plan_name||'–'} · ends ${s.end_date||'–'}`)
              ),
              !multi && el(Icon,{name:'edit',size:16,color:'var(--muted)'})
            )
          )
        ),
        el(Sheet, {
          open:!!edit, onClose:()=>setEdit(null),
          title:`Edit Sub #${edit?.id||''}`,
          footer: edit && el('button', { className:'btn btn-primary btn-full press', onClick:saveEdit }, el(Icon,{name:'check',size:16}), 'Save Changes')
        },
          edit && el('div', { className:'col g14' },
            el(Field, { label:'Plan', value:String(edit.plan_id||''), onChange:v=>setEdit({...edit,plan_id:parseInt(v)}), type:'select', options:plans.map(p=>({value:String(p.id),label:`${p.name} ($${p.price})` })) }),
            el(Field, { label:'Status', value:edit.status||'active', onChange:v=>setEdit({...edit,status:v}), type:'select', options:['active','pending','inactive','expired'] }),
            el(Field, { label:'Start Date', value:edit.start_date||'', onChange:v=>setEdit({...edit,start_date:v}), placeholder:'YYYY-MM-DD' }),
            el(Field, { label:'End Date', value:edit.end_date||'', onChange:v=>setEdit({...edit,end_date:v}), placeholder:'YYYY-MM-DD' }),
            el(Field, { label:'Connections', value:String(edit.connections||1), onChange:v=>setEdit({...edit,connections:parseInt(v)||1}) })
          )
        )
      );
    };

    const SportsGuide = ({ onBack }) => {
      const [list, setList]         = useState([]);
      const [loading, setLoading]   = useState(true);
      const [editSheet, setEditSheet] = useState(null);
      const [chanSheet, setChanSheet] = useState(null);
      const [extractOpen, setExtractOpen] = useState(false);
      const [extractMode, setExtractMode] = useState('text');
      const [extractInput, setExtractInput] = useState('');
      const [extracting, setExtracting] = useState(false);
      const [extracted, setExtracted] = useState([]);
      const [creating, setCreating]   = useState(false);
      const [newMatch, setNewMatch]   = useState({ title:'', league:'', sport_type:'Football', date:'', time:'', channel:'' });

      const load = async () => {
        setLoading(true);
        try {
          const r = await apiFetch('sports');
          setList(Array.isArray(r)?r:(r.data||[]));
        }
        catch(_){ setList([]); }
        finally { setLoading(false); }
      };
      useEffect(() => { load(); }, []);

      const saveMatch = async (form) => {
        try {
          await apiFetch(form.id ? `sports/${form.id}` : 'sports/new', 'POST', form);
          showToast(form.id?'Updated ?':'Created ?'); setEditSheet(null); setCreating(false); load();
        } catch(_){}
      };

      const saveChannels = async () => {
        if (!chanSheet) return;
        try {
          await apiFetch(`sports/${chanSheet.id}`, 'POST', { channels: chanSheet.channels });
          showToast('Channels saved ?'); setChanSheet(null); load();
        } catch(_){}
      };

      const del = async id => {
        const code = Math.floor(1000+Math.random()*9000);
        const ok = await confirm('Delete Match', 'Remove this event?', { danger:true, codeRequired:true, code, confirmLabel:'Delete' });
        if (!ok) return;
        try { await apiFetch(`sports/${id}/delete`, 'DELETE'); showToast('Deleted'); load(); }
        catch(_){}
      };

      const runExtract = async () => {
        if (!extractInput.trim()) return showToast('Paste some content first', 'err');
        setExtracting(true);
        setExtracted([]);
        try {
          const r = await apiRaw('sports/smart-extract', 'POST', { mode: extractMode, input: extractInput });
          setExtracted(Array.isArray(r.matches) ? r.matches : r);
        } catch(_) {
          const lines = extractInput.split('\n').filter(l => l.trim().length > 5);
          const matches = lines.map(line => {
            const tm = line.match(/\b(\d{1,2}:\d{2})\b/);
            const vm   = line.match(/(.+?)\s+(?:vs?\.?|VS|–|-)\s+(.+?)(?:\s+\d|$)/i);
            const cm = line.match(/\b(ESPN|BBC|Sky|Fox|beIN|DAZN|TNT|Canal\+?|SuperSport|Star)\w*/i);
            if (!vm) return null;
            return { _new:true, title:vm[0].trim(), home_team:vm[1].trim(), away_team:vm[2].trim(), time:tm?.[1]||'', channel:cm?.[0]||'', date:new Date().toISOString().slice(0,10) };
          }).filter(Boolean);
          setExtracted(matches);
        }
        setExtracting(false);
      };

      const importExtracted = async (items) => {
        let ok = 0;
        for (const m of items) { try { await apiRaw('sports/new', 'POST', m); ok++; } catch(_){} }
        showToast(`Imported ${ok} matches ?`); setExtractOpen(false); setExtracted([]); load();
      };

      const SmartExtractSheet = () =>
        el(Sheet, { open:extractOpen, onClose:()=>{setExtractOpen(false);setExtracted([]);}, title:'? Smart Extract' },
          el('div', { className:'col g14' },
            el('div', { className:'extract-banner' },
              el('div', { style:{display:'flex',alignItems:'center',gap:10,marginBottom:8} },
                el(Icon,{name:'sparkles',size:18,color:'#a5b4fc'}),
                el('span', { style:{fontWeight:800,fontSize:15,color:'#a5b4fc'} }, 'Smart Extract')
              ),
              el('p', { style:{fontSize:13,color:'var(--muted)',lineHeight:1.6} }, 'Paste fixture data. AI will extract details automatically.')
            ),
            el('div', { className:'tab-row' }, [['text','?? Text'],['paste','?? Schedule'],['url','?? URL']].map(([m,l]) => el('button', { key:m, className:`tab-btn ${extractMode===m?'active':''}`, onClick:()=>setExtractMode(m) }, l))),
            el(Field, { label:'Input', value:extractInput, onChange:setExtractInput, type:extractMode==='url'?'text':'textarea', rows:7 }),
            el('button', { className:'btn btn-primary btn-full press', onClick:runExtract, disabled:extracting }, el(Icon,{name:'sparkles',size:16}), extracting ? 'Extracting…' : 'Extract Matches'),
            extracted.length > 0 && el('div', { className:'col g10' }, extracted.map((m,i) => el('div', { key:i, className:'card2', style:{padding:'12px 14px'} }, el('p', { style:{fontWeight:700} }, m.title||`${m.home_team} vs ${m.away_team}`), el('button', { className:'btn btn-ghost btn-sm press', onClick:()=>importExtracted([m]) }, 'Import'))))
          )
        );

      const MatchFormSheet = () => {
        const isNew = !editSheet?.id;
        const [form, setForm] = useState(editSheet || newMatch);
        return el(Sheet, { open:!!(editSheet||creating), onClose:()=>{setEditSheet(null);setCreating(false);}, title: isNew?'? New Match':'?? Edit Match', footer: el('button', { className:'btn btn-primary btn-full press', onClick:()=>saveMatch(form) }, el(Icon,{name:'check',size:16}), isNew?'Create':'Save') },
          el('div', { className:'col g14' }, el(Field, { label:'Title', value:form.title, onChange:v=>setForm({...form,title:v}) }), el(Field, { label:'League', value:form.league, onChange:v=>setForm({...form,league:v}) }), el('div', { className:'grid grid-cols-2 gap-3' }, el(Field, { label:'Date', value:form.date, onChange:v=>setForm({...form,date:v}), type:'date' }), el(Field, { label:'Time', value:form.time, onChange:v=>setForm({...form,time:v}) })), el(Field, { label:'Channel', value:form.channel, onChange:v=>setForm({...form,channel:v}) }))
        );
      };

      const ChannelsSheet = () => {
        const [chans, setChans] = useState(Array.isArray(chanSheet?.channels) ? chanSheet.channels : []);
        const add = () => setChans([...chans, { name:'', url:'', quality:'HD' }]);
        const upd = (i,k,v) => { const n=[...chans]; n[i]={...n[i],[k]:v}; setChans(n); };
        const rm  = i => setChans(chans.filter((_,j)=>j!==i));
        return el(Sheet, { open:!!chanSheet, onClose:()=>setChanSheet(null), title: `?? Channels — ${chanSheet?.title||''}`, footer: el('button', { className:'btn btn-primary btn-full press', onClick:()=>{ chanSheet.channels=chans; saveChannels(); } }, 'Save Channels') },
          el('div', { className:'col g12' }, chans.map((c,i) => el('div', { key:i, className:'card2', style:{padding:14,position:'relative'} }, el('button', { onClick:()=>rm(i), style:{position:'absolute',top:10,right:10,color:'var(--red)'} }, el(Icon,{name:'x',size:16})), el(Field, { label:'Name', value:c.name, onChange:v=>upd(i,'name',v) }), el(Field, { label:'URL', value:c.url, onChange:v=>upd(i,'url',v) }))), el('button', { className:'btn btn-ghost btn-full press', onClick:add }, '+ Add Channel'))
        );
      };

      return el('div', { className:'page-wrap' },
        el(BackHeader, { title:'Sports Guide', onBack, right: el('div', { className:'flex gap-2' }, el('button',{className:'btn btn-warning btn-sm',onClick:()=>setExtractOpen(true)},'Extract'), el('button',{className:'btn btn-primary btn-sm',onClick:()=>setCreating(true)},'+')) }),
        el('div', { className:'page-scroll no-nav' }, loading ? el(Loader) : list.map(item => el('div', { key:item.id, className:'card anim-up', style:{margin:'8px 12px 0',padding:16} }, el('p', { style:{fontWeight:800} }, item.title), el('div', { className:'flex gap-2 mt-4' }, el('button',{className:'btn btn-ghost btn-sm flex-1',onClick:()=>setChanSheet(item)},'Channels'), el('button',{className:'btn btn-ghost btn-sm flex-1',onClick:()=>setEditSheet(item)},'Edit'))))),
        el(SmartExtractSheet), el(MatchFormSheet), el(ChannelsSheet)
      );
    };

    const ResourceManager = ({ type, title, endpoint, fields, onBack }) => {
      const [list, setList]       = useState([]);
      const [loading, setLoading] = useState(true);
      const [form, setForm]       = useState({});
      const [sheetOpen, setSheetOpen] = useState(false);
      const [broadcast, setBroadcast] = useState(false);
      const [bForm, setBForm]     = useState({ subject:'', body:'' });

      const load = async () => {
        setLoading(true);
        try { const r = await apiFetch(endpoint); setList(Array.isArray(r)?r:(r.data||[])); }
        catch(_){ setList([]); }
        finally { setLoading(false); }
      };
      useEffect(() => { load(); }, []);

      const save = async () => {
        try { await apiFetch(form.id ? `${endpoint}/${form.id}` : `${endpoint}/new`, 'POST', form); setSheetOpen(false); load(); } catch(_){}
      };
      const del = async id => {
        const code = Math.floor(1000+Math.random()*9000);
        if (await confirm('Delete', 'Delete this item?', { danger:true, codeRequired:true, code })) {
          await apiFetch(`${endpoint}/${id}/delete`, 'DELETE'); load();
        }
      };
      const sendBroadcast = async () => {
        if (await confirm('Send', 'Email all active subs?')) {
          await apiFetch('messages/broadcast', 'POST', bForm); setBroadcast(false);
        }
      };

      if (broadcast) return el('div', { className:'page-wrap' }, el(BackHeader, { title:'Broadcast', onBack:()=>setBroadcast(false) }), el('div', { className:'page-scroll no-nav', style:{padding:20} }, el('div', { className:'col g14' }, el(Field, { label:'Subject', value:bForm.subject, onChange:v=>setBForm({...bForm,subject:v}) }), el(Field, { label:'Body', value:bForm.body, onChange:v=>setBForm({...bForm,body:v}), type:'textarea', rows:10 }), el('button', { className:'btn btn-primary btn-full', onClick:sendBroadcast }, 'Send Broadcast'))));

      return el('div', { className:'page-wrap' }, el(BackHeader, { title, onBack, right: el('div', { className:'flex gap-2' }, type==='messages'&&el('button',{onClick:()=>setBroadcast(true)},'Mail'), el('button',{onClick:()=>setSheetOpen(true)},'+')) }), el('div', { className:'page-scroll no-nav', style:{padding:12} }, loading ? el(Loader) : list.map(item => el('div', { key:item.id, className:'card p-4 mb-2 flex justify-between' }, el('div', null, el('p',{className:'font-bold'},item.name||item.code||item.title), el('p',{className:'text-xs'},item.price||item.status)), el('div', {className:'flex gap-2'}, el('button',{onClick:()=>setForm(item)},'E'), el('button',{onClick:()=>del(item.id)},'D'))))), el(Sheet, { open:sheetOpen, onClose:()=>setSheetOpen(false), title: form.id ? 'Edit':'New' }, el('div', { className:'col g14' }, fields.map(f => el(Field, { key:f.k, label:f.label, value:form[f.k], onChange:v=>setForm({...form,[f.k]:v}), type:f.type })), el('button', { onClick:save }, 'Save'))));
    };