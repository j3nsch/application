<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @package     Module_Import
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
class LicenceImport
{
	/**
	 * Imports Collection data to Opus4
	 *
	 * @param Strring $data XML-String with classifications to be imported
	 * @return array List of documents that have been imported
	 */
	public function __construct($data)
	{
		// Analyse the data to find out which classification systems there are
		// and which converter methods should be used
		$doclist = $data->getElementsByTagName('table_data');
		foreach ($doclist as $document) 
		{
			$tempdoc = new DOMDocument;
            $tempdoc->loadXML($data->saveXML($document));
            $tablename = $tempdoc->getElementsByTagName('table_data')->Item(0)->getAttribute('name');
            if ($tablename === 'license_de') {
            	// Works!
            	echo "Importing Licenses";
            	$mappingTable = $this->readLicenses($tempdoc);
            	echo "done!\n";
            	// store classification system
            }
		}
	}

	/**
	 * transfers any OPUS3-conform classification System into an array
	 *
	 * @param DOMDocument $data XML-Document to be imported
	 * @return array List of documents that have been imported
	 */
	protected function transferOpus3Licence($data)
	{
		$classification = array();
		$doclist = $data->getElementsByTagName('row');
		foreach ($doclist as $document) 
		{
			$lic = new Opus_Licence();
            foreach ($document->getElementsByTagName('field') as $field) {
           		if ($field->getAttribute('name') === 'shortname') $shortname = $field->nodeValue;
           		if ($field->getAttribute('name') === 'longname') $lic->setNameLong($field->nodeValue);
           		if ($field->getAttribute('name') === 'desc_text') $lic->setDescText($field->nodeValue);
           		if ($field->getAttribute('name') === 'active') $lic->setActive($field->nodeValue);
           		if ($field->getAttribute('name') === 'sort') $lic->setSortOrder($field->nodeValue);
           		if ($field->getAttribute('name') === 'pod_allowed') $lic->setPodAllowed($field->nodeValue);
           		if ($field->getAttribute('name') === 'language') $lic->setLanguage($field->nodeValue);
           		if ($field->getAttribute('name') === 'link') $lic->setLinkLicence($field->nodeValue);
           		if ($field->getAttribute('name') === 'link_tosign') $lic->setLinkSign($field->nodeValue);
           		if ($field->getAttribute('name') === 'desc_html') $lic->setDescMarkup($field->nodeValue);
                if ($field->getAttribute('name') === 'mime_type') $lic->setMimeType($field->nodeValue);
                if ($field->getAttribute('name') === 'logo') $lic->setLinkLogo($field->nodeValue);
                if ($field->getAttribute('name') === 'comment') $lic->setCommentInternal($field->nodeValue);
            }
            $licenses[$shortname] = $lic;
		}
		return $licenses;
	}

	/**
	 * Converts Bk-classification to Opus4
	 *
	 * @param DOMDocument $data XML-Document to be imported
	 * @return array List of documents that have been imported
	 */
	protected function readLicenses($data)
	{
		$licenses = $this->transferOpus3Licence($data);
		
		// Store the licenses and create a mapping file for migration
		$fp = fopen('../workspace/licenseMapping.txt', 'w');
		foreach ($licenses as $key => $licence) {
			$id = $licence->store();
			fputs($fp, $key . ' ' . $id . "\n"); 
		}
		fclose($fp);
	}
}