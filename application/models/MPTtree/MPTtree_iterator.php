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
 * An Iterator for the MPTtree.
 * Iterates over all descendants of a given node.\n
 * <b>Special Case:</b> if $lft is not specified, it iterates over the whole tree, including rootnode.\n
 * Usage:
 * @code
 * $iterator = new MPTtreeIterator($MPTtreeInstance);
 * foreach($iterator as $node){
 *     echo $node["title"];
 * }
 * @endcode
 * It is recommended to use MPTtree::iterator() or MPTtree_ORM_node::iterator() instead of direct instantiation
 * @since 0.1.5
 * @version 0.1.6
 * @attention Only PHP 5 and greater
 * @todo maybe change to RecursiveIterator (more queries, but better scalability (ex. if not all nodes gets iterated))
 */
class MPTtreeIterator implements Iterator{
 	/**
 	 * The rootnode for this recursion.
 	 */
	protected $basenode;
	/**
 	 * All nodes to be iterated over.
 	 */
	protected $nodes;
 	/**
 	 * The current key.
 	 */
 	protected $key;
 	
 	/**
 	 * Constructor.
 	 * @param $instance The instance of MPTtree to be used during iteration
 	 * @param $lft The lft for the node whose descendants shall be iterated (set to 0 (or omit) to iterate over the whole tree)
 	 */
 	function MPTtreeIterator($instance,$lft = 0){
 		if($lft == 0){
 			$lft = 1;
 			$this->basenode = $instance->get_node($lft);
 			$this->nodes = array_merge(array($this->basenode), $instance->get_descendants($this->basenode[$instance->left_col], $this->basenode[$instance->right_col]));
 		}
 		else{
 			$this->basenode = $instance->get_node($lft);
 			$this->nodes = $this->instance->get_descendants($this->basenode[$instance->left_col], $this->basenode[$instance->left_col]);
 		}
 	}
 	/**
 	 * Resets the iterator, so it starts it's iteration from the beginning.
 	 * @note Does not reload contents of the tree
 	 */
	public function rewind() {
		$this->key = -1;
		$this->next();
	}
	
	/**
	 * Steps this iterator forward one step.
	 */
	public function next() {
		$this->key++;
		$this->valid = isset($this->nodes[$this->key]);
	}
	
	/**
	 * Returns the current key.
	 * Pretty useless.
	 */
	public function key() {
		return $this->key;
	}
	
	/**
	 * Returns the current node.
	 * @return An array with the data for the current node
	 */
	public function current() {
		return $this->nodes[$this->key];
	}
	
	/**
	 * Are there any more items?
	 */
	public function valid() {
		return $this->valid;
	}
 }
 /**
 * ORM variant of MPTtreeIterator.
 * Iterates over all descendants of a given node, returning ORM objects.\n
 * <b>Special Case:</b> if $lft is not specified, it iterates over the whole tree, including rootnode.\n
 * Usage:
 * @code
 * $iterator = new MPTtreeORMIterator($MPTtreeInstance);
 * foreach($iterator as $node){
 *     echo $node->get("title");
 * }
 * @endcode
 * It is recommended to use MPTtree::iterator() or MPTtree_ORM_node::iterator() instead of direct instantiation
 * @since 0.1.5
 * @version 0.1.6
 * @note Only PHP 5 and greater
 * @todo maybe change to RecursiveIterator (more queries, but better scalability (ex. if not all nodes gets iterated))
 */
class MPTtreeORMIterator implements Iterator{
 	/**
 	 * The rootnode for this recursion.
 	 */
	protected $basenode;
	/**
 	 * All nodes to be iterated over.
 	 */
	protected $nodes;
 	/**
 	 * The current key.
 	 */
 	protected $key;
 	
 	/**
 	 * Constructor.
 	 * @param $instance The instance of MPTtree to be used during iteration
 	 * @param $lft The lft for the node whose descendants shall be iterated (set to 0 (or omit) to iterate over the whole tree)
 	 */
 	function MPTtreeORMIterator($instance,$lft = 0){
 		if($lft == 0){
 			$lft = 1;
 			$this->basenode = $instance->get_ORM($lft);
 			$this->nodes = array_merge(array($this->basenode), $this->basenode->descendants());
 		}
 		else{
 			$this->basenode = $instance->get_ORM($lft);
 			$this->nodes =  $this->basenode->descendants();
 		}
 	}
 	/**
 	 * Resets the iterator, so it starts it's iteration from the beginning.
 	 * @note Does not reload contents of the tree
 	 */
	public function rewind() {
		$this->key = -1;
		$this->next();
	}
	
	/**
	 * Steps this iterator forward one step.
	 */
	public function next() {
		$this->key++;
		$this->valid = isset($this->nodes[$this->key]);
	}
	
	/**
	 * Returns the current key.
	 * Pretty useless.
	 */
	public function key() {
		return $this->key;
	}
	
	/**
	 * Returns the current node.
	 * @return ORM object representing the current node
	 */
	public function current() {
		return $this->nodes[$this->key];
	}
	
	/**
	 * Are there any more items?
	 */
	public function valid() {
		return $this->valid;
	}
 }
 /**
 * @}
 */
 ?>