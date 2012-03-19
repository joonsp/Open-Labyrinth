<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for map_nodes table in database 
 */
class Model_Leap_Map_Node extends DB_ORM_Model {

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
            
            'title' => new DB_ORM_Field_String($this, array(
                'max_length' => 200,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'text' => new DB_ORM_Field_Text($this, array(
                'nullable' => TRUE,
                'savable' => TRUE,
            )),
            
            'content' => new DB_ORM_Field_Text($this, array(
                'nullable' => TRUE,
                'savable' => TRUE,
            )),
            
            'type_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'probability' => new DB_ORM_Field_Boolean($this, array(
                'default' => FALSE,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'conditional' => new DB_ORM_Field_String($this, array(
                'max_length' => 500,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'conditional_message' => new DB_ORM_Field_String($this, array(
                'max_length' => 1000,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'info' => new DB_ORM_Field_String($this, array(
                'max_length' => 1000,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'link_style_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'link_type_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'priority_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'kfp' => new DB_ORM_Field_Boolean($this, array(
                'default' => FALSE,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'undo' => new DB_ORM_Field_Boolean($this, array(
                'default' => FALSE,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'end' => new DB_ORM_Field_Boolean($this, array(
                'default' => FALSE,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
            
            'x' => new DB_ORM_Field_Double($this, array(
                'nullable' => TRUE,
                'savable' => TRUE,
            )),
            
            'y' => new DB_ORM_Field_Double($this, array(
                'nullable' => TRUE,
                'savable' => TRUE,
            )),
            
            'rgb' => new DB_ORM_Field_String($this, array(
                'max_length' => 8,
                'nullable' => TRUE,
                'savable' => TRUE,
            )),
        );
        
        $this->relations = array(
            'map' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('map_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map',
            )),
            
            'type' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('type_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node_type',
            )),
            
            'link_style' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('link_style_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node_link_style',
            )),
            
            'link_type' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('link_type_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node_link_type',
            )),
            
            'priority' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('priority_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node_priority',
            )),
            
            'sections' => new DB_ORM_Relation_HasMany($this, array(
                'child_key' => array('node_id'),
                'child_model' => 'map_node_section_node',
                'parent_key' => array('id'),
            )),
            
            'links' => new DB_ORM_Relation_HasMany($this, array(
                'child_key' => array('node_id_1'),
                'child_model' => 'map_node_link',
                'parent_key' => array('id'),
            )),
            
            'counters' => new DB_ORM_Relation_HasMany($this, array(
                'child_key' => array('node_id'),
                'child_model' => 'map_node_counter',
                'parent_key' => array('id'),
            )),
        );
    }

    public static function data_source() {
        return 'default';
    }

    public static function table() {
        return 'map_nodes';
    }

    public static function primary_key() {
        return array('id');
    }
    
    public function getNodesByMap($mapId) {
        $builder = DB_SQL::select('default')->from($this->table())->where('map_id', '=', $mapId);
        $result = $builder->query();
        
        if($result->is_loaded()) {
            $nodes = array();
            foreach($result as $record) {
                $nodes[] = DB_ORM::model('map_node', array((int)$record['id']));
            }
            
            return $nodes;
        }
        
        return NULL;
    }
    
    public function getAllNode() {
        $builder = DB_SQL::select('default')->from($this->table());
        $result = $builder->query();
        
        if($result->is_loaded()) {
            $nodes = array();
            foreach($result as $record) {
                $nodes [] = DB_ORM::model('map_node', array((int)$record['id']));
            }
            
            return $nodes;
        }
        
        return NULL;
    }
    
    public function createNode($values) {
        $mapId = Arr::get($values, 'map_id', NULL);
        if($mapId != NULL) {
            $this->map_id = $mapId;
            $this->title = Arr::get($values, 'mnodetitle', '');
            $this->text = Arr::get($values, 'mnodetext', '');
            $this->info = Arr::get($values, 'mnodeinfo', '');
            $this->probability = Arr::get($values, 'mnodeprobability', FALSE);
            $this->link_style_id = Arr::get($values, 'linkstyle', 1);
            $this->priority_id = Arr::get($values, 'priority', 1);
            $this->undo = Arr::get($values, 'mnodeUndo', FALSE);
            $this->end = Arr::get($values, 'ender', FALSE);
            
            $this->save();
            
            return $this;
        }
        
        return NULL;
    }
    
    public function createDefaultRootNode($mapId) {
        if($mapId != NULL) {
            $this->map_id = $mapId;
            $this->title = 'Root Node';
            $this->text = '';
            $this->info = '';
            $this->probability = FALSE;
            $this->link_style_id = 1;
            $this->priority_id = 1;
            $this->type_id = 1;
            $this->undo = FALSE;
            $this->end = FALSE;
            
            $this->save();
        }
    }
    
    public function updateNode($nodeId, $values) {
        $this->id = $nodeId;
        $this->load();
        if($this) {
            $this->title = Arr::get($values, 'mnodetitle', $this->title);
            $this->text = Arr::get($values, 'mnodetext', $this->text);
            $this->info = Arr::get($values, 'mnodeinfo', $this->info);
            $this->probability = Arr::get($values, 'mnodeprobability', $this->probability);
            $this->link_style_id = Arr::get($values, 'linkstyle', $this->link_style_id);
            $this->link_type_id = Arr::get($values, 'linktype', $this->link_type_id);
            $this->priority_id = Arr::get($values, 'priority', $this->priority_id);
            $this->undo = Arr::get($values, 'mnodeUndo', $this->undo);
            $this->end = Arr::get($values, 'ender', $this->end);
            
            $this->save();
            
            return $this;
        }
        
        return NULL;
    }
    
    public function getRootNode() {
        $typeId = DB_ORM::model('map_node_type')->getTypeByName('root')->id;
        if($typeId != NULL) {
            $builder = DB_SQL::select('default')->from($this->table())->where('type_id', '=', $typeId);
            $result = $builder->query();

            if($result->is_loaded()) {
                return DB_ORM::model('map_node', array((int)$result[0]['id']));
            }
        }
        
        return NULL;
    }
    
    public function setRootNode($nodeId) {
        $rootNode = $this->getRootNode();
        if($rootNode != NULL) {
            $rootNode->type_id = DB_ORM::model('map_node_type')->getTypeByName('child')->id;
            $rootNode->save();
        }
        
        $this->id = $nodeId;
        $this->load();
        $this->type_id = DB_ORM::model('map_node_type')->getTypeByName('root')->id;
        $this->save();
    }
    
    public function addCondtional($nodeId, $values, $countOfConditional) {
        $this->id = $nodeId;
        $this->load();
        
        if($this) {
            $this->conditional_message = Arr::get($values, 'abs', '');
            $operator = Arr::get($values, 'operator', 'and');
            $conditional = '(';
            for($i = 0; $i < $countOfConditional - 1; $i++) {
                $conditional .= '['.Arr::get($values, 'el_'.$i, 0).']'.$operator;
            }
            
            if($countOfConditional > 0) {
                $conditional .=  '['.Arr::get($values, 'el_'.($countOfConditional - 1), 0).']';
            }
            $conditional .= ')';
            
            $this->conditional = $conditional;
            
            $this->save();
        }
    }
    
    public function updateAllNode($values) {
        $nodes = $this->getAllNode();
        foreach($nodes as $node) {
            $node->title = Arr::get($values, 'title_'.$node->id, $node->title);
            $node->text = Arr::get($values, 'text_'.$node->id, $node->text);
            
            $node->save();
        }
    }
    
    public function getAllNodesNotInSection() {
        $tableName = DB_ORM::model('map_node_section_node');
        $builder = DB_SQL::select('default')
                ->from($tableName::table())
                ->column('node_id');
        
        $allNodeInSectionresult = $builder->query();
        
        $ids = array();
        if($allNodeInSectionresult->is_loaded()) {
            foreach($allNodeInSectionresult as $record) {
                $ids[] = (int)$record['node_id'];
            }
        }
        $builder = NULL;
        if(count($ids) > 0) {
            $builder = DB_SQL::select('default')->from($this->table())->where('id', 'NOT IN', $ids);
        } else {
            $builder = DB_SQL::select('default')->from($this->table());
        }
        
        $result = $builder->query();
        
        if($result->is_loaded()) {
            $nodes = array();
            foreach($result as $record) {
                $nodes[] = DB_ORM::model('map_node', array((int)$record['id']));
            }
            
            return $nodes;
        }
        
        return NULL;
    }
    
    public function getNodesWithoutLink($nodeId) {
        $this->id = $nodeId;
        $this->load();
        
        if(count($this->links) > 0) {
            $ids = array();
            foreach($this->links as $link) {
                $ids[] = $link->node_2->id;
            }
            
            $builder = DB_SQL::select('default')
                    ->from($this->table())
                    ->where('id', 'NOT IN', $ids);
            $result = $builder->query();
            
            if($result->is_loaded()) {
                $nodes = array();
                foreach($result as $record) {
                    $nodes[] = DB_ORM::model('map_node', array((int)$record['id']));
                }
                
                return $nodes;
            }
            
            return NULL;
        } 
            
        return $this->getAllNode();
    }
    
    public function getCounter($counterId) {
        if(count($this->counters) > 0) {
            foreach($this->counters as $counter) {
                if($counter->counter_id == $counterId) {
                    return $counter;
                }
            }
            
            return NULL;
        }
        
        return NULL;
    }
}

?>