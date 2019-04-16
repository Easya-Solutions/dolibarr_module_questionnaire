<?php
/*
 * Copyright (C) 2014 Florian HENRY <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file questionnaire/core/modules/questionnaire/modules_questionnaire.php
 * \ingroup questionnaire
 * \brief questionnaire for numbering questionnaire
 */

/**
 * \class ModelePDFReferenceLetters
 * \brief Absctart class for ReferenceLetters module
 */

dol_include_once('/questionnaire/class/commondocgeneratorquestionnaire.class.php');

abstract class ModeleQuestionnaire extends CommonDocGeneratorQuestionnaire
{
	public $error = '';
	public $pdf;
	public $instance_letter;
	public $outputlangs;

	/**
	 * Return list of active generation modules
	 *
	 * @param DoliDB $db handler
	 * @param string $maxfilenamelength length of value to show
	 * @return array of templates
	 */
	static function liste_modeles($db, $maxfilenamelength = 0) {
		global $conf;

		$type = 'questionnaire';
		$liste = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}

}

class ModelePDFQuestionnaire extends ModeleQuestionnaire
{

}


/**
 * Classe mere des modeles de numerotation des references de lead
 */
abstract class ModeleNumRefQuestionnaire
{
	var $error = '';

	/**
	 * Return if a module can be used or not
	 *
	 * @return boolean true if module can be used
	 */
	function isEnabled() {
		return true;
	}

	/**
	 * Renvoi la description par defaut du modele de numerotation
	 *
	 * @return string Texte descripif
	 */
	function info() {
		global $langs;
		$langs->load("referenceletters@referenceletters");
		return $langs->trans("NoDescription");
	}

	/**
	 * Renvoi un exemple de numerotation
	 *
	 * @return string Example
	 */
	function getExample() {
		global $langs;
		$langs->load("referenceletters");
		return $langs->trans("NoExample");
	}

	/**
	 * Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 * de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * @return boolean false si conflit, true si ok
	 */
	function canBeActivated() {
		return true;
	}

	/**
	 *
	 * Renvoi prochaine valeur attribuee
	 *
	 * @param int $fk_user
	 * @param string $element_type
	 * @param Societe $objsoc
	 * @param reference letter $referenceletters_element
	 * @return string
	 */
	function getNextValue() {
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * Renvoi version du module numerotation
	 *
	 * @return string Valeur
	 */
	function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development')
			return $langs->trans("VersionDevelopment");
			if ($this->version == 'experimental')
				return $langs->trans("VersionExperimental");
				if ($this->version == 'dolibarr')
					return DOL_VERSION;
					return $langs->trans("NotAvailable");
	}
}
