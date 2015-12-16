<?php

	class TestStreamManagerIntegration extends StreamManager_UnitTestCase {
		
		function testBuildPosts() {
			$pids = $this->buildPosts(10);
			$this->assertEquals(10, count($pids));
			$pids = $this->buildPosts(7);
			$this->assertEquals(7, count($pids));
		}

		function testBasicStream() {
			$count = rand(0, 25);
			$this->buildPosts($count);
			$stream = $this->buildStream();
			$posts = $stream->get_posts();
			$this->assertEquals($count, count($posts));
		}

		function testPostPublish() {
			$stream = $this->buildStream();
			$this->buildPosts(4);
			$posts = $stream->get_posts();
			$this->assertEquals(4, count($posts));
			$post_id = $this->factory->post->create(array('post_status' => 'draft'));
			wp_publish_post($post_id);
			$posts = $stream->get_posts(array('post_type' => 'post'));
			$this->assertEquals(5, count($posts));
		}

		function testPostUnpublish() {
			$stream = $this->buildStream();
			$post_id = $this->factory->post->create();
			$posts = $stream->get_posts();
			$this->assertEquals(1, count($posts));
			wp_update_post(array( 'ID' => $post_id, 'post_status' => 'draft' ));
			$stream = new TimberStream( $stream->ID );
			$posts = $stream->get_posts();
			$this->assertEquals(0, count($posts));
		}

		function testRemovePost() {
			$stream = $this->buildStream();
			$postids = $this->buildPosts(5);
			foreach($postids as $id) {
				$data[] = array('id' => $id, 'pinned' => '');
			}
			$stream->set('stream', $data);
			$posts = $stream->get_posts();
			$first = $posts[0]->ID;
			$stream->remove_post($first);
			$stream = new TimberStream($stream->ID);
			$posts = $stream->get_posts();
			$this->assertEquals($first, $posts[4]->ID);
		}	

	}
