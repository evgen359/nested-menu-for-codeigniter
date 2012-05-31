<?php
/**
 * @addtogroup MPTtree
 * @{
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
/**
 * An ORM wrapper for MPTtree.
 * @version 0.1.6
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 * @par License:
 * Released under the GNU Lesser General Public License (LGPL), see @ref LGPL and @ref GPL
 * @since 0.1.5
 * 
 * This class uses dynamic instantiation when loading new nodes, so you can extend this class to provide
 * new/different functionality without needing to redeclare many methods. But you need to instantiate the
 * class by yourself then, which shouldn't be too difficult:
 * @code
 * // load the MPTtree
 * $this->load->MPTT('page_tree','pages');
 * // load data for the node you want to fetch
 * $data = $this->pages->get_node(1); // or get_root()
 * // instantiate your extended class:
 * $obj = new MY_ORM_node($this->pages,$data);
 * // instantiate an empty instance of your extended class:
 * $empty_node = new MY_ORM_node($this->pages);
 * @endcode
 * 
 * @attention Shall be instantiated by using MPTtree::get_ORM() or MPTtree::new_ORM()
 * @attention The ORM nodes can only be used with other ORM nodes which have been created using the same MPTtree instance (or tree_table) (ie. when inserting or moving)
 */
class MPTtree_ORM_node{
	/**
	 * The MPTtree instance for this node.
	 */
	var $instance;
	/**
	 * The id value of this node.
	 */
	var $id;
	/**
	 * The lft value of this node
	 */
	var $lft;
	/**
	 * The rgt value of this node
	 */
	var $rgt;
	/**
	 * The data array for this object
	 */
	var $data;
	/**
	 * Cache for roots, static.
	 */
	static $root = array();
	/**
	 * Cache for parent
	 */
	var $parent;
	/**
	 * Cache for children
	 */
	var $children;
	/**
	 * Is this node not a member of the tree?
	 */
	var $orphan = false;
	/**
	 * Has this node been edited?
	 */
	var $edited = false;
	/**
	 * The name of the current class, used in dynamic instantiation.
	 */
	var $classname;
	
	/**
     * Initiates the node with MPTtree instance and lft value.
     * @since 0.1.5
     * @param $instance The instance of MPTtree
     * @param $data The data of the requested node, false if to make an empty node
     * @note ONLY used internally, instead of using new, call MPTtree::get_ORM() or MPTtree::new_ORM()
     */
	function MPTtree_ORM_node(&$instance,$data = false){
		$this->init($instance, $data);
	}
	
	/**
	 * Initiates the node with MPTtree instance and lft value.
	 * @since 0.1.5
     * @param $instance The instance of MPTtree
     * @param $data The data of the requested node, if false, an orphan will be created
     * @note ONLY used internally, by constructor and refresh().
	 */
	function init(&$instance,&$data){
		$this->instance =& $instance;
		$this->classname = get_class($this);
		if($data != false){
			$this->id = $data[$instance->id_col];
			$this->lft = $data[$instance->left_col];
			$this->rgt = $data[$instance->right_col];
			$this->data = $data;
			if ($this->lft == 1) {
				$this->parent = false;
				$this->root[$this->instance->tree_table] =& $this;
			}
		}
		else{
			// reset defaults, not data because we don't want to lose data on refresh
			$this->reset();
		}
	}
	
	/**
	 * Returns a new, empty, MPTtree_ORM_node
	 * @since 0.1.5
	 */
	function new_ORM(){
		$class = $this->classname;
		return new $class($this->instance, false);
	}
	
	/**
	 * Returns true if this node is not part of any tree.
	 * @since 0.1.5
	 */
	function is_orphan(){
		return $this->orphan;
	}
	
	/**
	 * Returns true if this node is a root node.
	 * @since 0.1.5
	 */
	function is_root(){
		return ($this->lft == 1);
	}
	
	/**
	 * Returns a MPTtreeORMIterator object to iterate over the all descendants of this node.
	 * If you want to iterate over the whole tree (including root node), use MPTtree::ORMiterator()
	 * @note Returns an iterator which returns ORM objects
	 * @note Requires PHP version 5 or higher
	 * @since 0.1.5
	 * @return A MPTtreeORMIterator if PHP version > 5, otherwise false
	 */
	function iterator(){
		if(!$this->instance->enable_iterator())
			return false;
		return new MPTtreeORMIterator($this->instance, $this->lft);
	}
	
