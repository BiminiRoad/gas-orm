<?php namespace Gas;

/**
 * CodeIgniter Gas ORM Packages
 *
 * A lighweight and easy-to-use ORM for CodeIgniter
 * 
 * This packages intend to use as semi-native ORM for CI, 
 * based on the ActiveRecord pattern. This ORM uses CI stan-
 * dard DB utility packages also validation class.
 *
 * @package     Gas ORM
 * @category    ORM
 * @version     2.0.0
 * @author      Taufan Aditya A.K.A Toopay
 * @link        http://gasorm-doc.taufanaditya.com/
 * @license     BSD
 *
 * =================================================================================================
 * =================================================================================================
 * Copyright 2011 Taufan Aditya a.k.a toopay. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 * 
 * 1. Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * 
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list
 * of conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY Taufan Aditya a.k.a toopay ‘’AS IS’’ AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL Taufan Aditya a.k.a toopay OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * The views and conclusions contained in the software and documentation are those of the
 * authors and should not be interpreted as representing official policies, either expressed
 * or implied, of Taufan Aditya a.k.a toopay.
 * =================================================================================================
 * =================================================================================================
 */

/**
 * Gas\ORM Class.
 *
 * @package     Gas ORM
 * @version     2.0.0
 */

use Gas\Core;
use Gas\Janitor;

class ORM {

	/**
	 * @var  string  Namespace
	 */
	public $namespace;

	/**
	 * @var  string  Table name
	 */
	public $table;

	/**
	 * @var  string  Primary key collumn name
	 */
	public $primary_key = 'id';

	/**
	 * @var  array  Composite key collumn name(s)
	 */
	public $composite_key = array();

	/**
	 * @var  array  Foreign key collumn name(s)
	 */
	public $foreign_key = array();

	/**
	 * @var  bool    Determine whether an instance hold a record
	 */
	public $empty = TRUE;

	/**
	 * @var  array   Hold any errors occured
	 */
	public $errors = array();

	/**
	 * @var  object  Records holder
	 */
	public $record;

	/**
	 * @var  object  Meta informations holder
	 */
	public $meta;

	/**
	 * @var  object  Related entities holder
	 */
	public $related;

	/**
	 * @var  array   Relationship collections
	 */
	public static $relationships = array();

	/**
	 * @var  array   Field collections
	 */
	public static $fields = array();

	/**
	 * @var  object  Recorder holder
	 */
	public static $recorder;

	/**
	 * Constructor
	 * 
	 * @param  array
	 * @return void
	 */
	function __construct($record = array(), $related = array())
	{
		// Validate namespace and table name
		$this->validate_namespace();
		$this->validate_table();

		// Instantiate data interface for `recorder`, `related`, `meta` and `record` properties
		static::$recorder = new Data();
		$this->related    = new Data();
		$this->meta       = new Data();
		$this->record     = new Data();

		// Is there any data to record?
		if ( ! empty($record))
		{
			$this->record->set('data', $record);
		}

		// Is there any related entites to save?
		if ( ! empty($related))
		{
			$this->related->set('entity', $related);
		}

		// Validate meta data, and assign into its place
		$metadata = self::validate_meta($this);
		$this->meta->set('entities', $metadata['entities']);
		$this->meta->set('fields',   $metadata['fields']);
		$this->meta->set('collumns', $metadata['collumns']);

		// Validate identifier key (PK) and composite key (FK)
		if (is_string($this->primary_key))
		{
			if ( ! in_array($this->primary_key, $this->meta->get('collumns')))
			{
				$this->primary_key = NULL;
			}
		}

		if ( ! empty($this->foreign_key))
		{
			// Validate foreign keys for consistency naming convention recognizer
			foreach($this->foreign_key as $namespace => $fk)
			{
				$this->foreign_key[strtolower($namespace)] = $fk;
				unset($this->foreign_key[$namespace]);
			}
		}
	}

	/**
	 * Initial _init
	 */
	function _init() {}

	/**
	 * Initial _before_check
	 */
	function _before_check() 
	{
		return $this;
	}

	/**
	 * Initial _after_check
	 */
	function _after_check() 
	{
		return $this;
	}

	/**
	 * Initial _before_save
	 */
	function _before_save() 
	{
		return $this;
	}

	/**
	 * Initial _after_save
	 */
	function _after_save() 
	{
		return $this;
	}

	/**
	 * Initial _before_delete
	 */
	function _before_delete() 
	{
		return $this;
	}

	/**
	 * Initial _after_delete
	 */
	function _after_delete() 
	{
		return $this;
	}

	/**
	 * Custom callback function for checking auto field
	 *
	 * @param   mixed
	 * @return  bool
	 */
	function _auto_check($val)
	{
		return (bool) (empty($val) or is_integer($val) or is_numeric($val));
	}
	
	/**
	 * Custom callback function for checking string field
	 *
	 * @param   mixed
	 * @return  bool
	 */
	function _char_check($val)
	{
		return (bool) (is_string($val) or $val === '');
	}

