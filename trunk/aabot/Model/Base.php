<?php
/**
 * This is the base DataObject Class.
 *
 * @package default
 * @author Sam
 **/
abstract class Model_Base {
	
	private $db_handle;
	protected $model_name;
	protected $model_id_name;
	protected $id = null;
	private $base_attribute_definitions = array(
		'created' => null,
		'modified' => null,
		'active' => null
	);
	protected $relations = array();
    /*
     * ex: array( 'group' => array('id' => '1'))
     */
    protected $submitted_habtm_data;
	/**
	 * defined in the implementing class thusly
	 * 	protected $attribute_definitions = array(
	 *	  'username' => null,
	 *	  'password' => null,
	 *	  'xmpp_jid' => null,
	 *	  'sms_number' => null,
	 *	  'active' => null
	 *  );
	 */
	protected $attribute_definitions = array();
	private $field_values = array();
	private $field_value_comparitors = array();
	
	/**
	 * Allow an injected db_handle, else create on the fly
	 */
	public function __construct($db_handle=null) {
		if ($db_handle===null && $config = ENV::load_config_file('db_conf')) {
			$db_handle = new Model_DBHandle($config);
		} else {
			ENV::$log->error(__METHOD__.' Unable to load db config file');
		}
		$this->db_handle = $db_handle;
		$this->attribute_definitions = array_merge($this->attribute_definitions, $this->base_attribute_definitions);
		if (isset($this->relations['belongs_to'])) {
			$belongs_to = explode(',',$this->relations['belongs_to']);
			foreach ($belongs_to as $relation) {
				$this->attribute_definitions[strtolower($relation).'_id'] = 'int';
			}
		}
		$this->model_name = strtolower(str_replace('Model_','',get_class($this)));
		$this->model_id_name = $this->model_name.'_id';
	}
	/**
	 * 
	 * ex usage: $this->payload->users = $profile->User(array('user_id'=>'username'));
	 */
	public function __call($name, $arguments) {
		if (isset($this->relations['belongs_to'][$name])) {
			$return_structure = array_get_else($arguments,0);
			$where_conditions = array_get_else($arguments,1);
			$result = null;
			// SELECT b, d FROM foo WHERE `b` = :b AND `d` = :d
			$find_statement = 
				$this->build_select_clause($return_structure).' FROM '.$name;
			ENV::$log->debug(__METHOD__.' built find QUERY: '.$find_statement);
			try {
				$statement = $this->db_handle->prepare($find_statement);
//				foreach ($this->field_values as $field_name => $field_value) {
//					$statement->bindValue(':'.$field_name, $field_value);
//				}
//				if ($this->id !== null) {
//					$statement->bindValue(':'.$this->model_id_name, $this->id);
//				}
				$statement->execute();
				$result = $statement->fetchAll(PDO::FETCH_ASSOC);
			} catch (Exception $e) {
				ENV::$log->error(__METHOD__.'-'.$e->getMessage());
			}
		}
		return $this->apply_return_structure($return_structure,$result);
		
	}
	/**
	 * set reacts to 2 parameter signatures:
	 * set($field_name, $comparison_operator, $field_value)
	 * OR
	 * set($field_name, $field_value)
	 */
	public function set() {
		// determine the param signature we are in and set vars accordingly
		$args = func_get_args();
		$comparison_operator = '=';
		$field_name = $args[0];
		$field_value = func_num_args()==3?func_get_arg(2):func_get_arg(1);
		if (func_num_args()==3) { // ($field_name, $comparison_operator, $field_value)
			$field_value = func_get_arg(2);
			$comparison_operator = func_get_arg(1);
		} else { // ($field_name, $field_value)
			$field_value = func_get_arg(1);
		}
		// look to see if setting id
		if ($field_name==$this->model_id_name) {
			$this->id = $field_value;
		} else if (key_exists($field_name,$this->attribute_definitions)) {
			$this->field_values[$field_name] = $field_value;
			$this->field_value_comparitors[$field_name] = $comparison_operator;
		}
	}

