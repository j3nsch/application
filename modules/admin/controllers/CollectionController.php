<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @author      Sascha Szott <szott@zib.de>
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Controller for administration of collections.
 *
 */
class Admin_CollectionController extends Controller_Action {

    /**
     * Setup theme path
     *
     * @return void
     */
    public function init() {
        parent::init();
        Opus_Collection::setThemesPath(APPLICATION_PATH . '/public/layouts');
    }

    public function indexAction() {
        $this->_redirectToAndExit('index', '', 'collectionroles');
    }

    /**
     * Create a new collection instance
     *
     * @return void
     */
    public function newAction() {
        $id = $this->getRequest()->getParam('id');
        if (is_null($id)) {
            $this->_redirectToAndExit('index', array('failure' => 'id parameter is missing'), 'collectionroles');
            return;
        }
        $type = $this->getRequest()->getParam('type');
        if (is_null($type)) {
            $this->_redirectToAndExit('index', array('failure' => 'type parameter is missing'), 'collectionroles');
            return;
        }

        $collectionModel = new Admin_Model_Collection();
        $this->view->form = $this->getForm($collectionModel->getObject(), $id, $type);
    }

    /**
     * Edit a collection instance
     *
     * @return void
     */
    public function editAction() {
        $collectionModel = new Admin_Model_Collection($this->getRequest()->getParam('id', ''));
        $this->view->form = $this->getForm($collectionModel->getObject());
    }

    /**
     * Moves a collection within the same hierarchy level.
     *
     * @return void
     */
    public function moveAction() {
        try {
            $collectionModel = new Admin_Model_Collection($this->getRequest()->getParam('id', ''));
            $parentId = $collectionModel->move($this->getRequest()->getParam('pos'));
            $this->_redirectTo('show', $this->view->translate('admin_collections_move', $collectionModel->getName()), 'collection', 'admin', array('id' => $parentId));
        }
        catch (Admin_Model_Exception $e) {
            $this->_redirectToAndExit('index', array('failure' => $e->getMessage()), 'collectionroles');
        }
    }

    private function changeCollectionVisibility($visibility) {
        try {
            $collectionModel = new Admin_Model_Collection($this->getRequest()->getParam('id', ''));
            $id = $collectionModel->setVisiblity($visibility);
            $this->_redirectTo('show', $this->view->translate('admin_collections_changevisibility', $collectionModel->getName()), 'collection', 'admin', array('id' => $id));
        }
        catch (Application_Exception $e) {
            $this->_redirectToAndExit('index', array('failure' => $e->getMessage()), 'collectionroles');
        }
    }

    public function hideAction() {
        $this->changeCollectionVisibility(false);
    }

    public function unhideAction() {
        $this->changeCollectionVisibility(true);
    }

    public function deleteAction() {
        try {
            $collectionModel = new Admin_Model_Collection($this->getRequest()->getParam('id', ''));
            $name = $collectionModel->getName();
            $returnId = $collectionModel->delete();
            $message = $this->view->translate('admin_collections_delete', $name);
            $this->_redirectTo('show', $message, 'collection', 'admin', array ('id' => $returnId));
        }
        catch (Application_Exception $e) {
            $this->_redirectToAndExit('index', array('failure' => $e->getMessage()), 'collectionroles');
        }
    }

    public function showAction() {
        $roleId = $this->getRequest()->getParam('role');
        $id = null;
        if (!is_null($roleId)) {
            $collectionRole = new Opus_CollectionRole($roleId);
            $rootCollection = $collectionRole->getRootCollection();
            if (is_null($rootCollection)) {
                // collection role without root collection: create a new root collection
                $rootCollection = $collectionRole->addRootCollection();
                $collectionRole->store();
            }
            $id = $rootCollection->getId();
        }
        else {
            $id = $this->getRequest()->getParam('id', '');
        }

        $collectionModel = null;
        try {
            $collectionModel = new Admin_Model_Collection($id);
        }
        catch (Application_Exception $e) {
            $this->_redirectToAndExit('index', array('failure' => $e->getMessage()), 'collectionroles');
            return;
        }

        $collection = $collectionModel->getObject();
        $this->view->breadcrumb = array_reverse($collection->getParents());
        $this->view->collections = $collection->getChildren();
        $this->view->collection_id = $collection->getId();

        $role = $collection->getRole();
        $this->view->role_id    = $role->getId();
        $this->view->role_name  = $role->getDisplayName();
    }

