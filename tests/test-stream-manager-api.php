<?php

	class TestStreamManagerApi extends StreamManager_UnitTestCase {
		
		function testInsertStream() {
			$sid = StreamManagerApi::insert_stream( 'test_stream' );
			$streams = get_posts( array( 'post_type' => 'sm_stream' ) ); 
			$this->assertEquals( 1, count( $streams ) );
			$this->assertEquals( $streams[0]->post_name, 'test_stream');
		}

		function testInsertStreamWithFilter() {
			$cid = wp_create_category('local');
			$cat = get_category( $cid );
			$sid = StreamManagerApi::insert_stream('local', null, array('category_name' => $cat->name ) );
			$postid = $this->factory->post->create( array( 'post_category' => array( $cid ) ) );
			$postid2 = $this->factory->post->create();
			$all_posts = get_posts();
			$this->assertEquals( 2, count( $all_posts ) );
			$stream = new TimberStream( $sid );
			$posts = $stream->get_posts();
			$this->assertEquals( 1, count( $posts ) );
		}

		function testDeleteStream() {
			$sid = StreamManagerApi::insert_stream('test_stream');
			$streams = get_posts( array( 'post_type' => 'sm_stream' ) );
			$stream = $streams[0];
			$this->assertEquals( 1, count( $streams ) );
			StreamManagerApi::delete_stream( $stream->post_name );
			$streams = get_posts( array( 'post_type' => 'sm_stream' ) );
			$this->assertEquals( 0, count( $streams ) );
		}

	}
