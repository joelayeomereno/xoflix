// part-ui-primitives.js
// Registers: _XOFLIX.ICONS, _XOFLIX.Icon, _XOFLIX.Field, _XOFLIX.Repeater,
//            _XOFLIX.Sheet, _XOFLIX.StatusBadge, _XOFLIX.Empty, _XOFLIX.Loader,
//            _XOFLIX.BackHeader, _XOFLIX.ConfirmDialog
// Depends on: React (global), _XOFLIX.showToast

(function () {
  'use strict';

  // Pulled in at call-time so React is guaranteed to be ready
  function register() {
    const { useState, createElement: el } = React;
    const { showToast } = window._XOFLIX;

    /* ---- ICONS ---- */
    const ICONS = {
      home:         'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10',
      users:        'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M23 21v-2a4 4 0 0 1-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
      creditCard:   'M1 4h22v16H1z M1 10h22',
      settings:     'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z',
      menu:         'M3 12h18 M3 6h18 M3 18h18',
      search:       'M11 17.5A6.5 6.5 0 1 0 11 4.5a6.5 6.5 0 0 0 0 13z M21 21l-4.35-4.35',
      plus:         'M12 5v14 M5 12h14',
      x:            'M18 6L6 18 M6 6l12 12',
      check:        'M20 6L9 17l-5-5',
      edit:         'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z',
      trash:        'M3 6h18 M8 6V4h8v2 M19 6l-1 14H6L5 6',
      tag:          'M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z M7 7h.01',
      zap:          'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
      activity:     'M22 12h-4l-3 9L9 3l-3 9H2',
      key:          'M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4',
      logOut:       'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
      tv:           'M2 7h20v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7 M17 2l-5 5-5-5',
      message:      'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z',
      chevronLeft:  'M15 18l-6-6 6-6',
      chevronRight: 'M9 18l6-6-6-6',
      chevronDown:  'M6 9l6 6 6-6',
      chevronUp:    'M18 15l-6-6-6 6',
      trophy:       'M6 9H4.5a2.5 2.5 0 0 1 0-5H6 M18 9h1.5a2.5 2.5 0 0 0 0-5H18 M4 22h16 M2 12h20 M12 2a5 5 0 0 0-5 5v2h10V7a5 5 0 0 0-5-5z',
      mail:         'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z M22 6l-10 7L2 6',
      eye:          'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
      userPlus:     'M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M8.5 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z M20 8v6 M23 11h-6',
      server:       'M2 2h20v8H2z M2 14h20v8H2z M6 6h.01 M6 18h.01',
      dollar:       'M12 1v22 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
      fileText:     'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8',
      wifi:         'M5 12.55a11 11 0 0 1 14.08 0 M1.42 9a16 16 0 0 1 21.16 0 M8.53 16.11a6 6 0 0 1 6.95 0 M12 20h.01',
      copy:         'M20 9H11a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2z M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1',
      filter:       'M22 3H2l8 9.46V19l4 2V12.46L22 3',
      refresh:      'M23 4v6h-6 M20.49 15a9 9 0 1 1-2.12-9.36L23 10',
      sort:         'M3 6h18 M7 12h10 M11 18h4',
      arrowUp:      'M12 19V5 M5 12l7-7 7 7',
      arrowDown:    'M12 5v14 M5 12l7 7 7-7',
      sparkles:     'M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z M19 14l.8 2.4L22 17l-2.2.6L19 20l-.8-2.4L16 17l2.2-.6L19 14z M6 17l.5 1.5L8 19l-1.5.5L6 21l-.5-1.5L4 19l1.5-.5L6 17z',
      link:         'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71 M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
      upload:       'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M17 8l-5-5-5 5 M12 3v12',
      broadcast:    'M19.27 5.33C20.37 6.7 21 8.35 21 10c0 3.87-3.13 7-7 7a7 7 0 0 1-7-7c0-3.87 3.13-7 7-7 M12 17v5 M9 22h6',
    };

    const Icon = ({ name, size = 20, color, style: st }) => {
      const d = ICONS[name] || ICONS.fileText;
      return el('svg', {
        xmlns: 'http://www.w3.org/2000/svg', width: size, height: size,
        viewBox: '0 0 24 24', fill: 'none', stroke: color || 'currentColor',
        strokeWidth: '2', strokeLinecap: 'round', strokeLinejoin: 'round', style: st,
        dangerouslySetInnerHTML: {
          __html: d.split(' M').map((seg, i) => `<path d="${i === 0 ? seg : 'M' + seg}" />`).join('')
        }
      });
    };

    /* ---- FIELD ---- */
    const Field = ({ label, value, onChange, type = 'text', placeholder = '', rows = 3, options = [], disabled = false }) => {
      const isSelect   = type === 'select';
      const isTextarea = type === 'textarea';
      return el('div', { style: { display: 'flex', flexDirection: 'column', gap: 6 } },
        label && el('label', { className: 'lbl' }, label),
        isSelect
          ? el('select', { className: 'field', value: value ?? '', onChange: e => onChange(e.target.value), disabled },
              options.map(o => {
                const val = typeof o === 'object' ? o.value : o;
                const lbl = typeof o === 'object' ? o.label : o;
                return el('option', { key: val, value: val }, lbl);
              })
            )
          : isTextarea
            ? el('textarea', { className: 'field', value: value ?? '', onChange: e => onChange(e.target.value), rows, placeholder, disabled })
            : el('input', { className: 'field', type, value: value ?? '', onChange: e => onChange(e.target.value), placeholder, disabled })
      );
    };

    /* ---- REPEATER ---- */
    const Repeater = ({ label, items, onChange, fields }) => {
      const arr = Array.isArray(items) ? items : [];
      const add = () => onChange([...arr, fields.reduce((a, f) => ({ ...a, [f.k]: '' }), {})]);
      const upd = (i, k, v) => { const n = [...arr]; n[i] = { ...n[i], [k]: v }; onChange(n); };
      const rm  = i => onChange(arr.filter((_, j) => j !== i));
      return el('div', { style: { border: '1px solid var(--border)', borderRadius: 12, overflow: 'hidden' } },
        el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 14px', background: 'var(--surface2)' } },
          el('span', { className: 'lbl', style: { margin: 0 } }, label),
          el('button', { className: 'btn btn-ghost btn-sm press', onClick: add }, '+ Add')
        ),
        arr.map((item, i) => el('div', { key: i, style: { borderTop: '1px solid var(--border)', padding: 14, display: 'flex', flexDirection: 'column', gap: 10, position: 'relative' } },
          el('button', { onClick: () => rm(i), style: { position: 'absolute', top: 10, right: 10, background: 'none', border: 'none', color: 'var(--red)', cursor: 'pointer', padding: 4 } },
            el(Icon, { name: 'x', size: 16 })
          ),
          fields.map(f => el(Field, { key: f.k, label: f.l, value: item[f.k] || '', onChange: v => upd(i, f.k, v) }))
        ))
      );
    };

    /* ---- SHEET ---- */
    const Sheet = ({ open, onClose, title, children, footer }) => {
      if (!open) return null;
      return el('div', null,
        el('div', { className: 'sheet-backdrop anim-in', onClick: onClose }),
        el('div', { className: 'sheet-panel sheet-in' },
          el('div', { className: 'sheet-handle' }),
          title && el('div', { className: 'sheet-header' },
            el('h3', { style: { fontSize: 17, fontWeight: 800 } }, title),
            el('button', { onClick: onClose, style: { background: 'none', border: 'none', color: 'var(--muted)', cursor: 'pointer', padding: 6 } },
              el(Icon, { name: 'x', size: 18 })
            )
          ),
          el('div', { className: 'sheet-body' }, children),
          footer && el('div', { className: 'sheet-footer' }, footer)
        )
      );
    };

    /* ---- STATUS BADGE ---- */
    const StatusBadge = ({ s }) => {
      const map = {
        active: 'green', approved: 'green', APPROVED: 'green', completed: 'green', Active: 'green',
        pending: 'amber', PENDING_ADMIN_REVIEW: 'amber', AWAITING_PROOF: 'amber', IN_PROGRESS: 'amber', Pending: 'amber',
        inactive: 'slate', expired: 'slate', Inactive: 'slate', Expired: 'slate',
        rejected: 'red', REJECTED: 'red', Rejected: 'red'
      };
      return el('span', { className: `pill pill-${map[s] || 'slate'}` }, s || '–');
    };

    /* ---- EMPTY ---- */
    const Empty = ({ text = 'No records found', icon = 'fileText' }) =>
      el('div', { style: { display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '60px 20px', color: 'var(--muted)', gap: 14 } },
        el(Icon, { name: icon, size: 38 }),
        el('p', { style: { fontSize: 14, fontWeight: 600, textAlign: 'center' } }, text)
      );

    /* ---- LOADER ---- */
    const Loader = ({ size = 28 }) =>
      el('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 60 } },
        el('div', { className: 'spinner', style: { width: size, height: size, border: '3px solid var(--surface3)', borderTopColor: 'var(--accent)', borderRadius: '50%' } })
      );

    /* ---- BACK HEADER ---- */
    const BackHeader = ({ title, onBack, right }) =>
      el('div', { className: 'page-header', style: { display: 'flex', alignItems: 'center', gap: 8 } },
        el('button', { onClick: onBack, style: { background: 'none', border: 'none', color: 'var(--text)', cursor: 'pointer', padding: '4px 8px 4px 0', flexShrink: 0 } },
          el(Icon, { name: 'chevronLeft', size: 22 })
        ),
        el('h2', { style: { fontSize: 18, fontWeight: 800, flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, title),
        right
      );

    /* ---- CONFIRM DIALOG ---- */
    const ConfirmDialog = ({ state, setState }) => {
      if (!state) return null;
      const choose = v => {
        setState(null);
        try { window._confirmResolve?.(v); } catch (_) {}
      };
      return el('div', { className: 'confirm-overlay anim-in', onClick: () => choose(false) },
        el('div', { className: 'confirm-box anim-up', onClick: e => e.stopPropagation() },
          el('h3', { style: { fontSize: 18, fontWeight: 800, marginBottom: 8 } }, state.title || 'Confirm'),
          el('p', { style: { color: 'var(--muted)', fontSize: 14, marginBottom: 24, lineHeight: 1.5 } }, state.body || 'Are you sure?'),
          state.codeRequired && el('div', { style: { marginBottom: 16 } },
            el('p', { style: { fontSize: 13, color: 'var(--amber)', marginBottom: 8, fontWeight: 600 } }, `Type ${state.code} to confirm:`),
            el('input', { className: 'field', placeholder: `Enter ${state.code}`, id: 'confirm-code-input', autoFocus: true })
          ),
          el('div', { style: { display: 'flex', gap: 12 } },
            el('button', { className: 'btn btn-ghost press', style: { flex: 1 }, onClick: () => choose(false) }, 'Cancel'),
            el('button', {
              className: `btn ${state.danger ? 'btn-danger' : 'btn-primary'} press`, style: { flex: 1 },
              onClick: () => {
                if (state.codeRequired) {
                  const val = document.getElementById('confirm-code-input')?.value?.trim() || '';
                  if (String(val) !== String(state.code)) { showToast('Wrong code', 'err'); return; }
                }
                choose(true);
              }
            }, state.confirmLabel || 'Confirm')
          )
        )
      );
    };

    Object.assign(window._XOFLIX, {
      ICONS, Icon, Field, Repeater, Sheet, StatusBadge, Empty, Loader, BackHeader, ConfirmDialog
    });
  }

  // Register factory so initApp() can call it after React is ready
  window._XOFLIX._registerPrimitives = register;
})();
