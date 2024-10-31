<?php
/*
Plugin Name: PluginViadeo
Plugin URI: http://www.dimgoto.com/open-source/wordpress/plugins/viadeo
Description: Affichage des profils Viadéo. Ce plugin permet l'affichage des profils Viadéo enregistrés dans la page créée dès activation. La désactivation supprime la page d'affichage mais conserve les profiles, vous devez supprimer le plugin pour une suppression complète.
Version: 1.0.0
Author: Dimitri GOY
Author URI: http://www.dimgoto.com
*/

/*  Copyright 2009  DimGoTo  (email : wordpress@dimgoto.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Plugin Viadéo.
 *
 * Affichage des fiches profil Viadéo renseignés en configuration, dans une page automatiquement créée par le plugin.
 *
 * @package Plugins
 * @subpackage Viadeo
 * @version 1.0.0
 * @author Dimitri GOY
 * @copyright 2009 - DimGoTo
 */
class PluginViadeo {

	const KEY_PROFILES = '-profiles';

	protected $_pluginname = null;
	protected $_plugindir = null;
	protected $_langdir = null;
	protected $_jsdir = null;
	protected $_cssdir = null;
	protected $_pluginfile = null;
	protected $_shortcode = null;

	function __construct() {

		$this->_pluginname = get_class($this);
		$this->_pluginfile = plugin_basename(__FILE__);
		$this->_plugindir = '/' . PLUGINDIR . '/' . str_replace('\\', '/', dirname(plugin_basename(__FILE__)));
		$this->_langdir = $this->_plugindir . '/lang';
		$this->_jsdir = $GLOBALS['wpbase'] . $this->_plugindir . '/js';
		$this->_cssdir = get_bloginfo('wpurl') . $GLOBALS['wpbase'] . $this->_plugindir . '/css';
		$this->_shortcode = strtolower($this->_pluginname) . '-view';



		$this->register();
		add_action('init', array($this, 'run'));

	}

	protected function register() {

		load_plugin_textdomain($this->_pluginname, null, $this->_langdir);

		if (is_admin()) {
			register_activation_hook($this->_pluginfile, array($this, 'activate'));
			register_deactivation_hook($this->_pluginfile, array($this, 'deactivate'));

			if (function_exists('register_uninstall_hook')) {
	    		register_uninstall_hook($this->_pluginfile, array($this, 'uninstall'));
			}
		} else {
			add_shortcode($this->_shortcode, array($this, 'page'));
		}

	}

	public function activate() {

		$post = array(
			'comment_status' 	=> 'closed',
			'ping_status' 			=> 'closed',
			'post_author' 			=> get_user_option('id'),
			'post_content' 		=> '['. $this->_shortcode .']',
			'post_name' 			=> 'viadeo',
			'post_status' 			=> 'publish',
			'post_title' 				=> 'Viadéo',
			'post_type' 				=> 'page'
		);

		$page_ID = wp_insert_post($post);
		$shortcodes = get_option('pages_shortcodes');
		$shortcodes[$this->_shortcode] = $page_ID;
		update_option('pages_shortcodes', $shortcodes);

		add_option($this->_pluginname . self::KEY_PROFILES, '');
	}

	public function deactivate() {

		$shortcodes = get_option('pages_shortcodes');
		wp_delete_post($shortcodes[$this->_shortcode]);
		unset($shortcodes[$this->_shortcode]);
		update_option('pages_shortcodes', $shortcodes);
	}

	public function uninstall() {

		delete_option($this->_pluginname . self::KEY_PROFILES);
	}

