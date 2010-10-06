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
 * @package     Application - Module Review
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Wrapper around Opus_Document to prepare presentation.
 *
 * TODO split off base class, URLs are controller specific
 */
class Review_Model_DocumentAdapter {

    /**
     * Document identifier.
     * @var int
     */
    public $docId = null;

    /**
     * Wrapped document.
     * @var Opus_Document
     */
    public $document = null;

    /**
     * Zend_View for presentation.
     * @var Zend_View
     */
    private $view;

    /**
     * Array of author names.
     * @var array
     */
    private $authors = null;

    /**
     * Constructs wrapper around document.
     * @param Zend_View $view
     * @param int $id
     */
    public function __construct($view, $id) {
        $this->view = $view;
        $this->docId = $id;
        $this->document = new Opus_Document( (int) $id);
    }

    /**
     * Returns document identifier.
     * @return int
     */
    public function getDocId() {
        return htmlspecialchars($this->docId);
    }

    /**
     * Returns state of document or 'undefined'.
     * @return string
     */
    public function getState() {
        try {
            return htmlspecialchars($this->document->getServerState());
        }
        catch (Exception $e) {
            return 'undefined';
        }
    }

    /**
     * Returns first title for document.
     * @return string
     */
    public function getDocTitle() {
        $titles = $this->document->getTitleMain();
        if (count($titles) > 0) {
            return htmlspecialchars($titles[0]->getValue());
        }
        else {
            return $this->view->translate('document_no_title') . '(id = ' . $this->getDocId() . ')';
        }
    }

    /**
     * Returns document type.
     * @return string
     */
    public function getDocType() {
        try {
            return htmlspecialchars($this->document->getType());
        }
        catch (Exception $e) {
            return 'undefined';
        }
    }

    /**
     * Return published date.
     *
     * TODO or should it be getPublishedYear (?)
     */
    public function getPublishedDate() {
        try {
            $date = $this->document->getPublishedDate();

            if (empty($date)) {
                $date = $this->document->getPublishedYear();
            }

            return htmlspecialchars($date);
        }
        catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Returns frontdoor URL for document.
     * @return  url
     */
    public function getUrlFrontdoor() {
        $url_frontdoor = array(
            'module'     => 'frontdoor',
            'controller' => 'index',
            'action'     => 'index',
            'docId'      => $this->getDocId()
        );
        return $this->view->url($url_frontdoor, 'default', true);
    }

    /**
     * Returns URL for editing document.
     * @return url
     */
    public function getUrlEdit() {
        $url_edit = array(
            'module'     => 'admin',
            'controller' => 'documents',
            'action'     => 'edit',
            'id'         => $this->getDocId()
        );
        return $this->view->url($url_edit, 'default', true);
    }

    /**
     * Returns the correct delete url depending on document state.
     * @return url
     */
    public function getUrlDelete() {
        if ($this->getDocState() === 'deleted') {
            return $this->getUrlPermanentDelete();
        }
        else {
            return $this->getUrlSimpleDelete();
        }
    }

    /**
     * Returns URL for deleting document.
     * @return url
     */
    public function getUrlSimpleDelete() {
        $url_delete = array (
            'module'     => 'admin',
            'controller' => 'documents',
            'action'     => 'delete',
            'id'         => $this->getDocId()
        );
        return $this->view->url($url_delete, 'default', true);
    }

    /**
     * Returns URL for permanently deleting document.
     * @return url
     */
    public function getUrlPermanentDelete() {
        $url_permadelete = array (
            'module'     => 'admin',
            'controller' => 'documents',
            'action'     => 'permanentdelete',
            'id'         => $this->getDocId()
        );
        return $this->view->url($url_permadelete, 'default', true);
    }

    /**
     * Return list of authors.
     * @return array
     */
    public function getAuthors() {
        if ($this->authors) {
            return $this->authors;
        }

        try {
            $c = count($this->document->getPersonAuthor());
        }
        catch (Exception $e) {
            $c = 0;
        }

        $authors = array();

        for ($counter = 0; $counter < $c; $counter++) {
            $name = $this->document->getPersonAuthor($counter)->getName();

            $authors[$counter] = htmlspecialchars($name);
        }

        $this->authors = $authors;

        return $authors;
    }

    /**
     * Returns the search URL for an author.
     */
    public function getAuthorUrl() {
        throw new Exception('not implemented yet');
        $this->view->author[$runningIndex] = array();
        $this->view->url_author[$runningIndex] = array();
        for ($counter = 0; $counter < $c; $counter++) {
                $name = $d->getPersonAuthor($counter)->getName();
            $this->view->url_author[$runningIndex][$counter] = $this->view->url(
                array(
                    'module'        => 'search',
                    'controller'    => 'search',
                    'action'        => 'metadatasearch',
                    'author'        => $name
                ),
                null,
                true
            );
            $this->view->author[$runningIndex][$counter] = $name;
        }
    }

    /**
     * Returns the document state.
     * @return string
     */
    public function getDocState() {
        try {
            return $this->document->getServerState();
        }
        catch (Exception $e) {
            return 'undefined';
        }
    }

    /**
     * Returns true if the document is deleted.
     * @return boolean
     */
    public function isDeleted() {
        return ($this->getDocState() === 'deleted');
    }

    public function isPublished() {
        return ($this->getDocState() === 'published');
    }

    public function isUnpublished() {
        return ($this->getDocState() === 'unpublished');
    }

}
?>