	/**
	 * Custom callback function for checking datetime field
	 *
	 * @param   mixed
	 * @return  bool
	 */
	function _date_check($val)
	{
		return (strtotime($val) !== FALSE);
	}

	/**
	 * Serve static calls for ORM instantiation (late binding)
	 * 
	 * @param  array  set the record
	 * @return object
	 */
	final public static function make($record = array())
	{
		return new static($record);
	}

	/**
	 * Eager-load entities marker
	 * 
	 * @param  mixed  entities to load
	 * @return void
	 */
	final public static function with()
	{
		$entities = func_get_args();
		$instance = self::make();

		// Mark necessary entities
		foreach ($entities as $index => $entity)
		{
			$instance->related->set('include.'.$index, $entity);
		}

		return $instance;
	}

	/**
	 * Validating model's meta
	 *
	 * @param   object Gas Instance
	 * @return  array  Entities (relationships) and fields meta data
	 */
	final public static function validate_meta($gas)
	{
		// Initial meta data
		$entity_metadata = array();

		if (FALSE != ($metadata = Core::$entity_repository->get('models.\\'.$gas->model())))
		{
			// This model has been exists in global repositories
			// Fetch the information
			$entity_metadata = $metadata;
		}
		else
		{
			// Run _init method
			$gas->_init();

			// Register meta entities information
			foreach ($gas::$relationships as $name => $relationship)
			{
				$entity_metadata['entities'][$name] = $relationship;
			}

			// Register meta fields information
			foreach ($gas::$fields as $name => $definition)
			{
				$entity_metadata['fields'][$name] = $definition;
			}

			$entity_metadata['collumns'] = array_keys($entity_metadata['fields']);

			// Save to global entities repository
			Core::$entity_repository->set('models.\\'.$gas->model(), $entity_metadata);
		}

		return $entity_metadata;
	}

	/**
	 * Validating model's method
	 *
	 * @param   string method name
	 * @param   mixed  arguments
	 * @return  mixed  formatted method argument
	 */
	final public static function validate_method($name, $arguments)
	{
		// Available condition, and internal methods
		// which need to converting argument into compile spec
		$conditions = Core::$dictionary['condition'];
		$methods    = array('find');

		if (in_array($name, $methods))
		{
			$arguments = array($arguments);
		}

		return $arguments;
	}

	/**
	 * Validating model's namespace
	 *
	 * @return  void
	 */
	final public function validate_namespace()
	{
		// If there is no namespace set, find it
		if (empty($this->namespace))
		{
			// Get namespace and set the corresponding property
			$reflector       = new \ReflectionClass($this); 
			$this->namespace = $reflector->getNamespaceName();
		}

		return $this;
	}

	/**
	 * Validating model's table
	 *
	 * @return  void
	 */
	final public function validate_table()
	{
		// If there is no table name set, use model name based by namespace path
		if (empty($this->table))
		{
			// Parse namespace into table spec
			$fragments   = explode('\\', $this->namespace);
			$namespace   = strtolower(array_shift($fragments));

			// Define table, path and set the table properties
			$table       = str_replace($namespace.'\\', '', $this->model());
			$path        = strtolower(implode('_', $fragments));
			$this->table = strtolower($path.$table);
		}
		
		return $this;
	}

	/**
	 * Get Gas model name
	 *
	 * @return  string
	 */
	final public function model($gas = null)
	{
		// If there is no model instance passed, use recent class
		return is_null($gas) ? strtolower(get_class($this)) : strtolower(get_class($gas));
	}

	/**
	 * Fetch record and register it into record data
	 *
	 * @param  string  Data key
	 * @param  mixed   Data value
	 * @return void
	 */
	final public function set_record($key, $data)
	{
		$this->record->set('data.'.$key, $data);
	}

