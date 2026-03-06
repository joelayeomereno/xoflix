// part-resource-manager.js
// Registers: _XOFLIX._registerResourceManager → ResourceManager
// Depends on: React, _XOFLIX.{ apiFetch, showToast, Icon, BackHeader, Sheet, Field, Repeater, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerResourceManager = function () {
    const { useState, useEffect, createElement: el } = React;
    const { apiFetch, showToast, Icon, BackHeader, Sheet, Field, Repeater, Loader, Empty } = window._XOFLIX;

    const ResourceManager = ({ type, title, endpoint, fields, onBack }) => {
      const [list, setList]       = useState([]);
      const [loading, setLoading] = useState(true);
      const [form, setForm]       = useState({});
      const [sheetOpen, setSheetOpen] = useState(false);
      const [broadcast, setBroadcast] = useState(false);
      const [bForm, setBForm]     = useState({ subject: '', body: '' });

      const load = async () => {
        setLoading(true);
        try { const r = await apiFetch(endpoint); setList(Array.isArray(r) ? r : (r.data || [])); }
        catch (_) { setList([]); }
        finally { setLoading(false); }
      };
      useEffect(() => { load(); }, []);

      const save = async () => {
        try {
          await apiFetch(form.id ? `${endpoint}/${form.id}` : `${endpoint}/new`, 'POST', form);
          showToast(form.id ? 'Updated ✅' : 'Created ✅'); setSheetOpen(false); setForm({}); load();
        } catch (_) {}
      };

      const del = async id => {
        const code = Math.floor(1000 + Math.random() * 9000);
        const ok = await window._XOFLIX._confirm(`Delete ${title}`, 'This will soft-delete the item.', { danger: true, codeRequired: true, code, confirmLabel: 'Delete' });
        if (!ok) return;
        try { await apiFetch(`${endpoint}/${id}/delete`, 'DELETE'); showToast('Deleted'); load(); } catch (_) {}
      };

      const sendBroadcast = async () => {
        const ok = await window._XOFLIX._confirm('Send Broadcast', 'Email ALL active subscribers. Continue?', { confirmLabel: 'Send' });
        if (!ok) return;
        try { const r = await apiFetch('messages/broadcast', 'POST', bForm); showToast(r.msg || 'Sent ✅'); setBroadcast(false); } catch (_) {}
      };

      if (broadcast) return el('div', { className: 'page-wrap' },
        el(BackHeader, { title: 'Broadcast Email', onBack: () => setBroadcast(false) }),
        el('div', { className: 'page-scroll no-nav', style: { padding: 20 } },
          el('div', { className: 'col g14' },
            el(Field, { label: 'Subject', value: bForm.subject, onChange: v => setBForm({ ...bForm, subject: v }) }),
            el(Field, { label: 'HTML Body', value: bForm.body, onChange: v => setBForm({ ...bForm, body: v }), type: 'textarea', rows: 10 }),
            el('button', { className: 'btn btn-primary btn-full press', onClick: sendBroadcast }, el(Icon, { name: 'mail', size: 16 }), 'Send to All Active')
          )
        )
      );

      return el('div', { className: 'page-wrap' },
        el(BackHeader, { title, onBack,
          right: el('div', { style: { display: 'flex', gap: 8 } },
            type === 'messages' && el('button', { className: 'btn btn-ghost btn-sm press', onClick: () => setBroadcast(true) }, el(Icon, { name: 'mail', size: 15 })),
            el('button', { className: 'btn btn-primary btn-sm press', onClick: () => { setForm({}); setSheetOpen(true); } }, el(Icon, { name: 'plus', size: 15 }))
          )
        }),
        el('div', { className: 'page-scroll no-nav', style: { padding: 12 } },
          loading && el(Loader),
          !loading && list.length === 0 && el(Empty),
          !loading && list.map(item =>
            el('div', { key: item.id, className: 'card press anim-up', style: { marginBottom: 10, padding: '14px 16px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
              el('div', { style: { flex: 1, minWidth: 0 } },
                el('p', { style: { fontWeight: 700, fontSize: 15, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, item.name || item.code || item.title || `#${item.id}`),
                el('p', { style: { fontSize: 12, color: 'var(--muted)', marginTop: 4 } },
                  item.price ? '$' + item.price : item.amount ? item.amount + '%' : item.status || item.start_time || ''
                )
              ),
              el('div', { style: { display: 'flex', gap: 6, flexShrink: 0, marginLeft: 10 } },
                el('button', { className: 'btn btn-ghost btn-sm press', onClick: () => { setForm(item); setSheetOpen(true); } }, el(Icon, { name: 'edit', size: 15 })),
                el('button', { className: 'btn btn-danger btn-sm press', onClick: () => del(item.id) }, el(Icon, { name: 'trash', size: 15 }))
              )
            )
          )
        ),
        el(Sheet, {
          open: sheetOpen, onClose: () => setSheetOpen(false),
          title: form.id ? `Edit ${title}` : `New ${title}`,
          footer: el('button', { className: 'btn btn-primary btn-full press', onClick: save }, el(Icon, { name: 'check', size: 16 }), 'Save')
        },
          el('div', { className: 'col g14' },
            fields.map(f => {
              if (f.type === 'repeater') return el(Repeater, { key: f.k, label: f.label, items: form[f.k] || [], onChange: v => setForm({ ...form, [f.k]: v }), fields: f.fields });
              if (f.type === 'select')   return el(Field, { key: f.k, label: f.label, value: form[f.k] || '', onChange: v => setForm({ ...form, [f.k]: v }), type: 'select', options: f.options || [] });
              if (f.type === 'textarea') return el(Field, { key: f.k, label: f.label, value: form[f.k] || '', onChange: v => setForm({ ...form, [f.k]: v }), type: 'textarea' });
              return el(Field, { key: f.k, label: f.label, value: form[f.k] || '', onChange: v => setForm({ ...form, [f.k]: v }), placeholder: f.placeholder || '' });
            })
          )
        )
      );
    };

    window._XOFLIX.ResourceManager = ResourceManager;
  };
})();
