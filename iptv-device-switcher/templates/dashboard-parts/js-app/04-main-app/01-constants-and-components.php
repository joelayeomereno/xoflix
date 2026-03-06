<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
// ==========================================
// 4. MAIN APP CONSTANTS (CLEANED)
// ==========================================
// NOTE: Global components (SportsCard, ModernSubCard, etc) have been moved to 
// 02-shared-components.php to prevent "Duplicate Declaration" errors (White Screen).

// --- CONSTANTS ---
const COUNTRIES = [
    {c:'US',n:'United States'},{c:'GB',n:'United Kingdom'},{c:'CA',n:'Canada'},{c:'AU',n:'Australia'},
    {c:'DE',n:'Germany'},{c:'FR',n:'France'},{c:'ES',n:'Spain'},{c:'IT',n:'Italy'},{c:'BR',n:'Brazil'},
    {c:'NG',n:'Nigeria'},{c:'ZA',n:'South Africa'},{c:'IN',n:'India'},{c:'CN',n:'China'},{c:'JP',n:'Japan'},
    {c:'NL',n:'Netherlands'},{c:'NZ',n:'New Zealand'},{c:'AE',n:'UAE'},{c:'SA',n:'Saudi Arabia'},
    {c:'MX',n:'Mexico'},{c:'AR',n:'Argentina'},{c:'CO',n:'Colombia'},{c:'PE',n:'Peru'},{c:'CL',n:'Chile'},
    {c:'SE',n:'Sweden'},{c:'NO',n:'Norway'},{c:'DK',n:'Denmark'},{c:'FI',n:'Finland'},{c:'IE',n:'Ireland'},
    {c:'PL',n:'Poland'},{c:'GR',n:'Greece'},{c:'PT',n:'Portugal'},{c:'TR',n:'Turkey'},{c:'RU',n:'Russia'},
    {c:'KR',n:'South Korea'},{c:'SG',n:'Singapore'},{c:'MY',n:'Malaysia'},{c:'ID',n:'Indonesia'},
    {c:'TH',n:'Thailand'},{c:'PH',n:'Philippines'},{c:'VN',n:'Vietnam'},{c:'PK',n:'Pakistan'},
    {c:'BD',n:'Bangladesh'},{c:'EG',n:'Egypt'},{c:'MA',n:'Morocco'},{c:'DZ',n:'Algeria'},{c:'KE',n:'Kenya'},
    {c:'GH',n:'Ghana'},{c:'UG',n:'Uganda'},{c:'TZ',n:'Tanzania'},{c:'IL',n:'Israel'},{c:'QA',n:'Qatar'},
    {c:'KW',n:'Kuwait'},{c:'BE',n:'Belgium'},{c:'CH',n:'Switzerland'},{c:'AT',n:'Austria'},{c:'CZ',n:'Czechia'},
    {c:'HU',n:'Hungary'},{c:'RO',n:'Romania'},{c:'BG',n:'Bulgaria'},{c:'RS',n:'Serbia'},{c:'HR',n:'Croatia'}
];

// --- UTILITIES ---

const getSmartStyles = (hex, isHex) => {
    const defaults = {
        title: 'text-white',
        desc: 'text-white/90',
        badge: 'bg-white/20 backdrop-blur-md border-white/10 text-white',
        overlay: 'bg-black/10 mix-blend-overlay'
    };

    if (!isHex || !hex || !hex.startsWith('#')) return defaults;

    let c = hex.substring(1);
    if (c.length === 3) c = c.split('').map(char => char + char).join('');
    if (c.length !== 6) return defaults;

    const rgb = parseInt(c, 16);
    const r = (rgb >> 16) & 0xff;
    const g = (rgb >>  8) & 0xff;
    const b = (rgb >>  0) & 0xff;

    const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b;

    if (luma > 160) {
        return {
            title: 'text-slate-900',
            desc: 'text-slate-600',
            badge: 'bg-slate-900/10 border-slate-900/10 text-slate-800',
            overlay: 'hidden'
        };
    } else {
        return defaults;
    }
};

// 1. Locked Country Display (Utility Component specific to Profile)
const LockedCountryDisplay = ({ countryCode }) => {
    const countryObj = COUNTRIES.find(c => c.c === countryCode || c.n === countryCode) || { c: '', n: countryCode || 'Unknown' };
    
    return (
        <div className="relative">
            <div className="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold text-slate-500 cursor-not-allowed opacity-80 flex items-center gap-3">
                {countryObj.c && (
                    <img 
                        src={`https://flagcdn.com/w40/${countryObj.c.toLowerCase()}.png`} 
                        alt={countryObj.c} 
                        className="w-6 h-auto rounded-sm grayscale opacity-70" 
                    />
                )}
                <span className="flex-1 truncate">{countryObj.n}</span>
                <Lock size={16} className="text-slate-400" />
            </div>
            <div className="text-[10px] text-rose-500 font-bold mt-1.5 flex items-center gap-1">
                <Lock size={10} /> Location is locked. Contact support to change.
            </div>
        </div>
    );
};