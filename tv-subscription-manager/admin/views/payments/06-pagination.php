<?php if (!defined('ABSPATH')) { exit; } ?>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="tv-toolbar" style="justify-content:center; border-top:1px solid var(--tv-border); border-bottom:none; border-radius:0 0 12px 12px;">
            <?php 
                $base_link = "?page=tv-subs-manager&tab=payments&s=".esc_attr($search_term)."&status=".esc_attr($filter_status);
                
                if($paged > 1) echo '<a href="'.$base_link.'&paged='.($paged-1).'" class="tv-btn tv-btn-sm tv-btn-secondary">&laquo; Prev</a>';
                
                for($i=max(1, $paged-2); $i<=min($total_pages, $paged+2); $i++) {
                    $cls = ($i == $paged) ? 'tv-btn-primary' : 'tv-btn-secondary';
                    echo '<a href="'.$base_link.'&paged='.$i.'" class="tv-btn tv-btn-sm '.$cls.'">'.$i.'</a>';
                }

                if($paged < $total_pages) echo '<a href="'.$base_link.'&paged='.($paged+1).'" class="tv-btn tv-btn-sm tv-btn-secondary">Next &raquo;</a>';
            ?>
        </div>
    <?php endif; ?>
