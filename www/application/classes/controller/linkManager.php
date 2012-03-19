<?php defined('SYSPATH') or die('No direct script access.');

class Controller_LinkManager extends Controller_Base {
    
    public function action_index() {
        $mapId = $this->request->param('id', NULL);
        if($mapId != NULL) {
            $this->templateData['map'] = DB_ORM::model('map', array((int)$mapId));
            $this->templateData['nodes'] = DB_ORM::model('map_node')->getNodesByMap($mapId);

            $linksView = View::factory('labyrinth/link/view');
            $linksView->set('templateData', $this->templateData);

            $leftView = View::factory('labyrinth/labyrinthEditorMenu');
            $leftView->set('templateData', $this->templateData);

            $this->templateData['center'] = $linksView;
            $this->templateData['left'] = $leftView;
            unset($this->templateData['right']);
            $this->template->set('templateData', $this->templateData);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_editLinks() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        if($mapId != NULL and $nodeId != NULL) {
            $this->templateData['map'] = DB_ORM::model('map', array((int)$mapId));
            $this->templateData['node'] = DB_ORM::model('map_node', array($nodeId));
            $this->templateData['link_nodes'] = DB_ORM::model('map_node')->getNodesWithoutLink($nodeId);
            $this->templateData['linkStylies'] = DB_ORM::model('map_node_link_style')->getAllLinkStyles();
            $this->templateData['linkTypes'] = DB_ORM::model('map_node_link_type')->getAllLinkTypes();
            $this->templateData['images'] = DB_ORM::model('map_element')->getImagesByMap((int)$mapId);

            $editLinkView = View::factory('labyrinth/link/edit');
            $editLinkView->set('templateData', $this->templateData);

            $leftView = View::factory('labyrinth/labyrinthEditorMenu');
            $leftView->set('templateData', $this->templateData);

            $this->templateData['center'] = $editLinkView;
            $this->templateData['left'] = $leftView;
            unset($this->templateData['right']);
            $this->template->set('templateData', $this->templateData);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_addLink() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        if($_POST and $mapId != NULL and $nodeId != NULL) {
            DB_ORM::model('map_node_link')->addLink($mapId, $nodeId, $_POST);
            Request::initial()->redirect(URL::base().'linkManager/editLinks/'.$mapId.'/'.$nodeId);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_deleteLink() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        $deleteLinkId = $this->request->param('id3', NULL);
        if($mapId != NULL and $nodeId != NULL and $deleteLinkId != NULL) {
            DB_ORM::model('map_node_link', array((int)$deleteLinkId))->delete();
            Request::initial()->redirect(URL::base().'linkManager/editLinks/'.$mapId.'/'.$nodeId);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_editLink() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        $editLinkId = $this->request->param('id3', NULL);
        if($mapId != NULL and $nodeId != NULL and $editLinkId != NULL) {
            $this->templateData['map'] = DB_ORM::model('map', array((int)$mapId));
            $this->templateData['node'] = DB_ORM::model('map_node', array($nodeId));
            $this->templateData['editLink'] = DB_ORM::model('map_node_link', array((int)$editLinkId));
            $this->templateData['linkStylies'] = DB_ORM::model('map_node_link_style')->getAllLinkStyles();
            $this->templateData['images'] = DB_ORM::model('map_element')->getImagesByMap((int)$mapId);

            $editLinkView = View::factory('labyrinth/link/edit');
            $editLinkView->set('templateData', $this->templateData);

            $leftView = View::factory('labyrinth/labyrinthEditorMenu');
            $leftView->set('templateData', $this->templateData);

            $this->templateData['center'] = $editLinkView;
            $this->templateData['left'] = $leftView;
            unset($this->templateData['right']);
            $this->template->set('templateData', $this->templateData);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_updateLink() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        $updateLinkId = $this->request->param('id3', NULL);
        if($_POST and $mapId != NULL and $nodeId != NULL and $updateLinkId != NULL) {
            DB_ORM::model('map_node_link')->updateLink($updateLinkId, $_POST);
            Request::initial()->redirect(URL::base().'linkManager/editLinks/'.$mapId.'/'.$nodeId);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_updateLinkStyle() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        if($_POST and $mapId != NULL and $nodeId != NULL) {
            DB_ORM::model('map_node')->updateNode($nodeId, $_POST);
            Request::initial()->redirect(URL::base().'linkManager/editLinks/'.$mapId.'/'.$nodeId);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_updateLinkType() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        if($_POST and $mapId != NULL and $nodeId != NULL) {
            DB_ORM::model('map_node')->updateNode($nodeId, $_POST);
            Request::initial()->redirect(URL::base().'linkManager/editLinks/'.$mapId.'/'.$nodeId);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
    
    public function action_updateOrder() {
        $mapId = $this->request->param('id', NULL);
        $nodeId = $this->request->param('id2', NULL);
        if($_POST and $mapId != NULL and $nodeId != NULL) {
            $node = DB_ORM::model('map_node', array((int)$nodeId));
            if($node->link_type->name == 'ordered') {
                DB_ORM::model('map_node_link')->updateOrders($mapId, $nodeId, $_POST);
            } else if($node->link_type->name == 'random select one *') {
                DB_ORM::model('map_node_link')->updateProbability($mapId, $nodeId, $_POST);
            }
            
            Request::initial()->redirect(URL::base().'linkManager/editLinks/'.$mapId.'/'.$nodeId);
        } else {
            Request::initial()->redirect(URL::base());
        }
    }
}

?>

