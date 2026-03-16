<?php if (!defined('ABSPATH')) { exit; } ?>
        // --- 2. ICON LIBRARY (Inline SVG) ---
        const Icon = ({ name, size=20, className="" }) => {
            const paths = {
                home: "M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z",
                users: "M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M16 3.13a4 4 0 0 1 0 7.75 M23 21v-2a4 4 0 0 0-3-3.87",
                creditCard: "M1 4h22v16H1z M1 10h22",
                settings: "M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.47a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z",
                search: "M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.35-4.35",
                check: "M20 6 9 17 4 12",
                x: "M18 6 6 18 M6 6 18 18",
                activity: "M22 12h-4l-3 9L9 3l-3 9H2",
                logOut: "M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17 21 12 16 7 M21 12H9",
                zap: "M13 2 3 14h9l-1 8 10-12h-9l1-8z",
                trendingUp: "M23 6 13.5 15.5 8.5 10.5 1 18 M17 6h6v6",
                server: "M2 2h20v8H2z M2 14h20v8H2z M6 6h.01 M6 18h.01",
                key: "M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4",
                edit: "M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z",
                save: "M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8",
                menu: "M3 12h18 M3 6h18 M3 18h18",
                plus: "M12 5v14 M5 12h14",
                trash: "M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2",
                tag: "M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z M7 7h.01",
                tv: "M2 7h20v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7 M17 2l-5 5-5-5",
                message: "M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z",
                chevronLeft: "M15 18l-6-6 6-6",
                trophy: "M6 9H4.5a2.5 2.5 0 0 1 0-5H6 M18 9h1.5a2.5 2.5 0 0 0 0-5H18 M4 22h16 M2 12h20 M12 2a5 5 0 0 0-5 5v2h10V7a5 5 0 0 0-5-5z",
                mail: "M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z M22 6l-10 7L2 6"
            };
            const d = paths[name] || paths.home;
            const html = d.startsWith('<') ? d : `<path d="${d}" />`;

            return el('svg', { 
                xmlns: "http://www.w3.org/2000/svg", 
                width: size, height: size, 
                viewBox: "0 0 24 24", 
                fill: "none", stroke: "currentColor", 
                strokeWidth: "2", strokeLinecap: "round", strokeLinejoin: "round", 
                className, 
                dangerouslySetInnerHTML: { __html: html }
            });
        };

