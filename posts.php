<?php

/*
Plugin Name: WP/LR Basic Posts
Description: A collection on LR will become a post on WP and the gallery within it will be kept synchronized.<br />Folders are ignored so they can be used to clearly organize you LR hierarchy.
Version: 1.0.0
Author: Jordy Meow
Author URI: http://www.meow.fr
*/

class Meow_WPLR_Sync_Plugin_Posts {

  public function __construct() {

    // Reset
    add_action( 'wplr_reset', array( $this, 'reset' ), 10, 0 );

    // Create / Update
    add_action( 'wplr_create_collection', array( $this, 'create_collection' ), 10, 3 );
    add_action( 'wplr_update_collection', array( $this, 'update_collection' ), 10, 2 );

    // Media
    add_action( "wplr_add_media_to_collection", array( $this, 'add_media_to_collection' ), 10, 2 );
    add_action( "wplr_remove_media_from_collection", array( $this, 'remove_media_from_collection' ), 10, 2 );
    add_action( "wplr_remove_media", array( $this, 'remove_media' ), 10, 1 );
    add_action( "wplr_remove_collection", array( $this, 'remove_collection' ), 10, 1 );
  }

  function reset() {
    global $wpdb;
  	$wpdb->query( "DELETE p FROM $wpdb->posts p INNER JOIN $wpdb->postmeta m ON p.ID = m.meta_value WHERE m.meta_key = \"lrid_to_id\"" );
  	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = \"lrid_to_id\"" );
  }

  function create_collection( $collectionId, $inFolderId, $collection ) {

    // If exists already, avoid re-creating
    if ( !empty( get_post_meta( $collectionId, 'lrid_to_id', true ) ) )
      return;

    // Create the collection.
    $post = array(
      'post_title'    => wp_strip_all_tags( $collection['name'] ),
      'post_content'  => '[gallery ids=""]',
      'post_status'   => 'draft',
      'post_type'     => 'post'
    );
    $id = wp_insert_post( $post );

    // Let's trick this meta. Instead of using the post as reference, we use the ID from LR. Makes the get_post_meta cleaner.
    add_post_meta( $collectionId, 'lrid_to_id', $id, true );
  }

  // Updated the collection with new information.
  // Currently, that would be only its name.
  function update_collection( $collectionId, $collection ) {
    // $id = get_post_meta( $collectionId, 'lrid_to_id', true );
    // $post = array( 'ID' => $id, 'post_title' => wp_strip_all_tags( $collection['name'] ) );
    // wp_update_post( $post );
  }

  // Added meta to a collection.
  // The $mediaId is actually the WordPress Post/Attachment ID.
  function add_media_to_collection( $mediaId, $collectionId, $isRemove = false ) {
    $id = get_post_meta( $collectionId, 'lrid_to_id', true );
    $content = get_post_field( 'post_content', $id );
    preg_match_all( '/\[gallery.*ids="([0-9,]*)"\]/', $content, $results );
    if ( !empty( $results ) && !empty( $results[1] ) ) {
      $str = $results[1][0];
      $ids = !empty( $str ) ? explode( ',', $str ) : array();
      $index = array_search( $mediaId, $ids, false );
      if ( $isRemove ) {
        if ( $index !== FALSE )
          unset( $ids[$index] );
      }
      else {
        // If mediaId already there then exit.
        if ( $index !== FALSE )
          return;
        array_push( $ids, $mediaId );
      }
      // Replace the array within the gallery shortcode.
      $content = str_replace( 'ids="' . $str, 'ids="' . implode( ',', $ids ), $content );
      $post = array( 'ID' => $id, 'post_content' => $content );
      wp_update_post( $post );
    }
  }

  // Remove media from the collection.
  function remove_media_from_collection( $mediaId, $collectionId ) {
    $this->add_media_to_collection( $mediaId, $collectionId, true );
  }

  // The media was physically deleted.
  function remove_media( $mediaId ) {
    // No need to do anything.
  }

  // The collection was deleted.
  function remove_collection( $collectionId ) {
    $id = get_post_meta( $collectionId, 'lrid_to_id', true );
    wp_delete_post( $id, true );
    delete_post_meta( $collectionId, 'lrid_to_id' );
  }
}

new Meow_WPLR_Sync_Plugin_Posts;

?>
