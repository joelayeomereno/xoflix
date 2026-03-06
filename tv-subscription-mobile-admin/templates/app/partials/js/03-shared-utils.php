<?php if (!defined('ABSPATH')) { exit; } ?>
        // --- 3. UTILITIES & API ---
        const api = async (ep, m='GET', b=null) => {
            try {
                const res = await fetch(window.TVMA.api + '/' + ep, {
                    method: m, headers: { 'X-WP-Nonce': window.TVMA.nonce, 'Content-Type': 'application/json' },
                    body: b ? JSON.stringify(b) : null
                });
                const j = await res.json();
                if(!res.ok) throw new Error(j.message || 'Server Error');
                return j;
            } catch(e) { 
                alert(e.message); 
                throw e; 
            }
        };

        // Sensitive action guards
        const verify4DigitCode = (label) => {
            const code = String(Math.floor(1000 + Math.random()*9000));
            const entered = window.prompt(`Type ${code} to confirm ${label}:`, '');
            return String(entered||'').trim() === code;
        };
        const confirmApprove = () => window.confirm('Are you sure you want to approve this transaction?');
        const confirmReject = () => window.confirm('Are you sure you want to reject this transaction?');
        const confirmDeleteWithCode = (label='delete') => {
            if(!window.confirm(`Are you sure you want to ${label}?`)) return false;
            return verify4DigitCode(label);
        };

        const haptic = () => { if(navigator.vibrate) navigator.vibrate(10); };

