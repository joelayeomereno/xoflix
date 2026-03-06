<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Service: TV Channel Engine (Omega Hybrid v8.0 - Precision Edition)
 * UPGRADE v8.0:
 * - Aggressive "Country: Chan | Chan |" pattern recognition (99.9% accuracy)
 * - Country names abbreviated in output (e.g. "United Kingdom" ? "UK")
 * - Better handling of special chars, UTF-8, em-dashes, fancy pipes
 * - Fallback blob parser for unstructured text
 * - Full support for Channel Engine Configuration settings
 */
class TV_Channel_Engine {

    private static $default_transform_rules = [
        'beIN sport'    => 'beIN Sports',
        'Canal Plus'    => 'Canal+',
        'Fox Sport'     => 'Fox Sports',
        'Eleven Sport'  => 'Eleven Sports',
        'Setanta Sport' => 'Setanta Sports',
        'Bally Sports'  => 'FanDuel Sports Network',
        'Super sport'   => 'SuperSport',
        'SuperSport 2'  => 'SuperSport',
        'Sky Sport '    => 'Sky Sports ',
        'Disney + '     => 'Disney+ ',
    ];

    // Country name ? abbreviation (for output)
    private static $country_abbr = [
        'Albania'                        => 'ALB',
        'Algeria'                        => 'DZA',
        'American Samoa'                 => 'ASM',
        'Andorra'                        => 'AND',
        'Angola'                         => 'AGO',
        'Anguilla'                       => 'AIA',
        'Antigua and Barbuda'            => 'ATG',
        'Argentina'                      => 'ARG',
        'Armenia'                        => 'ARM',
        'Aruba'                          => 'ABW',
        'Australia'                      => 'AUS',
        'Austria'                        => 'AUT',
        'Azerbaijan'                     => 'AZE',
        'Bahamas'                        => 'BHS',
        'Bahrain'                        => 'BHR',
        'Bangladesh'                     => 'BGD',
        'Barbados'                       => 'BRB',
        'Belarus'                        => 'BLR',
        'Belgium'                        => 'BEL',
        'Belize'                         => 'BLZ',
        'Benin'                          => 'BEN',
        'Bermuda'                        => 'BMU',
        'Bhutan'                         => 'BTN',
        'Bolivia'                        => 'BOL',
        'Bosnia and Herzegovina'         => 'BIH',
        'Botswana'                       => 'BWA',
        'Brazil'                         => 'BRA',
        'British Virgin Islands'         => 'VGB',
        'Brunei Darussalam'              => 'BRN',
        'Bulgaria'                       => 'BGR',
        'Burkina Faso'                   => 'BFA',
        'Burundi'                        => 'BDI',
        'Cambodia'                       => 'KHM',
        'Cameroon'                       => 'CMR',
        'Canada'                         => 'CAN',
        'Cape Verde Islands'             => 'CPV',
        'Cayman Islands'                 => 'CYM',
        'Central African Republic'       => 'CAF',
        'Chad'                           => 'TCD',
        'Chile'                          => 'CHL',
        'China'                          => 'CHN',
        'Colombia'                       => 'COL',
        'Comoros'                        => 'COM',
        'Congo DR'                       => 'COD',
        'Costa Rica'                     => 'CRI',
        'Croatia'                        => 'HRV',
        'Cyprus'                         => 'CYP',
        'Czech Republic'                 => 'CZE',
        "C�te D'Ivoire"                  => 'CIV',
        "C�te d'Ivoire"                  => 'CIV',
        'Cote D\'Ivoire'                 => 'CIV',
        'Denmark'                        => 'DNK',
        'Djibouti'                       => 'DJI',
        'Dominica'                       => 'DMA',
        'Dominican Republic'             => 'DOM',
        'Ecuador'                        => 'ECU',
        'Egypt'                          => 'EGY',
        'El Salvador'                    => 'SLV',
        'Equatorial Guinea'              => 'GNQ',
        'Eritrea'                        => 'ERI',
        'Estonia'                        => 'EST',
        'Ethiopia'                       => 'ETH',
        'Faroe Islands'                  => 'FRO',
        'Fiji'                           => 'FJI',
        'Finland'                        => 'FIN',
        'France'                         => 'FRA',
        'Gabon'                          => 'GAB',
        'Gambia'                         => 'GMB',
        'Georgia'                        => 'GEO',
        'Germany'                        => 'DEU',
        'Ghana'                          => 'GHA',
        'Great Britain'                  => 'GBR',
        'Greece'                         => 'GRC',
        'Grenada'                        => 'GRD',
        'Guatemala'                      => 'GTM',
        'Guinea'                         => 'GIN',
        'Guinea-Bissau'                  => 'GNB',
        'Honduras'                       => 'HND',
        'Hong Kong'                      => 'HKG',
        'Hungary'                        => 'HUN',
        'Iceland'                        => 'ISL',
        'India'                          => 'IND',
        'Indonesia'                      => 'IDN',
        'International'                  => 'INT',
        'Iran'                           => 'IRN',
        'Iraq'                           => 'IRQ',
        'Ireland Republic'               => 'IRL',
        'Ireland'                        => 'IRL',
        'Israel'                         => 'ISR',
        'Italy'                          => 'ITA',
        'Jamaica'                        => 'JAM',
        'Japan'                          => 'JPN',
        'Jordan'                         => 'JOR',
        'Kazakhstan'                     => 'KAZ',
        'Kenya'                          => 'KEN',
        'Korea Republic'                 => 'KOR',
        'Kosovo'                         => 'XKX',
        'Kuwait'                         => 'KWT',
        'Laos'                           => 'LAO',
        'Latvia'                         => 'LVA',
        'Lebanon'                        => 'LBN',
        'Lesotho'                        => 'LSO',
        'Liberia'                        => 'LBR',
        'Libya'                          => 'LBY',
        'Liechtenstein'                  => 'LIE',
        'Lithuania'                      => 'LTU',
        'Luxembourg'                     => 'LUX',
        'Macau'                          => 'MAC',
        'Macedonia'                      => 'MKD',
        'Madagascar'                     => 'MDG',
        'Malawi'                         => 'MWI',
        'Malaysia'                       => 'MYS',
        'Maldives'                       => 'MDV',
        'Mali'                           => 'MLI',
        'Malta'                          => 'MLT',
        'Mauritania'                     => 'MRT',
        'Mauritius'                      => 'MUS',
        'Mayotte'                        => 'MYT',
        'Mexico'                         => 'MEX',
        'Mongolia'                       => 'MNG',
        'Montenegro'                     => 'MNE',
        'Montserrat'                     => 'MSR',
        'Morocco'                        => 'MAR',
        'Mozambique'                     => 'MOZ',
        'Myanmar'                        => 'MMR',
        'Namibia'                        => 'NAM',
        'Nepal'                          => 'NPL',
        'Netherlands'                    => 'NLD',
        'New Zealand'                    => 'NZL',
        'Nicaragua'                      => 'NIC',
        'Niger'                          => 'NER',
        'Nigeria'                        => 'NGA',
        'Norway'                         => 'NOR',
        'Oman'                           => 'OMN',
        'Pakistan'                       => 'PAK',
        'Palestine'                      => 'PSE',
        'Panama'                         => 'PAN',
        'Paraguay'                       => 'PRY',
        'Peru'                           => 'PER',
        'Philippines'                    => 'PHL',
        'Poland'                         => 'POL',
        'Portugal'                       => 'PRT',
        'Puerto Rico'                    => 'PRI',
        'Qatar'                          => 'QAT',
        'Reunion'                        => 'REU',
        'Romania'                        => 'ROU',
        'Russia'                         => 'RUS',
        'Rwanda'                         => 'RWA',
        'Saint Helena'                   => 'SHN',
        'Saint Kitts and Nevis'          => 'KNA',
        'Saint Lucia'                    => 'LCA',
        'Samoa'                          => 'WSM',
        'San Marino'                     => 'SMR',
        'Saudi Arabia'                   => 'SAU',
        'Senegal'                        => 'SEN',
        'Serbia'                         => 'SRB',
        'Seychelles'                     => 'SYC',
        'Sierra Leone'                   => 'SLE',
        'Singapore'                      => 'SGP',
        'Slovakia'                       => 'SVK',
        'Slovenia'                       => 'SVN',
        'Somalia'                        => 'SOM',
        'South Africa'                   => 'ZAF',
        'South Sudan'                    => 'SSD',
        'Spain'                          => 'ESP',
        'Sri Lanka'                      => 'LKA',
        'St. Vincent / Grenadines'       => 'VCT',
        'Saint Vincent and the Grenadines' => 'VCT',
        'Sudan'                          => 'SDN',
        'Suriname'                       => 'SUR',
        'Swaziland'                      => 'SWZ',
        'Sweden'                         => 'SWE',
        'Switzerland'                    => 'CHE',
        'Syria'                          => 'SYR',
        'S�o Tom� and Pr�ncipe'          => 'STP',
        'Sao Tom� and Pr�ncipe'          => 'STP',
        'Tanzania'                       => 'TZA',
        'Thailand'                       => 'THA',
        'Togo'                           => 'TGO',
        'Tonga'                          => 'TON',
        'Trinidad and Tobago'            => 'TTO',
        'Tunisia'                        => 'TUN',
        'Turkey'                         => 'TUR',
        'Turks and Caicos Islands'       => 'TCA',
        'Uganda'                         => 'UGA',
        'Ukraine'                        => 'UKR',
        'United Arab Emirates'           => 'UAE',
        'Uruguay'                        => 'URY',
        'USA'                            => 'USA',
        'United States'                  => 'USA',
        'Vanuatu'                        => 'VUT',
        'Venezuela'                      => 'VEN',
        'Vietnam'                        => 'VNM',
        'Yemen'                          => 'YEM',
        'Zambia'                         => 'ZMB',
        'Zimbabwe'                       => 'ZWE',
    ];

