<?php

	class TestStreamManagerHooks extends StreamManager_UnitTestCase {

		function buildStream( $name = 'Sample Stream', $options = array() ) {
			$pid = $this->factory->post->create(array('post_type' => 'sm_stream', 'post_content' => '', 'post_title' => $name));
			add_filter('stream-manager/options/id='.$pid, function($defaults, $stream) use ($options) {
				$defaults['query'] = array_merge($defaults['query'], $options);
				return $defaults;
			}, 10, 2);
			$stream = new TimberStream($pid);
			return $stream;
		}

		function testQueryHook() {
			$stream = $this->buildStream('Sample Stream', array('post_type' => 'article'));
			$this->buildPosts(5);
			$posts = $stream->get_posts();
			$this->assertEquals( 0, count($posts) );
		}

		function testEmptyTaxonomyQueryHook() {
			$cooking_id = $this->factory->term->create(array('name' => 'Cooking'.rand(0, 1000)));
			$stream = $this->buildStream('Sample Stream', array('post_type' => 'post', 'tax_query' => array('relation' => 'OR', array('taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => array($cooking_id)))));
			$this->buildPosts(5);
			$posts = $stream->get_posts();
			$this->assertEquals( 0, count($posts) );
		}

		function testSinglePostTaxonomyQueryHook() {
			$cooking_id = $this->factory->term->create(array('name' => 'Cooking'));
			$stream = $this->buildStream('Sample Stream', array('post_type' => 'post', 'tax_query' => array('relation' => 'OR', array('taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => array($cooking_id)))));
			$this->buildPosts(5);
			$cooking_post = $this->factory->post->create(array('tags_input' => 'cooking', 'post_title' => 'All About Cooking'));
			$posts = $stream->get_posts();
			$this->assertEquals( 1, count($posts) );
			$this->assertEquals( $cooking_post, $posts[0]->ID );
		}

		function testDoublePostTaxonomyQueryHook() {
			$jump_term_id = $this->factory->term->create(array('name' => 'Jumping'));
			$jive_term_id = $this->factory->term->create(array('name' => 'Jiveing'));
			$stream = $this->buildStream('Sample Stream', array('post_type' => 'post', 'tax_query' => array('relation' => 'OR', array('taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => array($jump_term_id, $jive_term_id)))));
			$this->buildPosts(5);
			$jumping_post = $this->factory->post->create(array('tags_input' => 'jumping', 'post_title' => 'Jumping & Jiving', 'post_date' => '2014-12-15 12:00:00'));
			$jiving_post = $this->factory->post->create(array('tags_input' => 'jumping', 'post_title' => 'Jiving n Jumping', 'post_date' => '2014-12-25 12:00:00'));
			$posts = $stream->get_posts();
			$this->assertEquals( 2, count($posts) );
			$this->assertEquals( $jiving_post, $posts[0]->ID );
		}

		function testSinglePostTaxonomyHook() {
			//build some stuff
			$baking_id = $this->factory->term->create(array('name' => 'Baking'));
			$sid = $this->factory->post->create(array('post_type' => 'sm_stream', 'post_content' => '', 'post_title' => 'Sample Stream'));
			$stream = new TimberPost($sid);

			//add a filter
			add_filter('stream-manager/taxonomy/'.$stream->slug, function($defaults) use ($baking_id) {
				$defaults['relation'] = "OR";
				$defaults['post_tag'] = array( $baking_id );
				return $defaults;
			});
			$stream = new TimberStream($sid);

			//now make some posts
			$baking_post = $this->factory->post->create(array('tags_input' => 'baking', 'post_title' => 'Cookies'));
			$this->buildPosts(5);

			//and get them
			$posts = $stream->get_posts();
			$this->assertEquals( 1, count($posts) );
		}

		function testSinglePostDoubleHooks() {
			$handstand_id = $this->factory->term->create( array('name' => 'Handstands') );
			add_filter('stream-manager/taxonomy/fitness-stream', function($defaults) use ($handstand_id) {
				$defaults['relation'] = "OR";
				$defaults['post_tag'] = array( $handstand_id );
				return $defaults;
			});

			$parkour_id = $this->factory->term->create( array('name' => 'Parkour') );
			
			$stream = $this->buildStream('Fitness Stream', array('post_type' => 'post', 'tax_query' => array('relation' => 'OR', array('taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => array($parkour_id)))));
			

			$parkour_post = $this->factory->post->create(array('tags_input' => 'parkour', 'post_title' => 'Parkour!'));
			$handstand_post = $this->factory->post->create(array('tags_input' => 'handstands', 'post_title' => 'Handstands'));
			$combo_post = $this->factory->post->create(array('tags_input' => 'parkour, handstands', 'post_title' => 'Parkour & Handstands'));
			$combo = new TimberPost($combo_post);
			$this->buildPosts(5);
			$posts = $stream->get_posts();
			$this->assertEquals( 3, count($posts) );
		}
		
		

	}