    public function createAction() {
        if (!$this->getRequest()->isPost()) {
            return $this->_redirectToAndExit('index', '', 'collectionroles');
        }

        $data = $this->_request->getPost();
        $collectionModel = new Admin_Model_Collection($this->getRequest()->getParam('oid'));
        $collection = $collectionModel->getObject();

        $form_builder = new Form_Builder();
        $form_builder->buildModelFromPostData($collection, $data['Opus_Model_Filter']);
        $form = $form_builder->build($this->__createFilter($collection));

        if (!$form->isValid($data)) {
            if ($collection->isNewRecord()) {
                $form->setAction($this->view->url(array('action' => 'create', 'id' => $this->getRequest()->getParam('id'), 'type' => $this->getRequest()->getParam('type'))));
                $this->view->title = 'admin_collections_collection_new';
            }
            else {
                $form->setAction($this->view->url(array('action' => 'create', 'oid' => $collection->getId(), 'id' => $this->getRequest()->getParam('id'), 'type' => $this->getRequest()->getParam('type'))));
                $this->view->title = 'admin_collections_collection_edit';
            }
            $this->view->form = $form;
            return;
        }
        if (true === $collection->isNewRecord()) {
            $id = $this->getRequest()->getParam('id');
            $type = $this->getRequest()->getParam('type');
            if (is_null($id)) {
                return $this->_redirectToAndExit('index', array('failure' => 'id parameter is missing'), 'collectionroles');
            }
            if (is_null($type)) {
                return $this->_redirectToAndExit('index', array('failure' => 'type parameter is missing'), 'collectionroles');
            }
            if ($type === 'child') {
                $refCollection = new Opus_Collection($id);
                $refCollection->addFirstChild($collection);
                $refCollection->store();
                $message = $this->view->translate('admin_collections_add', $collectionModel->getName());
                return $this->_redirectTo('show', $message, 'collection', 'admin', array('id' => $collection->getId()));
            }
            if ($type === 'sibling') {
                $refCollection = new Opus_Collection($id);
                $refCollection->addNextSibling($collection);
                $refCollection->store();
                $message = $this->view->translate('admin_collections_add', $collectionModel->getName());
                return $this->_redirectTo('show', $message, 'collection', 'admin', array('id' => $collection->getId()));
            }
            return $this->_redirectToAndExit('index', array('failure' => 'type paramter invalid'), 'collectionroles');
        }
        // nur Änderungen        
        $collection->store();
        $message = $this->view->translate('admin_collections_edit', $collectionModel->getName());
        $parents = $collection->getParents();
        if (count($parents) === 1) {
            return $this->_redirectTo('show', $message, 'collection', 'admin', array('id' => $collection->getRoleId()));
        }
        return $this->_redirectTo('show', $message, 'collection', 'admin', array('id' => $parents[1]->getId()));
    }

    /**
     * Assign a document to a collection (used in document administration)
     *
     * @return void
     */
    public function assignAction() {
        $documentId = $this->getRequest()->getParam('document');
        if (is_null($documentId)) {
            return $this->_redirectToAndExit('index', array('failure' => 'document parameter missing'), 'collectionroles');
        }

        if ($this->getRequest()->isPost() === true) {
            // Zuordnung des Dokuments zur Collection ist erfolgt
            $collectionModel = new Admin_Model_Collection($this->getRequest()->getParam('id', ''));
            $collectionModel->addDocument($documentId);

            return $this->_redirectToAndExit(
                    'edit',
                    $this->view->translate('admin_document_add_collection_success', $collectionModel->getName()),
                    'document', 'admin', array('id' => $documentId, 'section' => 'collections'));
        }

        $collectionId = $this->getRequest()->getParam('id');
        if (is_null($collectionId)) {
            // Einsprungseite anzeigen
            $this->prepareAssignStartPage($documentId);
        }
        else {
            // Collection ausgewählt: Subcollections anzeigen
            $this->prepareAssignSubPage($documentId, $collectionId);
        }
        
        $this->view->documentAdapter = new Util_DocumentAdapter($this->view, $documentId);
    }

    private function prepareAssignStartPage($documentId) {
        $collectionRoles = Opus_CollectionRole::fetchAll();
        $this->view->collections = array();
        foreach ($collectionRoles as $collectionRole) {
            $rootCollection = $collectionRole->getRootCollection();
            if (is_null($rootCollection)) {
                // create empty root collection
                $rootCollection = $collectionRole->addRootCollection();
                $rootCollection->store();
            }

            array_push($this->view->collections,
                    array(
                        'id' => $rootCollection->getId(),
                        'name' => $this->view->translate('default_collection_role_' . $collectionRole->getDisplayName()),
                        'hasChildren' => $rootCollection->hasChildren()));
        }
        $this->view->documentId = $documentId;
        $this->view->breadcrumb = array();
        $this->view->role_name = $collectionRole->getDisplayName();
    }

    private function prepareAssignSubPage($documentId, $collectionId) {
        $collection = new Opus_Collection($collectionId);
        $children = $collection->getChildren();
        if (count($children) === 0) {
            // zurück zur Ausgangsansicht
            $this->_redirectToAndExit('assign', array('failure' => 'specified collection does not have any subcollections'), 'collection', 'admin', array('document' => $documentId));
            return;
        }
        $this->view->collections = array();
        foreach ($children as $child) {
            array_push($this->view->collections,
                    array(
                        'id' => $child->getId(),
                        'name' => $child->getNumberAndName(),
                        'hasChildren' => $child->hasChildren()));
        }
        $this->view->documentId = $documentId;
        $this->view->breadcrumb = array_reverse($collection->getParents());
        $this->view->role_name = $collection->getRole()->getDisplayName();
    }

    private function getForm($collection, $id = null, $type = null) {
        $form_builder = new Form_Builder();
        $collectionForm = $form_builder->build($this->__createFilter($collection));
        if ($collection->isNewRecord()) {
            $collectionForm->setAction($this->view->url(array('action' => 'create', 'id' => $id, 'type' => $type)));
        }
        else {
            $collectionForm->setAction($this->view->url(array('action' => 'create', 'oid' => $collection->getId(), 'id' => $id, 'type' => $type)));
        }
        return $collectionForm;
    }

    /**
     * Returns a filtered representation of the collection.
     *
     * @param  Opus_Model_Abstract $collection The collection to be filtered.
     * @return Opus_Model_Filter The filtered collection.
     */
    private function __createFilter(Opus_Model_Abstract $collection) {
        $filter = new Opus_Model_Filter();
        $filter->setModel($collection);
        $filter->setBlacklist(array('Parents', 'Children', 'PendingNodes', 'RoleId', 'RoleName', 'RoleDisplayFrontdoor', 'RoleVisibleFrontdoor', 'PositionKey', 'PositionId', 'SortOrder'));
        $filter->setSortOrder(array('Name', 'Number', 'Visible'));
        return $filter;
    }
}