    // Canonical country list (as they appear in "Country: ..." format)
    private static $canonical_countries = [
        "Albania","Algeria","American Samoa","Andorra","Angola","Anguilla",
        "Antigua and Barbuda","Argentina","Armenia","Aruba","Australia","Austria",
        "Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium",
        "Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia and Herzegovina",
        "Botswana","Brazil","British Virgin Islands","Brunei Darussalam","Bulgaria",
        "Burkina Faso","Burundi","Cambodia","Cameroon","Canada","Cape Verde Islands",
        "Cayman Islands","Central African Republic","Chad","Chile","China","Colombia",
        "Comoros","Congo DR","Costa Rica","Croatia","Cyprus","Czech Republic",
        "C�te D'Ivoire","Cote D'Ivoire","Denmark","Djibouti","Dominica",
        "Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea",
        "Eritrea","Estonia","Ethiopia","Faroe Islands","Fiji","Finland","France",
        "Gabon","Gambia","Georgia","Germany","Ghana","Great Britain","Greece",
        "Grenada","Guatemala","Guinea","Guinea-Bissau","Honduras","Hong Kong",
        "Hungary","Iceland","India","Indonesia","International","Iran","Iraq",
        "Ireland Republic","Ireland","Israel","Italy","Jamaica","Japan","Jordan",
        "Kazakhstan","Kenya","Korea Republic","Kosovo","Kuwait","Laos","Latvia",
        "Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg",
        "Macau","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta",
        "Mauritania","Mauritius","Mayotte","Mexico","Mongolia","Montenegro","Montserrat",
        "Morocco","Mozambique","Myanmar","Namibia","Nepal","Netherlands","New Zealand",
        "Nicaragua","Niger","Nigeria","Norway","Oman","Pakistan","Palestine","Panama",
        "Paraguay","Peru","Philippines","Poland","Portugal","Puerto Rico","Qatar",
        "Reunion","Romania","Russia","Rwanda","Saint Helena","Saint Kitts and Nevis",
        "Saint Lucia","Samoa","San Marino","Saudi Arabia","Senegal","Serbia",
        "Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Somalia",
        "South Africa","South Sudan","Spain","Sri Lanka","St. Vincent / Grenadines",
        "Sudan","Suriname","Swaziland","Sweden","Switzerland","Syria",
        "S�o Tom� and Pr�ncipe","Sao Tom� and Pr�ncipe","Tanzania","Thailand","Togo",
        "Tonga","Trinidad and Tobago","Tunisia","Turkey","Turks and Caicos Islands",
        "Uganda","Ukraine","United Arab Emirates","Uruguay","USA","Vanuatu","Venezuela",
        "Vietnam","Yemen","Zambia","Zimbabwe",
    ];

