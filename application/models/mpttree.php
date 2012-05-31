<?php
/*
 * Created on 2007 sep 21
 * by Martin Wernstahl <m4rw3r@gmail.com>
 */
/*
    This file is part of MPTtree.

    MPTtree is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    MPTtree is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class MPTtree extends CI_Model{
	/**
	 * The table which contains the tree.
	 */
	var $tree_table;
	/**
	 * The column which stores the lft value.
	 * Column would preferably be of type UNSIGNED INT
	 */
	var $left_col = 'lft';
	/**
	 * The column which stores the rgt value.
	 * Column would preferably be of type UNSIGNED INT
	 */
	var $right_col = 'rgt';
	/**
	 * The column which stores the unique id.
	 * Column would preferably be of type UNSIGNED INT, AUTO INCREMENTAL
	 * @note MPTtree does not assign this column any value, hence the prefered AUTO INCREMENT
	 */
	var $id_col = 'id';
	/**
	 * The column which stores the node titles.
	 * Used by display(), xpath(), xpath2() and MPTtree_ORM_node::path()
	 */
	var $title_col = 'title';
	/**
	 * Regulates debugging of MPTtree.
	 */
	var $debug_on = false;
	/**
	 * The dir where ORM and iterator include files are situated.
	 * @note With leading slash and Without trailing slash.
	 * @note relative to APPPATH constant (defined by CI)
	 */
	static $inc_dir = '/models/MPTtree';
	/**
	 * Variable to determine if ORM file has been loaded.
	 */
	static $ORM_enabled = false;
	/**
	 * Variable to determine if iterator file has been loaded.
	 */
	static $iterator_enabled = false;
	
	/**
	 * Constructor.
	 */
	function __construct(){
		parent::__construct();
	}
	
	/**
	 * Changes the options used for this class.
	 * @since 0.1
	 * @param $opts An array with the options,
	 * key = optionname and value = optionvalue.\n
	 * Available options:
	 * @arg <b>table</b> The table which holds the tree, default: null
	 * @arg <b>left</b> The name of the column which holds the lft value, default: 'lft'
	 * @arg <b>right</b> The name of the column which holds the rgt value, default: 'rgt'
	 * @arg <b>id</b> The name of the column which holds the unique id, default: 'id'
	 * @arg <b>title</b> The name of the title column (used by xpath() and display()), default: 'title'
	 * @return void
	 */
	function set_opts($opts){
		$table = isset($opts['table']) ? $opts['table'] : null;
		$left  = isset($opts['left'])  ? $opts['left']  : 'lft';
		$right = isset($opts['right']) ? $opts['right'] : 'rgt';
		$id    = isset($opts['id'])    ? $opts['id']    : 'id';
		$title = isset($opts['title']) ? $opts['title'] : 'title';
		$this->set_table($table);
		$this->left_col = $left;
		$this->right_col = $right;
		$this->id_col = $id;
		$this->title_col = $title;
	}
	
	/**
	 * Changes the table which the class operates on.
	 * @since 0.1
	 * @param $table_name The name of the new table
	 * @return void
	 */
	function set_table($table_name){
		if($table_name != null || $table_name != ''){
			$this->tree_table = $table_name;
			return;
		}
		debug_message('The table name was not correctly set.');
	}
	
	//////////////////////////////////////////
	//  Iterator functions
	//////////////////////////////////////////
	
	/**
	 * Enables iterator support by including file with iterator.
	 * Preferred Usage:
	 * @code
	 * if(!$this->enable_iterator())
	 *      return false; // the method requiring iterators to be available exits with false
	 * @endcode
	 * @since 0.1.5
	 * @return true if included, false if PHP version is less than 5
	 */
	function enable_iterator(){
		if(floor(phpversion()) < 5){
			show_error('MPTtree: PHP version 5 or greater is required to use the Iterator interface.');
			return false;
		}
		if(!self::$iterator_enabled){
			require_once(APPPATH.self::$inc_dir.'/MPTtree_iterator.php');
			self::$iterator_enabled = true;
		}
		return true;
	}
	
	/**
	 * Returns a MPTtreeIterator object to iterate over the whole tree.
	 * (or the descendants of a specified node)\n
	 * Enables iterator support if it isn't already.
	 * @note Returns an iterator which returns asociative arrays
	 * @since 0.1.5
	 * @param $lft The lft of the node to iterate (if set to 0 (default), the whole tree will be iterated)
	 * @note Requires PHP version 5 or higher
	 * @return A MPTtreeIterator if PHP version > 5, otherwise false
	 */
	function iterator($lft = 0){
		if(!$this->enable_iterator())
			return false;
		return new MPTtreeIterator($this,$lft);
	}
	
	/**
	 * Returns a MPTtreeORMIterator object to iterate over the whole tree.
	 * (or the descendants of a specified node)\n
	 * Enables iterator support if it isn't already.
	 * @note Returns an iterator which returns ORM objects
	 * @since 0.1.5
	 * @param $lft The lft of the node to iterate (if set to 0 (default), the whole tree will be iterated)
	 * @note Requires PHP version 5 or higher
	 * @return A MPTtreeORMIterator if PHP version > 5, otherwise false
	 */
	function ORMiterator($lft = 0){
		if(!$this->enable_iterator())
			return false;
		return new MPTtreeORMIterator($this,$lft);
	}
	
	//////////////////////////////////////////
	//  ORM functions
	//////////////////////////////////////////
	
	/**
	 * Enables ORM support.
	 * @since 0.1.5
	 */
	function enable_ORM(){
		if(!self::$ORM_enabled){
			require_once(APPPATH.self::$inc_dir."/MPTtree_ORM.php");
			self::$ORM_enabled = true;
		}
	}
	
	/**
	 * Returns a populated ORM object.
	 * Activates ORM if it isn't already.\n
	 * If the node does not exist, false is returned.
	 * @since 0.1.5
	 * @param $lft The lft value of the reqested node, or a path to the requested node (array or string (separator = '/))
	 * @return A MPTtree_ORM_noce object if node exists, false otherwise
	 */
	function get_ORM($lft = 1){
		$this->enable_ORM();
		// find by id
		if(is_numeric($lft)){
			if(!($node = $this->get_node($lft)))
				return false;
		}
		// find by path
		else if(is_string($lft) || is_array($lft)){
			if(!($node = $this->xpath($lft)))
				return false;
		}
		else{
			debug_message('Couldn\'t load node with path/id '.$lft.' because it is not numeric, a string or an array.');
		}
		return new MPTtree_ORM_node($this,$node);
	}
	
	/**
	 * Returns a populated ORM object, using id column to find the node.
	 * Activates ORM if it isn't already.
	 * If the node does not exist, false is returned.
	 * @since 0.1.6
	 * @return A MPTtree_ORM_noce object if node exists, false otherwise
	 */
	function get_ORM_byid($id = 1){
		$this->enable_ORM();
		if(!($node = $this->get_node_byid($id)))
			return false;
		return new MPTtree_ORM_node($this,$node);
	}
	
	/**
	 * Creates a new empty ORM object to be inserted into the tree.
	 * Activates ORM if it isn't already.
	 * @since 0.1.5
	 * @return A new MPTtree_ORM_node object
	 */
	function new_ORM(){
		$this->enable_ORM();
		return new MPTtree_ORM_node($this,false);
	}
	
	//////////////////////////////////////////
	//  Lock functions
	//////////////////////////////////////////

	/**
	 * Locks tree table.
	 * This is a straight write lock - the database blocks until the previous lock is released
	 * @since 0.1.4
	 */
	function lock_tree_table($aliases = array())
	{
		$q = "LOCK TABLE " . $this->tree_table . " WRITE";
		$res = $this->db->query($q);
	}

	/**
	 * Unlocks tree table.
	 * Releases previous lock
	 * @since 0.1.4
	 */
	function unlock_tree_table()
	{
		$q = "UNLOCK TABLES";
		$res = $this->db->query($q);
	}
	
	///////////////////////////////////////////////
	//  Get functions
	///////////////////////////////////////////////
	
	/**
	 * Returns the root node object.
	 * @since 0.1
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_root(){
		$query = $this->db->get_where($this->tree_table,array($this->left_col => 1),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		if(!$return)
			$this->debug_message('Root node was not found.');
		return $return;
	}
	
	/**
	 * Returns the node with lft value of $lft.
	 * @since 0.1
	 * @param $lft The lft of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_node($lft){
		$query = $this->db->get_where($this->tree_table,array($this->left_col => $lft),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		if(!$return)
			$this->debug_message('Node with '.$this->left_col.' '.$lft.' was not found.');
		return $return;
	}
	
	/**
	 * Returns the node with id value of $id.
	 * @since 0.1.5
	 * @param $id The id of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_node_byid($id){
		$query = $this->db->get_where($this->tree_table,array($this->id_col => $id),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		if(!$return)
			$this->debug_message('Node with '.$this->id_col.' '.$lft.' was not found.');
		return $return;
	}
	
	/**
	 * Returns all descendants to the node with the value lft and rgt.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @param $with_level_col If the results shall reflect the relative level the nodes lie on
	 * @note With the with_level_col switch the method is a bit slower because the database must do some extra processing
	 * @see get_descendants_wlevel()
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_descendants($lft,$rgt,$with_level_col = false){
		if($with_level_col)
			return $this->get_descendants_wlevel($lft,$rgt);
		$this->db->where($this->left_col.' >',$lft);
		$this->db->where($this->right_col.' <',$rgt);
		$this->db->order_by($this->left_col,'asc');
		$query = $this->db->get($this->tree_table);
		return $query->num_rows() ? $query->result_array() : array();
	}

	/**
	 * Returns all descendants to the node with lft $lft and rgt $rgt,
	 * with an extra column that contains the relative level.
	 * @note Primarily for internal use, use the with_level_col modifier on get_descendants() instead
	 * @since 0.1.6
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return A multidimensional accociative array with the table rows,
	 * with a column (depth) added,
	 * but if no rows returned, empty array
	 */
	function get_descendants_wlevel($lft,$rgt){
		if($rgt - $lft < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			return array();
			
		$result = $this->db->query(
"SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
	SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
			{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
			AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth > 0
ORDER BY node.{$this->left_col};");
		return $result->num_rows() ? $result->result_array() : array();
	}

	/**
	 * Returns all descendants to the node with the lft lft and the rgt rgt, filtered by the $where parameter.
	 * @note The where parameter is passed to the where() method in CodeIgniters Active Record class.
	 * @since 0.1.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @param $where The where filter of the query, sent to the where() method of the Active Record class
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_descendants_where($lft,$rgt,$where){
		$this->db->where($this->left_col.' >',$lft);
		$this->db->where($this->right_col.' <',$rgt);
		$this->db->where($where);
		$this->db->order_by($this->left_col,'asc');
		$query = $this->db->get($this->tree_table);
		return $query->num_rows() ? $query->result_array() : array();
	}
	
	/**
	 * Returns the number of descendants a node has.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return an int with the num of descendants
	 */
	function count_descendants($lft,$rgt){
		return (($rgt - $lft) - 1) / 2;
	}
	
	/**
	 * Returns all children of the node with the values lft and rgt.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_children($lft,$rgt){
		if($rgt - $lft < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			return array();
			
		$result = $this->db->query(
"SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
	SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
			{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
			AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col};");
		return $result->num_rows() ? $result->result_array() : array();
	}
	
	/**
	 * Returns all children to the node with the lft lft and rgt rgt, filtered by the $where parameter.
	 * @since 0.1.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @param $where The where filter of the query, sent to the where() method of the Active Record class
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_children_where($lft,$rgt,$where){
		if($rgt - $lft < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			return array();
		$this->db->select('*');
		// Circumvent the db escaping to enable a subquery
		$this->db->ar_from[] =
"(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as a";
		$this->db->where($where);
		$result = $this->db->get();
		$children = $result->result_array();
		return count($children)? $children : array();
	}
	
	/**
	 * Returns the number of children a node has.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return an int with the num of children
	 */
	function count_children($lft,$rgt){
		$result = $this->db->query(
"SELECT COUNT(*) as num FROM
(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as a");
		$result = $result->row_array();
		return $result['num'];
	}
	
	/**
	 * A method for determining if a node has children.
	 * @since 0.1.6
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return true or false, depending on result
	 */
	function hasChildren($lft,$rgt){
		return (($rgt - $lft) > 1);
	}
	
	/**
	 * Returns all parents to a node (grand parents and grandgrand parents and so on).
	 * Index 0 is the closest parent.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return A multidimensional asociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_parents($lft,$rgt){
		$this->db->where($this->left_col.' <',$lft);
		$this->db->where($this->right_col.' >',$rgt);
		$this->db->order_by($this->left_col,'desc');
		$query = $this->db->get($this->tree_table);
		return $query->num_rows() ? $query->result_array() : array();
	}
	
	/**
	 * Returns the closest related parent.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return An asociative array with the table rows,
	 * but if no rows returned, false
	 */
	function get_parent($lft,$rgt){
		$this->db->where($this->left_col.' <',$lft);
		$this->db->where($this->right_col.' >',$rgt);
		$this->db->order_by($this->left_col,'desc');
		$this->db->limit(1); // we only want the first of all parents
		$query = $this->db->get($this->tree_table);
		return $query->num_rows() ? $query->row_array() : false;
	}
	
	/**
	 * Returns the node at the end of the path $path.
	 * This is the improved version, it is more than 
	 * twice as fast as the old (xpath2()).
	 * But this performance comes at a price: \n
	 * It is not as accurate as xpath2(). \n
	 * If you have two paths like this: \n
	 * /fruit/green/apple \n
	 * /green/apple \n
	 * And if you search on /green/apple, xpath will find both.
	 * If xpath gets more than one row from the database,
	 * it will try to match the number of segemnts with the number of parents.\n
	 * So if you know you are going to have many paths like this
	 * in your tree, it may be better using xpath2().
	 * @since 0.1.2
	 * @param $path The path to the node (can be an array), separated by '/' (not needed if an array)
	 * @param $separator The separator between the segments, default: '/'
	 * @param $root The lft of the node to be root in the query, default: 1
	 * @note The path to the rootnode is only a '/', so
	 * the path to a child is '/childtitle', and to a grandchild:
	 * '/childtitle/grandchildtitle'
	 * @return An array with the requested node's data
	 */
	function xpath($path,$separator = '/',$root = 1){
		if(is_array($path)){
			$segments = $path;
		}
		else{
			// split the segments
			$segments = explode($separator,$path);
		}
		// load the rootnode
		$this->db->select($this->left_col.', '.$this->right_col);
		$query = $this->db->get_where($this->tree_table,array($this->left_col => $root));
		if(!$query->num_rows()){
			debug_message('xpath() cannot find node with '.
								$this->left_col.': '.$root.', aborting xpath().');
			return false;
		}
		$root_node = $query->row_array();
		// construct query
		$from_str = "(SELECT * FROM {$this->tree_table} WHERE {$this->left_col} = {$root_node[$this->left_col]}) as parent0";
		$where_str = '';
		// iterate the segments
		$j = 1;
		foreach($segments as $part){
			if($part != null || $part != ''){
				$from_str .= ",\n".$this->tree_table.' as parent'.$j;
				if($j > 1)
					$where_str .= 'AND ';
				
				$where_str .= "parent$j.{$this->left_col} BETWEEN parent".($j-1).
						".{$this->left_col} AND parent".($j-1).
						".{$this->right_col} AND parent$j.{$this->title_col} = '$part'\n";
				$j++;
			}
		}
		// $j = number of real segments + 1 (root node) + 1
		// last node
		$query = $this->db->query("SELECT parent".($j < 1 ? 0 : ($j-1)).".* FROM\n$from_str".($j == 1 ? '' : " WHERE\n$where_str"));
		// If we have multiple matches, determine which one is right
		$return = false; // default return
		foreach($query->result_array() as $node){
			// count the number of parents to a given node (with respect to the root)
			$this->db->select('title');
			$this->db->from($this->tree_table);
			$this->db->where($this->left_col . ' <',$node[$this->left_col]);
			$this->db->where($this->right_col . ' >',$node[$this->right_col]);
			$this->db->where($this->left_col . ' >',$root_node[$this->left_col]);
			$this->db->where($this->right_col . ' <',$root_node[$this->right_col]);
			$q = $this->db->get();
			// the number of parents should match $j - 2, which is the number of real segments
			// or if $j == 1, it is the root node which has been requested
			if($q->num_rows() == $j - 2 || $j == 1){
				// We have our match
				$return = $node;
				break;
			}
		}
		return $return;
	}
	
	/**
	 * Returns the node at the end of the path $path.
	 * @deprecated
	 * @since 0.1.4
	 * @param $path The path to the node (can be an array), separated by '/' (not needed if an array)
	 * @param $separator The separator between the segments, default: '/'
	 * @param $root The lft of the node to be root in the query, default: 1
	 * @note The path to the rootnode is only a '/', so
	 * the path to a child is '/childtitle', and to a grandchild:
	 * '/childtitle/grandchildtitle'
	 * @return An array with the requested node's data
	 */
	function xpath2($path,$separator = '/',$root = 1){
		if(is_array($path)){
			$segments = $path;
		}
		else{
			// split the segments
			$segments = explode($separator,$path);
		}
		// load the rootnode
		$this->db->select($this->left_col.', '.$this->right_col);
		$query = $this->db->get_where($this->tree_table,array($this->left_col => $root));
		if(!$query->num_rows()){
			debug_message('xpath2() cannot find node with '.
								$this->left_col.': '.$root.', aborting xpath2().');
			return false;
		}
		$current_node = $query->row_array();
		// iterate the segments
		foreach($segments as $part){
			if($part != null || $part != ''){
				// We have a segment, try to match it to the children

// Almost the same as in get_children_where(), but it selects only the needed data (lft and title)
				$this->db->select('*');
				// Circumvent the db escaping to enable a subquery
				$this->db->ar_from[] = "(SELECT node.{$this->left_col},
node.{$this->title_col}, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
{$this->tree_table} AS parent,
{$this->tree_table} AS sub_parent,
(
	SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
	FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent
	WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col}
		AND parent.{$this->right_col} AND node.{$this->left_col} = {$current_node[$this->left_col]}
	GROUP BY node.{$this->id_col}
	ORDER BY node.{$this->left_col}
)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as a";
				$this->db->where($this->title_col,$part);
				$query = $this->db->get();
				// Have we got a result?
				if(!$query->num_rows()){
					debug_message('xpath2() cannot find node with '.
										$this->title_col.' : '.$part.' in path:'.$path.', aborting.');
					return false;
				}
				$current_node = $query->row_array();
			}
		}
		return $this->get_node($current_node[$this->left_col]);
	}
	
	/**
	 * Returns all nodes in the path $path.
	 * @since 0.1.5
	 * @param $path The path to the node, separated by '/'
	 * @param $separator The separator between the segments, default: '/'
	 * @return An array with the root at index 0 and the last node in the path at the last index
	 */
	function fetch_path($path, $separator = '/'){
		$node = $this->xpath($path, $separator);
		if($node == false){
			return false;
		}
		return array_merge(array_reverse($this->get_parents($node[$this->left_col],$node[$this->right_col])),array($node));
	}
	
	/**
	 * Converts the tree structure in the tree to an array.
	 * Array Example:
	 * @code
	 * Array([0] => Array([id] => 1,
	 *                    [lft] => 1,
	 *                    [rgt] => 4,
	 *                    [children] => Array(
	 *                        [0] => Array([id] => 2,
	 *                                     [lft] => 2,
	 *                                     [rgt] => 3
	 *                                    )
	 *                    )
	 *      )
	 * )
	 * @endcode
	 * @since 0.1.6
	 * @param $root The node that shall be root in the tree (local scope)
	 * @return A recursive array, false if the root node was not found
	 */
	function tree2array($root = 1){
		$node = $this->get_node($root);
		if($node == false)
			return false;
		// query
		$query = $this->db->query('SELECT * FROM '.$this->tree_table.
			' WHERE '.$this->left_col.' BETWEEN '.$node[$this->left_col].
			' AND '.$node[$this->right_col].
			' ORDER BY '.$this->left_col.' ASC');
		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$lastlevel = 0;
		foreach($query->result_array() as $row){
			// go more shallow, if needed
			if(count($right)){
				while($right[count($right)-1] < $row[$this->right_col]){
					array_pop($right);
				}
			}
			// Go one level deeper?
			if(count($right) > $lastlevel){
				end($current);
				$current[key($current)]['children'] = array();
				$stack[count($right)] =& $current[key($current)]['children'];
			}
			// the stack contains all parents, current and maybe next level
			$current =& $stack[count($right)];
			// add the data
			$current[] = $row;
			// go one level deeper with the index
			$lastlevel = count($right);
			$right[] = $row[$this->right_col];
		}
		return $result;
	}
	
	//////////////////////////////////////////
	//  Active Record helpers
	//////////////////////////////////////////
	
	/**
	 * Puts the get_descendants() query in an Actve Record subquery, so you can use sorting, custom filtering etc.
	 * Should be used in conjunction with CodeIgniter's Active Record class.
	 * Adds the subquery in the from block, under the alias descendant (can be omitted if
	 * it's only one query/table in the FROM part of the query).
	 * @code
	 * $this->db->select('descendant.title, c.name');
	 * $this->MPTtree->AR_get_descendants(3,10); // puts the subquery which fetches all descendants to the node
	 * $this->db->from('customers AS c');
	 * $this->db->where('c.id = descendant.customer_id');
	 * $this->db->order_by('c.name');
	 * $nodes = $this->db->get();
	 * @endcode
	 * @since 0.1.4
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return void
	 */
	function AR_from_descendants_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->db->ar_from[] = "(SELECT * FROM {$this->tree_table}
WHERE {$this->left_col} > $lft AND {$this->right_col} < $rgt
ORDER BY {$this->left_col} ASC) as descendant";
	}

	function AR_from_alldescendants_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->db->ar_from[] = "(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth,
		GROUP_CONCAT(parent.title SEPARATOR '/') as path
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth >= 1
ORDER BY node.{$this->left_col}) as descendants";
	}
		
	/**
	 * Puts the get_children() query in an Active Record subquery, so you can use sorting, custom filtering etc.
	 * Should be used in conjunction with CodeIgniter's Active Record class.
	 * Adds the subquery in the from block, under the alias child (can be omitted if
	 * it's only one query/table in the FROM part of the query).
	 * @code
	 * $this->db->select('title');
	 * $this->MPTtree->AR_get_children(3,10); // puts the subquery which fetches all children to the node
	 * $this->db->order_by('title');
	 * $nodes = $this->db->get();
	 * @endcode
	 * @since 0.1.4
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return void
	 */
	function AR_from_children_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->db->ar_from[] = "(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as child";
	}

	/**
	 * Puts the get_parents() query in an Active Record subquery, so you can use sorting, custom filtering etc.
	 * Should be used in conjunction with CodeIgniter's Active Record class.
	 * Adds the subquery in the from block, under the alias parent (can be omitted if
	 * it's only one query/table in the FROM part of the query).
	 * @since 0.1.4
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return void
	 */
	function AR_from_parents_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->db->ar_from[] = "(SELECT * FROM {$this->tree_table}
