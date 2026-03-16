// part-sports.js
// Registers: _XOFLIX._registerSports → SportsGuide
// Depends on: React, _XOFLIX.{ apiFetch, apiRaw, showToast, Icon, BackHeader, Sheet, Field, Loader, Empty }

(function () {
  'use strict';

  window._XOFLIX._registerSports = function () {
    const { useState, useEffect, createElement: el } = React;
    const { apiFetch, apiRaw, showToast, Icon, BackHeader, Sheet, Field, Loader, Empty } = window._XOFLIX;

    const SportsGuide = ({ onBack }) => {
      const [list, setList]           = useState([]);
      const [loading, setLoading]     = useState(true);
      const [editSheet, setEditSheet] = useState(null);
      const [chanSheet, setChanSheet] = useState(null);
      const [extractOpen, setExtractOpen] = useState(false);
      const [extractMode, setExtractMode] = useState('text');
      const [extractInput, setExtractInput] = useState('');
      const [extracting, setExtracting]   = useState(false);
      const [extracted, setExtracted]     = useState([]);
      const [creating, setCreating]       = useState(false);
      const [newMatch, setNewMatch]       = useState({ title: '', league: '', sport_type: 'Football', date: '', time: '', channel: '' });

      const load = async () => {
        setLoading(true);
        try {
          const r = await apiFetch('sports');
          setList(Array.isArray(r) ? r : (r.data || []));
        } catch (_) { setList([]); }
        finally { setLoading(false); }
      };
      useEffect(() => { load(); }, []);

      const saveMatch = async form => {
        try {
          await apiFetch(form.id ? `sports/${form.id}` : 'sports/new', 'POST', form);
          showToast(form.id ? 'Updated ✅' : 'Created ✅'); setEditSheet(null); setCreating(false); load();
        } catch (_) {}
      };

      const saveChannels = async () => {
        if (!chanSheet) return;
        try {
          await apiFetch(`sports/${chanSheet.id}`, 'POST', { channels: chanSheet.channels });
          showToast('Channels saved ✅'); setChanSheet(null); load();
        } catch (_) {}
      };

      const del = async id => {
        const code = Math.floor(1000 + Math.random() * 9000);
        const ok = await window._XOFLIX._confirm('Delete Match', 'Remove this event?', { danger: true, codeRequired: true, code, confirmLabel: 'Delete' });
        if (!ok) return;
        try { await apiFetch(`sports/${id}/delete`, 'DELETE'); showToast('Deleted'); load(); } catch (_) {}
      };

      const runExtract = async () => {
        if (!extractInput.trim()) return showToast('Paste some content first', 'err');
        setExtracting(true); setExtracted([]);
        try {
          const r = await apiRaw('sports/smart-extract', 'POST', { mode: extractMode, input: extractInput });
          setExtracted(Array.isArray(r.matches) ? r.matches : r);
          if (!r.matches?.length && !r.length) showToast('No matches found in input', 'err');
          else showToast(`Extracted ${(r.matches || r).length} match(es) ✨`);
        } catch (_) {
          const lines = extractInput.split('\n').filter(l => l.trim().length > 5);
          const matches = lines.map(line => {
            const timeMatch = line.match(/\b(\d{1,2}:\d{2})\b/);
            const vsMatch   = line.match(/(.+?)\s+(?:vs?\.?|VS|–|-)\s+(.+?)(?:\s+\d|$)/i);
            const chanMatch = line.match(/\b(ESPN|BBC|Sky|Fox|beIN|DAZN|TNT|Canal\+?|SuperSport|Star)\w*/i);
            if (!vsMatch) return null;
            return { _new: true, title: vsMatch[0].trim(), home_team: vsMatch[1].trim(), away_team: vsMatch[2].trim(), time: timeMatch?.[1] || '', channel: chanMatch?.[0] || '', date: new Date().toISOString().slice(0, 10) };
          }).filter(Boolean);
          setExtracted(matches);
          if (matches.length) showToast(`Parsed ${matches.length} match(es) locally ✨`);
          else showToast('Could not parse content. Try pasting structured fixture data.', 'err');
        }
        setExtracting(false);
      };

      const importExtracted = async items => {
        let ok = 0;
        for (const m of items) { const { _new, ...data } = m; try { await apiRaw('sports/new', 'POST', data); ok++; } catch (_) {} }
        showToast(`Imported ${ok} matches ✅`); setExtractOpen(false); setExtracted([]); setExtractInput(''); load();
      };

      /* ---- Smart Extract Sheet ---- */
      const SmartExtractSheet = () =>
        el(Sheet, { open: extractOpen, onClose: () => { setExtractOpen(false); setExtracted([]); setExtractInput(''); }, title: '✨ Smart Extract' },
          el('div', { className: 'col g14' },
            el('div', { className: 'extract-banner' },
              el('div', { style: { display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8 } },
                el(Icon, { name: 'sparkles', size: 18, color: '#a5b4fc' }),
                el('span', { style: { fontWeight: 800, fontSize: 15, color: '#a5b4fc' } }, 'Smart Extract')
              ),
              el('p', { style: { fontSize: 13, color: 'var(--muted)', lineHeight: 1.6 } },
                'Paste a fixture list, schedule screenshot text, or broadcast schedule. AI will extract teams, times, and channels automatically.'
              )
            ),
            el('div', { className: 'tab-row' },
              [['text', '📄 Text'], ['paste', '📋 Schedule'], ['url', '🔗 URL']].map(([m, l]) =>
                el('button', { key: m, className: `tab-btn ${extractMode === m ? 'active' : ''}`, onClick: () => setExtractMode(m) }, l)
              )
            ),
            extractMode === 'url'
              ? el(Field, { label: 'Fixture URL', value: extractInput, onChange: setExtractInput, placeholder: 'https://example.com/fixtures' })
              : el(Field, { label: extractMode === 'paste' ? 'Paste Schedule Text' : 'Fixture Text', value: extractInput, onChange: setExtractInput, type: 'textarea', rows: 7,
                  placeholder: extractMode === 'paste' ? '18:00 Arsenal vs Chelsea  ESPN\n20:45 Liverpool vs Man City  Sky Sports\n...' : 'Paste any match list, fixture table, or broadcast schedule here...'
                }),
            el('button', { className: 'btn btn-primary btn-full press', onClick: runExtract, disabled: extracting },
              extracting ? el('div', { className: 'spinner', style: { width: 16, height: 16, border: '2px solid rgba(255,255,255,.3)', borderTopColor: '#fff', borderRadius: '50%' } })
                         : el(Icon, { name: 'sparkles', size: 16 }),
              extracting ? 'Extracting…' : 'Extract Matches'
            ),
            extracted.length > 0 && el('div', { className: 'col g10' },
              el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                el('p', { style: { fontWeight: 800, fontSize: 15 } }, `${extracted.length} matches found`),
                el('button', { className: 'btn btn-success btn-sm press', onClick: () => importExtracted(extracted) }, el(Icon, { name: 'upload', size: 14 }), 'Import All')
              ),
              extracted.map((m, i) =>
                el('div', { key: i, className: 'card2', style: { padding: '12px 14px' } },
                  el('p', { style: { fontWeight: 700, fontSize: 14, marginBottom: 4 } }, m.title || `${m.home_team} vs ${m.away_team}`),
                  el('div', { style: { display: 'flex', gap: 8, flexWrap: 'wrap' } },
                    m.time    && el('span', { className: 'pill pill-indigo' }, '🕒 ' + m.time),
                    m.channel && el('span', { className: 'pill pill-cyan'   }, '📺 ' + m.channel),
                    m.league  && el('span', { className: 'pill pill-slate'  }, m.league)
                  ),
                  el('div', { style: { display: 'flex', justifyContent: 'flex-end', marginTop: 8 } },
                    el('button', { className: 'btn btn-ghost btn-sm press', onClick: () => importExtracted([m]) }, 'Import this')
                  )
                )
              )
            )
          )
        );

      /* ---- Match Form Sheet (with inline Smart Extract) ---- */
      const MatchFormSheet = () => {
        const isNew = !editSheet?.id;
        const [form, setForm]             = useState(editSheet || newMatch);
        const [showExtract, setShowExtract] = useState(false);
        const [xtText, setXtText]         = useState('');
        const [xting, setXting]           = useState(false);

        const doExtract = async () => {
          if (!xtText.trim()) return showToast('Paste fixture text first', 'err');
          setXting(true);
          const fill = m => {
            setForm(f => ({
              ...f,
              title:      m.title || (m.home_team && m.away_team ? m.home_team + ' vs ' + m.away_team : f.title),
              league:      m.league     || f.league,
              sport_type: m.sport_type || f.sport_type,
              date:        m.date       || f.date,
              time:        m.time       || f.time,
              channel:     m.channel    || f.channel,
            }));
            showToast('Fields filled ✓'); setShowExtract(false); setXtText('');
          };
          try {
            const r  = await apiRaw('sports/smart-extract', 'POST', { mode: 'text', input: xtText });
            const ms = Array.isArray(r.matches) ? r.matches : (Array.isArray(r) ? r : []);
            if (ms.length) fill(ms[0]); else showToast('No match found', 'err');
          } catch (_) {
            const line = xtText.split('\n').find(l => l.trim().length > 5) || xtText;
            const tm = line.match(/\b(\d{1,2}:\d{2})\b/);
            const vm = line.match(/(.+?)\s+(?:vs?\.?|VS|–|-)\s+(.+?)(?:\s+\d|$)/i);
            const cm = line.match(/\b(ESPN|BBC|Sky|Fox|beIN|DAZN|TNT|Canal\+?|SuperSport|Star)\w*/i);
            if (vm) fill({ title: vm[0].trim(), time: tm?.[1] || '', channel: cm?.[0] || '' });
            else showToast('Could not parse — try a single fixture line', 'err');
          }
          setXting(false);
        };

        return el(Sheet, {
          open: !!(editSheet || creating), onClose: () => { setEditSheet(null); setCreating(false); },
          title: isNew ? '➕ New Match' : '📝 Edit Match Details',
          footer: el('button', { className: 'btn btn-primary btn-full press', onClick: () => saveMatch(form) }, el(Icon, { name: 'check', size: 16 }), isNew ? 'Create Match' : 'Save Match')
        },
          el('div', { className: 'col g14' },
            el('div', { style: { borderRadius: 12, border: '1px solid rgba(99,102,241,.3)', overflow: 'hidden' } },
              el('button', { className: 'press', onClick: () => setShowExtract(s => !s),
                style: { width: '100%', display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 14px', background: 'none', border: 'none', cursor: 'pointer' } },
                el('span', { style: { display: 'flex', alignItems: 'center', gap: 8, fontWeight: 700, fontSize: 13, color: '#a5b4fc' } },
                  el(Icon, { name: 'sparkles', size: 14, color: '#a5b4fc' }), '⚡ Smart Extract'
                ),
                el(Icon, { name: showExtract ? 'chevronUp' : 'chevronDown', size: 14, color: 'var(--muted)' })
              ),
              showExtract && el('div', { className: 'anim-in col g10', style: { padding: '0 12px 12px', borderTop: '1px solid rgba(99,102,241,.2)' } },
                el('p', { style: { fontSize: 11, color: 'var(--muted)', lineHeight: 1.5, marginTop: 8 } }, 'Paste a fixture line — AI auto-fills fields below.'),
                el(Field, { label: 'Fixture Text', value: xtText, onChange: setXtText, type: 'textarea', rows: 3, placeholder: '20:45 Arsenal vs Chelsea  Premier League  ESPN' }),
                el('button', { className: 'btn btn-warning btn-full press', onClick: doExtract, disabled: xting },
                  el(Icon, { name: 'sparkles', size: 13 }), xting ? 'Extracting…' : 'Extract & Fill'
                )
              )
            ),
            el(Field, { label: 'Title', value: form.title || '', onChange: v => setForm({ ...form, title: v }), placeholder: 'Team A vs Team B' }),
            el(Field, { label: 'League', value: form.league || '', onChange: v => setForm({ ...form, league: v }), placeholder: 'Premier League' }),
            el(Field, { label: 'Sport Type', value: form.sport_type || 'Football', onChange: v => setForm({ ...form, sport_type: v }), type: 'select',
              options: ['Football', 'Basketball', 'Tennis', 'Cricket', 'Rugby', 'Baseball', 'Hockey', 'Boxing', 'MMA', 'Other'] }),
            el('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 } },
              el(Field, { label: 'Date', value: form.date || '', onChange: v => setForm({ ...form, date: v }), type: 'date' }),
              el(Field, { label: 'Time', value: form.time || '', onChange: v => setForm({ ...form, time: v }), placeholder: '20:45' })
            ),
            el(Field, { label: 'Primary Channel', value: form.channel || '', onChange: v => setForm({ ...form, channel: v }), placeholder: 'ESPN, Sky Sports…' })
          )
        );
      };

      /* ---- Channels Sheet ---- */
      const ChannelsSheet = () => {
        const [chans, setChans] = useState(
          chanSheet?.channels
            ? (Array.isArray(chanSheet.channels) ? chanSheet.channels : JSON.parse(chanSheet.channels || '[]'))
            : []
        );
        const addChan = () => setChans(c => [...c, { name: '', url: '', quality: 'HD' }]);
        const updChan = (i, k, v) => setChans(c => { const n = [...c]; n[i] = { ...n[i], [k]: v }; return n; });
        const rmChan  = i => setChans(c => c.filter((_, j) => j !== i));

        return el(Sheet, {
          open: !!chanSheet, onClose: () => setChanSheet(null),
          title: `📺 Channels — ${chanSheet?.title || ''}`,
          footer: el('button', { className: 'btn btn-primary btn-full press', onClick: () => { chanSheet.channels = chans; saveChannels(); } }, el(Icon, { name: 'check', size: 16 }), 'Save Channels')
        },
          el('div', { className: 'col g12' },
            el('div', { style: { padding: '10px 14px', background: 'rgba(6,182,212,.08)', borderRadius: 12, border: '1px solid rgba(6,182,212,.2)', marginBottom: 4 } },
              el('p', { style: { fontSize: 13, color: '#22d3ee', fontWeight: 600, lineHeight: 1.5 } },
                '📺 Add streaming channels for this match. Each channel needs a name and stream URL.'
              )
            ),
            chans.map((c, i) =>
              el('div', { key: i, className: 'card2', style: { padding: 14, position: 'relative' } },
                el('button', { onClick: () => rmChan(i), style: { position: 'absolute', top: 10, right: 10, background: 'none', border: 'none', color: 'var(--red)', cursor: 'pointer', padding: 4 } },
                  el(Icon, { name: 'x', size: 16 })
                ),
                el(Field, { label: 'Channel Name', value: c.name || '', onChange: v => updChan(i, 'name', v), placeholder: 'ESPN HD' }),
                el(Field, { label: 'Stream URL', value: c.url || '', onChange: v => updChan(i, 'url', v), placeholder: 'http://...', type: 'url' }),
                el(Field, { label: 'Quality', value: c.quality || 'HD', onChange: v => updChan(i, 'quality', v), type: 'select', options: ['4K', 'FHD', 'HD', 'SD', 'Unknown'] })
              )
            ),
            el('button', { className: 'btn btn-ghost btn-full press', onClick: addChan }, el(Icon, { name: 'plus', size: 16 }), 'Add Channel')
          )
        );
      };

      return el('div', { className: 'page-wrap' },
        el(BackHeader, { title: 'Sports Guide', onBack,
          right: el('div', { style: { display: 'flex', gap: 8 } },
            el('button', { className: 'btn btn-warning btn-sm press', onClick: () => setExtractOpen(true) }, el(Icon, { name: 'sparkles', size: 15 }), 'Extract'),
            el('button', { className: 'btn btn-primary btn-sm press', onClick: () => { setNewMatch({ title: '', league: '', sport_type: 'Football', date: new Date().toISOString().slice(0, 10), time: '', channel: '' }); setCreating(true); } }, el(Icon, { name: 'plus', size: 15 }))
          )
        }),
        el('div', { className: 'page-scroll no-nav' },
          loading && el(Loader),
          !loading && list.length === 0 && el(Empty, { text: 'No sports events yet', icon: 'trophy' }),
          !loading && list.map(item =>
            el('div', { key: item.id, className: 'card anim-up', style: { margin: '8px 12px 0', padding: '14px 16px' } },
              el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 8 } },
                el('div', { style: { flex: 1, minWidth: 0, marginRight: 8 } },
                  el('p', { style: { fontWeight: 800, fontSize: 15, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, item.title || 'Event'),
                  el('div', { style: { display: 'flex', gap: 6, flexWrap: 'wrap', marginTop: 6 } },
                    item.league     && el('span', { className: 'pill pill-slate'   }, item.league),
                    item.sport_type && el('span', { className: 'pill pill-indigo'  }, item.sport_type),
                    item.date       && el('span', { style: { fontSize: 11, color: 'var(--muted)', fontWeight: 600 } }, item.date + (item.time ? ' · ' + item.time : ''))
                  )
                ),
                el('button', { className: 'btn btn-danger btn-sm press', onClick: () => del(item.id) }, el(Icon, { name: 'trash', size: 14 }))
              ),
              el('div', { style: { display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: 10 } },
                item.channel && el('span', { className: 'pill pill-cyan', style: { gap: 4 } }, el(Icon, { name: 'tv', size: 10 }), item.channel),
                (Array.isArray(item.channels) ? item.channels : []).slice(0, 3).map((c, ci) =>
                  el('span', { key: ci, className: 'pill pill-cyan', style: { gap: 4 } }, el(Icon, { name: 'tv', size: 10 }), c.name || 'Ch ' + (ci + 1))
                )
              ),
              el('div', { style: { display: 'flex', gap: 8 } },
                el('button', { className: 'btn btn-ghost btn-sm press', style: { flex: 1, gap: 6 }, onClick: () => setChanSheet({ ...item, channels: Array.isArray(item.channels) ? item.channels : [] }) },
                  el(Icon, { name: 'tv', size: 14, color: 'var(--cyan)' }),
                  el('span', { style: { color: 'var(--cyan)', fontWeight: 700 } }, 'Channels')
                ),
                el('button', { className: 'btn btn-ghost btn-sm press', style: { flex: 1 }, onClick: () => setEditSheet(item) }, el(Icon, { name: 'edit', size: 14 }), 'Edit Details')
              )
            )
          )
        ),
        el(SmartExtractSheet),
        el(MatchFormSheet),
        el(ChannelsSheet)
      );
    };

    window._XOFLIX.SportsGuide = SportsGuide;
  };
})();
