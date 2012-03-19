<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for map_node_links table in database 
 */
class Model_Leap_Map_Node_Link extends DB_ORM_Model {

    public function __construct() {
        parent::__construct();

        $this->fields = array(
            'id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
                'unsigned' => TRUE,
            )),
            
            'map_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'node_id_1' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
                'unsigned' => TRUE,
            )),
            
            'node_id_2' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'image_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'text' => new DB_ORM_Field_String($this, array(
                'max_length' => 500,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'order' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'probability' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
        );
        
        $this->relations = array(
            'map' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('map_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map',
            )),
            
            'node_1' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('node_id_1'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node',
            )),
            
            'node_2' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('node_id_2'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node',
            )),
            
            /*'image' => new DB_ORM_Relation_HasOne($this, array(
                'child_key' => array('image_id'),
                'parent_key' => array('id'),
                'child_model' => 'map_element',
            )),*/
        );
    }

    public static function data_source() {
        return 'default';
    }

    public static function table() {
        return 'map_node_links';
    }

    public static function primary_key() {
        return array('id');
    }
    
    public function addLink($mapId, $nodeId, $values) {
        $addNodeId = Arr::get($values, 'linkmnodeid', NULL);
        if($addNodeId != NULL) {
            $this->map_id = $mapId;
            $this->node_id_1 = $nodeId;
            $this->node_id_2 = $addNodeId;

            $this->image_id = Arr::get($values, 'linkImage', NULL);
            $this->text = Arr::get($values, 'linkLabel', '');
            
            $this->save();
            
            $linkDirection = Arr::get($values, 'linkDirection', 1);
            if($linkDirection == 2) {
                $builder = DB_SQL::select('default')
                        ->from($this->table())
                        ->where('node_id_1', '=', $this->node_id_2, 'AND')
                        ->where('node_id_2', '=', $this->node_id_1);
                $result = $builder->query();
                
                if(!$result->is_loaded()) {
                    if(count($result) <= 0) {
                        $links = DB_ORM::model('map_node_link');
                        $links->map_id = $this->map_id;
                        $links->node_id_1 = $this->node_id_2;
                        $links->node_id_2 = $this->node_id_1;
                        $links->image_id = $this->image_id;

                        $links->save();
                    }
                }
            }
        }
    }
    
    public function updateLink($linkId, $values) {
        $this->id = $linkId;
        $this->load();
        if($this) { 
            $this->image_id = Arr::get($values, 'linkImage', '');
            $this->text = Arr::get($values, 'linkLabel', '');
            
            $this->save();
            
            $linkDirection = Arr::get($values, 'linkDirection', 1);
            if($linkDirection == 2) {
                $builder = DB_SQL::select('default')
                        ->from($this->table())
                        ->where('node_id_1', '=', $this->node_id_2, 'AND')
                        ->where('node_id_2', '=', $this->node_id_1);
                $result = $builder->query();
                
                if(!$result->is_loaded()) {
                    if(count($result) <= 0) {
                        $links = DB_ORM::model('map_node_link');
                        $links->map_id = $this->map_id;
                        $links->node_id_1 = $this->node_id_2;
                        $links->node_id_2 = $this->node_id_1;
                        $links->image_id = $this->image_id;

                        $links->save();
                    } 
                }
            }
        }
    }
    
    public function updateOrders($mapId, $nodeId, $values) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('map_id', '=', $mapId, 'AND')
                ->where('node_id_1', '=', $nodeId);
        $result = $builder->query();
        
        if($result->is_loaded()) {
            foreach($result as $record) {
                $link = DB_ORM::model('map_node_link', array((int)$record['id']));
                $link->order = Arr::get($values, 'order_'.$link->id, 1);
                $link->save();
            }
        }
    }
    
    public function updateProbability($mapId, $nodeId, $values) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('map_id', '=', $mapId, 'AND')
                ->where('node_id_1', '=', $nodeId);
        $result = $builder->query();
        
        if($result->is_loaded()) {
            foreach($result as $record) {
                $link = DB_ORM::model('map_node_link', array((int)$record['id']));
                $link->probability = Arr::get($values, 'weight_'.$link->id, 1);
                $link->save();
            }
        }
    }
}

?>