<?php if (!defined('ABSPATH')) { exit; } ?>
    /* ---------------------------------------------
        6. MAIN APPLICATION LOGIC & NAVIGATION
    --------------------------------------------- */

    const Users = () => {
      const [list, setList]       = useState([]);
      const [search, setSearch]   = useState('');
      const [loading, setLoading] = useState(false);
      const [sel, setSel]          = useState(null);
      const [detail, setDetail]    = useState(null);
      const [tab, setTab]          = useState('profile');
      const [pForm, setPForm]      = useState({});
      const [multi, setMulti]      = useState(false);
      const [ids, setIds]          = useState([]);

      const load = useCallback(async () => {
        setLoading(true);
        try {
          const r = await apiFetch('users' + (search ? '?search=' + encodeURIComponent(search) : ''));
          setList(r.data || r || []);
        } finally { setLoading(false); }
      }, [search]);

      useEffect(() => { load(); }, [search]);

      useEffect(() => {
        if (sel) { 
            setDetail(null);
            apiFetch(`users/${sel}`).then(d => { 
                setDetail(d); 
                setPForm(d.profile || {}); 
            }); 
        }
      }, [sel]);

      const toggleId = id => setIds(p => p.includes(id) ? p.filter(i => i !== id) : [...p, id]);

      return el('div', { className: 'page-wrap' },
        el('div', { className: 'page-header top-safe' },
          el('div', { className: 'flex justify-between items-center mb-3' }, 
            el('h1', { className: 'text-2xl font-black' }, 'Customers'),
            el('button', { className: 'btn btn-ghost btn-sm', onClick: () => setMulti(!multi) }, multi ? 'Done' : 'Select')
          ),
          el('input', { className: 'field', placeholder: 'Search users...', value: search, onChange: e => setSearch(e.target.value) })
        ),
        el('div', { className: 'page-scroll' },
          loading ? el(Loader) : list.map(u => el('div', { 
              key: u.id, 
              className: 'card p-4 m-3 flex items-center gap-3 press', 
              onClick: () => multi ? toggleId(u.id) : setSel(u.id) 
            }, 
            el('div', { className: 'w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold' }, (u.name||'U')[0]), 
            el('div', null, el('p', { className: 'font-bold' }, u.name), el('p', { className: 'text-xs text-muted' }, u.email))
          )),
          el(Sheet, { open: !!sel, onClose: () => setSel(null), title: detail?.profile?.display_name || 'Loading…' },
            detail ? el('div', null, 
                el('div', { className: 'tab-row mb-5' }, 
                    ['profile','subs'].map(t => el('button', { key: t, className: `tab-btn ${tab === t ? 'active' : ''}`, onClick: () => setTab(t) }, t))
                ), 
                tab === 'profile' ? el('div', { className: 'col g14' }, 
                    el(Field, { label: 'Name', value: pForm.display_name, onChange: v => setPForm({ ...pForm, display_name: v }) }),
                    el('button', { className: 'btn btn-primary', onClick: () => alert('Profile Updated') }, 'Save')
                ) : el('div', null, 'Subscription details view...')
            ) : el(Loader)
          )
        )
      );
    };

    const MenuView = ({ onNavigate }) => el('div', { className: 'page-wrap' },
      el('div', { className: 'page-header top-safe' }, el('h1', { className: 'text-2xl font-black' }, 'More')),
      el('div', { className: 'page-scroll p-4 grid grid-cols-2 gap-3' },
        ['finance', 'subs', 'plans', 'coupons', 'methods', 'sports', 'messages', 'settings'].map(i => 
            el('button', { key: i, className: 'card p-5 text-left capitalize font-bold press', onClick: () => onNavigate(i) }, i)
        ),
        el('a', { href: TVMA.logoutUrl, className: 'card p-5 text-red-500 font-bold text-center col-span-2' }, 'Sign Out')
      )
    );

    const SettingsView = ({ onBack }) => {
      const [conf, setConf] = useState(null);
      useEffect(() => { apiFetch('settings').then(setConf); }, []);
      if (!conf) return el(Loader);
      return el('div', { className: 'page-wrap' }, 
        el(BackHeader, { title: 'Settings', onBack }), 
        el('div', { className: 'page-scroll no-nav p-5' }, 
            el(Field, { label: 'WhatsApp Number', value: conf.support?.whatsapp, onChange: v => setConf({ ...conf, support: { ...conf.support, whatsapp: v } }) }),
            el('button', { className: 'btn btn-primary btn-full mt-4', onClick: () => alert('Saved') }, 'Save Settings')
        )
      );
    };

    const App = () => {
      const [tab, setTab] = useState('home');
      const [subView, setSubView] = useState(null);
      const [searchOpen, setSearchOpen] = useState(false);
      const [confirmState, setConfirmState] = useState(null);

      _setConfirm = setConfirmState;

      const renderContent = () => {
        if (subView) {
          const props = { onBack: () => setSubView(null) };
          switch(subView) {
            case 'payments': return el(Payments, props);
            case 'finance':  return el(Finance, props);
            case 'settings': return el(SettingsView, props);
            case 'subs':     return el(SubsHub, props);
            case 'sports':   return el(SportsGuide, props);
            case 'plans':    return el(ResourceManager, { ...props, endpoint:'plans', title:'Plans', fields:[{k:'name',label:'Name'},{k:'price',label:'Price'}] });
            case 'coupons':  return el(ResourceManager, { ...props, endpoint:'coupons', title:'Coupons', fields:[{k:'code',label:'Code'},{k:'amount',label:'%'}] });
            case 'methods':  return el(ResourceManager, { ...props, endpoint:'methods', title:'Methods', fields:[{k:'name',label:'Name'},{k:'link',label:'URL'}] });
            case 'messages': return el(ResourceManager, { ...props, endpoint:'messages', title:'Messages', fields:[{k:'title',label:'Title'},{k:'message',label:'Body',type:'textarea'}] });
          }
        }
        switch(tab) {
            case 'home':     return el(Dashboard);
            case 'payments': return el(Payments);
            case 'users':    return el(Users);
            case 'menu':     return el(MenuView, { onNavigate: setSubView });
            default:         return el(Dashboard);
        }
      };

      const NavBtn = ({ id, icon, label }) => el('button', { 
          onClick: () => { setTab(id); setSubView(null); haptic(); }, 
          className: `nav-btn ${tab === id ? 'active' : ''}` 
        }, 
        el(Icon, { name: icon, size: 22 }), 
        el('span', null, label)
      );

      return el('div', { className: 'h-full flex flex-col' },
        el('main', { className: 'flex-1 overflow-hidden relative' }, renderContent()),
        !subView && el('nav', { className: 'nav-bar' },
          el(NavBtn, { id: 'home', icon: 'home', label: 'Home' }),
          el(NavBtn, { id: 'payments', icon: 'creditCard', label: 'Pay' }),
          el(NavBtn, { id: 'users', icon: 'users', label: 'Users' }),
          el(NavBtn, { id: 'menu', icon: 'menu', label: 'More' })
        ),
        searchOpen && el(SearchOverlay, { onClose: () => setSearchOpen(false) }),
        el(ConfirmDialog, { state: confirmState, setState: setConfirmState })
      );
    };

    ReactDOM.createRoot(document.getElementById('root')).render(el(ErrorBoundary, null, el(App)));