	/**
	 * 
	 * @param array $submitted_data {optional, we could have set the 
	 * various field values on the model prior to calling this method
	 */
	public function save(array $submitted_data = null) {
		$rows_affected = null;
		$this->set_field_values($submitted_data);
		if ($this->have_data_to_save()) {
			$save_statement = $this->is_new_model() 
				? $this->build_insert_statement()
				: $this->build_update_statement();
			ENV::$log->debug(__METHOD__.' built save QUERY: '.$save_statement);
			$statement = null;
			try {
				if( ! $statement = $this->db_handle->prepare($save_statement)) {
					ENV::$log->error(__METHOD__.' - $statement::prepare failed for query: '
						.$save_statement."\n".print_r($this->db_handle->errorInfo(),1));
				}
				foreach ($this->field_values as $field_name => $field_value) {
					 $statement->bindValue(':'.$field_name, $field_value);
				}
				if ( ! $this->is_new_model()) {
					$statement->bindValue(':'.$this->model_id_name, $this->id);
				}
				$rows_affected = $statement->execute();
                if ($rows_affected===false) {
					ENV::$log->error(__METHOD__.' - $statement->execute() failed for query: '
						.$save_statement."\n".print_r($statement->errorInfo(),1));
				} else if($this->is_new_model()) {
                    $this->id = $this->db_handle->last_insert_id();
                }
			} catch (Exception $e) {
				ENV::$log->error(__METHOD__.'-'.$e->getMessage());
			}
            $this->save_habtm_relations();
		} else {
			ENV::$log->error(__METHOD__. ' Valid model id not supplied as param and not currently set on $this');
		}
		return $rows_affected;
	}
    /**
     * First do select and then insert the array subtraction
     * of the id's in submitted data and the return rows from select
     *
     * this expects a wholistic set of the related models
     *
     */
    protected function save_habtm_relations() {

        if($this->submitted_habtm_data) {

            foreach ($this->submitted_habtm_data as $model => $data) {
                $existing_relations = $this->execute_find_statment(
                    "SELECT `{$model}_id` FROM `{$this->join_table($model)}` "
                    ."WHERE `{$this->model_id_name}` = :{$this->model_id_name}", 
                    array($this->model_id_name => $this->id),
                    array($model.'_id')
                );
                $existing_relations = $existing_relations ? $existing_relations : array();
                $new_ids_to_save = array();
                $ids_to_remove = array();
                $new_ids_to_save = array_diff($data['id'], $existing_relations);
                $ids_to_remove = array_diff($existing_relations, $data['id']);
                if(ENV::$log->debug()) {
                    ENV::$log->debug(__METHOD__." `{$this->model_name}` will add these new `{$model}` relations ["
                        .implode(', ',$new_ids_to_save)."]");
                    ENV::$log->debug(__METHOD__." `{$this->model_name}` will remove these invalid existing `{$ids_to_remove}` relations ["
                        .implode(', ',$existing_relations)."]");
                }
                if($new_ids_to_save) {
                    $save_statement = $this->build_habtm_join_insert_statement($model);
                    try {
                        if( ! $statement = $this->db_handle->prepare($save_statement)) {
                            ENV::$log->error(__METHOD__.' - $statement::prepare failed for query: '
                                .$save_statement."\n".print_r($this->db_handle->errorInfo(),1));
                        }
                        $statement->bindValue(':'.$this->model_id_name, $this->id);
                        foreach ($new_ids_to_save as $related_model_id) {
                            $statement->bindValue(':'.$model.'_id', $related_model_id);
                            $rows_affected = $statement->execute();
                            if ($rows_affected===false) {
                                ENV::$log->error(__METHOD__.' - $statement->execute() failed for query: '
                                    .$save_statement."\n".print_r($statement->errorInfo(),1));
                            }
                        }
                    } catch (Exception $e) {
                        ENV::$log->error(__METHOD__.'-'.$e->getMessage());
                    }
                }
                if($ids_to_remove) {
                    $delete_statement = $this->build_delete_statement($this->join_table($model),array($this->model_id_name,$model.'_id'));
                    try {
                        if( ! $statement = $this->db_handle->prepare($delete_statement)) {
                            ENV::$log->error(__METHOD__.' - $statement::prepare failed for query: '
                                .$delete_statement."\n".print_r($this->db_handle->errorInfo(),1));
                        }
                        $statement->bindValue(':'.$this->model_id_name, $this->id);
                        foreach ($ids_to_remove as $related_model_id) {
                            $statement->bindValue(':'.$model.'_id', $related_model_id);
                            $rows_affected = $statement->execute();
                            if ($rows_affected===false) {
                                ENV::$log->error(__METHOD__.' - $statement->execute() failed for query: '
                                    .$delete_statement."\n".print_r($statement->errorInfo(),1));
                            }
                        }
                    } catch (Exception $e) {
                        ENV::$log->error(__METHOD__.'-'.$e->getMessage());
                    }
                }
                
            }
        }
    }
	public function delete() {
		if ($this->id !== null) {
			$result = null;
            $delete_sql = $this->build_delete_statement($this->model_name, $this->model_id_name);
			try {
				$statement = $this->db_handle->prepare($delete_sql);
				$statement->bindValue(':'.$this->model_id_name, $this->id);
				$result = $statement->execute();
			} catch (Exception $e) {
				ENV::$log->error(__METHOD__.'-'.$e->getMessage());
			}
		}
		return $result;
	}
	
