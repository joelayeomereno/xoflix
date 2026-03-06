<?php
/**
 * FILE PATH: tv-subscription-manager/admin/classes/class-tv-admin-sports.php
 *
 * Fixes applied:
 *  - Removed duplicate wp_ajax_tv_sbe_extract registration (TV_Channel_Engine owns that hook).
 *  - 'multi' added to allowed_ext so the Multiple Channel Input extractor can be saved.
 *  - All original logic preserved verbatim.
 */
if (!defined('ABSPATH')) { exit; }

class TV_Admin_Sports extends TV_Admin_Base {

    public function __construct($wpdb) {
        parent::__construct($wpdb);
        add_action('wp_ajax_tv_bulk_commit_sports', [$this, 'ajax_bulk_commit_sports']);
        // NOTE: wp_ajax_tv_sbe_extract is intentionally NOT registered here.
        // TV_Channel_Engine owns that AJAX hook exclusively so that all Channel Engine
        // settings (active countries, transform rules, exclusions, priority, dedupe) apply
        // and country names are correctly abbreviated via the $country_abbr lookup table.
    }

    /* --- AJAX: Bulk commit staged events --- */
    public function ajax_bulk_commit_sports() {
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        check_ajax_referer('tv_bulk_commit_nonce', 'nonce');

        $events = isset($_POST['events']) ? $_POST['events'] : [];
        if (empty($events) || !is_array($events)) wp_send_json_error('No events provided');

        $committed = 0;
        foreach ($events as $e) {
            $start_time = sanitize_text_field($e['date'] . ' ' . $e['time']);
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->table_sports} WHERE title = %s AND start_time = %s",
                sanitize_text_field($e['title']),
                $start_time
            ));
            if (!$exists) {
                $channels    = isset($e['channels']) ? (array)$e['channels'] : [];
                $json_data   = wp_json_encode($channels);
                $names       = [];
                foreach ($channels as $c) { if (!empty($c['name'])) $names[] = $c['name']; }
                $legacy_str  = implode(', ', $names);
                if (mb_strlen($legacy_str) > 95) {
                    $legacy_str = mb_substr($legacy_str, 0, 85) . '... (+' . count($names) . ')';
                }
                $this->wpdb->insert($this->table_sports, [
                    'title'         => sanitize_text_field($e['title']),
                    'league'        => sanitize_text_field($e['league']),
                    'start_time'    => $start_time,
                    'sport_type'    => sanitize_text_field($e['type']),
                    'channel'       => $legacy_str,
                    'channels_json' => $json_data,
                    'status'        => 'scheduled'
                ]);
                $committed++;
            }
        }
        $this->log_event('Bulk Import Sports', "Imported $committed events.");
        delete_transient('streamos_sports_v2');
        wp_send_json_success(['count' => $committed]);
    }

    /* --- ACTION HANDLER --- */
    public function handle_actions() {

        // -- SAVE EXTRACTOR SETTINGS (from Sports page modal) --
        if (isset($_POST['tv_save_extractor_settings'])) {
            check_admin_referer('tv_save_extractor_settings');
            if (!current_user_can('manage_options')) return;

            $submitted    = isset($_POST['tv_extractors']) ? (array)$_POST['tv_extractors'] : [];
            $allowed_ext  = ['manual', 'smart', 'bulk', 'multi']; // 'multi' = Multiple Channel Input
            $clean        = array_values(array_intersect($submitted, $allowed_ext));
            if (!in_array('manual', $clean)) $clean[] = 'manual'; // manual is always on

            update_option('tv_enabled_extractors', $clean);
            $this->show_notice('Extractor settings saved successfully.');
            wp_redirect(add_query_arg(['page' => 'tv-sports'], $this->admin_base_url()));
            exit;
        }

        // -- SAVE / UPDATE EVENT --
        if (isset($_POST['save_event'])) {
            check_admin_referer('save_event_verify');

            $raw_json       = isset($_POST['event_channels_json']) ? wp_unslash($_POST['event_channels_json']) : '';
            $channels_json  = null;
            $channel_legacy = sanitize_text_field($_POST['event_channel']);

            if (!empty($raw_json)) {
                $decoded = json_decode($raw_json, true);
                if (is_array($decoded)) {
                    $channels_json = wp_json_encode($decoded);
                    $names = [];
                    foreach ($decoded as $c) { if (!empty($c['name'])) $names[] = $c['name']; }
                    $generated_legacy = implode(', ', $names);
                    if (mb_strlen($generated_legacy) > 95) {
                        $generated_legacy = mb_substr($generated_legacy, 0, 85) . '... (+' . count($names) . ')';
                    }
                    if (empty($channel_legacy)) {
                        $channel_legacy = $generated_legacy;
                    }
                }
            }

            $data = [
                'title'         => sanitize_text_field($_POST['event_title']),
                'league'        => sanitize_text_field($_POST['event_league']),
                'start_time'    => sanitize_text_field($_POST['event_start_date'] . ' ' . $_POST['event_start_time']),
                'channel'       => $channel_legacy,
                'channels_json' => $channels_json,
                'sport_type'    => sanitize_text_field($_POST['event_type']),
                'status'        => isset($_POST['event_status']) ? sanitize_text_field($_POST['event_status']) : 'scheduled'
            ];

            if (isset($_POST['home_score']) && $_POST['home_score'] !== '') $data['home_score'] = intval($_POST['home_score']);
            if (isset($_POST['away_score']) && $_POST['away_score'] !== '') $data['away_score'] = intval($_POST['away_score']);

            if (!empty($_POST['event_id'])) {
                $id = intval($_POST['event_id']);
                $this->wpdb->update($this->table_sports, $data, ['id' => $id]);
                $this->log_event('Update Event', 'Updated event ID: ' . $id);
                delete_transient('streamos_sports_v2');
                wp_redirect(add_query_arg(['page' => 'tv-sports', 'msg' => 'updated'], $this->admin_base_url()));
                exit;
            } else {
                $this->wpdb->insert($this->table_sports, $data);
                $this->log_event('Create Event', 'Created: ' . $data['title']);
                delete_transient('streamos_sports_v2');
                $this->show_notice('Sports event added successfully.');
            }
        }

        // -- DELETE EVENT --
        if (isset($_GET['action']) && $_GET['action'] == 'delete_event' && isset($_GET['id'])) {
            check_admin_referer('delete_event_' . $_GET['id']);
            if (!$this->tv_require_delete_verification_or_notice()) return;
            $id = intval($_GET['id']);
            if ($this->recycle_bin_soft_delete('sport_event', $this->table_sports, (int)$id, 'id')) {
                $this->log_event('Delete Event', 'Soft-deleted event ID: ' . $id);
                delete_transient('streamos_sports_v2');
                $this->show_notice('Event deleted.');
            }
        }
    }

    /* --- RENDER --- */
    public function render() {
        $edit_event = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit_event' && isset($_GET['id'])) {
            $id         = intval($_GET['id']);
            $edit_event = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM $this->table_sports WHERE id = %d", $id
            ));
        }

        $events = $this->wpdb->get_results("SELECT * FROM $this->table_sports ORDER BY start_time ASC");

        // Pass enabled extractors to the view (includes 'multi' for Multiple Channel Input)
        $enabled_extractors = get_option('tv_enabled_extractors', ['manual', 'smart']);
        if (!is_array($enabled_extractors) || !in_array('manual', $enabled_extractors)) {
            $enabled_extractors[] = 'manual';
        }

        include TV_MANAGER_PATH . 'admin/views/view-sports.php';
    }
}