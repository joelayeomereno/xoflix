<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trait: TV_Admin_Users_Trait_Migration
 * Handling user import CSV upload and processing.
 */
trait TV_Admin_Users_Trait_Migration {

    public function handle_migration_actions() {
        if (isset($_POST['tv_upload_migration_csv'])) {
            check_admin_referer('tv_migration_verify');
            
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $this->show_notice('Error uploading file.', 'error');
                return;
            }

            $file = $_FILES['csv_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($ext !== 'csv') {
                $this->show_notice('Invalid file type. Please upload a CSV.', 'error');
                return;
            }

            // Ensure Service is loaded
            if (!class_exists('TV_Migration_Service')) {
                $service_file = TV_MANAGER_PATH . 'includes/services/class-tv-migration-service.php';
                if (file_exists($service_file)) {
                    require_once $service_file;
                } else {
                    $this->show_notice('Migration Service file missing.', 'error');
                    return;
                }
            }
            
            $service = new TV_Migration_Service();
            $result = $service->process_csv_import($file['tmp_name']);

            if (isset($result['error'])) {
                $this->show_notice($result['error'], 'error');
            } else {
                $msg = sprintf(
                    'Migration Complete. Created: <strong>%d</strong>. Updated: <strong>%d</strong>. Skipped: <strong>%d</strong>.',
                    $result['imported'], $result['updated'], $result['skipped']
                );
                
                if (!empty($result['errors'])) {
                    $msg .= '<br>Errors: ' . implode(', ', array_slice($result['errors'], 0, 5)) . (count($result['errors']) > 5 ? '...' : '');
                }
                
                $this->show_notice($msg);
                
                if (class_exists('TV_Domain_Audit_Service')) {
                    // Use $this->wpdb inherited from Base
                    $audit = new TV_Domain_Audit_Service($this->wpdb);
                    $audit->log_event(get_current_user_id(), 'User Migration', "Imported {$result['imported']} users from CSV.");
                }
            }
        }
    }

    public function render_migration_view() {
        ?>
        <div class="tv-card">
            <div class="tv-card-header">
                <h3><span class="dashicons dashicons-upload" style="margin-right:8px;"></span> User Migration / Import</h3>
            </div>
            <div class="tv-card-body">
                <div class="tv-grid-2">
                    <div>
                        <p style="margin-top:0; font-size:14px; color:var(--tv-text-muted); line-height:1.6;">
                            Upload a CSV file to bulk import users from a legacy system. 
                            Users imported this way will be marked as <strong>"Migrated/Unclaimed"</strong>.
                        </p>
                        
                        <div style="background:var(--tv-success-bg); border:1px solid rgba(16, 185, 129, 0.2); padding:16px; border-radius:8px; margin:20px 0;">
                            <h4 style="margin:0 0 8px; font-size:13px; color:var(--tv-success); text-transform:uppercase; font-weight:800;">How Claiming Works</h4>
                            <p style="font-size:13px; margin:0; color:var(--tv-text);">
                                When a user attempts to log in with an email found in this list, they will be allowed to 
                                <strong>enter any password</strong>. The system will accept it, set it as their permanent password, 
                                and mark the account as "Claimed".
                            </p>
                        </div>

                        <div style="background:#f8fafc; border:1px solid var(--tv-border); padding:16px; border-radius:8px;">
                            <h4 style="margin:0 0 10px; font-size:12px; font-weight:700; text-transform:uppercase; color:var(--tv-text-muted);">Required CSV Format</h4>
                            <code style="display:block; background:white; padding:10px; border:1px solid #e2e8f0; border-radius:6px; font-size:12px; color:#0f172a;">
                                email, first_name, last_name, phone, country, city
                            </code>
                            <ul style="font-size:12px; color:var(--tv-text-muted); margin:10px 0 0 16px; list-style:disc;">
                                <li><strong>email</strong> is mandatory.</li>
                                <li><strong>country</strong> should be a 2-letter ISO code (e.g. US, NG, RW).</li>
                                <li>Existing emails will have their phone/country updated only.</li>
                            </ul>
                        </div>
                    </div>

                    <div style="background:var(--tv-surface-active); padding:24px; border-radius:12px; border:1px solid var(--tv-border); display:flex; flex-direction:column; justify-content:center;">
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('tv_migration_verify'); ?>
                            
                            <div class="tv-form-group" style="text-align:center;">
                                <label for="csv_file" class="tv-btn tv-btn-secondary" style="width:100%; height:120px; display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed var(--tv-border); cursor:pointer;">
                                    <span class="dashicons dashicons-media-spreadsheet" style="font-size:32px; width:32px; height:32px; color:var(--tv-primary); margin-bottom:10px;"></span>
                                    <span style="font-weight:600;">Select CSV File</span>
                                    <span style="font-size:11px; font-weight:400; color:var(--tv-text-muted); margin-top:4px;">Max size: 8MB</span>
                                </label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required style="display:none;" onchange="document.querySelector('label[for=csv_file] span:nth-child(2)').textContent = this.files[0].name">
                            </div>

                            <button type="submit" name="tv_upload_migration_csv" class="tv-btn tv-btn-primary w-full" style="padding:12px;">
                                <span class="dashicons dashicons-upload" style="margin-right:6px;"></span> Start Import
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}