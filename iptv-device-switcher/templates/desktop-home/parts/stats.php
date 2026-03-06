<!-- STATS BAR -->
<div class="v24-stats-bar reveal">
    <div class="v24-stats-grid">
        <div class="v24-stat">
            <span class="v24-stat-val count-up" data-target="25000">0</span>
            <span class="v24-stat-lbl">Live Channels</span>
        </div>
        <div class="v24-stat">
            <span class="v24-stat-val count-up" data-target="120000">0</span>
            <span class="v24-stat-lbl">Movies & Series</span>
        </div>
        <div class="v24-stat">
            <span class="v24-stat-val">4K</span>
            <span class="v24-stat-lbl">UHD Quality</span>
        </div>
        <div class="v24-stat">
            <span class="v24-stat-val">99.9%</span>
            <span class="v24-stat-lbl">Uptime SLA</span>
        </div>
    </div>

    <!-- UTILITY TOOLS SECTION (Relocated Down-Page) -->
    <div style="margin-top: 40px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <!-- M3U CONVERTER TOOL -->
        <a href="<?php echo esc_url( home_url( '/m3u-parser' ) ); ?>" 
           style="
               display: inline-flex;
               align-items: center;
               gap: 12px;
               padding: 14px 28px;
               background: rgba(99, 102, 241, 0.1);
               border: 1px solid rgba(99, 102, 241, 0.3);
               border-radius: 14px;
               color: white;
               text-decoration: none;
               font-weight: 700;
               font-size: 0.95rem;
               transition: all 0.3s var(--v24-ease);
               backdrop-filter: blur(10px);
           "
           onmouseover="this.style.background='rgba(99, 102, 241, 0.2)'; this.style.borderColor='var(--v24-primary)';"
           onmouseout="this.style.background='rgba(99, 102, 241, 0.1)'; this.style.borderColor='rgba(99, 102, 241, 0.3)';"
        >
            <i class="fas fa-magic" style="color: var(--v24-primary);"></i>
            M3U to Xtream Converter
            <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.5; margin-left: 8px;"></i>
        </a>

        <!-- IPTV SETUP GUIDE (New External Link) -->
        <a href="https://kuality1st.com/iptv-guide/" 
           target="_blank"
           rel="noopener"
           style="
               display: inline-flex;
               align-items: center;
               gap: 12px;
               padding: 14px 28px;
               background: rgba(255, 255, 255, 0.05);
               border: 1px solid rgba(255, 255, 255, 0.1);
               border-radius: 14px;
               color: white;
               text-decoration: none;
               font-weight: 700;
               font-size: 0.95rem;
               transition: all 0.3s var(--v24-ease);
               backdrop-filter: blur(10px);
           "
           onmouseover="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.borderColor='rgba(255, 255, 255, 0.3)';"
           onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.borderColor='rgba(255, 255, 255, 0.1)';"
        >
            <i class="fas fa-book-open" style="color: var(--v24-accent);"></i>
            App Setup Guide
            <i class="fas fa-external-link-alt" style="font-size: 0.7rem; opacity: 0.5; margin-left: 8px;"></i>
        </a>
    </div>
</div>