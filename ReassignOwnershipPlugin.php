<?php

class ReassignOwnershipPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
			'initialize',
            'admin_items_panel_fields',
			'admin_items_show_sidebar',
            'admin_items_batch_edit_form',
            'admin_collections_panel_fields',
			'admin_collections_show_sidebar',
            'before_save_item',
            'items_batch_edit_custom',
            'before_save_collection'
            );
			
	/**
    * Add the translations.
    */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages');
    }
    
    public function setUp()
    {
        parent::setUp();
        if(plugin_is_active('ExhibitBuilder')) {
           // $this->_hooks[] = 'before_save_exhibit';
        }
    }
    
    public function hookAdminItemsPanelFields($args)
    {
        $this->_echoChangePanel($args);
    }
	
	public function hookAdminItemsShowSidebar($args)
    {
        $this->_echoShowPanel($args);
    }
    
    public function hookAdminItemsBatchEditForm($args)
    {
        $this->_echoBatchPanel($args);
    }

    public function hookAdminCollectionsPanelFields($args)
    {
        $this->_echoChangePanel($args);
    }
	
	public function hookAdminCollectionsShowSidebar($args)
    {
        $this->_echoShowPanel($args);
    }

    public function hookBeforeSaveItem($args)
    {
        $this->_reassignOwnership($args);
    }
    
    public function hookItemsBatchEditCustom($args)
    {
		if (isset($args['custom']['reassign_ownership_id']) && $args['custom']['reassign_ownership_id'] != '') {
			$newOwnerId = $args['custom']['reassign_ownership_id'];
			$item = $args['item'];
			$newOwner = $this->_db->getTable('User')->find($newOwnerId);
			$item->setOwner($newOwner);
			$item->save();
		}
    }   

    public function hookBeforeSaveCollection($args)
    {
        $this->_reassignOwnership($args);
    }
    
    private function _reassignOwnership($args)
    {
        if (isset($args['post']['reassign_ownership_id'])) {
            $newOwnerId = $args['post']['reassign_ownership_id'];
            $record = $args['record'];
            $newOwner = $this->_db->getTable('User')->find($newOwnerId);
            $record->setOwner($newOwner);
        }
    }
    
    private function _echoShowPanel($args)
    {
        $record = isset($args['item']) ? $args['item'] : $args['collection'];
        $owner = $record->getOwner();
        if ($owner) {
            echo "<div class='info panel'>\n";
            echo "<h4>". __('Owner'). "</h4>\n";
			echo "<div><p>" . $owner->name . "</p></div>\n";
			echo "</div>\n";
        }
    }

    private function _echoChangePanel($args)
    {
        $view = $args['view'];
        $record = $args['record'];
        $user = current_user();
        if( $record->exists() && ( ($user->id == $record->isOwnedBy($user)) || $user->role == 'admin' || $user->role == 'super' )) {
            echo "<div id='reassign_ownership-form' class='field'>";
            echo "<label for='reassign_ownership_id'>" . __('Owner') .  "</label>";
            echo "<div class='inputs'>";
            echo $view->formSelect('reassign_ownership_id', $record->getOwner()->id, array('id'=>'reassign-ownership'), get_table_options('User', null, array('sort_field' => 'name')));
            echo "</div>";
            echo "</div>";
        }
    }

    private function _echoBatchPanel($args)
    {
        $view = $args['view'];
        echo "<div class='field'>";
        echo "<label class='two columns alpha' for='custom[reassign_ownership_id]'>" . __('Owner') .  "</label>";
        echo "<div class='inputs five columns omega'>";
        $ownershipOptions = get_table_options('User', null, array('sort_field' => 'name'));
        $ownershipOptions = array('' => __('Select Below')) + $ownershipOptions;
        echo $view->formSelect('custom[reassign_ownership_id]', null, array(), $ownershipOptions);
        echo "</div>";
        echo "</div>";
    }
}
