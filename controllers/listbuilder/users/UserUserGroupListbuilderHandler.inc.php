<?php

/**
 * @file controllers/listbuilder/users/UserUserGroupListbuilderHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserUserGroupListbuilderHandler
 * @ingroup controllers_listbuilder_users
 *
 * @brief Class assign/remove mappings of user user groups
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

class UserUserGroupListbuilderHandler extends ListbuilderHandler {
	/** @var integer the user id for which to map user groups */
	var $_userId;

	/** @var $_context Context */
	var $_context;


	/**
	 * Constructor
	 */
	function UserUserGroupListbuilderHandler() {
		parent::ListbuilderHandler();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('fetch', 'fetchRow', 'fetchOptions', 'save')
		);
	}


	//
	// Setters and Getters
	//
	/**
	 * Set the user id
	 * @param $userId integer
	 */
	function setUserId($userId) {
		$this->_userId = $userId;
	}


	/**
	 * Get the user id
	 * @return integer
	 */
	function getUserId() {
		return $this->_userId;
	}


	/**
	 * Set the context
	 * @param $context Context
	 */
	function setContext(&$context) {
		$this->_context =& $context;
	}


	/**
	 * Get the context
	 * @return Context
	 */
	function &getContext() {
		return $this->_context;
	}


	//
	// Overridden parent class functions
	//
	/**
	 * @see GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		return array(
			'userId' => $this->getUserId()
		);
	}


	/**
	 * @see ListbuilderHandler::getOptions
	 * @param $includeDesignations boolean
	 */
	function getOptions($includeDesignations = false) {
		// Initialize the object to return
		$items = array(
			array(), // Names
			array() // Designations
		);

		// Fetch the user groups
		$context =& $this->getContext();
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userGroups =& $userGroupDao->getByContextId($context->getId());
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleNames = $roleDao->getRoleNames(true);

		// Assemble the array to return
		while (!$userGroups->eof()) {
			$userGroup =& $userGroups->next();
			$userGroupId = $userGroup->getId();
			$roleId = $userGroup->getRoleId();
			$roleName = __($roleNames[$roleId]);

			$items[0][$roleId][$userGroupId] = $userGroup->getLocalizedName();
			if ($includeDesignations) {
				$items[1][$userGroupId] = $userGroup->getLocalizedAbbrev();
			}

			// Add the optgroup label.
			$items[0][LISTBUILDER_OPTGROUP_LABEL][$roleId] = $roleName;

			unset($userGroup);
		}

		return $items;
	}


	/**
	 * Initialize the grid with the currently selected set of user groups.
	 */
	function loadData() {
		$context =& $this->getContext();
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userGroups =& $userGroupDao->getByUserId($this->getUserId(), $context->getId());

		return $userGroups;
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		// FIXME Validate user ID?

		// Load user-related translations.
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);

		$this->setUserId((int) $request->getUserVar('userId'));

		$this->setContext($request->getContext());
		parent::initialize($request);

		// Basic configuration
		$this->setTitle($request->getUserVar('title'));
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('roles');

		import('lib.pkp.controllers.listbuilder.users.UserGroupListbuilderGridCellProvider');
		$cellProvider = new UserGroupListbuilderGridCellProvider();

		// Name column
		$nameColumn = new ListbuilderGridColumn($this, 'name', 'common.name');
		$nameColumn->setCellProvider($cellProvider);
		$this->addColumn($nameColumn);

		// Designation column
		$designationColumn = new ListbuilderGridColumn($this,
			'designation',
			'common.designation',
			null,
			'controllers/listbuilder/listbuilderNonEditGridCell.tpl'
		);
		$designationColumn->setCellProvider($cellProvider);
		$this->addColumn($designationColumn);
	}

	/**
	 * @see GridHandler::getRowDataElement
	 * Get the data element that corresponds to the current request
	 * Allow for a blank $rowId for when creating a not-yet-persisted row
	 */
	function getRowDataElement(&$request, $rowId) {
		// fallback on the parent if a rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the $newRowId
		$newRowId = $this->getNewRowId($request);
		$userGroupId = $newRowId['name'];
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$context =& $this->getContext();
		$userGroup =& $userGroupDao->getById($userGroupId, $context->getId());
		return $userGroup;
	}
}

?>
