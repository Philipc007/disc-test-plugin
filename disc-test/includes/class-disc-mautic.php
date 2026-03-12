<?php
/**
 * Classe DISC_Mautic_Integration
 * Intégration native avec l'API REST Mautic
 *
 * @package DISC_Test
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DISC_Mautic_Integration {

	/**
	 * Vérifie si l'intégration est activée et entièrement configurée.
	 */
	public static function is_enabled() {
		return (bool) get_option( 'disc_mautic_enabled', 0 )
			&& ! empty( get_option( 'disc_mautic_url', '' ) )
			&& ! empty( get_option( 'disc_mautic_user', '' ) )
			&& ! empty( get_option( 'disc_mautic_password', '' ) );
	}

	/**
	 * Retourne les paramètres Mautic.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return array(
			'url'      => trailingslashit( get_option( 'disc_mautic_url', '' ) ),
			'user'     => get_option( 'disc_mautic_user', '' ),
			'password' => get_option( 'disc_mautic_password', '' ),
			'debug'    => (bool) get_option( 'disc_mautic_debug', 0 ),
		);
	}

	/**
	 * Envoie un contact vers Mautic (requête non-bloquante).
	 * Ne lève jamais d'exception vers l'appelant.
	 *
	 * @param array $payload Données du test (format interne plugin).
	 */
	public static function send_contact( $payload ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( empty( $payload['email'] ) ) {
			self::log( 'send_contact: email manquant — abandon.' );
			return;
		}

		try {
			$settings = self::get_settings();
			$endpoint = $settings['url'] . 'api/contacts/new';

			// Tags : array → chaîne séparée par virgules (attendu par l'API Mautic)
			$tags = '';
			if ( ! empty( $payload['tags'] ) && is_array( $payload['tags'] ) ) {
				$tags = implode( ',', $payload['tags'] );
			}

			// Mapping champs plugin → champs API Mautic
			$body = array(
				'email'             => $payload['email'],
				'firstname'         => $payload['first_name']        ?? '',
				'lastname'          => $payload['last_name']         ?? '',
				'company'           => $payload['company']           ?? '',
				'position'          => $payload['position']          ?? '',
				'disc_profile_type' => $payload['profile_type']      ?? '',
				'disc_score_d'      => $payload['score_d']           ?? 0,
				'disc_score_i'      => $payload['score_i']           ?? 0,
				'disc_score_s'      => $payload['score_s']           ?? 0,
				'disc_score_c'      => $payload['score_c']           ?? 0,
				'disc_consistency'  => $payload['consistency_score'] ?? 0,
				'disc_completed_at' => $payload['completed_at']      ?? '',
				'disc_source'       => 'disc_test_libermouv',
				'tags'              => $tags,
			);

			$credentials = base64_encode( $settings['user'] . ':' . $settings['password'] );

			$args = array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $credentials,
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 5,
				'blocking' => false, // Non-bloquant : ne ralentit pas la soumission
			);

			self::log( 'send_contact → ' . $endpoint . ' | payload: ' . wp_json_encode( $body ) );

			wp_remote_post( $endpoint, $args );

		} catch ( \Exception $e ) {
			self::log( 'send_contact exception: ' . $e->getMessage() );
		}
	}

	/**
	 * Teste la connexion à l'API Mautic (requête bloquante — usage admin uniquement).
	 *
	 * @return array { success: bool, message: string }
	 */
	public static function test_connection() {
		$settings = self::get_settings();

		if ( empty( $settings['url'] ) || empty( $settings['user'] ) || empty( $settings['password'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Paramètres Mautic incomplets. Renseignez URL, utilisateur et mot de passe.', 'disc-test' ),
			);
		}

		$endpoint    = $settings['url'] . 'api/contacts?limit=1';
		$credentials = base64_encode( $settings['user'] . ':' . $settings['password'] );

		$response = wp_remote_get( $endpoint, array(
			'headers' => array(
				'Authorization' => 'Basic ' . $credentials,
			),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 === $code ) {
			return array(
				'success' => true,
				/* translators: %d: HTTP response code */
				'message' => sprintf( __( 'Connexion réussie (HTTP %d).', 'disc-test' ), $code ),
			);
		}

		// Tente d'extraire le message d'erreur Mautic
		$decoded   = json_decode( $body, true );
		$error_msg = isset( $decoded['errors'][0]['message'] )
			? $decoded['errors'][0]['message']
			/* translators: %d: HTTP response code */
			: sprintf( __( 'HTTP %d — réponse inattendue.', 'disc-test' ), $code );

		return array(
			'success' => false,
			'message' => $error_msg,
		);
	}

	/**
	 * Écrit une ligne dans le log Mautic si le mode debug est activé.
	 *
	 * @param string $message Message à enregistrer.
	 */
	public static function log( $message ) {
		if ( ! get_option( 'disc_mautic_debug', 0 ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		$log_file   = $upload_dir['basedir'] . '/disc-mautic.log';
		$timestamp  = current_time( 'Y-m-d H:i:s' );
		$line       = "[{$timestamp}] {$message}" . PHP_EOL;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
	}
}
