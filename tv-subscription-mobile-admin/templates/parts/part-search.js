// part-search.js
// Registers: _XOFLIX._registerSearch → SearchOverlay
// Depends on: React, _XOFLIX.{ apiFetch, Icon, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerSearch = function () {
    const { useState, useEffect, useRef, createElement: el } = React;
    const { apiFetch, Icon, Loader, Empty } = window._XOFLIX;

    const SearchOverlay = ({ onClose }) => {
      const [q, setQ]           = useState('');
      const [res, setRes]       = useState([]);
      const [loading, setLoading] = useState(false);
      const inputRef = useRef(null);

      useEffect(() => { setTimeout(() => inputRef.current?.focus(), 100); }, []);

      useEffect(() => {
        if (q.length < 2) { setRes([]); return; }
        const t = setTimeout(async () => {
          setLoading(true);
          try {
            const r = await apiFetch('search?q=' + encodeURIComponent(q));
            setRes(Array.isArray(r) ? r : []);
          } catch (_) { setRes([]); }
          finally { setLoading(false); }
        }, 300);
        return () => clearTimeout(t);
      }, [q]);

      return el('div', { className: 'search-overlay anim-in' },
        el('div', { style: { display: 'flex', gap: 12, padding: '16px', borderBottom: '1px solid var(--border)' } },
          el('div', { style: { flex: 1, display: 'flex', alignItems: 'center', gap: 10, background: 'var(--surface2)', borderRadius: 12, border: '1px solid var(--border)', padding: '0 14px' } },
            el(Icon, { name: 'search', size: 18, color: 'var(--muted)' }),
            el('input', { ref: inputRef, className: 'field', style: { background: 'none', border: 'none', flex: 1, padding: '12px 0' }, placeholder: 'Search users, payments…', value: q, onChange: e => setQ(e.target.value) })
          ),
          el('button', { onClick: onClose, style: { background: 'none', border: 'none', color: 'var(--muted)', cursor: 'pointer', fontWeight: 700, fontSize: 15, padding: '0 4px' } }, 'Cancel')
        ),
        el('div', { style: { flex: 1, overflowY: 'auto', padding: 16 } },
          loading && el(Loader),
          !loading && res.length === 0 && q.length >= 2 && el(Empty, { text: 'No results found' }),
          !loading && res.map(r =>
            el('div', { key: r.type + r.id, className: 'card2 anim-up', style: { padding: 16, marginBottom: 10, display: 'flex', alignItems: 'center', gap: 14 } },
              el('div', { style: { width: 40, height: 40, borderRadius: '50%', background: 'var(--surface3)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--accent)', flexShrink: 0 } },
                el(Icon, { name: r.type === 'user' ? 'users' : 'creditCard', size: 18 })
              ),
              el('div', null,
                el('p', { style: { fontWeight: 700, fontSize: 15 } }, r.title),
                el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 2 } }, r.subtitle)
              )
            )
          )
        )
      );
    };

    window._XOFLIX.SearchOverlay = SearchOverlay;
  };
})();
