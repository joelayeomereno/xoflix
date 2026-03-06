<?php if (!defined('ABSPATH')) { exit; } ?>
    /* ---------------------------------------------
        SHARED UI PRIMITIVES
    --------------------------------------------- */

    /**
     * Standard form input component.
     * Supports text, email, password, url, select, and textarea.
     */
    const Field = ({ label, value, onChange, type = 'text', placeholder = '', rows = 3, options = [], disabled = false }) => {
      const isSelect   = type === 'select';
      const isTextarea = type === 'textarea';
      
      return el('div', { style: { display: 'flex', flexDirection: 'column', gap: 6 } },
        label && el('label', { className: 'lbl' }, label),
        isSelect
          ? el('select', { 
              className: 'field', 
              value: value ?? '', 
              onChange: e => onChange(e.target.value), 
              disabled 
            },
              options.map(o => {
                const val = typeof o === 'object' ? o.value : o;
                const lbl = typeof o === 'object' ? o.label : o;
                return el('option', { key: val, value: val }, lbl);
              })
            )
          : isTextarea
            ? el('textarea', { 
                className: 'field', 
                value: value ?? '', 
                onChange: e => onChange(e.target.value), 
                rows, 
                placeholder, 
                disabled 
              })
            : el('input', { 
                className: 'field', 
                type, 
                value: value ?? '', 
                onChange: e => onChange(e.target.value), 
                placeholder, 
                disabled 
              })
      );
    };

    /**
     * Dynamic list editor for arrays of objects.
     */
    const Repeater = ({ label, items, onChange, fields }) => {
      const arr = Array.isArray(items) ? items : [];
      
      const add = () => {
        const newItem = fields.reduce((acc, f) => ({ ...acc, [f.k]: '' }), {});
        onChange([...arr, newItem]);
      };
      
      const upd = (i, k, v) => {
        const next = [...arr];
        next[i] = { ...next[i], [k]: v };
        onChange(next);
      };
      
      const rm = i => {
        onChange(arr.filter((_, j) => j !== i));
      };

      return el('div', { 
        style: { border: '1px solid var(--border)', borderRadius: 12, overflow: 'hidden' } 
      },
        el('div', { 
          style: { 
            display: 'flex', 
            justifyContent: 'space-between', 
            alignItems: 'center', 
            padding: '10px 14px', 
            background: 'var(--surface2)' 
          } 
        },
          el('span', { className: 'lbl', style: { margin: 0 } }, label),
          el('button', { className: 'btn btn-ghost btn-sm press', onClick: add }, '+ Add')
        ),
        arr.map((item, i) => el('div', { 
          key: i, 
          style: { 
            borderTop: '1px solid var(--border)', 
            padding: 14, 
            display: 'flex', 
            flexDirection: 'column', 
            gap: 10, 
            position: 'relative' 
          } 
        },
          el('button', { 
            onClick: () => rm(i), 
            style: { 
              position: 'absolute', 
              top: 10, 
              right: 10, 
              background: 'none', 
              border: 'none', 
              color: 'var(--red)', 
              cursor: 'pointer', 
              padding: 4 
            } 
          }, el(Icon, { name: 'x', size: 16 })),
          fields.map(f => el(Field, { 
            key: f.k, 
            label: f.l, 
            value: item[f.k] || '', 
            onChange: v => upd(i, f.k, v) 
          }))
        ))
      );
    };

    /**
     * Mobile-first bottom sheet modal.
     */
    const Sheet = ({ open, onClose, title, children, footer }) => {
      if (!open) return null;
      
      return el('div', null,
        el('div', { className: 'sheet-backdrop anim-in', onClick: onClose }),
        el('div', { className: 'sheet-panel sheet-in' },
          el('div', { className: 'sheet-handle' }),
          title && el('div', { className: 'sheet-header' },
            el('h3', { style: { fontSize: 17, fontWeight: 800 } }, title),
            el('button', { 
              onClick: onClose, 
              style: { background: 'none', border: 'none', color: 'var(--muted)', cursor: 'pointer', padding: 6 } 
            }, el(Icon, { name: 'x', size: 18 }))
          ),
          el('div', { className: 'sheet-body' }, children),
          footer && el('div', { className: 'sheet-footer' }, footer)
        )
      );
    };

    /**
     * Unified status badge component with color mapping.
     */
    const StatusBadge = ({ s }) => {
      const map = {
        active: 'green', approved: 'green', APPROVED: 'green', completed: 'green', Active: 'green',
        pending: 'amber', PENDING_ADMIN_REVIEW: 'amber', AWAITING_PROOF: 'amber', IN_PROGRESS: 'amber', Pending: 'amber',
        inactive: 'slate', expired: 'slate', Inactive: 'slate', Expired: 'slate',
        rejected: 'red', REJECTED: 'red', Rejected: 'red'
      };
      return el('span', { className: `pill pill-${map[s] || 'slate'}` }, s || '–');
    };

    /**
     * Placeholder for empty data states.
     */
    const Empty = ({ text = 'No records found', icon = 'fileText' }) =>
      el('div', { 
        style: { 
          display: 'flex', 
          flexDirection: 'column', 
          alignItems: 'center', 
          justifyContent: 'center', 
          padding: '60px 20px', 
          color: 'var(--muted)', 
          gap: 14 
        } 
      },
        el(Icon, { name: icon, size: 38 }),
        el('p', { style: { fontSize: 14, fontWeight: 600, textAlign: 'center' } }, text)
      );

    /**
     * Global loading spinner.
     */
    const Loader = ({ size = 28 }) =>
      el('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 60 } },
        el('div', { 
          className: 'spinner', 
          style: { 
            width: size, 
            height: size, 
            border: '3px solid var(--surface3)', 
            borderTopColor: 'var(--accent)', 
            borderRadius: '50%' 
          } 
        })
      );

    /**
     * Shared sub-view header with back navigation.
     */
    const BackHeader = ({ title, onBack, right }) =>
      el('div', { className: 'page-header', style: { display: 'flex', alignItems: 'center', gap: 8 } },
        el('button', { 
          onClick: onBack, 
          style: { background: 'none', border: 'none', color: 'var(--text)', cursor: 'pointer', padding: '4px 8px 4px 0', flexShrink: 0 } 
        }, el(Icon, { name: 'chevronLeft', size: 22 })),
        el('h2', { 
          style: { 
            fontSize: 18, 
            fontWeight: 800, 
            flex: 1, 
            overflow: 'hidden', 
            textOverflow: 'ellipsis', 
            whiteSpace: 'nowrap' 
          } 
        }, title),
        right
      );