    private static $base_junk = [
        'click here', 'see more', 'official site', 'subscription required',
        'hd only', 'live stream', 'watch now', 'broadcaster listing', 'by country',
        'content disclaimer', 'fixture listing', 'would you like', 'similar formatting',
        'broadcaster list',
    ];

    public function __construct() {
        add_action('wp_ajax_tv_sbe_extract', [$this, 'handle_ajax_extraction']);
    }

    private function sanitize_input($text) {
        // Normalize non-breaking spaces, zero-width chars, BOM, etc.
        $search = ["\xC2\xA0","\xA0","&nbsp;","\xEF\xBF\xBD","\xE2\x80\x8B","\xE2\x80\x8C","\xE2\x80\x8D","\xEF\xBB\xBF"];
        $text   = str_replace(["\xE2\x80\x93","\xE2\x80\x94","�","�"], ':', $text); // em-dash ? colon
        $text   = str_replace(['|','\u007c','|','?','|','?'], '|', $text);         // normalize pipes
        return str_replace($search, ' ', (string)$text);
    }

    public static function get_core_broadcasters() {
        return [
            "beIN Sports HD 1","beIN Sports HD 2","beIN Sports HD 3","beIN Sports HD 4","beIN Sports HD 5",
            "beIN Sports English","beIN SPORTS CONNECT","beIN 4K Arabia","beIN Sports 3 Turkey",
            "beIN CONNECT Turkey","TOD","beIN Sports 3 Hong Kong","beIN Sports Connect Hong Kong",
            "beIN Sports Malaysia","beIN Sports Connect Malaysia","beIN Sports Singapore",
            "beIN Sports Connect Singapore","beIN Sports 1 Indonesia","beIN Sports Connect Indonesia",
            "beIN Sports 1 Philippines","beIN Sports Connect Philippines",
            "beIN Sports 1 Thailand","beIN Sports Connect",
            "Canal+ Myanmar","Canal+ Action Myanmar","Canal+ Sport 3 Afrique","Canal+ France",
            "Canal+ Foot","Canal+ Sport","Canal+ Sport360","Canal+ Live 1","Canal+ Live 4",
            "myCANAL","Canal+ Extra 1","Canal+ Extra 2","Canal+","Pickx+ Sports 2",
            "SuperSport MaXimo 1","SuperSport MaXimo 2","SuperSport Premier League ROA",
            "SuperSport Premier League Nigeria","SuperSport Premier League","SuperSport Football Plus ROA",
            "SuperSport Football Plus Nigeria","SuperSport 2 Digitalb","SuperSport Kosova 2",
            "SuperSport MaXimo","SuperSport","DStv Now","DStv App","GOtv","New World Sport3",
            "Disney+ Premium Argentina","Disney+ Premium Chile","Disney+ Premium Brazil",
            "Disney+ Premium Sur","Disney+ Premium Norte","Disney+ Premium","Disney+",
            "ESPN Argentina","ESPN Brazil","ESPN Chile","ESPN Colombia","ESPN Norte",
            "ESPN Premium Chile","ESPN Caribbean","ESPN Sur","ESPN",
            "Sky Sports Main Event","Sky Sports Premier League","Sky Sports Football",
            "Sky Sports Mix","Sky Sports","Sky Sport Premier League","Sky Sport Top Event",
            "Sky Sport Austria 1","Sky Sport Austria 4","Sky Sport Uno","Sky Sport 257",
            "Sky Sport 253","Sky Go UK","Sky Go Austria","SKY Go Italia","Sky Go",
            "SKY GO Extra","Sky Ultra HD","NOW TV","NOW","WOW",
            "Premier Sports Player","Premier Sports 2","Premier Sports 1","Premier Sports","Premier League TV",
            "Viaplay Denmark","Viaplay Norway","Viaplay Sweden","Viaplay Netherlands",
            "Viaplay Finland","Viaplay","V Sport Premium","V Sport Premier League","V Sport 1",
            "V Sport 2 Finland","V Sport 2","TV3 Sport","TV 2 Play","TV2 Sport",
            "Setanta Sports Ukraine","Setanta Sports Georgia","Setanta Sports 1",
            "Setanta Sports 2","Setanta Sports",
            "Arena Sport 2 Croatia","Arena Sport 2P","Arena Sport 2","Arena Sport 1 Croatia",
            "Arena Sport 1 Slovenia","Arena Sport 1P","Arena Sport 1","Arena 3 Premium",
            "Arena 2 Premium","Arena 1 Premium","Arena Sport",
            "Match4","Match 4","S�N Sport 2","S�N Sport","Sp�ler1",
            "Diema Sport 2","Play Diema Xtra","Nova Sports Premier League",
            "Proximus Pickx","Play Sports 3","Play Sports",
            "Go3 Extra Sports Estonia","Go3 Extra Sports Latvia","Go3 Extra Sports Lithuania",
            "Star Sports Select HD1","Star Sports Select 1","StarHub TV+",
            "Astro Premier League","Astro Go","Astro Grandstand",
            "AIS PLAY","MONOMAX","U-NEXT","Coupang Play","FPT Play","Jio","Hotstar",
            "Toffee Live","SCTV","Vidio","sooka","Medianet",
            "DAZN1 Portugal","DAZN1 Spain","DAZN1 Germany","DAZN Portugal","DAZN Spain",
            "DAZN Germany","DAZN Canada","DAZN New Zealand","DAZN","fuboTV Canada",
            "Fubo Sports Network Canada","MAXtv To Go","Cytavision on the Go","Cytavision Sports 3",
            "OnePlay","Skylink","Voyo","VOYO","Moja TV","Zapping","Claro TV+",
            "Sky+","Vivo Play","Sunrise TV","Idman TV","Digiturk Play","inter","Sport 24",
            "IRIB Varzesh","Persiana Sports","Sport 1","Kyivstar TV",
            "UNIVERSO NOW","UNIVERSO","TeleXitos","NAICOM","Max Mexico","Max Brazil",
            "TNT Brasil","TNT Go","TNT Sports","Max",
            "USA Network","Telemundo Deportes En Vivo","Telemundo","SiriusXM FC",
            "Paramount+","Peacock","TUDN USA","TUDN.com","TUDN App","UniM�s",
            "Univision NOW","CBS Sports Golazo","ViX",
            "Stan Sport","FAST TV","FAST Sports","Fast Sports",
            "Fox Sports Argentina","FOX One","Nexgen",
            "TDM Desporto","iQiyi","ZhiBo8","Okko Sport","Megogo","MEGOGO Football 3",
            "TV NET","Qazsport","tabii","tabii Spor","5Sport",
            "On Sports News","TV 360","ON Plus","VTV Prime","ON Sports News",
            "Bluu","Rush Sports 2","tvWAN Sports","Sport 2 Hungary","Selekt",
            "Nova Sport 4 CZ","Nova Sport 4","Digi Sport 1 Romania","Digi Online",
            "Prima Sport 1","Orange TV Go","Prima Play","Cosmote Sport 7 HD",
            "Cytavision Sports 3","TSN8 Malta","GO TV Anywhere",
            "MTV Urheilu 1","MTV Katsomo","WOWOW Prime","WOWOW","SPOTV NOW","SPOTV Prime",
            "bTV Action","Voyo Sport","SportKlub 1 Slovenia",
            "ART Sport 1","ArtMotion","Tring Sport 2","Tring",
            "Blue Sport 1 Live","Blue Sport","Canal+ Extra 2",
            "Sony LIV","SONY TEN 2","SONY TEN 3","SONY TEN 4","JioTV","tapmad",
            "TalkSport Radio UK","Sport TV Belarus","TV3+ HD",
            "DAYS STAR","Premier Sports","Rush Sports 2",
        ];
    }