	/**
	 * Updates this node's data.
	 * @since 0.1.5
	 * @param $children If set to true it also calls update() on them too (recursively), false is default
	 */
	function refresh($children = false){
		if($this->orphan)
			return false;
		$this->init($this->instance, $this->instance->get_node_byid($this->id));
		if($children){
			foreach($this->children() as $child){
				$child->refresh(true);
			}
		}
	}
	
	/////////////////////////////////////
	// Get methods
	/////////////////////////////////////
	
	/**
	 * Returns the root node.
	 * @since 0.1.5
	 * @return a MPTtree_ORM_node
	 */
	function &root(){
		if(!isset($this->root[$this->instance->tree_table])){
			if(!($data = $this->instance->get_root()))
				return false;
			$class = $this->classname;
			$this->root[$this->instance->tree_table] = new $class($this->instance,$data);
		}
		return $this->root[$this->instance->tree_table];
	}
	
	/**
	 * Returns the closest related parent.
	 * @since 0.1.5
	 * @return a MPTtree_ORM_node, but false if node has no parents
	 */
	function &parent(){
		// if this is an orphan, return false
		if($this->orphan)
			return false;
		if(!isset($this->parent)){
			if(!($data = $this->instance->get_parent($this->lft,$this->rgt)))
				$this->parent = false;
			else{
				$class = $this->classname;
				$this->parent = new $class($this->instance,$data);
			}
		}
		return $this->parent;
	}
	
	/**
	 * Returns all children to this node.
	 * @since 0.1.5
	 * @return an array containing all children as MPTtree_ORM_node objects.
	 */
	function &children(){
		// if this is an orphan, return empty array
		if($this->orphan)
			return array();
		if(!isset($this->children)){
			$children = $this->instance->get_children($this->lft,$this->rgt);
			$this->children = array();
			$class = $this->classname;
			foreach($children as $child){
				$node = new $class($this->instance,$child);
				$node->parent =& $this;
				$this->children[] = $node;
			}
		}
		return $this->children;
	}
	
	/**
	 * Returns the number of children this node has.
	 * If this node is an orphan, 0 is returned
	 * @since 0.1.5
	 */
	function count_children(){
		// If this is an orphan, return 0
		if($this->orphan)
			return 0;
		// if we already have the children loaded, count that array
		if(isset($this->children))
			return count($this->children);
		// call MPTtree
		return $this->instance->count_children($this->lft,$this->rgt);
	}
	
	/**
	 * Returns true if this node has children.
	 * @since 0.1.6
	 */
	function has_children(){
		return ($this->orphan || $this->rgt - $this->lft > 1);
	}
	
	/**
	 * Returns all descendants of the current node.
	 * @note Loads the whole subtree
	 * @since 0.1.5
	 */
	function &descendants(){
		// if this is an orphan, return empty array
		if($this->orphan)
			return array();
		$ret = array();
		foreach($this->children() as $child){
			$ret[] = $child;
			$ret = array_merge($ret,$child->descendants());
		}
		return $ret;
	}
	
	/**
	 * Returns the number of descendants this node has.
	 * If this node is an orphan, 0 is returned
	 * @since 0.1.5
	 */
	function count_descendants(){
		// If this is an orphan, return 0
		if($this->orphan)
			return 0;
		return $this->instance->count_descendants($this->lft,$this->rgt);
	}
	
	/**
	 * Returns the path to the current node.
	 * The segments consists of the title column specified in the MPTtree instance used by this ORM node.
	 * If $true_path is set to false, the path can be used by xpath directly.
	 * @since 0.1.5
	 * @param $true_path Whether to include the root node in the path, default: false
	 * @return An array with the path to the current node (including or excluding root node, depending on $true_path), false if node is an orphan
	 */
	function path($true_path = false){
		// if this is an orphan, return false
		if($this->orphan)
				return false;
		$path = array($this->data[$this->instance->title_col]);
		$parent =& $this->parent();
		while($parent != false){
			$path[] = $parent->get($this->instance->title_col);
			$parent =& $parent->parent();
		}
		if(!$true_path){
			array_pop($path);
		}
		return array_reverse($path);
	}
	
