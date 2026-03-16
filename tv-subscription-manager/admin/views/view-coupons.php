<div class="tv-page-header">
    <div>
        <h1>Coupon Management</h1>
        <p>Create and manage discount codes for subscribers.</p>
    </div>
</div>

<div class="tv-grid-2">
    <!-- Create Coupon Form -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3>Create New Coupon</h3>
        </div>
        <div class="tv-card-body">
            <form method="post" action="?page=tv-subs-manager&tab=coupons">
                <?php wp_nonce_field('add_coupon_verify'); ?>
                
                <div class="tv-form-group">
                    <label class="tv-label">Coupon Code</label>
                    <input type="text" name="coupon_code" required class="tv-input" placeholder="e.g. SUMMER2024" style="text-transform:uppercase;">
                </div>
                
                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Discount Type</label>
                        <select name="coupon_type" class="tv-input">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount ($)</option>
                        </select>
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Amount</label>
                        <input type="number" step="0.01" name="coupon_amount" required class="tv-input" placeholder="10">
                    </div>
                </div>

                <div class="tv-row">
                    <div class="tv-col">
                        <label class="tv-label">Usage Limit</label>
                        <input type="number" name="coupon_limit" class="tv-input" placeholder="0 (Unlimited)">
                    </div>
                    <div class="tv-col">
                        <label class="tv-label">Expiry Date</label>
                        <input type="date" name="coupon_expiry" class="tv-input">
                    </div>
                </div>
                
                <button type="submit" name="submit_coupon" class="tv-btn tv-btn-primary w-full" style="height:38px;">
                    Create Coupon
                </button>
            </form>
        </div>
    </div>

    <!-- Coupons List -->
    <div class="tv-card">
        <div class="tv-card-header">
            <h3>Active Coupons</h3>
        </div>
        <div class="tv-table-container">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Usage</th>
                        <th>Expiry</th>
                        <th align="right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($coupons): foreach($coupons as $c): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--tv-primary); letter-spacing:0.5px;"><?php echo esc_html($c->code); ?></div>
                        </td>
                        <td>
                            <?php echo $c->type == 'percent' ? esc_html($c->amount) . '%' : '$' . esc_html($c->amount); ?>
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--tv-text-muted);">
                                <?php echo $c->usage_count; ?> / <?php echo ($c->usage_limit == 0) ? '8' : $c->usage_limit; ?>
                            </span>
                        </td>
                        <td style="font-size:13px;">
                            <?php 
                                if($c->expiry_date == '0000-00-00 00:00:00') echo 'Never';
                                else {
                                    $exp = strtotime($c->expiry_date);
                                    if(time() > $exp) echo '<span style="color:var(--tv-danger);">Expired</span>';
                                    else echo date('M d, Y', $exp);
                                }
                            ?>
                        </td>
                        <td align="right">
                            <!-- FIXED: Added data-tv-delete="1" to enforce JS interception -->
                            <a href="<?php echo wp_nonce_url('?page=tv-subs-manager&tab=coupons&action=delete_coupon&id='.$c->id, 'delete_coupon_'.$c->id); ?>" class="tv-btn tv-btn-danger tv-btn-sm" data-tv-delete="1">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--tv-text-muted);">No coupons found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="tv-toolbar" style="border-top:1px solid var(--tv-border); border-radius:0 0 8px 8px; justify-content:center;">
             <?php 
                $current_url = remove_query_arg('paged');
                for($i=1; $i<=$total_pages; $i++) {
                    $style = ($i == $paged) ? 'background:var(--tv-primary); color:white;' : 'background:white;';
                    echo '<a href="'.esc_url(add_query_arg('paged', $i, $current_url)).'" class="tv-btn tv-btn-secondary tv-btn-sm" style="margin:0 2px; '.$style.'">'.$i.'</a>';
                }
             ?>
        </div>
        <?php endif; ?>
    </div>
</div>