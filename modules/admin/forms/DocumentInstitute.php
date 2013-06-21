<?php
/*
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
 * @package     Module_Admin
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Unterformular fuer Institute.
 */
class Admin_Form_DocumentInstitute extends Admin_Form_AbstractModelSubForm {
    
    const ROLE_PUBLISHER = 'publisher';
    
    const ROLE_GRANTOR = 'grantor';
    
    const ELEMENT_DOC_ID = 'Id';
    
    const ELEMENT_INSTITUTE = 'Institute';
    
    private $__role;
    
    public function __construct($role, $options = null) {
        $this->__role = $role;
        parent::__construct($options);
    }
    
    public function init() {
        parent::init();
        
        $element = new Form_Element_Hidden(self::ELEMENT_DOC_ID);
        $this->addElement($element);
        
        $element = $this->createInstituteSelect(self::ELEMENT_INSTITUTE);
        $element->setRequired(true);
        $element->addValidator('Int');
        $this->addElement($element);
    }

    public function populateFromModel($link) {
        $linkId = $link->getId();
        $this->getElement(self::ELEMENT_DOC_ID)->setValue($linkId[0]);
        $this->getElement(self::ELEMENT_INSTITUTE)->setValue($link->getModel()->getId());
    }
    
    /**
     * 
     * @param type $model
     * 
     * TODO handle unknown ID
     */
    public function updateModel($link) {
        $instituteId = $this->getElement(self::ELEMENT_INSTITUTE)->getValue();
        $institute = new Opus_DnbInstitute($instituteId);
        $link->setModel($institute);
    }
    
    public function getModel() {
        $docId = $this->getElement(self::ELEMENT_DOC_ID)->getValue();
        
        if (empty($docId)) {
            $linkId = null;
        }
        else {
            $instituteId = $this->getElement(self::ELEMENT_INSTITUTE)->getValue();
            $linkId = array($docId, $instituteId, $this->__role);
        }
        
        try {
            $link = new Opus_Model_Dependent_Link_DocumentDnbInstitute($linkId);
        }
        catch (Opus_Model_NotFoundException $omnfe) {
            $link = new Opus_Model_Dependent_Link_DocumentDnbInstitute();
        }
        
        $this->updateModel($link);
        
        return $link;
    }

    /**
     * 
     * @param type $name
     * @return \Zend_Form_Element_Select
     * 
     * TODO move?
     * TODO Unit Test
     */
    public function createInstituteSelect($name = 'Institute') {
        $select = new Zend_Form_Element_Select($name);
        
        switch ($this->__role) {
            case self::ROLE_PUBLISHER:
                $options = Opus_DnbInstitute::getPublishers();
                break;
            case self::ROLE_GRANTOR:
                $options = Opus_DnbInstitute::getGrantors(); 
                break;
            default:
                // TODO should never happen
                $options = null;
                break;
        }
        
        foreach ($options as $option) {
            $select->addMultiOption($option->getId(), $option->getName());
        }
                
        return $select;
    }
    
    public function loadDefaultDecorators() {
        parent::loadDefaultDecorators();
        
        $this->removeDecorator('Fieldset');
    }
    
}
