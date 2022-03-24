<?php

/*
Plugin Name: Quiz
Description: Créer un questionnaire de satisfaction
*/
if (!class_exists("My_quiz"))
{
    class My_quiz
    {
        private $table;
        private $url;

        function __construct()
        {
            /* objet $wpdb de Wordpress permet de se connecter à la base de données
            * Il s'agit d'une variable globale, il faut donc la récupérer dans la
            * fonction avec la mention 'global'
            *
            * https://developer.wordpress.org/reference/classes/wpdb
            * https://apical.xyz/fiches/base_de_donnees_wordpress/La_classe_wpdb
            */
            global $wpdb;

            // $table vaudra 'wp_osm' si le préfixe de table configuré à l'installation est 'wp_' (celui par défaut)
            $this->table = $wpdb->prefix . 'quiz';

            // Définit l'url vers le fichier de classe du plugin
            $this->url = get_bloginfo("url") . "/wp-admin/options-general.php?page=my-osm/my-osm.php";
        } // -- __construct()

// Fonction déclenchée à l'activation du plugin
        function quiz_install()
        {
            global $wpdb;

            /* fonction get_var() :
            * exécute une requête SQL et retourne une variable
            * https://developer.wordpress.org/reference/classes/wpdb/get_var
            *
            * SHOW TABLES ne fonctionne pas avec des quotes obliques ``, il faut des droites ''
            * ici get_var() retourne NULL car la table n'existe pas
            */

            // On s'assure que la table n'existe pas déjà ('!=')
            if ($wpdb->get_var("SHOW TABLES LIKE '" . $this->table . "'") != $this->table) {
                /*
                * - Longitude: 11 chiffres max dont 8 max après la virgule (exemple: -180.00000001)
                * - Latitude max : 10 chiffres max dont 8 max après la virgule (exemple: -90.00000001)
                *
                * On devrait donc les stocker en type DECIMAL mais cela pose des problèmes de formlatage dans les requêtes préparées,
                * pour simplifier on les stocke comme chaînes en VARCHAR.
                *
                * https://qastack.fr/programming/15965166/what-is-the-maximum-length-of-latitude-and-longitude
                */
                $sql = "CREATE TABLE " . $this->table . "
                 (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                 `titre` VARCHAR(100) NOT NULL,
                 `question` VARCHAR(300) NOT NULL,  
                 `reponse` VARCHAR(300) NOT NULL,  
                 `resultat` INT(100) NOT NULL                        
                 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

                /* Inclusion du fichier 'upgrade.php' nécessaire car c'est lui qui contient le code
                * de la fonction dbDelta utilisée à la ligne suivante
                * ABSPATH = chemin absolu vers le répertoire du projet = 'C:\wamp\www\wordpress/'
                */
                if (require_once(ABSPATH . "wp-admin/includes/upgrade.php")) {
                    /*
                    * La fonction dbDelta() applique les changements de structure sur les objets de la base (tables, colonnes...)
                    * https://developer.wordpress.org/reference/functions/dbdelta/
                    * https://codex.wordpress.org/Creating_Tables_with_Plugins
                    * https://apical.xyz/fiches/donnees_personnalisees_wordpress/Ajouter_des_tables_personnalisees_dbDelta
                    */
                    dbDelta($sql);
                }
            }
        } // -- quiz_install()

        // Fonction déclenchée lors de la désactivation du plugin
        function quiz_uninstall()
        {
            global $wpdb;

            // On s'assure que la table existe
            // ici, get_var() retourne le nom de la table, par exemple 'wp_osm'
            if ($wpdb->get_var("SHOW TABLES LIKE '" . $this->table . "'") == $this->table) {
                // On la supprime
                // ATTENTION : pensez à sauvegarder les données au préalable si nécessaire
                $wpdb->query("DROP TABLE `" . $this->table . "`");
            }
        } // -- quiz_uninstall()

        function quiz_init()
        {
            if (function_exists('add_options_page')) {
                /* fonction add_options_page() : ajout d'un lien (sous-menu) dans le menu 'Réglages'
                * de l'administration
                * + fonction qui doit être lancée quand on clique sur ce lien, ici osm_admin_page()
                *
                * add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', int $position = null )
                *
                * - $page_title : balise <title> de la page (= aussi dans l'onglet du navigateur)
                * - $menu_title : libellé du lien dans le menu de l'administration
                * - $capability : rôle pour lequel la page d'admin est disponible
                * - $menu_slug : nom technique du lien dans le menu de l'administration
                * - $function : fonction à exécuter pour l'affichage des pages du plugins
                *               (attention l'argument passé est un tableau indiquant le plugin/l'instance - et la méthode)
                * - $position : position dans le menu d'admin, placé à la fin si non précisé.
                *
                * Ici $sPage vaut 'settings_page_my-osm/my-osm'
                */
                $sPage = add_options_page('My QuizEcran', 'My Quiz', 'administrator', __FILE__, array($this, 'quiz_admin_page'));

                /* Créer un hook 'load-settings_page_my-osm/my-osm'
                 * qui appelle la fonction quiz_ admin_header()
                 */
                add_action("load-" . $sPage, array($this, "quiz_admin_header"));
            }
        } // -- quiz_init()

        // Charge les CSS et JS nécessaires au plugin côté admin
        function quiz_admin_header()
        {
            // plugin_dir_url('css/admin-osm.css', __FILE__)) = 'http://localhost/wordpress/wp-content/plugins/css/'
            // plugins_url('css/admin-osm.css', __FILE__)) = 'http://localhost/wordpress/wp-content/plugins/my-osm/css/admin-osm.css'

            wp_register_style('my_quiz_css', plugins_url('css/admin-quiz.css', __FILE__));
            wp_enqueue_style('my_quiz_css');
            wp_enqueue_script('my_quiz_js', plugins_url('js/admin-quiz.js', __FILE__), array('jquery'));

            // Leafleft JS et CSS
            wp_enqueue_script('leaflet_js', "https://unpkg.com/leaflet@1.7.1/dist/leaflet.js");
            wp_enqueue_style('leaflet_css', "https://unpkg.com/leaflet@1.7.1/dist/leaflet.css");
        } // -- quiz_admin_header()

        // Gestion des pages/formulaires dans l'administration
        function quiz_admin_page()
        {
            // quiz = id d'une question
            if (isset($_GET["quiz"])) {
                require_once("templates/admin_quiz_detail.php");
            } else {
                require_once("templates/admin_home.php");
            }

            if ($_GET['action'] == 'createquest') {
                // +++ TODO : Sécuriser davantage les données provenant du formulaire : filter_var, type de données etc.) +++
                if (!empty(trim($_POST['Mg-title'])) && (!empty(trim($_POST['Mg-question']))) && (!empty(trim($_POST['Mg-reponse']))) && (!empty(trim($_POST['Mg-resultat'])))) {
                    if ($this->quiz_insertQuest($_POST['Mg-title'], $_POST['Mg-question'], $_POST['Mg-reponse'], $_POST['Mg-resultat'])) {
                        /*
                         * https://www.w3schools.com/howto/howto_js_redirect_webpage.asp
                         * https://christianelagace.com/wordpress/la-redirection-avec-wordpress
                         */
                        $sUrl = $this->url . "&msg=cre_ok";
                        echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                        exit;
                    } else {
                        $sUrl = $this->url . "&msg=cre_ko";
                        echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                        exit;
                    }
                } else {
                    $sUrl = $this->url . "&msg=empty_ko";
                    echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                    exit;
                }
            } else if ($_GET['action'] == 'updatequest') {
                if ((trim($_POST['Mg-title']) != '') && (trim($_POST['Mg-question']) != '') && (trim($_POST['Mg-reponse']) != '') && (trim($_POST['Mg-resultat']) != "") && (trim($_POST['Mg-id']) != '')) {
                    if ($this->quiz_updateQuest($_POST['Mg-id'], $_POST['Mg-title'], $_POST['Mg-question'], $_POST['Mg-reponse'], $_POST['Mg-resultat'])) {
                        $sUrl = $this->url . "&msg=upd_ok&map=" . $_POST["Mg-id"];
                        echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                        exit;
                    } else {
                        $sUrl = $this->url . "&msg=upd_ko&map=" . $_POST["Mg-id"];
                        echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                        exit;
                    }
                } else {
                    $sUrl = $this->url . "&msg=empty_ko&map=" . $_POST["Mg-id"];
                    echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                    exit;
                }
            } elseif ($_GET['action'] == 'deletequest') {
                if (trim($_POST['Mg-id']) != '') {
                    if ($this->quiz_deleteQuest($_POST['Mg-id'])) {
                        $sUrl = $this->url . "&msg=del_ok";
                        echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                        exit;
                    } else {
                        $sUrl = $this->url . "&msg=del_ko&quiz=" . $_POST["Mg-id"];
                        echo "<script>window.location.replace('" . $sUrl . "');</script>\n";
                        exit;
                    }
                }
            }
        } // -- quiz_admin_page()
    // Liste des quiz en base
        function quiz_getQuizList()
        {
            global $wpdb;

            // +++ TODO : prepare() vraiment nécessaire ??? +++
            $sql = $wpdb->prepare("SELECT * FROM ".$this->table, "");

            // https://developer.wordpress.org/reference/classes/wpdb/get_results
            return $wpdb->get_results($sql);
        } // -- quiz_getQuizList()

        // Sélection d'un quiz (via son id) en base
        function quiz_getQuiz($id)
        {
            global $wpdb;

            /* https://developer.wordpress.org/reference/classes/wpdb/prepare
             * %d = nombre entier (digit)
             */
            $sql = $wpdb->prepare("SELECT * FROM ".$this->table." WHERE id = %d LIMIT 1", $id);

            // https://developer.wordpress.org/reference/classes/wpdb/get_row
            $quiz = $wpdb->get_row($sql);

            return $quiz;
        } // -- quiz_getQuiz()

        // Insertion d'un quiz en base
        function quiz_insertQuiz($title, $quest, $rep, $result)
        {
            global $wpdb;

            /* https://developer.wordpress.org/reference/classes/wpdb/prepare
             *
             * - %s = chaîne (string)
             *
             * Marqueur pour un décimal :
             * https://wordpress.stackexchange.com/questions/385581/how-to-insert-a-value-to-decimal-type-field-using-wpdb-prepare
             */
            $sql = $wpdb->prepare("INSERT INTO ".$this->table." (titre, question, reponse, resultat) VALUES (%s, %s, %s, %d)", $title, $quest, $rep, $result);

            if ($wpdb->query($sql))
            {
                return true;
            }

            return false;
        } // -- quiz_insertQuiz()

        // Modification d'un quiz en base
        function quiz_updateQuiz($id, $title, $quest, $rep, $result)
        {
            global $wpdb;

            $sql = $wpdb->prepare("UPDATE ".$this->table."
                           SET
                           titre = %s,
                           question = %s,
                           reponse = %s
                           resultat = %s
                           WHERE id = %d",
                $title,
                $quest,
                $rep,
                $result,
                $id);

            if ($wpdb->query($sql))
            {
                return true;
            }

            return false;
        } // -- quiz_updateQuiz()

        // Suppression d'une question en base
        function quiz_deleteQuiz($id)
        {
            global $wpdb;

            $sql = $wpdb->prepare("DELETE FROM ".$this->table." WHERE id=%d LIMIT 1", $id);

            if ($wpdb->query($sql))
            {
                return true;
            }

            return false;
        } // -- quiz_deleteQuiz()
        // Register Navigation Menus


    } // -- classe
    function quiz_navigation_menus() {

        $locations = array(
            'Créer Quiz' => __( 'Créer un quiz' ),
            'Modif Quiz' => __( 'Modifier un quiz' ),
        );
        register_nav_menus( $locations );

    }
    add_action( 'init', 'quiz_navigation_menus' );
    // Register Sidebars
    function quiz_sidebars() {

        $args = array(
            'id'            => '1',
            'class'         => 'quiz_theme',
            'name'          => __( 'Navigation' ),
            'description'   => __( 'navigation' ),
        );
        register_sidebar( $args );

    }
    add_action( 'widgets_init', 'quiz_sidebars' );
    // Register Default Headers
    function quiz_default_headers() {

        $headers = array(
            'Quiz' => array(
                'description'   => __( 'theme Quiz' ),
                'url'           => '',
                'thumbnail_url' => '',
            ),
        );
        register_default_headers( $headers );

    }
    add_action( 'after_setup_theme', 'quiz_default_headers' );
    // Register Style
    function function_theme() {

        wp_register_style( 'quizTheme', '', array(  ), '', '' );
    }
    add_action( 'wp_enqueue_scripts', 'function_theme' );
    // Register Script
    function function_script() {

        wp_register_script( 'scriptQuiz', '', array(  ), '', false );
    }
    add_action( 'wp_enqueue_scripts', 'function_script' );
} // -- class_exists()
// Instanciation
if (class_exists("My_quiz"))
{
    $oMap = new My_quiz();
}
// Si instance créée
if (isset($oMap))
{
    // Sur l'action 'Activer le plugin', exécution de la fonction quiz_install()
    // register_activation_hook()
    register_activation_hook(__FILE__, array($oMap, 'quiz_install'));

    // Sur l'action 'Désinstaller le plugin', exécution de la fonction quiz_uninstall()
    register_deactivation_hook(__FILE__, array($oMap, 'quiz_uninstall'));

    //////////////////////////////////////////////////////////////////////////////////////////////

// Sur l'action 'Afficher le menu d'admin', exécution de la fonction `quiz_init()` de ce fichier
    add_action('admin_menu', array($oMap, 'quiz_init'));

// Ajout du chargement des scripts définis dans la fonction quiz_front_header()
    // add_action('wp_enqueue_scripts', array($oMap, 'quiz_front_header'));

} // -- fin si objet créé
// Schedule Cron Job Event
if ( ! wp_next_scheduled( 'quiz_cron_job_function_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'quiz_cron_job_function_hook' );
}

add_action('quiz_cron_job_function_hook', 'quiz_cron_job_function');