	public function run() {

		if (is_admin()) {

			add_action('wp_ajax_viadeo_add', array($this, 'ajax_add'));
			add_action('wp_ajax_viadeo_delete', array($this, 'ajax_delete'));
			add_action('wp_ajax_viadeo_update', array($this, 'ajax_update'));
			add_action('wp_ajax_viadeo_list', array($this, 'ajax_list'));
			add_action('admin_menu', array($this, 'menu'));

			if (isset($_GET)
			&& isset($_GET['page'])
			&& $_GET['page'] == $this->_pluginname) {
				add_thickbox();

				wp_enqueue_script($this->_pluginname . '-js', $this->_jsdir . '/' . $this->_pluginname . 'Admin.js', array(), false);
				wp_enqueue_style($this->_pluginname . '-css', $this->_cssdir . '/' . $this->_pluginname . 'Admin.css', array(), false, 'screen');

				add_action('admin_head', array($this, 'head'));
				add_filter('contextual_help', array($this, 'help'));
			}

		} else if ($post_ID == $page_ID) {
			wp_enqueue_script('jquery');
			wp_enqueue_script($this->_pluginname.'-js', $this->_jsdir . '/' . $this->_pluginname . 'Page.js', array(), false);
			wp_enqueue_style($this->_pluginname . '-css', $this->_cssdir . '/' . $this->_pluginname . 'Page.css', array(), false, 'screen');

			add_action('wp_head', array($this, 'head'));
		}

	}

	public function head() {

		$shortcodes = get_option('pages_shortcodes');
		$page_ID = $shortcodes[$this->_shortcode];

		$html = '<script type="text/javascript">';

		if (is_admin()
		&& isset($_GET)
		&& isset($_GET['page'])
		&& $_GET['page'] == $this->_pluginname) {

			$html .= 'var viadeo_update_selectone = "' . __('Veuillez sélectionner un seul profil!', $this->_pluginname) . '";';
			$html .= 'var viadeo_update_select = "' . __('Veuillez sélectionner un profil pour modification!', $this->_pluginname) . '";';
			$html .= 'var viadeo_required = "' . __('Veuillez saisir les champs obligatoires!', $this->_pluginname) . '";';
			$html .= 'var viadeo_select_any = "' . __('Veuillez sélectionner au moins un profil!', $this->_pluginname) . '";';
			$html .= 'var viadeo_empty = "' . __('Aucun profil.', $this->_pluginname) . '";';
		}

		$html .= 'var viadeo_idprefix = "' . $this->_pluginname . '";';

		$html .= '</script>';

		echo $html;
	}

	public function menu() {
		add_submenu_page('plugins.php',
				__('Viad&eacute;o', $this->__pluginname),
				__('Viad&eacute;o', $this->__pluginname),
				'edit_users',
				$this->_pluginname,
				array($this, 'control'));
	}

	public function help($context = '') {

		global $plugin_page;

		$help = '';
		if (strlen($plugin_page) > 1) {
			$folder = substr($plugin_page,0,strrpos($plugin_page,'/')+1);
			$racine =  '../' . $folder . 'doc/' . $this->_pluginname . '-';

			$fileDoc = $racine . WPLANG .'.html';
			$fileDocFr = $racine . 'fr_FR.html';
			if (file_exists($fileDoc)) {
				$help .= file_get_contents($fileDoc);
			} else if (file_exists($fileDocFr)) {
				$help .= file_get_contents($fileDocFr);
			}
		}
		$help .= $context;

		return $help;
	}

