// part-utils.js
// Registers: _XOFLIX.showToast, _XOFLIX.apiRaw, _XOFLIX.apiFetch, _XOFLIX.haptic
// NOTE: Must be loaded before all other parts.

(function () {
  'use strict';

  const showToast = (msg, type = 'ok') => {
    const d = document.createElement('div');
    d.className = `toast toast-${type}`;
    d.textContent = msg;
    const root = document.getElementById('toast-root');
    if (!root) return;
    root.appendChild(d);
    setTimeout(() => { try { d.remove(); } catch (_) {} }, 3200);
  };

  const apiRaw = async (ep, m = 'GET', b = null) => {
    const res = await fetch(TVMA.api + '/' + ep, {
      method: m,
      headers: { 'X-WP-Nonce': TVMA.nonce, 'Content-Type': 'application/json' },
      body: b ? JSON.stringify(b) : null
    });
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j.message || j.error || `HTTP ${res.status}`);
    return j;
  };

  const apiFetch = async (ep, m = 'GET', b = null) => {
    try { return await apiRaw(ep, m, b); }
    catch (e) { showToast(e.message, 'err'); throw e; }
  };

  const haptic = (ms = 8) => { try { navigator.vibrate?.(ms); } catch (_) {} };

  Object.assign(window._XOFLIX, { showToast, apiRaw, apiFetch, haptic });
})();
