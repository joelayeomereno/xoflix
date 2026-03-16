// part-app.js
// Contains: initApp(), App, ErrorBoundary
// This is the LAST script loaded. It calls all _register* functions,
// wires the confirm helper, then mounts the React tree.

function initApp() {
  const {
    useState, useEffect, useRef, useCallback, useMemo,
    createElement: el, Component
  } = React;

  // ── 1. Finalise utils (already on _XOFLIX from part-utils.js) ──────────────
  const { showToast, apiFetch, apiRaw, haptic } = window._XOFLIX;

  // ── 2. Build confirm() promise helper; App will wire _setConfirm ──────────
  let _setConfirm = null;
  const confirm = (title, body, opts = {}) => new Promise(res => {
    _setConfirm({ title, body, ...opts });
    window._confirmResolve = res;
  });
  // Expose confirm for all parts that call window._XOFLIX._confirm()
  window._XOFLIX._confirm = confirm;

  // ── 3. Register all component parts (they need React + helpers ready) ──────
  window._XOFLIX._registerPrimitives();
  window._XOFLIX._registerSearch();
  window._XOFLIX._registerDashboard();
  window._XOFLIX._registerFinance();
  window._XOFLIX._registerPayments();
  window._XOFLIX._registerUsers();
  window._XOFLIX._registerSubs();
  window._XOFLIX._registerSports();
  window._XOFLIX._registerResourceManager();
  window._XOFLIX._registerSettings();
  window._XOFLIX._registerMenu();

  // ── 4. Pull all registered components ─────────────────────────────────────
  const {
    Icon, ConfirmDialog,
    SearchOverlay,
    Dashboard,
    Finance,
    Payments,
    Users,
    SubsHub,
    SportsGuide,
    ResourceManager,
    SettingsView,
    MenuView,
  } = window._XOFLIX;

  // ── 5. App ─────────────────────────────────────────────────────────────────
  const App = () => {
    const [tab, setTab]         = useState('home');
    const [subView, setSubView] = useState(null);
    const [searchOpen, setSearchOpen] = useState(false);
    const [confirmState, setConfirmState] = useState(null);

    // Wire confirm dialog state setter
    _setConfirm = setConfirmState;

    useEffect(() => {
      const h = () => setSubView(null);
      window.addEventListener('nav-back', h);
      return () => window.removeEventListener('nav-back', h);
    }, []);

    const goBack = () => setSubView(null);

    const renderContent = () => {
      if (subView) {
        if (subView === 'payments') return el(Payments, { onBack: goBack });
        if (subView === 'finance')  return el(Finance,  { onBack: goBack });
        if (subView === 'settings') return el(SettingsView, { onBack: goBack });
        if (subView === 'sports')   return el(SportsGuide,  { onBack: goBack });
        if (subView === 'subs')     return el(SubsHub,      { onBack: goBack });
        if (subView === 'plans')    return el(ResourceManager, { onBack: goBack, type: 'plans', title: 'Plans', endpoint: 'plans',
          fields: [
            { k: 'name',        label: 'Name' },
            { k: 'price',       label: 'Price' },
            { k: 'duration_days', label: 'Days (default 30)' },
            { k: 'description', label: 'Features', type: 'textarea' },
            { k: 'tiers',       label: 'Discount Tiers', type: 'repeater', fields: [{ k: 'months', l: 'Months' }, { k: 'percent', l: '% Off' }] }
          ]
        });
        if (subView === 'coupons')  return el(ResourceManager, { onBack: goBack, type: 'coupons', title: 'Coupons', endpoint: 'coupons',
          fields: [{ k: 'code', label: 'Code' }, { k: 'amount', label: 'Discount %' }, { k: 'limit', label: 'Usage Limit' }, { k: 'expiry_date', label: 'Expiry Date' }]
        });
        if (subView === 'methods')  return el(ResourceManager, { onBack: goBack, type: 'methods', title: 'Pay Methods', endpoint: 'methods',
          fields: [{ k: 'name', label: 'Name' }, { k: 'bank_name', label: 'Bank Name' }, { k: 'account_number', label: 'Account #' }, { k: 'account_name', label: 'Account Name' }, { k: 'link', label: 'Link URL' }, { k: 'instructions', label: 'Instructions', type: 'textarea' }, { k: 'flutterwave_public_key', label: 'FW Public Key' }, { k: 'flutterwave_secret_key', label: 'FW Secret Key' }]
        });
        if (subView === 'messages') return el(ResourceManager, { onBack: goBack, type: 'messages', title: 'Messages', endpoint: 'messages',
          fields: [{ k: 'title', label: 'Title' }, { k: 'message', label: 'Body', type: 'textarea' }, { k: 'button_text', label: 'Button Text' }, { k: 'color_scheme', label: 'Color Scheme' }]
        });
      }
      if (tab === 'home')     return el(Dashboard);
      if (tab === 'payments') return el(Payments, { onBack: null });
      if (tab === 'users')    return el(Users);
      if (tab === 'menu')     return el(MenuView, { onNavigate: v => setSubView(v) });
      return el(Dashboard);
    };

    const NAV = [
      { id: 'home',     icon: 'home',       label: 'Home'  },
      { id: 'payments', icon: 'creditCard', label: 'Pay'   },
      { id: 'users',    icon: 'users',      label: 'Users' },
      { id: 'menu',     icon: 'menu',       label: 'More'  },
    ];

    return el('div', { style: { display: 'flex', flexDirection: 'column', height: '100%', background: 'var(--bg)' } },
      el('main', { style: { flex: 1, overflow: 'hidden', position: 'relative' } },
        renderContent()
      ),
      !subView && el('button', {
        onClick: () => { setSearchOpen(true); haptic(); },
        style: { position: 'fixed', bottom: `calc(var(--nav-h) + env(safe-area-inset-bottom,0px) + 14px)`, right: 18, width: 50, height: 50, borderRadius: '50%', background: 'var(--accent)', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 4px 24px rgba(99,102,241,.5)', zIndex: 30 }
      }, el(Icon, { name: 'search', size: 20 })),
      !subView && el('nav', { className: 'nav-bar' },
        NAV.map(n => el('button', { key: n.id, className: `nav-btn ${tab === n.id ? 'active' : ''}`, onClick: () => { setTab(n.id); haptic(); } },
          el(Icon, { name: n.icon, size: 22 }),
          el('span', null, n.label)
        ))
      ),
      searchOpen && el(SearchOverlay, { onClose: () => setSearchOpen(false) }),
      el(ConfirmDialog, { state: confirmState, setState: setConfirmState })
    );
  };

  // ── 6. ErrorBoundary ───────────────────────────────────────────────────────
  class ErrorBoundary extends Component {
    constructor(p) { super(p); this.state = { err: null, info: null }; }
    static getDerivedStateFromError(e) { return { err: e }; }
    componentDidCatch(e, info) { console.error('[XOFLIX Admin Crash]', e, info); }
    render() {
      if (this.state.err) return el('div', { style: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%', padding: 40, textAlign: 'center', background: 'var(--bg)' } },
        el('div', { style: { width: 64, height: 64, borderRadius: '50%', background: 'rgba(239,68,68,.12)', color: 'var(--red)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, fontWeight: 900, marginBottom: 16 } }, '❓'),
        el('h2', { style: { fontSize: 20, fontWeight: 800, marginBottom: 8 } }, 'Something went wrong'),
        el('p',  { style: { color: 'var(--muted)', fontSize: 13, marginBottom: 8, maxWidth: 300, lineHeight: 1.6 } }, String(this.state.err?.message || this.state.err || 'Unknown error')),
        el('p',  { style: { color: 'var(--muted)', fontSize: 11, marginBottom: 24, maxWidth: 300, fontFamily: 'monospace', wordBreak: 'break-all' } }, this.state.err?.stack?.split('\n')[1]?.trim() || ''),
        el('button', { className: 'btn btn-primary press', onClick: () => this.setState({ err: null, info: null }) }, 'Try Again'),
        el('button', { className: 'btn btn-ghost press', style: { marginTop: 10 }, onClick: () => location.reload() }, 'Reload App')
      );
      return this.props.children;
    }
  }

  // ── 7. Mount ───────────────────────────────────────────────────────────────
  ReactDOM.createRoot(document.getElementById('root')).render(
    el(ErrorBoundary, null, el(App))
  );
}