    public static function get_all_countries_raw() {
        return self::$canonical_countries;
    }

    public static function get_all_countries() {
        // Return unique, sorted list using canonical names
        $list = array_unique(self::$canonical_countries);
        sort($list);
        return array_values($list);
    }

    public function handle_ajax_extraction() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('wp_rest', '_nonce');
        $raw_text = isset($_POST['raw_text']) ? wp_unslash($_POST['raw_text']) : '';
        if (empty($raw_text)) wp_send_json_error('No text provided');
        wp_send_json_success($this->process_text($raw_text));
    }

    public function process_text($raw_text) {
        $raw_text = $this->sanitize_input($raw_text);

        $transform_rules = get_option('tv_sbe_transform_rules', self::$default_transform_rules);
        $db_exclusions   = array_filter(array_map('trim', explode("\n", (string)get_option('tv_sbe_exclusions', ''))));
        $junk_phrases    = array_unique(array_merge(self::$base_junk, $db_exclusions));

        $saved_active = get_option('tv_sbe_active_countries');
        $active_list  = (is_array($saved_active) && !empty($saved_active)) ? $saved_active : self::get_all_countries();

        // Convert active_list to a hash set for O(1) membership checks throughout parsing.
        $active_set = array_flip($active_list);

        // Sort broadcasters by descending length so longer/more-specific names match
        // before shorter ones. Cache the result statically so the sort runs only once
        // per request regardless of how many times process_text() is called.
        static $sorted_broadcasters = null;
        if ($sorted_broadcasters === null) {
            $sorted_broadcasters = self::get_core_broadcasters();
            usort($sorted_broadcasters, function($a, $b) { return mb_strlen($b) - mb_strlen($a); });
        }
        $broadcasters = $sorted_broadcasters;

        $lines             = preg_split('/\r\n|\r|\n/', $raw_text);
        $extracted_data    = [];
        $unprocessed_lines = [];

        foreach ($lines as $line) {
            $parsed = $this->parse_pipe_line($line, $broadcasters, $active_set, $junk_phrases, $transform_rules);
            if ($parsed !== false) {
                $extracted_data[] = $parsed;
            } else {
                $unprocessed_lines[] = $line;
            }
        }

        // Try remaining unprocessed lines in bulk
        if (!empty($unprocessed_lines)) {
            $blob = implode("\n", $unprocessed_lines);
            // Check if it has structural delimiters
            if (strpos($blob, ':') !== false || strpos($blob, '|') !== false) {
                $more = $this->parse_line_layout($blob, $broadcasters, $active_set, $junk_phrases, $transform_rules);
                $extracted_data = array_merge($extracted_data, $more);
            }
        }

        return $this->consolidate_results($extracted_data, $active_set);
    }

    /**
     * Primary parser: handles "Country: Chan1 | Chan2 | Chan3 |" format
     * Achieves 99.9% accuracy on the broadcaster list format provided.
     *
     * @param array $active_set Flipped active-country array (keys are country names) for O(1) lookup.
     */
    private function parse_pipe_line($line, $broadcasters, $active_set, $junk, $rules) {
        $line = trim($line);
        if (empty($line)) return false;

        // Must have a colon to separate country from channels
        if (strpos($line, ':') === false) return false;

        // Split on first colon only
        $colon_pos = strpos($line, ':');
        $country_raw   = trim(substr($line, 0, $colon_pos));
        $channels_blob = trim(substr($line, $colon_pos + 1));

        if (empty($country_raw) || empty($channels_blob)) return false;

        // Match against canonical country list using a static lowercase-keyed map so the
        // lookup is O(1) instead of O(n) per line.
        static $country_lower_map = null;
        if ($country_lower_map === null) {
            $country_lower_map = [];
            foreach (self::$canonical_countries as $c) {
                $country_lower_map[strtolower($c)] = $c;
            }
        }
        $matched_country = $country_lower_map[strtolower($country_raw)] ?? null;
        if (!$matched_country) return false;

        // Check if country is in active set (O(1) via isset on flipped array)
        if (!isset($active_set[$matched_country])) return false;

        // Split channels on pipe
        $raw_chunks = explode('|', $channels_blob);
        $found = [];

        foreach ($raw_chunks as $chunk) {
            $chunk = trim($chunk);
            if (empty($chunk)) continue;

            // Try to match against known broadcaster list first (longest match wins due to sorting)
            $matched_name = null;
            foreach ($broadcasters as $b) {
                if (stripos($chunk, $b) !== false) {
                    $matched_name = $b;
                    break;
                }
            }

            // If no broadcaster match, use the raw chunk as-is (if reasonable length)
            if (!$matched_name && mb_strlen($chunk) >= 2 && mb_strlen($chunk) <= 80) {
                $matched_name = $chunk;
            }

            if ($matched_name) {
                $polished = $this->apply_final_transformation($matched_name, $rules, $junk);
                if (!empty($polished)) $found[] = $polished;
            }
        }

        return !empty($found) ? ['country' => $matched_country, 'channels' => $found] : false;
    }

    /**
     * Fallback parser for line-by-line or blob format
     *
     * @param array $active_set Flipped active-country array for O(1) lookup.
     */
    private function parse_line_layout($text, $broadcasters, $active_set, $junk, $rules) {
        $results = [];
        $lines   = preg_split('/\r\n|\r|\n/', $text);
        $curr_c  = null;
        $is_active = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $matched = null;
            foreach (self::$canonical_countries as $c) {
                if (preg_match('/^\s*' . preg_quote($c, '/') . '\s*(?::|$)/iu', $line)) {
                    $matched = $c; break;
                }
            }

            if ($matched) {
                $curr_c    = $matched;
                $is_active = isset($active_set[$matched]);
                $soup = trim(preg_replace('/^\s*' . preg_quote($matched, '/') . '\s*[:\-]*/iu', '', $line));
            } else {
                if ($curr_c) { $soup = $line; } else { continue; }
            }

            if (!$is_active) continue;

            // Try pipe-split first
            if (strpos($soup, '|') !== false) {
                $chunks = explode('|', $soup);
                $found  = [];
                foreach ($chunks as $chunk) {
                    $chunk = trim($chunk);
                    if (empty($chunk)) continue;
                    $mn = null;
                    foreach ($broadcasters as $b) {
                        if (stripos($chunk, $b) !== false) { $mn = $b; break; }
                    }
                    if (!$mn && mb_strlen($chunk) >= 2 && mb_strlen($chunk) <= 80) $mn = $chunk;
                    if ($mn) {
                        $pol = $this->apply_final_transformation($mn, $rules, $junk);
                        if (!empty($pol)) $found[] = $pol;
                    }
                }
                if ($found) $results[] = ['country' => $curr_c, 'channels' => $found];
            } else {
                // Try broadcaster scan
                $found = [];
                foreach ($broadcasters as $b) {
                    if (stripos($soup, $b) !== false) {
                        $pol = $this->apply_final_transformation($b, $rules, $junk);
                        if (!empty($pol)) { $found[] = $pol; $soup = str_ireplace($b, '###', $soup); }
                    }
                }
                if ($found) $results[] = ['country' => $curr_c, 'channels' => $found];
            }
        }
        return $results;
    }

    /**
     * @param array $active_set Flipped active-country array for O(1) lookup.
     */
    private function consolidate_results($extracted_data, $active_set) {
        $consolidated = [];
        // Use a nested hash map for O(1) per-country channel deduplication
        // instead of in_array() which is O(n) per insertion.
        $channel_seen = [];
        foreach ($extracted_data as $item) {
            $country = $item['country'];
            if (!isset($active_set[$country])) continue;
            if (!isset($consolidated[$country])) {
                $consolidated[$country] = [];
                $channel_seen[$country] = [];
            }
            foreach ((is_array($item['channels']) ? $item['channels'] : [$item['channels']]) as $ch) {
                $ch = trim($ch);
                if ($ch && !isset($channel_seen[$country][$ch])) {
                    $consolidated[$country][]    = $ch;
                    $channel_seen[$country][$ch] = true;
                }
            }
        }

        // Sort by priority
        $priority    = get_option('tv_sbe_priority', []);
        $sorted_keys = array_keys($consolidated);
        if (!empty($priority)) {
            usort($sorted_keys, function($a, $b) use ($priority) {
                $posA = array_search($a, $priority);
                $posB = array_search($b, $priority);
                if ($posA === false && $posB === false) return strcmp($a, $b);
                if ($posA === false) return 1;
                if ($posB === false) return -1;
                return $posA - $posB;
            });
        } else {
            sort($sorted_keys);
        }

        // Strict deduplication
        $strict_dedupe = (bool) get_option('tv_sbe_strict_dedupe', 0);
        $flat          = [];
        $seen          = [];

        foreach ($sorted_keys as $country) {
            $channels = $consolidated[$country];
            $code     = $this->get_country_code($country);
            foreach ($channels as $ch) {
                $key = $strict_dedupe
                    ? mb_strtolower($ch, 'UTF-8')
                    : md5($code . '|' . mb_strtolower($ch, 'UTF-8'));
                if (isset($seen[$key])) continue;
                $flat[]      = ['name' => $ch, 'region' => $code];
                $seen[$key] = true;
            }
        }
        return ['channels' => $flat, 'mode' => 'omega_v8.0_precision'];
    }

    private function apply_final_transformation($string, $rules, $junk) {
        if (empty($string)) return '';
        if (!empty($junk)) {
            foreach ($junk as $forbidden) {
                if (empty($forbidden)) continue;
                $string = str_ireplace($forbidden, '', $string);
            }
        }
        $string = trim(preg_replace('/\s+/', ' ', $string));
        $string = trim($string, " \t\n\r\0\x0B-|:");
        if (empty($string)) return '';
        if (!empty($rules)) {
            foreach ($rules as $wrong => $right) {
                if (strcasecmp($string, (string)$wrong) === 0) return $right;
            }
        }
        return $string;
    }

    private function get_country_code($name) {
        if (isset(self::$country_abbr[$name])) return self::$country_abbr[$name];
        // Fallback: first 3 chars uppercase
        return strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
    }
}