<?php

/**
 * LICENSE:
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @package		Bright Game Panel V2
 * @version		0.1
 * @category	Systems Administration
 * @author		warhawk3407 <warhawk3407@gmail.com> @NOSPAM
 * @copyright	Copyleft 2015, Nikita Rousseau
 * @license		GNU General Public License version 3.0 (GPLv3)
 * @link		http://www.bgpanel.net/
 */

if ( !class_exists('BGP_Controller')) {
	trigger_error('Controller_Config -> BGP_Controller is missing !');
}

/**
 * Admin Configuration Controller
 */

class BGP_Controller_Config extends BGP_Controller {

	function __construct( )	{
	
		// Call parent constructor
		parent::__construct( basename(__DIR__) );
	}

	/**
	 * Update System Configuration
	 *
	 * @param string $panelName
	 * @param string $panelUrl
	 * @param string $userTemplate
	 * @param optional bool $maintenanceMode
	 *
	 * @author Nikita Rousseau
	 */
	public function updateSysConfig( $panelName, $panelUrl, $userTemplate, $maintenanceMode = FALSE )
	{
		$form = array (
			'panelName' 	=> $panelName,
			'panelUrl' 		=> $panelUrl,
			'userTemplate' 	=> $userTemplate
		);

		$errors			= array();  	// array to hold validation errors
		$data 			= array(); 		// array to pass back data
		
		$dbh = Core_DBH::getDBH();		// Get Database Handle

		// Get templates
		$templates = parse_ini_file( CONF_TEMPLATES_INI );
		$templates = array_flip(array_values($templates));
		
		// validate the variables ======================================================

		$v = new Valitron\Validator( $form );

		$rules = [
				'required' => [
					['panelName'],
					['panelUrl'],
					['userTemplate']
				],
				'regex' => [
					['panelName', "/^([-a-z0-9_ -])+$/i"]
				],
				'url' => [
					['panelUrl']
				],
				'in' => [
					['userTemplate', $templates]
				]
			];

		$labels = array(
				'panelName' 	=> 'Panel Name',
				'panelUrl' 		=> 'Panel URL',
				'userTemplate' 	=> 'User Template'
			);

		$v->rules( $rules );
		$v->labels( $labels );
		$v->validate();

		$errors = $v->errors();

		// Apply the form ==============================================================

		if (empty($errors))
		{
			// Database update

			$db_data['panel_name']			= $form['panelName'];
			$db_data['system_url']			= $form['panelUrl'];
			$db_data['user_template'] 		= $form['userTemplate'];
			$db_data['maintenance_mode']	= '0';

			if ( !empty($maintenanceMode) ) {
				$db_data['maintenance_mode'] = '1';
			}

			foreach ($db_data as $key => $value) {

				$sth = $dbh->prepare( "UPDATE " . DB_PREFIX . "config SET value = :" . $key . " WHERE setting = '" . $key . "';" );
				$sth->bindParam( ':' . $key, $value );
				$sth->execute();
			}
		}

		// return a response ===========================================================
		
		// response if there are errors
		if (!empty($errors)) {
		
			// if there are items in our errors array, return those errors
			$data['success'] = false;
			$data['errors']  = $errors;

			$data['msgType'] = 'warning';
			$data['msg'] = T_('Bad Settings!');
		}
		else {

			$data['success'] = true;
		}
		
		// return all our data to an AJAX call
		return $data;
	}
}