	/**
	 * Returns the node at the end of the path.
	 * @since 0.1.5
	 * @param $path The path to the requested node, this/root node not included
	 * @param $separator The separator in the path, not needed if input is array, default: '/'
	 * @param $relative If the path should be relative to this node, or if it shall start from the root node, default: true
	 * @see MPTtree::xpath()
	 * @return A MPTtree_ORM_node object representing the new node, false if nothing found
	 */
	function xpath($path, $separator = '/',$relative = true){
		if($relative)
			$data = $this->instance->xpath($path,$separator,$this->lft);
		else
			$data = $this->instance->xpath($path,$separator);
		$class = $this->classname;
		return $data == false ? false : new $class($this->instance, $data);
	}
	
	/**
	 * Returns the property with the name $property.
	 * @since 0.1.5
	 * @param $property The property to retrieve
	 * @return The property if exists, false otherwise
	 */
	function get($property = ''){
		return (isset($this->data[$property]) ? $this->data[$property] : false);
	}
	
	/**
	 * Returns all node data.
	 * @since 0.1.5
	 * @return An array with all properties
	 */
	function &get_all(){
		return $this->data;
	}
	
	/////////////////////////////////////
	// Set methods
	/////////////////////////////////////
	
	/**
	 * Sets the property $property.
	 * @since 0.1.5
	 * @param $property The name of the property (column name)
	 * @param $data The data to save in the column $property
	 */
	function set($property = '',$data){
		$this->edited = true;
		$this->data[$property] = $data;
	}
	
	/**
	 * Updates the node data in the database to the currrent data of this node.
	 * @since 0.1.5
	 */
	function update(){
		if($this->edited && !$this->orphan){
			$this->instance->update_node($this->lft,$this->data);
			$this->edited = false;
		}
	}
	
	/////////////////////////////////////
	// Insert methods
	/////////////////////////////////////
	
	/**
	 * Inserts this node as a root node, if the tree has none and this node is not part of any tree.
	 * @since 0.1.5
	 * @return True if inserted, false if fail (if root exists or if this node is already part of a tree)
	 */
	function insert_as_root(){
		if(!$this->orphan || $this->root() != false)
			return false;
		$this->instance->insert_root($this->data);
		$this->lft = 1;
		$this->rgt = 2;
		$this->parent = false;
		$this->root[$this->instance->tree_table] =& $this;
		$this->orphan = false;
		return true;
	}
	
	/**
	 * Inserts this node as a sibling above the node $node.
	 * Will not insert node if the node is already part of a tree.
	 * @since 0.1.5
	 * @param $node The sibling
	 * @return True if success, false otherwise
	 * @todo Update the parent node, so it's children gets updated
	 */
	function insert_above(&$node){
		if($node->lft == 1 || !$this->orphan || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->insert_node_before($node->lft,$this->data)){
			list($this->lft,$this->rgt,$this->id) = $ret;
			$this->parent =& $node->parent();
			$this->orphan = false;
			return true;
		}
		return false;
	}
	
	/**
	 * Inserts this node as a sibling below the node $node.
	 * Will not insert node if the node is already part of a tree.
	 * @since 0.1.5
	 * @param $node The sibling
	 * @return True if successful, false otherwise
	 * @todo Update the parent node, so it's children gets updated
	 */
	function insert_below(&$node){
		if($node->lft == 1 || !$this->orphan  || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->insert_node_after($node->lft,$this->data)){
			list($this->lft,$this->rgt,$this->id) = $ret;
			$this->parent =& $node->parent();
			$this->orphan = false;
			return true;
		}
		return false;
	}
	
	/**
	 * Inserts this node as the first child of the node $node.
	 * Will not insert node if the node is already part of a tree.
	 * @since 0.1.5
	 * @param $node The node to be parent
	 * @return True if success, false otherwise
	 */
	function insert_as_first_child_of(&$node){
		if(!$this->orphan || 
		$node->orphan || 
		$node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->append_node($node->lft,$this->data)){
			list($this->lft,$this->rgt,$this->id) = $ret;
			$this->parent =& $node;
			$this->orphan = false;
			if($this->parent->children != null) // check if parent already has data
				array_unshift_ref($this->parent->children,$this);
			return true;
		}
		return false;
	}
	