	public function control() {
		$html = '<div class="wrap" id="' . $this->_pluginname . '">';
		$html .= '<div id="icon-options-general" class="icon32"><br /></div>';
		$html .= '<h2>' . __('Configuration Viadéo', $this->pluginname) . '</h2>';
		$html .= '<br/>';

		$html .= '<span class="description">' . __('Consultez l&acute;aide contextuelle concernant la documentation de ce plugin.', $this->_pluginname) . '</span><br/>';

		$html .= '<div id="notice" class="error"><p></p></div>';
		$html .= '<div id="message" class="updated fade" style="background-color: rgb(255, 251, 204);"><p></p></div>';

		$html .= '<form id="' . $this->_pluginname . '" name="' . $this->_pluginname . '" method="post" action="">';

		$html .= '<h3 class="title">' . __('Liste des profils', $this->_pluginname) . '</h3>';
		$html .= '<table class="widefat fixed" cellspacing="0">';
		$html .= '<thead>';
		$html .= '<tr class="thead">';
		$html .= '<th id="cb" class="manage-column column-cb check-column" style="" scope="col">';
		$html .= '<input type="checkbox" id="' . $this->_pluginname . '-all1" name="' . $this->_pluginname . '-all1"/>';
		$html .= '</th>';
		$html .= '<th id="name" class="manage-column" style="" scope="col">' . __('Nom, Pr&eacute;nom', $this->_pluginname) . '</th>';
		$html .= '<th id="url" class="manage-column" style="" scope="col">' . __('Url du profil', $this->_pluginname) . '</th>';
		$html .= '<th id="comment" class="manage-column" style="" scope="col">' . __('Commentaire', $this->_pluginname) . '</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tfoot>';
		$html .= '<tr class="thead">';
		$html .= '<th id="col-cb" class="manage-column column-cb check-column" style="" scope="col">';
		$html .= '<input type="checkbox" id="' . $this->_pluginname . '-all2" name="' . $this->_pluginname . '-all2"/>';
		$html .= '</th>';
		$html .= '<th id="col-name" class="manage-column column-name" style="" scope="col">' . __('Nom, Pr&eacute;nom', $this->_pluginname) . '</th>';
		$html .= '<th id="col-url" class="manage-column column-url" style="" scope="col">' . __('Url du profil', $this->_pluginname) . '</th>';
		$html .= '<th id="col-comment" class="manage-column column-comment" style="" scope="col">' . __('Commentaire', $this->_pluginname) . '</th>';
		$html .= '</tr>';
		$html .= '</tfoot>';
		$html .= '<tbody id="' . $this->_pluginname . '-profiles" class="list:user user-list">';
		$html .= '</tbody>';
		$html .= '</table>';

		$html .= '<div class="tablenav">';
		$html .= '<div class="alignleft actions">';
		$html .= '<select name="' . $this->_pluginname . '-action-list" id="' . $this->_pluginname . '-action-list">';
		$html .= '<option selected="selected" value="all">' . __('Action globale', $this->_pluginname) . '</option>';
		$html .= '<option value="delete">' . __('Supprimer', $this->_pluginname) .' </option>';
		$html .= '</select>';
		$html .= '<input id="' . $this->_pluginname . '-doaction-list" class="button-secondary action" type="submit" name="' . $this->_pluginname . '-doaction-list" value="' . __('Appliquer', $this->_pluginname) . '"/>';
		$html .= '</div>';
		$html .= '<br class="clear"/>';
		$html .= '</div>';

		$html .= '<h3 class="title">' . __('Ajouter/Modifier un profil', $this->_pluginname) . '</h3>';
		$html .= '<span class="description">' . __('Pour modifier un profil s&eacute;lectionnez le dans la liste', $this->_pluginname) . '</span>';
		$html .= '<table class="form-table">';
		$html .= '<tbody>';
		$html .= '<tr class="form-field form-required">';
		$html .= '<th scope="row">';
		$html .= '<label for="viadeo-name">' . __('Nom, pr&eacute;nom', $this->_pluginname);
		$html .= '<span class="description">(' . __('Obligatoire', $this->_pluginname) . ')</span></label>';
		$html .= '<input id="' . $this->_pluginname . '-id" type="hidden" value="" name="' . $this->_pluginname . '-id"/>';
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<input id="' . $this->_pluginname . '-name" type="text" aria-required="true" value="" name="' . $this->_pluginname . '-name"/>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr class="form-field form-required">';
		$html .= '<th scope="row">';
		$html .= '<label for="' . $this->_pluginname . '-url">' . __('Url du profil', $this->_pluginname);
		$html .= '<span class="description">(' . __('Obligatoire', $this->_pluginname) . ')</span></label>';
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<input id="' . $this->_pluginname . '-url" type="text" value="" name="' . $this->_pluginname . '-url"/>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr class="form-field">';
		$html .= '<th scope="row">';
		$html .= '<label for="' . $this->_pluginname . '-comment">' . __('Commentaire', $this->_pluginname) . '</label>';
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<textarea id="' . $this->_pluginname . '-comment" type="text" value="" name="' . $this->_pluginname . '-comment"></textarea>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '<p class="submit">';
		$html .= '<input id="' . $this->_pluginname . '-add" class="button-primary" type="submit" value="' . __('Ajouter un profil', $this->_pluginname) . '" name="' . $this->_pluginname . '-add"/>';
		$html .= '<input id="' . $this->_pluginname . '-update" class="button-primary" type="submit" value="' . __('Modifier le profil', $this->_pluginname) . '" name="' . $this->_pluginname . '-update"/>';
		$html .= '</p>';

		$html .= '</form>';

		$html .= '<div id="' . $this->_pluginname . '-overlay" class="TB_overlayBG">';
		$html .= '</div>';

		$html .= '</div>';

		echo $html;
	}