	/**
	 * Serve fields anotation and description
	 *
	 * @param  string 	 Field general rule
	 * @param  array 	 Field custom rule
	 * @param  string 	 Field anotation
	 * @return void
	 */
	final public static function field($type = '', $args = array(), $schema = '')
	{
		$rules       = array();
		$args        = is_array($args) ? $args : (array) $args;
		$annotations = array();

		// Diagnose the type pattern
		if (preg_match('/^([^)]+)\[(.*?)\]$/', $type, $m) AND count($m) == 3)
		{
			// Parsing [n,n] 
			$type       = $m[1];
			$constraint = explode(',', $m[2]);

			if (count($constraint) == 2)
			{
				$rules[]       = 'min_length['.trim($constraint[0]).']';
				$rules[]       = 'max_length['.trim($constraint[1]).']';
				$annotations[] = trim($constraint[1]);
			}
			else
			{
				$rules[]       = 'max_length['.$constraint[0].']';
				$annotations[] = $constraint[0];
			}
		}
		
		// Determine each type into its validation rule respectively
		switch ($type) 
		{
			case 'auto':
				$rules[]       = 'callback_auto_check'; 
				$annotations[] = 'INT';
				$annotations[] = 'unsigned';
				$annotations[] = 'auto_increment';

				break;

			case 'datetime':
				$rules[]       = 'callback_date_check'; 
				$annotations[] = 'DATETIME';

				break;
			
			case 'string':
				$rules[]       = 'callback_char_check'; 
				$annotations[] = 'TEXT';

				break;

			case 'spatial':
				$rules[]       = 'callback_char_check'; 
				$annotations[] = 'GEOMETRY';

				break;

			case 'char':
				$rules[]       = 'callback_char_check'; 
				$annotations[] = 'VARCHAR';

				break;

			case 'numeric':
				$rules[]       = 'numeric'; 
				$annotations[] = 'TINYINT';

				break;
				
			case 'int':
				$rules[]       = 'integer';
				$annotations[] = 'INT';

				break;
			
			case 'email':
				$rules[]       = 'valid_email';
				$annotations[] = 'VARCHAR';

				break;
		}

		// Are there other annotations?
		$other_annotations = explode(',', $schema);

		// If yes, then merge it with above 
		if ( ! empty($other_annotations))
		{
			$other_annotations = Janitor::arr_trim($other_annotations);
			$annotations       = array_merge($annotations, $other_annotations);
		}

		// Merge the rules and separate between internal callback
		// And CI validation rules
		$callbacks = array();
		$rules     = array_merge($rules, $args);

		foreach ($rules as $index => $rule)
		{
			if (strpos($rule, 'callback') === 0)
			{
				$callbacks[] = str_replace('callback', '', $rule);
				unset($rules[$index]);
			}
		}
		
		// We now have define all rules, callbacks and annotations
		return array('rules'       => implode('|', $rules), 
		             'callbacks'   => $callbacks,
		             'annotations' => $annotations);
	}

	/**
	 * Interpret relationships definition
	 *
	 * @param  mixed 	 Gas instance
	 * @param  string 	 Relationship Type (`has_one`, `has_many`, `belongs_to`, `self`)
	 * @param  string 	 Relationship Models path
	 * @param  array 	 Relationship Options (pre-process queries)
	 * @return void
	 */
	final public static function relationships($gas = NULL, $type = '', $path = '', $options = array())
	{
		// Not found
		if (empty($path) && $type != 'self')
		{
			throw new \InvalidArgumentException('models_found_no_relations:'.__CLASS__);
		}

		// Remove any spaces within path
		$path     = str_replace(' ', '', $path);
		$entities = explode('=', str_replace(array('<', '>'), '', $path));
		$child    = array_pop($entities);

		// Generate the full path
		$root      = '\\'.get_class($gas);

		switch($type)
		{
			case 'self':
				$direction = '=';
				$single    = TRUE;

				break;

			case 'has_one':
				$direction = '<=';
				$single    = TRUE;

				break;

			case 'has_many':
				$direction = '<=';
				$single    = FALSE;

				break;

			case 'belongs_to':
				$direction = '=>';
				$single    = TRUE;

				break;
		}

		$full_path = $root.$direction.$path;
		
		// We're done
		return array('path'    => $full_path,
		             'child'   => $child,
		             'single'  => $single,
		             'options' => empty($options) ? array() : $options);
	}

	/**
	 * Overloading method triggered when invoking special method.
	 *
	 * @param	string
	 * @param	array
	 * @return	mixed
	 */
	public function __call($name, $arguments)
	{
		$this->validate_namespace();
		$this->validate_table();

		if (preg_match('/^(has_one|has_many|belongs_to|self)$/', $name, $m) AND count($m) == 2)
		{
			// If try to define relationship, immediately serve.
			// Merge passed arguments with relationship type and caller instance
			array_unshift($arguments, $m[1]);
			array_unshift($arguments, $this);

			return call_user_func_array(array('\\Gas\\ORM', 'relationships'), $arguments);
		}
		elseif (($entities = $this->related->get('entities', array())) && array_key_exists($name, $entities))
		{
			return $this->related->get('entities.'.$name, array());
		}

		$arguments = self::validate_method($name, $arguments);

		return Core::compile($this, $name, $arguments);
	}

	/**
	 * Overloading static method triggered when invoking special method.
	 *
	 * @param	string
	 * @param	array
	 * @return	mixed
	 */
	public static function __callStatic($name, $arguments)
	{
		$gas       = self::make();
		$arguments = self::validate_method($name, $arguments);
		
		return Core::compile($gas, $name, $arguments);
	}

	/**
	 * Overloading, utilized for reading data from inaccessible properties.
	 *
	 * @param	string
	 * @return	mixed
	 */
	public function __get($var)
	{
		return $this->record->get('data.'.$var, NULL);
	}

	/**
	 * Overloading, utilized for write data to inaccessible properties.
	 *
	 * @param	string
	 * @return	mixed
	 */
	public function __set($var, $val)
	{
		return $this->record->set('data.'.$var, $val);
	}
}