	/**
	 * Inserts this node as the last child of the node $node.
	 * Will not insert node if the node is already part of a tree.
	 * @since 0.1.5
	 * @param $node The node to be parent
	 * @return True if success, false otherwise
	 */
	function insert_as_last_child_of(&$node){
		if(!$this->orphan || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->append_node_last($node->lft,$this->data)){
			list($this->lft,$this->rgt,$this->id) = $ret;
			$this->parent =& $node;
			$this->orphan = false;
			if($this->parent->children != null) // check if parent already has data
				$this->parent->children[] =& $this;
			return true;
		}
		return false;
	}
	
	/////////////////////////////////////
	// Move methods
	/////////////////////////////////////
	
	/**
	 * Moves this node to the position of sibling above the node $node.
	 * @since 0.1.5
	 * @param $node The sibling node
	 * @return false if insert failed, true if success
	 * @todo Update the parent node, so it's children gets updated
	 */
	function move_above(&$node){
		if($this->orphan)
			return $this->insert_above($node);
		if($node->lft == 1 || $this->lft != 1 || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->move_node_before($this->lft,$node->lft)){
			list($this->lft , $this->rgt) = $ret;
			$this->parent =& $node->parent();
			return true;
		}
		return false;
	}
	
	/**
	 * Moves this node to the position of sibling below the node $node.
	 * @since 0.1.5
	 * @param $node The sibling node
	 * @return false if insert failed, true if success
	 * @todo Update the parent node, so it's children gets updated
	 */
	function move_below(&$node){
		if($this->orphan)
			return $this->insert_below($node);
		if($node->lft == 1 || $this->lft != 1 || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->move_node_after($this->lft,$node->lft)){
			list($this->lft , $this->rgt) = $ret;
			$this->parent =& $node->parent();
			return true;
		}
		return false;
	}
	
	/**
	 * Moves this node to the position of first child of the node $node.
	 * @since 0.1.5
	 * @param $node The node to be parent
	 * @return false if insert failed, true if success
	 */	
	function move_to_first_child_of(&$node){
		if($this->orphan)
			return $this->insert_as_first_child_of($node);
		if($this->lft != 1 || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->move_node_append($this->lft,$node->lft)){
			list($this->lft , $this->rgt) = $ret;
			$this->parent =& $node;
			if($this->parent->children != null) // check if parent already has data
				array_unshift_ref($this->parent->children,$this);
			return true;
		}
		return false;
	}
	
	/**
	 * Moves this node to the position of last child of the node $node.
	 * @since 0.1.5
	 * @param $node The node to be parent
	 * @return false if insert failed, true if success
	 */
	function move_to_last_child_of(&$node){
		if($this->orphan)
			return $this->insert_as_last_child_of($node);
		if($this->lft != 1 || $node->orphan || $node->instance->tree_table != $this->instance->tree_table)
			return false;
		if($ret = $this->instance->move_node_append_last($this->lft,$node->lft)){
			list($this->lft , $this->rgt) = $ret;
			$this->parent =& $node;
			if($this->parent->children != null) // check if parent already has data
				$this->parent->children[] =& $this;
			return true;
		}
		return false;
	}
	
	/////////////////////////////////////
	// Delete methods
	/////////////////////////////////////
	
	/**
	 * Deletes this node from the tree, making it an orphan.
	 * @note data does not disappear, only children, parent, id, lft and rgt properties will be erased from this node.
	 * @since 0.1.5
	 * @param $children Set to true to also delete all childern, default: false
	 */
	function delete($children = false){
		if($this->orphan)
			return;
		if($children)
			$this->instance->delete_branch($this->lft);
		else
			$this->instance->delete_node($this->lft);
		if($this->lft == 1)
			unset($this->root[$this->instance->tree_table]);
		$this->reset();
	}
	
	/**
	 * Resets the current object, removes all references to database and other ORM objects.
	 * Exception of row data and MPTtree instance.
	 */
	function reset(){
		$this->orphan = true;
		$this->edited = false;
		$this->parent = null;
		$this->children = null;
		$this->rgt = null;
		$this->lft = null;
		$this->id = null;
	}
	
	/**
	 * Unshifts an array with a reference
	 * @param $array array
	 * @param $value mixed
	 * Prepend a reference to an element to the beginning of an array. Renumbers numeric keys, so $value is always inserted to $array[0]
	 * @return an int from array_unshift()
	 */
	function array_unshift_ref(&$array, &$value){
		$return = array_unshift($array,'');
		$array[0] =& $value;
		return $return;
	}
}
/**
 * @}
 */
?>