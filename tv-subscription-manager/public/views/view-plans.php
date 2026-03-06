<?php if (!defined('ABSPATH')) exit; ?>

<style>
    .tv-plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .tv-plan-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 32px;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .tv-plan-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: #3b82f6;
    }
    .tv-plan-name { font-size: 18px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    .tv-plan-price { font-size: 42px; font-weight: 800; color: #0f172a; margin-bottom: 24px; }
    .tv-plan-price span { font-size: 16px; color: #94a3b8; font-weight: 500; }
    
    .tv-features { list-style: none; padding: 0; margin: 0 0 32px 0; flex: 1; }
    .tv-features li { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; color: #334155; font-size: 14px; }
    .tv-check { color: #10b981; }

    .tv-plan-btn {
        display: block; width: 100%; text-align: center;
        background: #0f172a; color: white;
        padding: 16px; border-radius: 12px;
        font-weight: 700; text-decoration: none;
        transition: 0.2s;
    }
    .tv-plan-btn:hover { background: #3b82f6; }
    
    /* Recommended Highlight */
    .tv-plan-card.featured { border: 2px solid #3b82f6; background: #eff6ff; }
    .tv-plan-card.featured .tv-plan-btn { background: #3b82f6; }
    .tv-plan-card.featured .tv-plan-btn:hover { background: #2563eb; }
    .tv-badge {
        position: absolute; top: 20px; right: 20px;
        background: #3b82f6; color: white;
        padding: 4px 12px; border-radius: 99px;
        font-size: 10px; font-weight: 700; text-transform: uppercase;
    }
</style>

<div class="tv-plans-grid">
    <?php if($plans): foreach($plans as $p): 
        $is_featured = (strpos(strtolower($p->name), 'premium') !== false);
        
        // [FOX INTEGRATION] Localize Price Display
        $price_display = '$' . $p->price;
        if (method_exists($this, 'get_currency_data')) {
            $cdata = $this->get_currency_data(floatval($p->price));
            if (is_array($cdata) && isset($cdata['formatted'])) {
                $price_display = $cdata['formatted'];
            }
        }
    ?>
    <div class="tv-plan-card <?php echo $is_featured ? 'featured' : ''; ?>">
        <?php if($is_featured): ?><div class="tv-badge">Popular</div><?php endif; ?>
        
        <div class="tv-plan-name"><?php echo esc_html($p->name); ?></div>
        <div class="tv-plan-price"><?php echo esc_html($price_display); ?> <span>/ <?php echo $p->duration_days; ?>d</span></div>
        
        <ul class="tv-features">
            <?php 
            $feats = explode("\n", $p->description);
            foreach($feats as $f): if(trim($f)): ?>
                <li>
                    <svg class="tv-check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo esc_html($f); ?>
                </li>
            <?php endif; endforeach; ?>
            
            <?php if($p->allow_multi_connections): ?>
                <li>
                    <svg class="tv-check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    <strong>Multi-Device Supported</strong>
                </li>
            <?php endif; ?>
        </ul>

        <form method="post" action="<?php echo esc_url(add_query_arg('tv_flow', 'select_method', home_url('/'))); ?>">
            <input type="hidden" name="plan_id" value="<?php echo $p->id; ?>">
            <button type="submit" class="tv-plan-btn">Choose Plan</button>
        </form>
    </div>
    <?php endforeach; else: ?>
        <p style="text-align:center; width:100%; color:#64748b;">No plans available.</p>
    <?php endif; ?>
</div>
