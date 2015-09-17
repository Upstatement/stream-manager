<?php

	class TestStreamManagerHooks extends StreamManager_UnitTestCase {

		function buildStream( $options = array() ) {
			$pid = $this->factory->post->create(array('post_type' => 'sm_stream', 'post_content' => '', 'post_title' => 'Sample Stream'));
			add_filter('stream-manager/options/id='.$pid, function($defaults, $stream) use ($options) {
				$defaults['query'] = array_merge($defaults['query'], $options);
				return $defaults;
			}, 10, 2);
			$stream = new TimberStream($pid);
			return $stream;
		}

		function testQueryHook() {
			$stream = $this->buildStream(array('post_type' => 'article'));
			$this->buildPosts(5);
			$posts = $stream->get_posts();
			$this->assertEquals(0, count($posts));
		}

		// function testTaxonomyHook() {
		// 	$cooking_id = $this->factory->term->create(array('name' => 'Cooking'));
		// 	$jumping_id = $this->factory->term->create(array('name' => 'Jumping'));
			
		// 	$sid = $this->factory->post->create(array('post_type' => 'sm_stream', 'post_content' => '', 'post_title' => 'Sample Stream'));
		// 	$stream = new TimberPost($sid);
		// 	add_filter('stream-manager/taxonomy/'.$stream->slug, function($terms) use ($cooking_id) {
		// 		$terms['post_tag'][] = $cooking_id;
		// 		return $terms;
		// 	});
		// 	$this->buildPosts(5);
		// 	$cooking_post = $this->factory->post->create(array('tags_input' => 'cooking', 'post_title' => 'All About Cooking'));
		// 	$jumping_post = $this->factory->post->create(array('tags_input' => 'jumping', 'post_title' => ''));
		// 	$stream->save_stream();
		// 	$posts = $stream->get_posts();
		// 	print_r($posts);
		// 	$cooking_post_query = Timber::get_posts(array('post_type' => 'post', 'tax_query' => array('relation' => 'OR', array('taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => array($cooking_id)))));
		// 	$this->assertEquals( $cooking_post, $cooking_post_query[0]->ID );
		// 	$this->assertEquals( $cooking_post, $posts[0]->ID );
		// 	$this->assertEquals(1, count($pids));
		// 	//$this->assertFalse( in_array($jumping_post, $pids) );
		// }
		
		
		function testSample() {
			$this->assertTrue(true);
		}
		

	}