	public function page() {

		$html = '<div id="notice" class="error"><p></p></div>';
		$html .= '<div id="message" class="updated fade" style="background-color: rgb(255, 251, 204);"><p></p></div>';
		$html .= '<div class="description">' . __('La fiche du profil est affichée dans une fenêtre différente pour éviter tout conflit d&acute;autorité!', $this->_pluginname) . '</div>';
		$html .= '<br/>';
		$html .= '<div id="' . $this->_pluginname . '-profiles">';
		$profiles = get_option($this->_pluginname . self::KEY_PROFILES);
		foreach ($profiles as $profile) {
			$html .= '<li>';
			$html .= '<a href="#" id="' . $this->_pluginname . '-profile-' . $profile['id'] . '">' . $profile['name'] . '</a>';
			$html .= '<input type="hidden" name="' . $this->_pluginname . '-url-' . $profile['id'] . '" id="' . $this->_pluginname . '-url-' . $profile['id'] . '" value="' . $profile['url'] . '" />';
			$html .= '<p>' . $profile['comment'] . '</p>';
			$html .= '</li>';
		}
		$html .= '</div>';

		return $html;
	}

	public function ajax_add() {

		if (isset($_POST)
		&& !empty($_POST)) {
			if (!isset($_POST['name'])
			|| empty($_POST['name'])
			|| !$this->validateName($_POST['name'])) {
				header("Status: 400 Bad Request", true, 400);
				die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'name'));
			} else if (!isset($_POST['url'])
			|| empty($_POST['url'])
			|| !$this->validateURL($_POST['url'])) {
				header("Status: 400 Bad Request", true, 400);
				die(sprintf(__('Param&egrave;tre: %s invalide!', $this->_pluginname), 'url'));
			} else {
				if (isset($_POST['comment'])
				&& !empty($_POST['comment'])
				&& !$this->validateComment($_POST['comment'])) {
					header("Status: 400 Bad Request", true, 400);
					die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'comment'));
				} else {
					$profile = array(
										'id'				=> time(),
										'name'		=> $_POST['name'],
										'url'			=> $_POST['url'],
										'comment'	=> $_POST['comment']);

					$profiles = get_option($this->_pluginname . self::KEY_PROFILES);
					$exists = false;
					if (!empty($profiles)) {
						foreach ($profiles as $p) {
							if ($profile['id'] == $p['id']
							|| $profile['name'] == $p['name']) {
								header("Status: 400 Bad Request", true, 400);
								die(__('Ce profil semble déjà exister, veuillez vérifier!', $this->_pluginname));
								$exists = true;
								break;
							}
						}
					} else {
						$profiles = array();
					}
					if ($exists == false) {
						array_push($profiles, $profile);
						update_option($this->_pluginname . self::KEY_PROFILES, $profiles);
						echo sprintf(__('%s ajouté avec succès', $this->_pluginname), $_POST['name']);
					}
				}
			}
		} else {
			header("Status: 400 Bad Request", true, 400);
			die(__('Param&egrave;tres invalides!', $this->_pluginname));
		}
	}

	public function ajax_delete() {

		if (isset($_POST)
		&& !empty($_POST)) {
			if (!isset($_POST['ids'])
			|| empty($_POST['ids'])) {
				header("Status: 400 Bad Request", true, 400);
				die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'ids'));
			} else {
				$ids = explode(',', $_POST['ids']);
				$profiles = get_option($this->_pluginname . self::KEY_PROFILES);
				$updates = array();
				foreach ($profiles as $profile) {
					if (!in_array($profile['id'], $ids)) {
						array_push($updates, $profile);
					}
				}
				update_option($this->_pluginname . self::KEY_PROFILES, $updates);
				echo __('Liste des profils mise à jour avec succès', $this->_pluginname);
			}
		} else {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètres invalides!', $this->_pluginname));
		}
	}

	public function ajax_update() {

		if (isset($_POST)
		||  !empty($_POST)) {
			if (!isset($_POST['id'])
			|| empty($_POST['id'])) {
				header("Status: 400 Bad Request", true, 400);
				die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'id'));
			} else if (!isset($_POST['name'])
			|| empty($_POST['name'])
			|| !$this->validateName($_POST['name'])) {
				header("Status: 400 Bad Request", true, 400);
				die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'name'));
			} else if (!isset($_POST['url'])
			|| empty($_POST['url'])
			|| !$this->validateURL($_POST['url'])) {
				header("Status: 400 Bad Request", true, 400);
				die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'url'));
			} else {
				if (isset($_POST['comment'])
				&& !empty($_POST['comment'])
				&& !$this->validateComment($_POST['comment'])) {
					header("Status: 400 Bad Request", true, 400);
					die(sprintf(__('Paramètre: %s invalide!', $this->_pluginname), 'comment'));
				} else {
					$profile = array(
										'id'				=> $_POST['id'],
										'name'		=> $_POST['name'],
										'url'			=> $_POST['url'],
										'comment'	=> $_POST['comment']);

					$profiles = get_option($this->_pluginname . self::KEY_PROFILES);
					$updates = array();
					if (empty($profiles)) {
						header("Status: 400 Bad Request", true, 400);
						die(__('Aucun profil, veuillez le créer!', $this->_pluginname));
					} else  {
						foreach ($profiles as $p) {
							if ($p['id'] == $profile['id']) {
								array_push($updates, $profile);
							} else {
								array_push($updates, $p);
							}
						}
						update_option($this->_pluginname . self::KEY_PROFILES, $updates);
						echo sprintf(__('%s mise à jour avec succès!', $this->_pluginname), $_POST['name']);
					}
				}
			}
		} else {
			header("Status: 400 Bad Request", true, 400);
			die(__('Paramètres invalides!', $this->_pluginname));
		}

	}

	public function ajax_list() {

		$profiles = get_option($this->_pluginname . self::KEY_PROFILES);
		sort($profiles, SORT_STRING);

		header('Content-type: application/json');
		echo json_encode($profiles);
	}

	private function validateName($name) {

		$pattern = '/^([a-zA-Z \-\']{1,45})$/';
		return $this->validate($pattern, trim($name));
	}
	private function validateURL($url) {

		$pattern = '/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/';
		return $this->validate($pattern, $url);
	}
	private function validateComment($comment) {

		$pattern = '/^([a-zA-Z0-9 \-\',:\.éèêëàçùûüôöîï@]{1,500})$/mesi';
		return $this->validate($pattern, trim($comment));
	}
	private function validate($pattern, $value) {

		return preg_match($pattern, $value);
	}
}
new PluginViadeo();
?>