	/**
	 * 
	 * @param array $field_values {optional, we could have set the 
	 * various field values on the model prior to calling this method
	 */
	public function find(array $field_values = null) {
        return $this->_find($field_values);
    }
    /**
	 *
	 * @param array $field_values {optional, we could have set the
	 * various field values on the model prior to calling this method
     * @param string $table_to_query if given we query that table not
     * the one belonging to this model (for internal use only)
	 */
    private function _find(array $field_values = null, $table_to_query = null, $attach_habtm = true) {
        $result = null;
		$this->set_field_values($field_values);
        $table = $table_to_query!==null ? "`$table_to_query`" : "`{$this->model_name}`";
        // SELECT b, d FROM foo WHERE `b` = :b AND `d` = :d
		$find_statement = 
			"SELECT {$table}.* FROM ".$table.$this->build_where_clause($table_to_query);
        $bind_params = $this->field_values;
        if ($this->id !== null) {
            $bind_params += array($this->model_id_name => $this->id);
        }
        $result = $this->execute_find_statment($find_statement, $bind_params);
        if($attach_habtm) {
            $result = $this->attach_habtm_data($result);
        }
		return $result;
	}

    private function execute_find_statment($statement_text, $bind_params, $result_structure=null) {
        $result = null;
        ENV::$log->debug(__METHOD__.' executing QUERY: '.$statement_text);
        try {
			$statement = $this->db_handle->prepare($statement_text);
			foreach ($bind_params as $field_name => $field_value) {
				$statement->bindValue(':'.$field_name, $field_value);
			}
			$statement->execute();
			$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			ENV::$log->error(__METHOD__.'-'.$e->getMessage());
		}
        return $result_structure===null ? $result : $this->apply_return_structure($result_structure, $result);
    }