WHERE {$this->left_col} < $lft AND {$this->right_col} > $rgt
ORDER BY {$this->left_col} DESC) as parent";
	}
	
	//////////////////////////////////////////
	//  Update functions
	//////////////////////////////////////////
	
	/**
	 * Updates the node values.
	 * Uses the codeigniter db->update() function, so all values
	 * in the data array are to be an asociative array, ex:
	 * @code
	 * update_node(1,array('title'=>'Home Page',
	 *       'url'=>'http://webpage.com')); // will generate this sql
	 * // UPDATE SET title = 'Home Page', SET url='http://webpage.com' WHERE lft = 1
	 * @endcode
	 * @since 0.1
	 * @param $lft The lft of the node to be manipulated
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return true if success, false otherwise
	 */
	function update_node($lft,$data){
		if(!$this->get_node($lft))return false;
		$data = $this->sanitize_data($data);
		// Make the update
		$this->db->where($this->left_col,$lft);
		$this->db->update($this->tree_table,$data);
		return true;
	}
	
	//////////////////////////////////////////
	//  Insert functions
	//////////////////////////////////////////
	
	/**
	 * Creates the root node in the table.
	 * @since 0.1
	 * @param $data The rootnode data
	 * @return true if success, but if rootnode exists, it returns false
	 */
	function insert_root($data){
		$this->lock_tree_table(); // Lock table first then check if root exits - I am being pedantic in the sequence of these statements.
		if($this->get_root() != false) {
			$this->unlock_tree_table();
			return false;
		}
		$data = $this->sanitize_data($data);
		$data = array_merge($data,array($this->left_col => 1,$this->right_col => 2));
		$this->db->insert($this->tree_table,$data);
		$this->unlock_tree_table();
		return true;
	}
	
	/**
	 * Inserts the node before the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted before
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node_before($lft,$data){
		if(!$this->get_node($lft))
			return false;
		return $this->insert_node($lft,$data);
	}
	
	/**
	 * Inserts the node after the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted before
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node_after($lft,$data){
		$node = $this->get_node($lft);
		if(!$node)
			return false;
		return $this->insert_node($node[$this->right_col] + 1,$data);
	}
	
	/**
	 * Inserts the node as the first child of the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (asociative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node($lft,$data){
		if(!$this->get_node($lft))
			return false;
		return $this->insert_node($lft + 1,$data);
	}
	
	/**
	 * Inserts the node as the last child the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node_last($lft,$data){
		$node = $this->get_node($lft);
		if(!$node)
			return false;
		return $this->insert_node($node[$this->right_col],$data);
	}
	
	/**
	 * Inserts a child to the parent with lft $lft and sorts it after the title column.
	 * It orders the nodes in ascending order.
	 * @since 0.1.4
	 * @param $lft The lft value of the parent node.
	 * @param $data The data to be inserted.
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_sorted($lft,$data){
		// set up locks for BOTH the SELECT and the INSERT queries
		$q = "LOCK TABLE " . $this->tree_table . " WRITE, " .
			  $this->tree_table . " AS node READ, " .
			  $this->tree_table . " AS parent READ, " .
			  $this->tree_table . " AS st_node READ, " .
			  $this->tree_table . " AS st_parent READ, " .
			  $this->tree_table . " AS sub_parent READ";
		$res = $this->db->query($q);
		
		$node = $this->get_node($lft);
		if(!$node){
			$this->unlock_tree_table();
			return false;
		}
		
		$children = array();
		
		if($node['rgt'] - $node['lft'] < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			$children = array();
		else {
			$q = "SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
				  FROM 	{$this->tree_table} AS node, 
						{$this->tree_table} AS parent, 
						{$this->tree_table} AS sub_parent,
							(SELECT st_node.{$this->id_col}, (COUNT(st_parent.{$this->id_col}) - 1) AS depth
							 FROM	{$this->tree_table} AS st_node,
									{$this->tree_table} AS st_parent
							 WHERE st_node.{$this->left_col} BETWEEN st_parent.{$this->left_col} AND st_parent.{$this->right_col}
							 AND st_node.{$this->left_col} = {$lft}
							 GROUP BY st_node.{$this->id_col}
							 ORDER BY st_node.{$this->left_col}
							)AS sub_tree
				  WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
				  AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
				  AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
				  GROUP BY node.{$this->id_col}
				  HAVING depth = 1
				  ORDER BY node.{$this->left_col};";
			$result = $this->db->query($q);
			$children = $result->num_rows() ? $result->result_array() : array();
		}
			
		// set default
		$insert_lft = $node[$this->right_col];
		if (count($children)){
			foreach($children as $child){
				if(strcmp($data[$this->title_col], $child[$this->title_col]) < 0){
					$insert_lft = $child[$this->left_col];
					break;
				}
			}
		}

		$ret = $this->insert_node($insert_lft,$data, false);
		$this->unlock_tree_table();
		return $ret;
	}
	
	/**
	 * Inserts a node at the lft specified.
	 * Primarily for internal use.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @param $lock If the method needs to aquire a lock, default true
	 * Use this option when calling from a method wich already have got a lock on the tables used
	 * by this method.
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node($lft,$data,$lock = true){
		$root = $this->get_root();
		if($lft > $root[$this->right_col] || $lft < 1)return false;
		$data = $this->sanitize_data($data);
		
		if ($lock)
			$this->lock_tree_table();

		// HCG: I have deprecated the use of the function create_space here - I regard it as good practice NOT
		// to have function calls when a table is locked. NOTE: Under mySQL, you can only lock a table ONCE from
		// a single process.

		$this->db->query('UPDATE '.$this->tree_table.
						' SET '.$this->left_col.' = '.$this->left_col.' + 2 '.
						' WHERE '.$this->left_col.' >= '.$lft);
		$this->db->query('UPDATE '.$this->tree_table.
						' SET '.$this->right_col.' = '.$this->right_col.' + 2 '.
						' WHERE '.$this->right_col.' >= '.$lft);
		
		$data = array_merge($data,array($this->left_col => $lft,$this->right_col => $lft+1));
		$this->db->insert($this->tree_table,$data);
		
		if ($lock)
			$this->unlock_tree_table();
		return array($lft, $lft + 1, $this->db->insert_id());
	}
	
	///////////////////////////////////////
	//  Move functions
	///////////////////////////////////////
	/**
	 * Moves a node with lft to before the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which it will be place before
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_before($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$nlft); //- 1);
	}
	
	/**
	 * Moves a node with lft to after the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which it will be place before
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_after($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$node[$this->right_col] + 1);
	}
	
	/**
	 * Moves a node with lft to be the first child of the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which will be parent
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_append($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$nlft + 1);
	}
	
	/**
	 * Moves a node with lft to be the last of child the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which will be parent
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_append_last($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$node[$this->right_col]);
	}
	
	/**
	 * Moves a node with lft to nlft.
	 * Primary for internal use.
	 * HCG: We need to look into this again to find better ways of dealing with the gaps.
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The new lft of the node
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node($lft,$nlft){
		// Validate values
		$node = $this->get_node($lft);
		if(!$node || $lft == $nlft)return false;
		$root = $this->get_root();
		if($nlft > $root[$this->right_col] || $nlft < 2 || $lft == 1)return false;
		
		// Lock tree
		$this->lock_tree_table();
		
		// Create WHERE string, so we only affect those we want to
		$where = $this->id_col . ' = '.$node[$this->id_col];
		$descendants = $this->get_descendants($node[$this->left_col],$node[$this->right_col]);
		if($descendants){
			foreach($descendants as $to_move){
				$where .= ' OR '.$this->id_col.' = ' . $to_move[$this->id_col];
			}
		}
		
		// Move the ones to be moved outside the tree
		$this->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->left_col.' = '.$this->left_col.' + '.($root[$this->right_col] - $lft + 1).
			' WHERE '.$where);
		$this->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->right_col.' = '.$this->right_col.' + '.($root[$this->right_col] - $lft + 1).
			' WHERE '.$where);
		
		// Shrink the tree
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		
		$size = ($node[$this->right_col] - $node[$this->left_col] + 1);
		
		if($lft < $nlft){
			// We move the node down in tree (to a greater lft),
			// so compensate for size of the moved
			
			// Create space for nodes
			$this->create_space(($nlft - $size),$size);
			
			// Move them
			$this->db->query(
				'UPDATE '.$this->tree_table.
				' SET '.$this->left_col.' = '.$this->left_col.' - '.
								(($root[$this->right_col] - $nlft + 1) + $size).
				' WHERE '.$where);
			$this->db->query(
				'UPDATE '.$this->tree_table.
				' SET '.$this->right_col.' = '.$this->right_col.' - '.
								(($root[$this->right_col] - $nlft + 1) + $size).
				' WHERE '.$where);
			// to get correct rgt value
			$nlft = $lft;
		}
		else{
			// Create space for nodes
			$this->create_space($nlft,$size);
			
			// Move them
			$this->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->left_col.' = '.$this->left_col.' - '.($root[$this->right_col] - $nlft + 1).
				' WHERE '.$where);
			$this->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->right_col.' = '.$this->right_col.' - '.($root[$this->right_col] - $nlft + 1).
				' WHERE '.$where);
		}
		/*$this->db->select($this->right_col);
		$data = $this->db->get_where($this->tree_table, array($this->left_col => $nlft));
		$ret = $data->row_array();*/
		$ret = array($nlft,$nlft + $node[$this->right_col] - $node[$this->left_col]);
		$this->unlock_tree_table();
		return $ret;
	}
	
	//////////////////////////////////////////////
	//  Delete functions
	//////////////////////////////////////////////
	
	/**
	 * Deletes the node with the lft specified and promotes all children.
	 * @since 0.1
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_node($lft){
		$node = $this->get_node($lft);
		if(!$node || $node[$this->left_col] <= 1)
			return false;
		// Lock table
		$this->lock_tree_table();
		
		$this->db->where($this->id_col,$node[$this->id_col]);
		$this->db->delete($this->tree_table);
		
		// these are not needed, remove_gaps() fixes it
		/*$this->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->left_col.' = '.$this->left_col.' - '.(1).
			' WHERE '.$this->left_col.' > '.$node[$this->left_col]);
		$this->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->right_col.' = '.$this->right_col.' - '.(1).
			' WHERE '.$this->right_col.' > '.$node[$this->right_col]);*/
		
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		$this->unlock_tree_table();
		return true;
	}
	
	/**
	 * Deletes the node with the lft specified and all children.
	 * @since 0.1
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_branch($lft){
		$node = $this->get_node($lft);
		if(!$node || $node[$this->left_col] == 1)
			return false;
		// lock table
		$this->lock_tree_table();
		
		$this->db->where($this->left_col.' BETWEEN '.$node[$this->left_col].' AND '.$node[$this->right_col]);
		$this->db->delete($this->tree_table);
		
		/*$this->db->query('DELETE '.$this->tree_table.
			' WHERE '.$this->left_col.' > '.$node[$this->left_col].
			' AND '.$this->left_col.' < '.$node[$this->right_col]);
*/
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		$this->unlock_tree_table();
		return true;
	}
	
	//////////////////////////////////////////////
	//  Gap functions
	//////////////////////////////////////////////
	
	// HCG: I do not like ANY of these functions because I HATE having function calls involved in scenarios where table locking is done.
	// This is mainly a style choice, but it can bite if one is not VERY careful.
	//
	// NOTE ALSO: I have REMOVED all of the transaction stuff from here, and there is purposefully NO TABLE locking done within these functions
	// The reason is because in mySQL, LOCK TABLES releases any table locks currently held by the thread before acquiring new locks.
	// This implies that if one were to attempt to 'nest' table locks, the second LOCK TABLES instruction will automatically release the first table
	// lock, which is what we definately DO NOT WANT! Again, you can see why I do not like functions where there are locks in place - THEY ARE BAD!
	//
	// All calls to the Gap functions below MUST be called from within a function which has acquired a table lock, otherwise IT WILL BREAK!
	
	/**
	 * Creates an empty space inside the tree beginning at $pos and with size $size.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @since 0.1
	 * @param $pos The starting position of the empty space.
	 * @param $size The size of the gap
	 * @return True if success, false if not or if space is outside root
	 */
	function create_space($pos,$size){
		$root = $this->get_root();
		if($pos > $root[$this->right_col] || $pos < $root[$this->left_col])return false;
		$this->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->left_col.' = '.$this->left_col.' + '.$size.
			' WHERE '.$this->left_col.' >='.$pos);
		$this->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->right_col.' = '.$this->right_col.' + '.$size.
			' WHERE '.$this->right_col.' >='.$pos);
		return true;
	}
	
	/**
	 * Returns the first gap in table.
	 * Primary for internal use.
	 * @since 0.1
	 * @return The starting pos of the gap and size
	 */
	function get_first_gap(){
		$ret = $this->find_gaps();
		return $ret === false ? false : $ret[0];
	}
	
	/**
	 * Removes the first gap in table.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @since 0.1
	 * @return True if gap removed, false if none are found
	 */
	function remove_first_gap(){
		$ret = $this->get_first_gap();
		if($ret !== false){
			$this->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->left_col.' = '.$this->left_col.' - '.$ret['size'].
				' WHERE '.$this->left_col.' > '. $ret['start']);
			$this->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->right_col.' = '.$this->right_col.' - '.$ret['size'].
				' WHERE '.$this->right_col.' > '. $ret['start']);
			return true;
		}
		return false;
	}
	/**
	 * Removes all gaps in the table.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @since 0.1
	 * @return True if gaps are found, false if none are found
	 */
	function remove_gaps(){
		$ret = false;
		while($this->remove_first_gap() !== false){$ret = true;}
		return $ret;
	}
	
	/**
	 * Finds all the gaps inside the tree.
	 * Primary for internal use.
	 * HCG: I do not like this!
	 * @since 0.1
	 * @return Returns an array with the start and size of all gaps,
	 * if there are no gaps, false is returned
	 */
	function find_gaps(){
		// Get all lfts and rgts and sort them in a list
		$this->db->select($this->left_col.', '.$this->right_col);
		$this->db->order_by($this->left_col,'asc');
		$table = $this->db->get($this->tree_table);
		$nums = array();
		foreach($table->result() as $row){
			$nums[] = $row->{$this->left_col};
			$nums[] = $row->{$this->right_col};
		}
		sort($nums);
		
		// Init vars for looping
		$old = array();
		$current = 1;
		$foundgap = 0;
		$gaps = array();
		$current = 1;
		$i = 0;
		$max = max($nums);
		while($max >= $current){
			$val = $nums[$i];
			if($val == $current){
				$old[] = $val;
				$foundgap = 0;
				$i++;
			}
			else{
				// have gap or duplicate
				if($val > $current){
					if(!$foundgap)$gaps[] = array('start'=>$current,'size'=>1);
					else{
						$gaps[count($gaps) - 1]['size']++;
					}
					$foundgap = 1;
				}
			}
			$current++;
		}
		return count($gaps) > 0 ? $gaps : false;
	}
	
	//////////////////////////////////////////////
	//  Validate Node/Tree functions
	//////////////////////////////////////////////
	
	/**
	 * Makes a check if the node is a valid node.
	 * @since 0.1
	 * @param $lft The lft of the node
	 * @return true if valid node, else false
	 */
	function is_valid_node($lft){
		$node = $this->get_node($lft);
		if(!$node)return false;
		if($node[$this->left_col]<$node[$this->right_col] &&
			$node[$this->left_col] > 0 &&
			($node[$this->right_col] - $node[$this->left_col]) % 2 == 1)
			return true;
		return false;
	}
	
	/**
	 * Reports any errors in tree.
	 * @since 0.1
	 * @param $ret True if a string with all the errors is requested, default: true
	 * @return A string with the errors if $ret is true,
	 * otherwise, it returns true if there are no errors
	 * and false if there are.
	 */
	function validate($ret = true){
		$this->db->select($this->left_col.', '.$this->right_col);
		$query = $this->db->get($this->tree_table);
		$lftrgt = array();
		$lfts = array();
		$errors = 0;
		$text = '';
		foreach($query->result_array() as $row){
			array_push($lftrgt, $row[$this->left_col]);
			array_push($lfts, $row[$this->left_col]);
			array_push($lftrgt, $row[$this->right_col]);
		}
		sort($lftrgt);
		foreach($lfts as $lft){
			if(!$this->is_valid_node($lft)){
				$text .= 'The node with lft '.$lft.'is not a valid node';
				$errors++;
			}
		}
		$next = 1;
		foreach($lftrgt as $temp){
			if($temp == $next){
				$next++;
			}
			else{
				if($temp > $next){
					$text .= "Gap before $temp\n<br />";
					$next = $temp + 1;
					$errors++;
				}
				else{
					if($temp == ($next - 1)){
						$text .= "Duplicate of $temp<br />";
						$errors++;
					}
				}
			}
		}
		if($errors == 0 && $ret == true){
			$text .= "No errors in Tree found<br />";
		}
		else{
			$text .= "$errors ERRORS found! Correct them as soon as possible!<br />";
		}
		if($ret == true)
			return $text;
		else{
			if($errors == 0)return true;
			return false;
		}
	}
	
	/**
	 * Returns an simple indented html string with the tree structure.
	 * @since 0.1
	 * @param $lft The lft of the parent to display children (default root)
	 * @return Html string
	 */
	function display($lft = 1){
		$node = $this->get_node($lft);
		$str = '';
		$right = array();
		$query = $this->db->query('SELECT '.$this->title_col.', '.$this->left_col.', '.
									$this->right_col.' FROM '.$this->tree_table.
			' WHERE '.$this->left_col.' BETWEEN '.$node[$this->left_col].
			' AND '.$node[$this->right_col].
			' ORDER BY '.$this->left_col.' ASC');
		foreach($query->result_array() as $row){
			if(count($right) > 0){
				while($right[count($right)-1] < $row[$this->right_col]){
					array_pop($right);
				}
			}
			$str .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',count($right)) . $row[$this->title_col] . "<br />\n";
			$right[] = $row[$this->right_col];
		}
		return $str;
	}
	
	//////////////////////////////////////////////
	//  Helper functions
	//////////////////////////////////////////////
	
	/**
	 * Sanitizes the data given.
	 * Removes the left_col and right_col from the data, if they exists in $data.
	 * @since 0.1.2
	 * @param $data The data to be sanitized
	 * @return The sanitized data
	 */
	function sanitize_data($data){
		// Remove fields which potentially can damage the tree structure
		if(is_array($data)){
			unset($data[$this->left_col]);
			unset($data[$this->right_col]);
		}
		elseif(is_object($data)){
			unset($data->{$this->left_col});
			unset($data->{$this->right_col});
		}
		return $data;
	}
	
	/**
	 * Logs a debug message if debug is on.
	 * $message is taken by reference to avoid copying to much.
	 * @since 0.1.6-fix
	 * @param $message The message to be logged
	 */
	function debug_message($message){
		if($this->debug_on == true){
			// save debug message
			Console::log('MPTtree: '.$message);
		}
	}
	
	/**
	 * Changes the debug mode.
	 * Default is off.
	 * @since 0.1.6-fix
	 * @param $value True if debug shall be on, false if to turn it off.
	 */
	function set_debug($value){
		$this->debug_on = $value;
	}
	
	/**
	 * Wrapper for protect_identifiers (New function in 1.6.x)
	 * If the protect_identifiers function is not available, simply return the identifier unaltered
	 * @param $ident - The data to be protected
	 * @return The protected data
	 */
	/*function _protect_identifiers($ident)
	{
		if (is_callable(array('CI_DB', 'protect_identifiers')))
			return $this->db->protect_identifiers($ident);
		else
			return $ident;
	}*/
}
/**
 * @}
 */
?>