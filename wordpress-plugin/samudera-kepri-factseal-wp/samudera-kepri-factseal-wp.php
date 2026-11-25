<?php
/**
 * Plugin Name: Samudera Kepri FactSeal WP
 * Plugin URI: https://www.samuderakepri.co.id/
 * Description: Otomatisasi pencatatan sidik jari kriptografi (contentHash bytes32) artikel WordPress ke Smart Contract SamuderaKepriFactSeal pada jaringan Ethereum Sepolia melalui relayer resmi.
 * Version: 1.0.0
 * Author: PT Media Samudera Kepri
 * Author URI: https://www.samuderakepri.co.id/
 * License: MIT
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: samudera-kepri-factseal-wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Samudera_Kepri_FactSeal_WP' ) ) {

	class Samudera_Kepri_FactSeal_WP {

		const OPTION_GROUP   = 'msk_factseal_settings_group';
		const OPTION_NAME    = 'msk_factseal_options';
		const PAGE_SLUG      = 'samudera-kepri-factseal';
		const META_TX_HASH   = '_msk_factseal_tx_hash';
		const META_HASH      = '_msk_factseal_content_hash';
		const META_STATUS    = '_msk_factseal_status';
		const META_EXPLORER  = '_msk_factseal_explorer_url';
		const META_SEALED_AT = '_msk_factseal_sealed_at';
		const META_ERROR     = '_msk_factseal_error';

		/**
		 * Nilai default pengaturan plugin.
		 */
		public static function get_defaults() {
			return array(
				'relayer_endpoint' => 'https://api.samuderakepri.co.id/factseal/seal',
				'bearer_token'     => '',
				'contract_address' => '0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033',
				'chain_id'         => '11155111',
				'explorer_tx_base' => 'https://sepolia.etherscan.io/tx/',
				'auto_seal'        => '1',
			);
		}

		/**
		 * Inisialisasi hook WordPress.
		 */
		public static function init() {
			register_activation_hook( __FILE__, array( __CLASS__, 'on_activate' ) );

			add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

			add_action( 'transition_post_status', array( __CLASS__, 'handle_post_publish' ), 20, 3 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );
			add_action( 'admin_post_msk_factseal_reseal', array( __CLASS__, 'handle_manual_reseal' ) );
			add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );

			add_filter( 'the_content', array( __CLASS__, 'append_factseal_badge' ), 35 );
		}

		/**
		 * Simpan opsi default saat plugin pertama kali diaktifkan.
		 */
		public static function on_activate() {
			$existing = get_option( self::OPTION_NAME, null );
			if ( null === $existing || false === $existing ) {
				add_option( self::OPTION_NAME, self::get_defaults() );
			}
		}

		/**
		 * Mengambil opsi tergabung dengan nilai default.
		 */
		public static function get_options() {
			$saved = get_option( self::OPTION_NAME, array() );
			if ( ! is_array( $saved ) ) {
				$saved = array();
			}
			return wp_parse_args( $saved, self::get_defaults() );
		}

		/**
		 * Daftarkan menu Settings -> Samudera Kepri FactSeal.
		 */
		public static function register_settings_page() {
			add_options_page(
				__( 'Samudera Kepri FactSeal', 'samudera-kepri-factseal-wp' ),
				__( 'Samudera Kepri FactSeal', 'samudera-kepri-factseal-wp' ),
				'manage_options',
				self::PAGE_SLUG,
				array( __CLASS__, 'render_settings_page' )
			);
		}

		/**
		 * Daftarkan pengaturan menggunakan WordPress Settings API.
		 */
		public static function register_settings() {
			register_setting(
				self::OPTION_GROUP,
				self::OPTION_NAME,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
					'default'           => self::get_defaults(),
				)
			);

			add_settings_section(
				'msk_factseal_main_section',
				__( 'Konfigurasi Relayer & Smart Contract FactSeal', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'render_section_intro' ),
				self::PAGE_SLUG
			);

			add_settings_field(
				'relayer_endpoint',
				__( 'Endpoint Relayer API', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'field_relayer_endpoint' ),
				self::PAGE_SLUG,
				'msk_factseal_main_section'
			);

			add_settings_field(
				'bearer_token',
				__( 'API Bearer Token', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'field_bearer_token' ),
				self::PAGE_SLUG,
				'msk_factseal_main_section'
			);

			add_settings_field(
				'contract_address',
				__( 'Alamat Kontrak FactSeal', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'field_contract_address' ),
				self::PAGE_SLUG,
				'msk_factseal_main_section'
			);

			add_settings_field(
				'chain_id',
				__( 'Chain ID', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'field_chain_id' ),
				self::PAGE_SLUG,
				'msk_factseal_main_section'
			);

			add_settings_field(
				'explorer_tx_base',
				__( 'URL Dasar Explorer TX', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'field_explorer_tx_base' ),
				self::PAGE_SLUG,
				'msk_factseal_main_section'
			);

			add_settings_field(
				'auto_seal',
				__( 'Otomatis Saat Tayang', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'field_auto_seal' ),
				self::PAGE_SLUG,
				'msk_factseal_main_section'
			);
		}

		/**
		 * Sanitasi seluruh input pengaturan admin.
		 */
		public static function sanitize_options( $input ) {
			$defaults = self::get_defaults();
			$current  = self::get_options();
			$clean    = array();

			$endpoint = isset( $input['relayer_endpoint'] ) ? esc_url_raw( trim( $input['relayer_endpoint'] ), array( 'http', 'https' ) ) : '';
			$clean['relayer_endpoint'] = ! empty( $endpoint ) ? $endpoint : $defaults['relayer_endpoint'];

			if ( isset( $input['bearer_token'] ) ) {
				$raw_token = trim( (string) $input['bearer_token'] );
				// Pertahankan token lama jika kolom password dikosongkan saat menyimpan pengaturan lain.
				$clean['bearer_token'] = '' !== $raw_token ? sanitize_text_field( $raw_token ) : $current['bearer_token'];
			} else {
				$clean['bearer_token'] = $current['bearer_token'];
			}

			$contract = isset( $input['contract_address'] ) ? sanitize_text_field( trim( $input['contract_address'] ) ) : '';
			if ( preg_match( '/^0x[0-9a-fA-F]{40}$/', $contract ) ) {
				$clean['contract_address'] = $contract;
			} else {
				$clean['contract_address'] = $defaults['contract_address'];
				add_settings_error(
					self::OPTION_NAME,
					'invalid_contract',
					__( 'Format Alamat Kontrak FactSeal tidak valid, dikembalikan ke alamat kontrak default.', 'samudera-kepri-factseal-wp' ),
					'warning'
				);
			}

			$chain_id = isset( $input['chain_id'] ) ? absint( $input['chain_id'] ) : 0;
			$clean['chain_id'] = $chain_id > 0 ? (string) $chain_id : $defaults['chain_id'];

			$explorer_base = isset( $input['explorer_tx_base'] ) ? esc_url_raw( trim( $input['explorer_tx_base'] ), array( 'http', 'https' ) ) : '';
			if ( ! empty( $explorer_base ) ) {
				$clean['explorer_tx_base'] = trailingslashit( $explorer_base );
			} else {
				$clean['explorer_tx_base'] = $defaults['explorer_tx_base'];
			}

			$clean['auto_seal'] = ! empty( $input['auto_seal'] ) ? '1' : '0';

			return $clean;
		}

		public static function render_section_intro() {
			echo '<p>' . esc_html__( 'Hubungkan WordPress ke layanan relayer Node.js Samudera Kepri untuk menyegel hash artikel secara otomatis ke Smart Contract FactSeal pada jaringan Ethereum Sepolia.', 'samudera-kepri-factseal-wp' ) . '</p>';
		}

		public static function field_relayer_endpoint() {
			$options = self::get_options();
			printf(
				'<input type="url" class="regular-text code" name="%1$s[relayer_endpoint]" value="%2$s" placeholder="https://api.samuderakepri.co.id/factseal/seal" required />',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $options['relayer_endpoint'] )
			);
			echo '<p class="description">' . esc_html__( 'Default: https://api.samuderakepri.co.id/factseal/seal', 'samudera-kepri-factseal-wp' ) . '</p>';
		}

		public static function field_bearer_token() {
			$options    = self::get_options();
			$has_token  = ! empty( $options['bearer_token'] );
			$help_label = $has_token
				? __( 'Token saat ini sudah tersimpan. Biarkan kosong jika tidak ingin mengubah token.', 'samudera-kepri-factseal-wp' )
				: __( 'Masukkan token rahasia yang sama persis dengan RELAYER_API_TOKEN di server Node.js Hostinger.', 'samudera-kepri-factseal-wp' );

			printf(
				'<input type="password" class="regular-text" name="%1$s[bearer_token]" value="" autocomplete="new-password" placeholder="%2$s" />',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $has_token ? '••••••••••••••••••••••••' : __( 'Masukkan API Bearer Token', 'samudera-kepri-factseal-wp' ) )
			);
			echo '<p class="description">' . esc_html( $help_label ) . '</p>';
		}

		public static function field_contract_address() {
			$options = self::get_options();
			printf(
				'<input type="text" class="regular-text code" name="%1$s[contract_address]" value="%2$s" pattern="^0x[0-9a-fA-F]{40}$" required />',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $options['contract_address'] )
			);
			echo '<p class="description">' . esc_html__( 'Alamat Smart Contract FactSeal resmi (default: 0xbAED3a7fb42736d6597145FAE88b3c9a00d5a033).', 'samudera-kepri-factseal-wp' ) . '</p>';
		}

		public static function field_chain_id() {
			$options = self::get_options();
			printf(
				'<input type="number" class="small-text" name="%1$s[chain_id]" value="%2$s" min="1" required />',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $options['chain_id'] )
			);
			echo '<p class="description">' . esc_html__( 'Ethereum Sepolia Testnet menggunakan Chain ID 11155111.', 'samudera-kepri-factseal-wp' ) . '</p>';
		}

		public static function field_explorer_tx_base() {
			$options = self::get_options();
			printf(
				'<input type="url" class="regular-text code" name="%1$s[explorer_tx_base]" value="%2$s" required />',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $options['explorer_tx_base'] )
			);
			echo '<p class="description">' . esc_html__( 'Default: https://sepolia.etherscan.io/tx/', 'samudera-kepri-factseal-wp' ) . '</p>';
		}

		public static function field_auto_seal() {
			$options = self::get_options();
			printf(
				'<label><input type="checkbox" name="%1$s[auto_seal]" value="1" %2$s /> %3$s</label>',
				esc_attr( self::OPTION_NAME ),
				checked( $options['auto_seal'], '1', false ),
				esc_html__( 'Aktifkan pencatatan FactSeal otomatis saat artikel pertama kali dipublikasikan (publish).', 'samudera-kepri-factseal-wp' )
			);
		}

		/**
		 * Render halaman Settings -> Samudera Kepri FactSeal.
		 */
		public static function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Samudera Kepri FactSeal WP', 'samudera-kepri-factseal-wp' ); ?></h1>
				<p><?php echo esc_html__( 'Sistem verifikasi integritas jurnalistik PT Media Samudera Kepri berbasis Ethereum Sepolia.', 'samudera-kepri-factseal-wp' ); ?></p>
				<form method="post" action="options.php">
					<?php
					settings_fields( self::OPTION_GROUP );
					do_settings_sections( self::PAGE_SLUG );
					submit_button( __( 'Simpan Pengaturan FactSeal', 'samudera-kepri-factseal-wp' ) );
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Hitung contentHash bytes32 kanonis artikel WordPress (0x + 64 hex SHA-256).
		 */
		public static function build_canonical_payload( $post ) {
			$post_id      = (int) $post->ID;
			$permalink    = get_permalink( $post_id );
			$published_at = get_post_time( 'c', true, $post_id );
			$raw_title    = wp_strip_all_tags( html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) );
			$clean_body   = preg_replace( '/\s+/u', ' ', trim( wp_strip_all_tags( (string) $post->post_content ) ) );

			$canonical = array(
				'publisher'   => 'PT Media Samudera Kepri',
				'postId'      => (string) $post_id,
				'title'       => $raw_title,
				'url'         => $permalink ? $permalink : home_url( '/?p=' . $post_id ),
				'publishedAt' => $published_at ? $published_at : gmdate( 'c' ),
				'bodyDigest'  => hash( 'sha256', (string) $clean_body ),
			);

			$json_string  = wp_json_encode( $canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$content_hash = '0x' . hash( 'sha256', (string) $json_string );

			return array(
				'postId'      => (string) $post_id,
				'title'       => $canonical['title'],
				'url'         => $canonical['url'],
				'publishedAt' => $canonical['publishedAt'],
				'contentHash' => $content_hash,
				'category'    => self::resolve_primary_category( $post_id ),
			);
		}

		/**
		 * Ambil nama kategori utama artikel untuk dicatat pada smart contract.
		 */
		protected static function resolve_primary_category( $post_id ) {
			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				return sanitize_text_field( $categories[0]->name );
			}
			return 'Berita Media';
		}

		/**
		 * Hook transition_post_status: otomatis segel saat artikel berubah ke status publish.
		 */
		public static function handle_post_publish( $new_status, $old_status, $post ) {
			if ( ! $post instanceof WP_Post ) {
				return;
			}
			if ( 'publish' !== $new_status || 'post' !== $post->post_type ) {
				return;
			}
			if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
				return;
			}

			$options = self::get_options();
			if ( '1' !== $options['auto_seal'] ) {
				return;
			}

			$existing_tx = get_post_meta( $post->ID, self::META_TX_HASH, true );
			if ( ! empty( $existing_tx ) ) {
				return;
			}

			self::seal_post( $post->ID );
		}

		/**
		 * Eksekusi permintaan POST ke Relayer API dan simpan hasilnya ke post meta.
		 */
		public static function seal_post( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
				return array(
					'success' => false,
					'message' => __( 'Artikel harus berstatus terbit (publish) sebelum disegel.', 'samudera-kepri-factseal-wp' ),
				);
			}

			$options  = self::get_options();
			$endpoint = trim( (string) $options['relayer_endpoint'] );
			$token    = trim( (string) $options['bearer_token'] );

			$payload = self::build_canonical_payload( $post );
			update_post_meta( $post_id, self::META_HASH, $payload['contentHash'] );

			if ( empty( $endpoint ) || empty( $token ) ) {
				$msg = __( 'Endpoint Relayer API atau API Bearer Token belum diisi pada Settings -> Samudera Kepri FactSeal.', 'samudera-kepri-factseal-wp' );
				update_post_meta( $post_id, self::META_STATUS, 'failed' );
				update_post_meta( $post_id, self::META_ERROR, $msg );
				return array(
					'success' => false,
					'message' => $msg,
				);
			}

			update_post_meta( $post_id, self::META_STATUS, 'pending' );
			delete_post_meta( $post_id, self::META_ERROR );

			$response = wp_remote_post(
				$endpoint,
				array(
					'timeout'     => 45,
					'redirection' => 2,
					'headers'     => array(
						'Content-Type'  => 'application/json; charset=utf-8',
						'Authorization' => 'Bearer ' . $token,
					),
					'body'        => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				)
			);

			if ( is_wp_error( $response ) ) {
				$error_message = sanitize_text_field( $response->get_error_message() );
				update_post_meta( $post_id, self::META_STATUS, 'failed' );
				update_post_meta( $post_id, self::META_ERROR, $error_message );
				return array(
					'success' => false,
					'message' => $error_message,
				);
			}

			$http_code = (int) wp_remote_retrieve_response_code( $response );
			$raw_body  = wp_remote_retrieve_body( $response );
			$decoded   = json_decode( (string) $raw_body, true );

			if ( $http_code >= 200 && $http_code < 300 && is_array( $decoded ) && ! empty( $decoded['success'] ) ) {
				$tx_hash      = isset( $decoded['txHash'] ) ? sanitize_text_field( (string) $decoded['txHash'] ) : '';
				$final_hash   = isset( $decoded['contentHash'] ) && preg_match( '/^0x[0-9a-fA-F]{64}$/', (string) $decoded['contentHash'] )
					? strtolower( (string) $decoded['contentHash'] )
					: $payload['contentHash'];
				$explorer_url = ! empty( $decoded['explorerUrl'] )
					? esc_url_raw( (string) $decoded['explorerUrl'], array( 'http', 'https' ) )
					: ( ! empty( $tx_hash ) ? trailingslashit( $options['explorer_tx_base'] ) . $tx_hash : '' );

				if ( ! empty( $tx_hash ) ) {
					update_post_meta( $post_id, self::META_TX_HASH, $tx_hash );
				}
				update_post_meta( $post_id, self::META_HASH, $final_hash );
				update_post_meta( $post_id, self::META_STATUS, 'sealed' );
				if ( ! empty( $explorer_url ) ) {
					update_post_meta( $post_id, self::META_EXPLORER, $explorer_url );
				}
				update_post_meta( $post_id, self::META_SEALED_AT, gmdate( 'c' ) );
				delete_post_meta( $post_id, self::META_ERROR );

				return array(
					'success' => true,
					'txHash'  => $tx_hash,
				);
			}

			$api_error = is_array( $decoded ) && ! empty( $decoded['error'] )
				? sanitize_text_field( (string) $decoded['error'] )
				: sprintf( __( 'Relayer merespons HTTP %d.', 'samudera-kepri-factseal-wp' ), $http_code );

			update_post_meta( $post_id, self::META_STATUS, 'failed' );
			update_post_meta( $post_id, self::META_ERROR, $api_error );

			return array(
				'success' => false,
				'message' => $api_error,
			);
		}

		/**
		 * Daftarkan Meta Box status FactSeal pada layar editor artikel.
		 */
		public static function register_meta_box() {
			add_meta_box(
				'msk_factseal_meta_box',
				__( 'Samudera Kepri FactSeal', 'samudera-kepri-factseal-wp' ),
				array( __CLASS__, 'render_meta_box' ),
				'post',
				'side',
				'high'
			);
		}

		/**
		 * Tampilkan status segel blockchain & tombol kirim ulang di Meta Box editor.
		 */
		public static function render_meta_box( $post ) {
			$options      = self::get_options();
			$status       = get_post_meta( $post->ID, self::META_STATUS, true );
			$tx_hash      = get_post_meta( $post->ID, self::META_TX_HASH, true );
			$content_hash = get_post_meta( $post->ID, self::META_HASH, true );
			$explorer_url = get_post_meta( $post->ID, self::META_EXPLORER, true );
			$last_error   = get_post_meta( $post->ID, self::META_ERROR, true );

			if ( empty( $explorer_url ) && ! empty( $tx_hash ) ) {
				$explorer_url = trailingslashit( $options['explorer_tx_base'] ) . $tx_hash;
			}

			$status_label = __( 'Menunggu', 'samudera-kepri-factseal-wp' );
			$badge_color  = '#d97706';
			if ( 'sealed' === $status && ! empty( $tx_hash ) ) {
				$status_label = __( 'Tercatat', 'samudera-kepri-factseal-wp' );
				$badge_color  = '#059669';
			} elseif ( 'sealed' === $status ) {
				$status_label = __( 'Tercatat', 'samudera-kepri-factseal-wp' );
				$badge_color  = '#059669';
			} elseif ( 'failed' === $status ) {
				$status_label = __( 'Gagal', 'samudera-kepri-factseal-wp' );
				$badge_color  = '#dc2626';
			}

			echo '<p style="margin-bottom:8px;"><strong>' . esc_html__( 'Status Segel:', 'samudera-kepri-factseal-wp' ) . '</strong> ';
			echo '<span style="display:inline-block;padding:2px 8px;border-radius:4px;color:#fff;font-weight:600;background:' . esc_attr( $badge_color ) . ';">' . esc_html( $status_label ) . '</span></p>';

			if ( ! empty( $content_hash ) ) {
				echo '<p style="margin-bottom:6px;word-break:break-all;"><strong>Content Hash:</strong><br><code style="font-size:11px;">' . esc_html( $content_hash ) . '</code></p>';
			}

			if ( ! empty( $tx_hash ) ) {
				echo '<p style="margin-bottom:6px;word-break:break-all;"><strong>TX Hash:</strong><br><code style="font-size:11px;">' . esc_html( $tx_hash ) . '</code></p>';
				if ( ! empty( $explorer_url ) ) {
					echo '<p style="margin-bottom:8px;"><a class="button button-secondary" href="' . esc_url( $explorer_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Lihat di Sepolia Etherscan', 'samudera-kepri-factseal-wp' ) . '</a></p>';
				}
			}

			if ( 'failed' === $status && ! empty( $last_error ) ) {
				echo '<p style="color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;padding:6px 8px;border-radius:4px;font-size:12px;">' . esc_html( $last_error ) . '</p>';
			}

			if ( 'publish' === $post->post_status && ( 'sealed' !== $status || empty( $tx_hash ) ) ) {
				$reseal_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=msk_factseal_reseal&post_id=' . (int) $post->ID ),
					'msk_factseal_reseal_' . (int) $post->ID
				);
				echo '<p style="margin-top:10px;"><a class="button button-primary" href="' . esc_url( $reseal_url ) . '">' . esc_html__( 'Kirim Ulang Segel FactSeal', 'samudera-kepri-factseal-wp' ) . '</a></p>';
			} elseif ( 'publish' !== $post->post_status ) {
				echo '<p class="description">' . esc_html__( 'Artikel akan disegel otomatis saat status berubah menjadi Publish (Terbitkan).', 'samudera-kepri-factseal-wp' ) . '</p>';
			}
		}

		/**
		 * Tangani tombol manual "Kirim Ulang Segel FactSeal" dari editor.
		 */
		public static function handle_manual_reseal() {
			$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Anda tidak memiliki izin untuk menyegel artikel ini.', 'samudera-kepri-factseal-wp' ) );
			}

			check_admin_referer( 'msk_factseal_reseal_' . $post_id );

			$result = self::seal_post( $post_id );
			$status = ! empty( $result['success'] ) ? 'ok' : 'err';

			$redirect_url = add_query_arg(
				array(
					'msk_factseal_notice' => $status,
				),
				get_edit_post_link( $post_id, 'raw' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Tampilkan notifikasi hasil pengiriman ulang di layar editor WordPress.
		 */
		public static function render_admin_notices() {
			if ( ! isset( $_GET['msk_factseal_notice'] ) ) {
				return;
			}
			$notice = sanitize_text_field( wp_unslash( $_GET['msk_factseal_notice'] ) );
			if ( 'ok' === $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Segel FactSeal berhasil dicatat ke jaringan Ethereum Sepolia.', 'samudera-kepri-factseal-wp' ) . '</p></div>';
			} elseif ( 'err' === $notice ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Pengiriman segel FactSeal gagal. Periksa pesan detail pada kotak Samudera Kepri FactSeal di sidebar editor.', 'samudera-kepri-factseal-wp' ) . '</p></div>';
			}
		}

		/**
		 * Filter the_content: tampilkan lencana verifikasi integritas FactSeal di bawah artikel tunggal.
		 */
		public static function append_factseal_badge( $content ) {
			if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
				return $content;
			}

			$post_id = get_the_ID();
			if ( ! $post_id ) {
				return $content;
			}

			$status       = get_post_meta( $post_id, self::META_STATUS, true );
			$tx_hash      = get_post_meta( $post_id, self::META_TX_HASH, true );
			$content_hash = get_post_meta( $post_id, self::META_HASH, true );
			$explorer_url = get_post_meta( $post_id, self::META_EXPLORER, true );
			$sealed_at    = get_post_meta( $post_id, self::META_SEALED_AT, true );

			if ( 'sealed' !== $status && empty( $tx_hash ) ) {
				return $content;
			}

			$options = self::get_options();
			if ( empty( $explorer_url ) && ! empty( $tx_hash ) ) {
				$explorer_url = trailingslashit( $options['explorer_tx_base'] ) . $tx_hash;
			}

			ob_start();
			?>
			<aside class="msk-factseal-card" aria-label="<?php echo esc_attr__( 'Verifikasi Integritas Blockchain FactSeal', 'samudera-kepri-factseal-wp' ); ?>" style="margin:28px 0 12px;padding:18px 20px;border-radius:14px;background:linear-gradient(135deg,#060f1d 0%,#0a192f 65%,#132a4a 100%);border:1px solid rgba(52,211,153,0.42);color:#f8fafc;box-shadow:0 12px 28px rgba(6,15,29,0.28);font-family:inherit;max-width:100%;box-sizing:border-box;">
				<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
					<div style="display:flex;align-items:center;gap:8px;min-width:0;">
						<span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:999px;background:rgba(16,185,129,0.22);border:1px solid #34d399;color:#34d399;font-weight:800;font-size:13px;flex-shrink:0;" aria-hidden="true">&#10003;</span>
						<strong style="font-size:15px;line-height:1.3;color:#fde047;">
							<?php echo esc_html__( 'Terverifikasi Integritas Berita · FactSeal Samudera Kepri', 'samudera-kepri-factseal-wp' ); ?>
						</strong>
					</div>
					<span style="display:inline-block;padding:4px 10px;border-radius:999px;background:rgba(16,185,129,0.18);border:1px solid rgba(52,211,153,0.45);color:#6ee7b7;font-size:11.5px;font-weight:700;letter-spacing:0.03em;">
						<?php echo esc_html( sprintf( 'Sepolia Chain ID %s', $options['chain_id'] ) ); ?>
					</span>
				</div>

				<p style="margin:0 0 12px;font-size:13.5px;line-height:1.55;color:#cbd5e1;">
					<?php echo esc_html__( 'Sidik jari kriptografi artikel ini telah disegel ke Smart Contract resmi PT Media Samudera Kepri untuk menjamin keaslian konten dan jejak publikasi.', 'samudera-kepri-factseal-wp' ); ?>
				</p>

				<div style="display:grid;gap:8px;padding:12px;border-radius:10px;background:rgba(6,15,29,0.72);border:1px solid rgba(148,163,184,0.2);font-size:12.5px;margin-bottom:14px;">
					<?php if ( ! empty( $content_hash ) ) : ?>
					<div style="min-width:0;">
						<span style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#94a3b8;margin-bottom:2px;">Content Hash (bytes32)</span>
						<code style="display:block;color:#34d399;background:transparent;padding:0;font-size:12px;word-break:break-all;overflow-wrap:anywhere;"><?php echo esc_html( $content_hash ); ?></code>
					</div>
					<?php endif; ?>
					<div style="min-width:0;">
						<span style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#94a3b8;margin-bottom:2px;">Smart Contract FactSeal</span>
						<code style="display:block;color:#fde047;background:transparent;padding:0;font-size:12px;word-break:break-all;overflow-wrap:anywhere;"><?php echo esc_html( $options['contract_address'] ); ?></code>
					</div>
					<?php if ( ! empty( $sealed_at ) ) : ?>
					<div style="font-size:11.5px;color:#cbd5e1;">
						<?php echo esc_html( sprintf( __( 'Disegel pada (UTC): %s', 'samudera-kepri-factseal-wp' ), $sealed_at ) ); ?>
					</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $explorer_url ) ) : ?>
				<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
					<a href="<?php echo esc_url( $explorer_url ); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:8px;background:linear-gradient(135deg,#eab308 0%,#ca8a04 100%);color:#060f1d;font-size:12.5px;font-weight:800;text-decoration:none;box-shadow:0 4px 12px rgba(234,179,8,0.24);">
						<?php echo esc_html__( 'Verifikasi TX di Sepolia Etherscan', 'samudera-kepri-factseal-wp' ); ?> &rarr;
					</a>
				</div>
				<?php endif; ?>
			</aside>
			<?php
			$badge_html = ob_get_clean();

			return $content . "\n" . $badge_html;
		}
	}

	Samudera_Kepri_FactSeal_WP::init();
}