	public function findOne(array $field_values = null) {
		$one = $this->_find($field_values);
		return isset($one[0]) ? $one[0] : null;
	}
    public function lookup_list($related_model=null) {
        // if $related_model is null, return for this model
        if($related_model===null) {
            $results = $this->_find();
        } else {
            $results = $this->_find(null,$related_model,false);

        }
        return $this->apply_return_structure(array('id'=>'name'), $results);
    }
	/**
	 * ex: 
	 * return structure is: array('user_id'=>array('username','age'));
	 * row of results: array('user_id'=> 2, 'age'=>30, 'username' => 'sam');
	 * transformed row = array(2=>array(age=>30, username=>sam))
     *
     * ex: $result_structure = array('user_id');
     * row of results: array('user_id'=> 2, 'age'=>30, 'username' => 'sam');
	 * transformed row = array(0 => '2')
	 * 
	 * @param $return_structure
	 * array({key} => {field1})
	 * OR
	 * array({key} => array({field1}, {field2},...))
	 */
	private function apply_return_structure(array $return_structure, $results) {
		$structure_formatted_results = null;
		if (isset($results[0])) {
			foreach ($results as $result) {
				foreach ($return_structure as $structure_key => $field) {
					if( ! is_array($field)) {
                        if($structure_key===0) {
                            $structure_formatted_results[] = $result[$field];
                        } else {
                            $structure_formatted_results[$result[$structure_key]] = $result[$field];
                        }
						
					} else {
						foreach ($field as $value) {
							$structure_formatted_results[$result[$structure_key]][$value] = $result[$value];
						}
					}
				}
			}
		}
		return $structure_formatted_results;
	}
    
