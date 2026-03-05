<?php
/**
 * Plugin Name: Test DISC Lead Magnet
 * Plugin URI: https://votresite.com/disc-test
 * Description: Plugin complet pour administrer un test DISC professionnel comme lead magnet pour dirigeants et managers
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://votresite.com
 * License: GPL v2 or later
 * Text Domain: disc-test
 * Domain Path: /languages
 */

// Sécurité : empêche l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
define('DISC_TEST_VERSION', '1.0.0');
define('DISC_TEST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DISC_TEST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DISC_TEST_PLUGIN_FILE', __FILE__);

/**
 * Classe principale du plugin DISC Test
 * Cette classe coordonne tous les composants et gère le cycle de vie du plugin
 */
class DISC_Test_Plugin {
    
    /**
     * Instance unique du plugin (pattern Singleton)
     */
    private static $instance = null;
    
    /**
     * Retourne l'instance unique du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    /**
     * Charge toutes les dépendances nécessaires
     */
    private function load_dependencies() {
        require_once DISC_TEST_PLUGIN_DIR . 'includes/class-disc-database.php';
        require_once DISC_TEST_PLUGIN_DIR . 'includes/class-disc-security.php';
        require_once DISC_TEST_PLUGIN_DIR . 'includes/class-disc-renderer.php';
        require_once DISC_TEST_PLUGIN_DIR . 'includes/class-disc-frontend.php';
        require_once DISC_TEST_PLUGIN_DIR . 'includes/class-disc-email.php';
        require_once DISC_TEST_PLUGIN_DIR . 'includes/class-disc-admin.php';
    }
    
    /**
     * Définit tous les hooks WordPress utilisés par le plugin
     */
    private function define_hooks() {
        // Hooks d'activation et désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hook d'initialisation
        add_action('plugins_loaded', array($this, 'init'));
        
        // Enregistrement du shortcode (approche hybride)
        add_shortcode('disc_test', array('DISC_Renderer', 'render_test'));
        
        // Enregistrement du bloc Gutenberg
        add_action('init', array($this, 'register_gutenberg_block'));
        
        // Enregistrement des scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers pour le test (accessible aux non-connectés)
        add_action('wp_ajax_disc_submit_response', array('DISC_Frontend', 'handle_response_submission'));
        add_action('wp_ajax_nopriv_disc_submit_response', array('DISC_Frontend', 'handle_response_submission'));
        
        add_action('wp_ajax_disc_submit_contact', array('DISC_Frontend', 'handle_contact_submission'));
        add_action('wp_ajax_nopriv_disc_submit_contact', array('DISC_Frontend', 'handle_contact_submission'));
    }
    
    /**
     * Activation du plugin : crée les tables et initialise les données
     */
    public function activate() {
        DISC_Database::create_tables();
        DISC_Database::insert_default_questions();
        
        // Crée une page par défaut pour le test si elle n'existe pas
        $this->create_default_page();
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialisation du plugin après le chargement de WordPress
     */
    public function init() {
        // Charge les traductions
        load_plugin_textdomain('disc-test', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialise les composants
        DISC_Admin::get_instance();
        DISC_Frontend::get_instance();
    }
    
    /**
     * Enregistre le bloc Gutenberg (approche hybride avec le shortcode)
     */
    public function register_gutenberg_block() {
        // Vérifie que Gutenberg est disponible
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Enregistre le script du bloc
        wp_register_script(
            'disc-test-block',
            DISC_TEST_PLUGIN_URL . 'build/block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            DISC_TEST_VERSION
        );
        
        // Enregistre le style du bloc pour l'éditeur
        wp_register_style(
            'disc-test-block-editor',
            DISC_TEST_PLUGIN_URL . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            DISC_TEST_VERSION
        );
        
        // Enregistre le bloc en pointant vers la même fonction de rendu que le shortcode
        register_block_type('disc-test/test-block', array(
            'editor_script' => 'disc-test-block',
            'editor_style' => 'disc-test-block-editor',
            'render_callback' => array('DISC_Renderer', 'render_test'),
            'attributes' => array(
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'buttonText' => array(
                    'type' => 'string',
                    'default' => 'Commencer le test'
                ),
                'redirectUrl' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }
    
    /**
     * Charge les assets CSS et JS pour le frontend
     */
    public function enqueue_frontend_assets() {
        // CSS principal
        wp_enqueue_style(
            'disc-test-frontend',
            DISC_TEST_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            DISC_TEST_VERSION
        );
        
        // JavaScript principal avec Chart.js pour les graphiques
        wp_enqueue_script(
            'chartjs',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_enqueue_script(
            'disc-test-frontend',
            DISC_TEST_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'chartjs'),
            DISC_TEST_VERSION,
            true
        );
        
        // Passe les données nécessaires au JavaScript
        wp_localize_script('disc-test-frontend', 'discTest', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('disc_test_nonce'),
            'strings' => array(
                'submitting' => __('Envoi en cours...', 'disc-test'),
                'error' => __('Une erreur est survenue. Veuillez réessayer.', 'disc-test'),
                'emailInvalid' => __('Veuillez entrer une adresse email valide.', 'disc-test'),
                'required' => __('Ce champ est requis.', 'disc-test')
            )
        ));
    }
    
    /**
     * Charge les assets pour l'administration
     */
    public function enqueue_admin_assets($hook) {
        // Charge seulement sur les pages du plugin
        if (strpos($hook, 'disc-test') === false) {
            return;
        }
        
        wp_enqueue_script('chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1', true);
        
        wp_enqueue_style(
            'disc-test-admin',
            DISC_TEST_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DISC_TEST_VERSION
        );
        
        wp_enqueue_script(
            'disc-test-admin',
            DISC_TEST_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chartjs'),
            DISC_TEST_VERSION,
            true
        );
    }
    
    /**
     * Crée une page par défaut pour le test lors de l'activation
     */
    private function create_default_page() {
        // Vérifie si une page avec le shortcode existe déjà
        $existing_page = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'any',
            's' => '[disc_test]',
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_page)) {
            return;
        }
        
        // Crée la page
        $page_data = array(
            'post_title' => __('Découvrez votre profil DISC', 'disc-test'),
            'post_content' => '[disc_test]',
            'post_status' => 'draft',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        
        wp_insert_post($page_data);
    }
}

/**
 * Initialise le plugin
 */
function disc_test_init() {
    return DISC_Test_Plugin::get_instance();
}

// Lance le plugin
disc_test_init();