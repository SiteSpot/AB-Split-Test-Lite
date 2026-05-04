<?php
if ( ! defined( 'ABSPATH' ) ) exit;

//agency hum

// add a secret hash into the settings page that ppl can use on the agency management patge to remotely vioerw the data

// use format of website home url + secret hash then base64 it into a string so it can easily be shared

//then create an agency page that ppl can use to view the data

// add site modal where you  paste the site key in froim above, exceopt on the child site

//save the site key to wp_options 'abst_site_keys' ['site_url' => 'url','hash' => 'hash',]

if ( ! class_exists( 'BT_BB_AB_Agency_Hub' ) ) {

	class BT_BB_AB_Agency_Hub {

		const OPTION_SECRET    = 'abst_agency_secret';
		const OPTION_ENABLED   = 'abst_remote_access_enabled';
		const OPTION_SITE_KEYS = 'abst_site_keys';

		public function __construct() {
			// Child-site role: manages secret + shareable key.
			add_action( 'admin_init', [ $this, 'maybe_generate_secret' ] );
            add_action( 'wp_ajax_abst_regenerate_agency_key', [ $this, 'ajax_regenerate_key' ] );
            add_action( 'wp_ajax_abst_sync_site', [ $this, 'ajax_sync_site' ] );
			// REST endpoint for Agency Hub summaries (dummy data for now).
			add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
		}

		/**
		 * Check if agency sharing is enabled on this site.
		 */
		public static function is_enabled() {
			$enabled = get_option( self::OPTION_ENABLED, 0 );

			return (bool) $enabled;
		}

		/**
		 * Ensure the site has a stable secret for agency access.
		 *
		 * This does not toggle sharing on/off; it just guarantees a secret
		 * exists so we can show a shareable key in the settings UI.
		 */
		public function maybe_generate_secret() {
			if ( get_option( self::OPTION_SECRET, '' ) ) {
				return;
			}

			$secret = wp_generate_password( 32, false );
			update_option( self::OPTION_SECRET, $secret );
		}

		/**
		 * Get the raw secret used for agency authentication.
		 */
		public static function get_secret() {
			$secret = get_option( self::OPTION_SECRET, '' );
			if ( ! is_string( $secret ) ) {
				$secret = '';
			}

			return $secret;
		}

		/**
		 * Regenerate the secret. Intended to be called from a settings action
		 * when the user explicitly chooses to reset access.
		 */
		public static function regenerate_secret() {
			$secret = wp_generate_password( 32, false );
			update_option( self::OPTION_SECRET, $secret );

			return $secret;
		}

		/**
		 * AJAX handler to regenerate the secret and return a fresh shareable key.
		 */
		public function ajax_regenerate_key() {
			if ( ! check_ajax_referer( 'abst_agency_regenerate_key', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'ab-split-test-lite' ), 403 );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Permission denied', 'ab-split-test-lite' ), 403 );
			}

			// Regenerate secret and build new site key.
			self::regenerate_secret();
			$key = self::get_shareable_key();

			wp_send_json_success( [
				'key' => $key,
			] );
		}

		/**
		 * AJAX handler to sync data from a remote site.
		 */
		public function ajax_sync_site() {
			if ( ! check_ajax_referer( 'abst_agency_sync_site', 'nonce', false ) ) {
				wp_send_json_error( 'Security check failed', 403 );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Permission denied', 403 );
			}

			$key = isset( $_POST['key'] ) ? trim( wp_unslash( $_POST['key'] ) ) : '';

			if ( empty( $key ) ) {
				wp_send_json_error( 'No site key provided, auth required.' );
			}

			$key = sanitize_text_field( $key );

			// Decode the site key to get URL and secret
			$decoded = self::decode_site_key( $key );
			
			if ( empty( $decoded['site_url'] ) || empty( $decoded['secret'] ) ) {
				wp_send_json_error( 'Invalid site key' );
			}

			$site_url = $decoded['site_url'];
			$secret   = $decoded['secret'];

			$result = self::request_remote_summary( $site_url, $secret, $key );

			if ( ! $result['success'] ) {
				wp_send_json_error( $result['error'], isset( $result['status_code'] ) ? (int) $result['status_code'] : 400 );
			}

			wp_send_json_success( [
				'message' => 'Successfully synced data from ' . $site_url,
				'data'    => $result['data'],
			] );
		}

		/**
		 * Register REST routes used by the Agency Hub.
		 */
		public static function register_rest_routes() {
			register_rest_route(
				'abst/v1',
				'/agency-summary',
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'rest_agency_summary' ],
					// Authentication is handled inside the callback via X-ABST-Secret header
					'permission_callback' => '__return_true',
				]
			);
		}

		/**
		 * REST callback returning dummy test summary data for Agency Hub.
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return WP_REST_Response
		 */
        public static function rest_agency_summary( $request ) {
            // Validate the secret from the request header
            $provided_secret = $request->get_header( 'X-ABST-Secret' );
            $stored_secret   = self::get_secret();
            
            // Check if remote access is enabled
            $enabled = get_option( self::OPTION_ENABLED, false );
            
            if ( ! $enabled ) {
                return new WP_REST_Response(
                    [ 'error' => 'Remote access is not enabled on this site' ],
                    403
                );
            }
            
            // Validate the secret
            if ( empty( $provided_secret ) || empty( $stored_secret ) ) {
                return new WP_REST_Response(
                    [ 'error' => 'Authentication required' ],
                    401
                );
            }
            
            if ( ! hash_equals( $stored_secret, $provided_secret ) ) {
                return new WP_REST_Response(
                    [ 'error' => 'Invalid authentication credentials' ],
                    403
                );
            }
            
            global $btab;
            $site_url = home_url();
            $tests    = [];

            $experiments = get_posts( [
                'post_type'      => 'bt_experiments',
                'post_status'    => 'any',
                'posts_per_page' => -1,
            ] );
            
            // Debug: Add count of experiments found
            $debug_info = [
                'total_experiments' => count( $experiments ),
                'method_exists'     => is_object( $btab ) && method_exists( $btab, 'get_experiment_stats_array' ),
                'btab_type'         => is_object( $btab ) ? get_class( $btab ) : gettype( $btab ),
            ];

            foreach ( $experiments as $exp ) {
                // Skip trash status
                if ( get_post_status( $exp->ID ) === 'trash' ) {
                    continue;
                }
                
                if ( ! is_object( $btab ) || ! method_exists( $btab, 'get_experiment_stats_array' ) ) {
                    $tests[] = [
                        'id'              => $exp->ID,
                        'name'            => get_the_title( $exp->ID ),
                        'status'          => get_post_status( $exp->ID ),
                        'time_remaining'  => 'N/A',
                        'duration_so_far' => 'N/A',
                        'winner_key'      => '',
                        'winner_conf'     => 0.0,
                        'variations'      => [],
                        'error'           => 'Method not available',
                    ];
                    continue;
                }

                $stats = $btab->get_experiment_stats_array( $exp );
                
                
                // If stats function returns null, create basic test info
                if ( ! is_array( $stats ) ) {
                    $basic_test = [
                        'id'              => $exp->ID,
                        'name'            => get_the_title( $exp->ID ),
                        'status'          => get_post_status( $exp->ID ),
                        'time_remaining'  => 'N/A',
                        'duration_so_far' => 'N/A',
                        'winner_key'      => '',
                        'winner_conf'     => 0.0,
                        'variations'      => [],
                        'error'           => 'No statistical data available yet',
                    ];
                    $tests[] = $basic_test;
                    continue;
                }
                
                if ( empty( $stats['variations'] ) ) {
                    $tests[] = [
                        'id'              => $stats['id'],
                        'name'            => $stats['name'],
                        'status'          => $stats['status'],
                        'time_remaining'  => $stats['time_remaining'],
                        'duration_so_far' => $stats['duration_so_far'],
                        'winner_key'      => $stats['winner_key'],
                        'winner_conf'     => $stats['winner_conf'],
                        'confidence_target' => isset( $stats['confidence_target'] ) ? (float) $stats['confidence_target'] : 95.0,
                        'start_date'      => isset( $stats['start_date'] ) ? $stats['start_date'] : '',
                        'variations'      => [],
                        'error'           => 'No variations data',
                    ];
                    continue;
                }

                $variation_items = [];
                $variation_meta = get_post_meta( $exp->ID, 'variation_meta', true );
                
                foreach ( $stats['variations'] as $var_key => $var_data ) {
                    // Get proper variation label using the centralized function
                    $variation_label = function_exists( 'abst_get_variation_label' ) 
                        ? abst_get_variation_label( $var_key, $variation_meta )
                        : self::get_variation_label( $var_key, $variation_meta );

                    // Heatmap-related metadata, mirroring what the main admin UI uses
                    // eid: experiment ID, variation: variation key, page_id: tested page/post ID (when available)	
                    // Derive page_id on the fly from variation stats (same idea as admin results),
                    // instead of relying on persisted variation_meta.
                    $page_id = 0;
                    if ( isset( $var_data['page_id'] ) ) {
                        $page_id = (int) $var_data['page_id'];
                    }
                    
                    $variation_items[] = [
                        'key'                   => (string) $var_key,
                        'label'                 => $variation_label,
                        'visits'                => isset( $var_data['visits'] ) ? (int) $var_data['visits'] : 0,
                        'conversions'           => isset( $var_data['conversions'] ) ? (float) $var_data['conversions'] : 0,
                        'conversion_rate'       => isset( $var_data['conversion_rate'] ) ? (float) $var_data['conversion_rate'] : 0.0,
                        'uplift_vs_control'     => isset( $var_data['uplift_vs_control'] ) ? (float) $var_data['uplift_vs_control'] : 0.0,
                        'likelihood_of_winning' => isset( $var_data['likelihood_of_winning'] ) ? (float) $var_data['likelihood_of_winning'] : 0.0,
                        // New: heatmap metadata
                        'eid'                   => (int) $exp->ID,
                        'variation'             => (string) $var_key,
                        'page_id'               => $page_id,
                    ];
                }

                $tests[] = [
                    'id'              => $stats['id'],
                    'name'            => $stats['name'],
                    'status'          => $stats['status'],
                    'time_remaining'  => $stats['time_remaining'],
                    'duration_so_far' => $stats['duration_so_far'],
                    'winner_key'      => $stats['winner_key'],
                    'winner_conf'     => $stats['winner_conf'],
                    'confidence_target' => isset( $stats['confidence_target'] ) ? (float) $stats['confidence_target'] : 95.0,
                    'variations'      => $variation_items,
                ];
            }
            
            return new WP_REST_Response(
                [
                'site_url'     => $site_url,
                'generated_at' => gmdate( 'c' ),
                'tests'        => $tests,
                'debug'        => $debug_info,
                ],
                200
            );
        }

		/**
		 * Build the shareable site key: base64( home_url() + '|' + secret ).
		 */
		public static function get_shareable_key() {
			$secret = self::get_secret();
			if ( empty( $secret ) ) {
				return '';
			}

			$raw = home_url() . '|' . $secret;

			return base64_encode( $raw );
		}

		/**
		 * Decode a site key into [ site_url, secret ]. Used on the hub side when
		 * the user pastes a key into the "Add site" form.
		 *
		 * @param string $key Base64-encoded site key.
		 *
		 * @return array{site_url:string,secret:string}|
		 */
		public static function decode_site_key( $key ) {
			$key   = trim( (string) $key );
			$raw   = base64_decode( $key, true );

			if ( false === $raw ) {
				return [
					'site_url' => '',
					'secret'   => '',
				];
			}

			$parts = explode( '|', (string) $raw, 2 );

			if ( count( $parts ) !== 2 ) {
				return [
					'site_url' => '',
					'secret'   => '',
				];
			}

			return [
				'site_url' => esc_url_raw( $parts[0] ),
				'secret'   => sanitize_text_field( $parts[1] ),
			];
		}

		/**
		 * Get proper variation label based on variation key and meta.
		 * Fallback method - prefer using abst_get_variation_label() from main plugin.
		 *
		 * @param string|int $variation Variation key.
		 * @param array|null $variation_meta Variation metadata from post meta.
		 * @return string Formatted variation label.
		 */
		public static function get_variation_label( $variation, $variation_meta = null ) {
			$variation = (string) $variation;
			$variation_label = $variation;
			$alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
			
			// Check for custom label in variation_meta first (highest priority)
			if ( is_array( $variation_meta ) && isset( $variation_meta[ $variation ]['label'] ) && ! empty( $variation_meta[ $variation ]['label'] ) ) {
				return $variation_meta[ $variation ]['label'];
			}
			
			// Handle magic test variations (magic-0, magic-1, etc.)
			if ( substr( $variation, 0, 6 ) === 'magic-' ) {
				$index = (int) substr( $variation, 6 );
				if ( $index === 0 ) {
					return 'Variation ' . $alphabet[0] . ' (Original)';
				}
				return 'Variation ' . ( isset( $alphabet[ $index ] ) ? $alphabet[ $index ] : $index );
			}
			
			// Handle CSS test variations (test-css-TESTID-N)
			if ( preg_match( '/^test-css-(\d+)-(\d+)$/', $variation, $matches ) ) {
				$var_num = (int) $matches[2];
				$index = $var_num - 1; // Convert 1-based to 0-based for alphabet
				if ( $index === 0 ) {
					return 'Variation ' . $alphabet[0] . ' (Original)';
				}
				return 'Variation ' . ( isset( $alphabet[ $index ] ) ? $alphabet[ $index ] : $var_num );
			}
			
			// Handle numeric post IDs (full page tests)
			if ( is_numeric( $variation ) ) {
				$post_id = (int) $variation;
				if ( get_post_status( $post_id ) ) {
					return get_the_title( $post_id );
				}
			}
			
			// Fallback: return the variation key as-is
			return $variation_label;
		}

		/**
		 * Retrieve saved agency hub site definitions on the hub side.
		 *
		 * Stored shape:
		 * [
		 *   [ 'site_url' => 'https://example.com', 'key' => 'base64...', 'label' => 'Client' ],
		 * ].
		 */
		public static function get_saved_sites() {
			$sites = get_option( self::OPTION_SITE_KEYS, [] );
			if ( ! is_array( $sites ) ) {
				$sites = [];
			}

			return $sites;
		}

		/**
		 * Get cached data for a specific site key.
		 *
		 * @param string $key Site key.
		 * @return array|null Cached data with 'data', 'synced_at', 'site_url' or null if not cached.
		 */
		public static function get_cached_site_data( $key ) {
			$cache_key = 'abst_remote_data_' . md5( $key );
			$cached = get_transient( $cache_key );
			
			if ( ! $cached || ! is_array( $cached ) ) {
				return null;
			}
			
			return $cached;
		}

		public static function delete_cached_site_data( $key ) {
			$cache_key = 'abst_remote_data_' . md5( trim( (string) $key ) );

			delete_transient( $cache_key );
		}

		public static function request_remote_summary( $site_url, $secret, $key = '' ) {
			$site_url = esc_url_raw( trim( (string) $site_url ) );
			$secret   = sanitize_text_field( $secret );
			$key      = trim( (string) $key );

			if ( empty( $site_url ) || empty( $secret ) ) {
				return [
					'success'     => false,
					'error'       => 'Invalid site key',
					'status_code' => 400,
				];
			}

			$endpoint_url = trailingslashit( $site_url ) . 'wp-json/abst/v1/agency-summary';
			$response = wp_remote_get( $endpoint_url, [
				'timeout' => 15,
				'headers' => [
					'X-ABST-Secret' => $secret,
				],
			] );

			if ( is_wp_error( $response ) ) {
				return [
					'success'     => false,
					'error'       => 'Failed to connect to remote site: ' . $response->get_error_message(),
					'status_code' => 502,
				];
			}

			$response_code = (int) wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$data          = json_decode( $body, true );

			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
				$message = 'Invalid JSON response from remote site';
				if ( ! empty( $body ) ) {
					$message .= ': ' . sanitize_text_field( wp_trim_words( wp_strip_all_tags( $body ), 20, '...' ) );
				}

				return [
					'success'     => false,
					'error'       => $message,
					'status_code' => $response_code > 0 ? $response_code : 502,
				];
			}

			if ( 200 !== $response_code ) {
				$remote_error = '';
				if ( ! empty( $data['error'] ) ) {
					$remote_error = sanitize_text_field( $data['error'] );
				} elseif ( ! empty( $data['message'] ) ) {
					$remote_error = sanitize_text_field( $data['message'] );
				}

				$message = 'Remote site returned error code: ' . $response_code;
				if ( $remote_error !== '' ) {
					$message .= ' - ' . $remote_error;
				}

				return [
					'success'     => false,
					'error'       => $message,
					'status_code' => $response_code,
				];
			}

			if ( ! empty( $key ) ) {
				$cache_key = 'abst_remote_data_' . md5( $key );
				$cached_data = [
					'data' => $data,
					'synced_at' => time(),
					'site_url' => $site_url,
				];
				set_transient( $cache_key, $cached_data, 3 * DAY_IN_SECONDS );
			}

			return [
				'success'     => true,
				'data'        => $data,
				'status_code' => 200,
			];
		}

	}

	new BT_BB_AB_Agency_Hub();
}

