<?php
/**
 * Open Labyrinth [ http://www.openlabyrinth.ca ]
 *
 * Open Labyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Labyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Open Labyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2012 Open Labyrinth. All Rights Reserved.
 *
 */
defined('SYSPATH') or die('No direct script access.');

/**
 * Model for map_counter_rule_relations table in database 
 */
class Model_Leap_Map_Node_Counter extends DB_ORM_Model {

    public function __construct() {
        parent::__construct();

        $this->fields = array(
            'id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
                'unsigned' => TRUE,
            )),
            
            'node_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'counter_id' => new DB_ORM_Field_Integer($this, array(
                'max_length' => 11,
                'nullable' => FALSE,
            )),
            
            'function' => new DB_ORM_Field_String($this, array(
                'max_length' => 20,
                'nullable' => FALSE,
                'savable' => TRUE,
            )),
        );
        
        $this->relations = array(
            'node' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('node_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map_node',
            )),
            
            'counter' => new DB_ORM_Relation_BelongsTo($this, array(
                'child_key' => array('counter_id'),
                'parent_key' => array('id'),
                'parent_model' => 'map_counter',
            )),
        );
    }

    public static function data_source() {
        return 'default';
    }

    public static function table() {
        return 'map_node_counters';
    }

    public static function primary_key() {
        return array('id');
    }
    
    
    public function getAllNodeCounters() {
        $builder = DB_SQL::select('default')->from($this->table());
        $result = $builder->query();
        
        if($result->is_loaded()) {
            $counters = array();
            foreach($result as $record) {
                $counters[] = DB_ORM::model('map_node_counter', array((int)$record['id']));
            }
            
            return $counters;
        }
        
        return NULL;
    }
    
    public function getNodeCounter($nodeId, $counterId) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('node_id', '=', $nodeId, 'AND')
                ->where('counter_id', '=', $counterId);
        $result = $builder->query();
        
        if($result->is_loaded()) {   
            return DB_ORM::model('map_node_counter', array((int)$result[0]['id']));
        }
        
        return NULL;
    }
    
    public function addNodeCounter($nodeId, $counterId, $function) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('node_id', '=', $nodeId, 'AND')
                ->where('counter_id', '=', $counterId);
        
        $result = $builder->query();
        
        if(!$result->is_loaded()){
            $this->node_id = $nodeId; 
            $this->counter_id = $counterId;
            $this->function = $function;

            $this->save();
            $this->reset();
        } else {
            $this->updateNodeCounter($nodeId, $counterId, $function);
        }
    }

    public function deleteNodeCounter($nodeId, $counterId) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('node_id', '=', $nodeId, 'AND')
                ->where('counter_id', '=', $counterId);
        $result = $builder->query();
        
        if($result->is_loaded()) {
            DB_ORM::model('map_node_counter', array((int)$result[0]['id']))->delete();
        }
    }
    
    public function deleteAllNodeCounterByCounter($counterId) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('counter_id', '=', $counterId);
        $result = $builder->query();
        
        if($result->is_loaded()) {
            foreach($result as $record) {
                DB_ORM::model('map_node_counter', array((int)$record['id']))->delete();
            }
        }
    }
    
    public function updateNodeCounter($nodeId, $counterId, $function) {
        $builder = DB_SQL::select('default')
                ->from($this->table())
                ->where('node_id', '=', $nodeId, 'AND')
                ->where('counter_id', '=', $counterId);
        
        $result = $builder->query();
        
        if($result->is_loaded()){
            $this->id = $result[0]['id'];
            $this->load();
            
            if($this) {
                $this->function = $function;

                $this->save();
            }
        }
    }
    
    public function updateNodeCounterByNode($nodeId, $map_id, $values) {
        $counters = DB_ORM::model('map_counter')->getCountersByMap($map_id);
        if(count($counters) > 0) {
            foreach($counters as $counter) {
                $function = Arr::get($values, 'cfunc_'.$counter->id, NULL);
                $nodeCounter = DB_ORM::model('map_node_counter')->getNodeCounter($nodeId, $counter->id);
                if($nodeCounter != NULL) {
                    $this->updateNodeCounter($nodeId, $counter->id, $function);
                } else {
                    $this->addNodeCounter($nodeId, $counter->id, $function);
                }
            }
        }
    }
    
    public function updateNodeCounters($values, $counterId = NULL, $mapId = NULL) {
        $counters = DB_ORM::model('map_node_counter')->getAllNodeCounters();
        if(count($counters) > 0) {
            foreach($counters as $counter) {
                if($counterId != NULL) {
                    if($counterId == $counter->counter_id) {
                        $inputName = 'nc_'.$counter->node_id.'_'.$counter->counter_id;
                        $counter->function = Arr::get($values, $inputName, $counter->function);
                        $counter->save();
                    }
                } else {
                    $inputName = 'nc_'.$counter->node_id.'_'.$counter->counter_id;
                    $counter->function = Arr::get($values, $inputName, $counter->function);
                    $counter->save();
                }
                unset($values[$inputName]);
            }

            foreach($values as $key => $value){
                if ((strpos($key, 'nc_') !== false) & ($value != NULL)){
                    $array = explode('_', $key);
                    $this->addNodeCounter($array[1], $array[2], $value);
                }
            }
        } else {
            if($mapId != NULL) {
                $nodes = DB_ORM::model('map_node')->getNodesByMap($mapId);
                $counters = DB_ORM::model('map_counter')->getCountersByMap($mapId);
                if(count($counters) > 0) {
                    foreach($counters as $counter) {
                        if(count($nodes) > 0) {
                            foreach($nodes as $node) {
                                $newMapCounter = DB_ORM::model('map_node_counter');
                                $newMapCounter->counter_id = $counter->id;
                                $newMapCounter->node_id = $node->id;
                                
                                $newMapCounter->save();
                            }
                        }
                    }
                }
                
                $this->updateNodeCounters($values, $counterId);
            }
        }
    }
}

?>