	/**
	 * if $fields_is_return_struct is true, we blend the keys into the values and create the
	 * select from that
	 */
	private function build_select_clause(array $fields, $fields_is_return_struct=true) {
		$select_fields[] = key($fields);
		foreach ($fields as $field) {
			if (is_array($field)) {
				$select_fields = array_merge($select_fields, $field);
			} else {
				$select_fields[] = $field;
			}
		}
		return isset($select_fields[0]) ? 'SELECT '.implode(', ',$select_fields):null;
	}
	private function build_where_clause($table_to_query=null) {
        $table = $table_to_query===null?$this->model_name:$table_to_query;
		$where_clause = '';
		// if $this->id is set, just do
		if ($this->id !==null) {
			$where_clause = " WHERE `{$table}`.`{$this->model_id_name}` = :{$this->model_id_name} ";
		} else if(count($this->field_values)) {
			$where_clause = ' WHERE ';
			$and = '';
			foreach (array_keys($this->field_values) as $field_name) {		
				$where_clause .= $and." `{$table}`.`{$field_name}` {$this->field_value_comparitors[$field_name]} :{$field_name}";
				$and = ' AND ';
			}
		}
		return $where_clause;	
	}
	private function build_insert_statement() {
		$insert_statement = 
			'INSERT INTO '.$this->model_name.'( `'.implode('`,`',array_keys($this->field_values)).'`, `modified`, `created` )'
			.' VALUES ( :'.implode(',:',array_keys($this->field_values)).', now(), now() )';
		return $insert_statement;
	}
    private function build_habtm_join_insert_statement($related_model) {
        $insert_statement =
            'INSERT INTO `'.$this->join_table($related_model)."` ( `{$this->model_name}_id`, `{$related_model}_id` )"
			." VALUES ( :{$this->model_name}_id, :{$related_model}_id )";
		return $insert_statement;
	}
    private function build_delete_statement($table, $fieldnames_for_condition) {
        $fieldnames_for_condition = is_array($fieldnames_for_condition)?$fieldnames_for_condition:array($fieldnames_for_condition);
        $statement = "DELETE FROM `$table` WHERE ";
        $and = '';
        foreach ($fieldnames_for_condition as $fieldname) {
            $statement .= " $and `{$fieldname}`= :{$fieldname} ";
            $and = 'AND';
        }
        return $statement;
    }
	private function build_update_statement() {
		$update_statement = 'UPDATE `'.$this->model_name.'` SET modified = now(), ';
		$comma = '';
		foreach (array_keys($this->attribute_definitions) as $field_name) {		
			if (isset($this->field_values[$field_name])) {
				$update_statement .= $comma.'`'.$field_name.'` = :'.$field_name;
				$comma = ', ';
			}
		}
		return $update_statement . ' WHERE `'.$this->model_name.'_id` =  :'.$this->model_id_name;
	}
	/**
	 * store the cleansed submitted values and merge them with the
	 * attribute_definitions for this model. (we keep the submitted values for
	 * doing updates)
	 */
	private function set_field_values(array $submitted_data=null) {
		$submitted_data = array_get_else($submitted_data,$this->model_name);
		if ($submitted_data) {
			// keep `created` and `modified` internal
			if (isset($submitted_data['created']) || isset($submitted_data['modified']) ) {
				ENV::$log->notice(__METHOD__.' Found `created` and/or `modified` in submitted data.  These are for internal use only so they will be ignored');
				unset($submitted_data['created']);
				unset($submitted_data['modified']);
			}
			// check for model_id
			if (isset($submitted_data[$this->model_id_name])) {
				$this->id = $submitted_data[$this->model_id_name];
			}
            // look for habtm relations
            $this->submitted_habtm_data = array_intersect_key($submitted_data,array_get_else($this->relations, 'has_and_belongs_to_many',array()));
			$submitted_data = array_intersect_key($submitted_data, $this->attribute_definitions);
			$this->field_values = array_merge($this->field_values, $submitted_data);
		}
	}
	private function have_data_to_save() {
		return (boolean)count($this->field_values);
	}
	private function is_new_model() {
		return $this->id === null;
	}
	protected function field_values($key_name=null) {
		$return = null;
		if ($key_name!==null) {
			$return = array_get_else($this->field_values,$key_name);
		} else {
			$return = $this->field_values;
		}
		return $return;
	}
	protected function query($sql) {
		return $this->db_handle->query($sql);
	}
	protected function execute($sql) {
		return $this->db_handle->execute($sql);
	}
    public function is_habtm($related_model) {
        return isset($this->relations['has_and_belongs_to_many'][$related_model]);
    }
    /*
     * ex:
     * SELECT `user_id` ,  `group_id`
     * FROM `group_user`
     * WHERE `user_id` IN (:user_id0, :user_id1)
     */
    private function attach_habtm_data($find_results) {
        foreach (array_get_else($this->relations,'has_and_belongs_to_many',array()) as $model => $relation_meta) {
            $id_placeholders = implode(', ',array_fill(0,count($find_results),'?'));
            $save_statement = "SELECT `{$model}_id`, `{$this->model_id_name}` FROM `{$this->join_table($model)}` "
                ."WHERE {$this->model_id_name} IN ($id_placeholders) ";

            ENV::$log->debug(__METHOD__.' QUERY to attach HABTM: '.$save_statement);
            $statement = null;
            $results = array();
			try {
				if( ! $statement = $this->db_handle->prepare($save_statement)) {
					ENV::$log->error(__METHOD__.' - $statement::prepare failed for query: '
						.$save_statement."\n".print_r($this->db_handle->errorInfo(),1));
				}
				foreach ($find_results as $index => $find_result) {
                    $statement->bindParam($index+1, $find_results[$index][$this->model_id_name]);
                }
				$statement->execute();
                $results = $statement->fetchAll(PDO::FETCH_ASSOC);
			} catch (Exception $e) {
				ENV::$log->error(__METHOD__.'-'.$e->getMessage());
			}
            foreach ($find_results as &$find_result) {
                foreach ($results as $index => $result) {
                    if($result[$this->model_id_name]==$find_result[$this->model_id_name]) {
                        $find_result[$model][] = $result[$model.'_id'];
                        unset($results[$index]);
                    }
                }
                $find_result[$model] = isset ($find_result[$model]) ? $find_result[$model] : array();
            }
        }
        return $find_results;
    }
	private function join_table($related_model) {
        $table_name = array($this->model_name, $related_model);
        sort($table_name);
        return implode('_', $table_name);
    }
}
