<?php if (!defined('ABSPATH')) { exit; } ?>
    /* ---------------------------------------------
        TOAST
    --------------------------------------------- */
    const showToast = (msg, type='ok') => {
      const d = document.createElement('div');
      d.className = `toast toast-${type}`;
      d.textContent = msg;
      const root = document.getElementById('toast-root');
      if (!root) return;
      root.appendChild(d);
      setTimeout(() => { try { d.remove(); } catch(_){} }, 3200);
    };

    /* ---------------------------------------------
        API
    --------------------------------------------- */
    const apiRaw = async (ep, m='GET', b=null) => {
      const res = await fetch(TVMA.api + '/' + ep, {
        method: m,
        headers: { 'X-WP-Nonce': TVMA.nonce, 'Content-Type': 'application/json' },
        body: b ? JSON.stringify(b) : null
      });
      const j = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(j.message || j.error || `HTTP ${res.status}`);
      return j;
    };
    const apiFetch = async (ep, m='GET', b=null) => {
      try { return await apiRaw(ep, m, b); }
      catch(e) { showToast(e.message, 'err'); throw e; }
    };

    const haptic = (ms=8) => { try { navigator.vibrate?.(ms); } catch(_){} };

    /* ---------------------------------------------
        CONFIRM SHEET
    --------------------------------------------- */
    let _setConfirm = null;
    const confirm = (title, body, opts={}) => new Promise(res => {
      _setConfirm({ title, body, ...opts });
      window._confirmResolve = res;
    });

    const ConfirmDialog = ({ state, setState }) => {
      if (!state) return null;
      const choose = v => {
        setState(null);
        try { window._confirmResolve?.(v); } catch(_){}
      };
      return el('div', { className:'confirm-overlay anim-in', onClick:()=>choose(false) },
        el('div', { className:'confirm-box anim-up', onClick:e=>e.stopPropagation() },
          el('h3', { style:{fontSize:18,fontWeight:800,marginBottom:8} }, state.title || 'Confirm'),
          el('p',  { style:{color:'var(--muted)',fontSize:14,marginBottom:24,lineHeight:1.5} }, state.body || 'Are you sure?'),
          state.codeRequired && el('div', { style:{marginBottom:16} },
            el('p', { style:{fontSize:13,color:'var(--amber)',marginBottom:8,fontWeight:600} }, `Type ${state.code} to confirm:`),
            el('input', { className:'field', placeholder:`Enter ${state.code}`, id:'confirm-code-input', autoFocus:true })
          ),
          el('div', { style:{display:'flex',gap:12} },
            el('button', { className:'btn btn-ghost press', style:{flex:1}, onClick:()=>choose(false) }, 'Cancel'),
            el('button', {
              className:`btn ${state.danger?'btn-danger':'btn-primary'} press`, style:{flex:1},
              onClick:() => {
                if (state.codeRequired) {
                  const val = document.getElementById('confirm-code-input')?.value?.trim() || '';
                  if (String(val) !== String(state.code)) { showToast('Wrong code','err'); return; }
                }
                choose(true);
              }
            }, state.confirmLabel || 'Confirm')
          )
        )
      );
    };

    /* ---------------------------------------------
        SEARCH OVERLAY
    --------------------------------------------- */
    const SearchOverlay = ({ onClose }) => {
      const [q, setQ] = useState('');
      const [res, setRes] = useState([]);
      const [loading, setLoading] = useState(false);
      const inputRef = useRef(null);
      useEffect(() => { setTimeout(() => inputRef.current?.focus(), 100); }, []);
      useEffect(() => {
        if (q.length < 2) { setRes([]); return; }
        const t = setTimeout(async () => {
          setLoading(true);
          try { const r = await apiFetch('search?q=' + encodeURIComponent(q)); setRes(Array.isArray(r)?r:[]); }
          catch(_){ setRes([]); }
          finally { setLoading(false); }
        }, 300);
        return () => clearTimeout(t);
      }, [q]);
      return el('div', { className:'search-overlay anim-in' },
        el('div', { style:{display:'flex',gap:12,padding:'16px',borderBottom:'1px solid var(--border)'} },
          el('div', { style:{flex:1,display:'flex',alignItems:'center',gap:10,background:'var(--surface2)',borderRadius:12,border:'1px solid var(--border)',padding:'0 14px'} },
            el(Icon,{name:'search',size:18,color:'var(--muted)'}),
            el('input', { ref:inputRef, className:'field', style:{background:'none',border:'none',flex:1,padding:'12px 0'}, placeholder:'Search users, payments…', value:q, onChange:e=>setQ(e.target.value) })
          ),
          el('button', { onClick:onClose, style:{background:'none',border:'none',color:'var(--muted)',cursor:'pointer',fontWeight:700,fontSize:15,padding:'0 4px'} }, 'Cancel')
        ),
        el('div', { style:{flex:1,overflowY:'auto',padding:16} },
          loading && el(Loader),
          !loading && res.length===0 && q.length>=2 && el(Empty,{text:'No results found'}),
          !loading && res.map(r => el('div', { key:r.type+r.id, className:'card2 anim-up', style:{padding:16,marginBottom:10,display:'flex',alignItems:'center',gap:14} },
            el('div', { style:{width:40,height:40,borderRadius:'50%',background:'var(--surface3)',display:'flex',alignItems:'center',justifyContent:'center',color:'var(--accent)',flexShrink:0} },
              el(Icon,{name:r.type==='user'?'users':'creditCard',size:18})),
            el('div', null,
              el('p', { style:{fontWeight:700,fontSize:15} }, r.title),
              el('p', { style:{fontSize:12,color:'var(--muted)',marginTop:2} }, r.subtitle)
            )
          ))
        )
      );
    };