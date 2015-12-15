<?php

	class TestStreamManagerManager extends StreamManager_UnitTestCase {
		
		function testOnSavePostPublish() {
			$stream = $this->buildStream();
			$post_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
			$posts = $stream->get_posts();
			$this->assertEquals(0, count($posts));
			wp_publish_post($post_id);
			$posts = $stream->get_posts();
			$this->assertEquals(1, count($posts));
		}

		function testOnSavePostUnpublish() {
			$stream = $this->buildStream();
			$post_id = $this->factory->post->create();
			$posts = $stream->get_posts();
			$this->assertEquals(1, count($posts));
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			$stream = new TimberStream( $stream->ID );
			$posts = $stream->get_posts();
			$this->assertEquals(0, count($posts));
		}

	}