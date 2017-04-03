<?php

	class TestStreamManagerAjaxHelper extends StreamManager_UnitTestCase { 

		function testRetrievePosts() {
			$postid = $this->factory->post->create();
			$queue = array(array('id' => $postid, 'position' => 0));
			$output = StreamManagerAjaxHelper::retrieve_posts($queue);
			$this->assertEquals($output[$postid]['position'], 0);
			$html = $output[$postid]['object'];
			if (strpos($html, 'id="post-'.$postid) !== false) {
				$contains_id = true;
			} else {
				$contains_id = false;
			}
			$this->assertTrue($contains_id);
		}

		function testRetrievePostsCheckTitle() {
			$postid = $this->factory->post->create(array('post_title' => 'The Wrong Trousers'));
			$queue = array(array('id' => $postid, 'position' => 0));
			$output = StreamManagerAjaxHelper::retrieve_posts($queue);
			$html = $output[$postid]['object'];
			if (strpos($html, 'The Wrong Trousers') !== false) {
				$contains_title = true;
			} else {
				$contains_title = false;
			}
			$this->assertTrue($contains_title);
		}

		function testSearchPosts() {
			$pid = $this->buildStream();
			$bagel_post = $this->factory->post->create(array('post_title' => 'Bagels'));
			$croissant_post = $this->factory->post->create(array('post_title' => 'Croissants'));
			$pastries_post = $this->factory->post->create(array('post_title' => 'Croissants and Bagels'));
			$output = StreamManagerAjaxHelper::search_posts('bagel', $pid);
			$this->assertEquals(2, count($output));
			$this->assertEquals('1 min', $output[0]['human_date']);
		}

		function testSearchPostsAppliesFilter() {
			$pid = $this->buildStream('Test Stream', array('post_type' => 'pastry'));
			$this->factory->post->create(array('post_title' => 'bagel1'));
			$this->factory->post->create(array('post_title' => 'bagel2'));
			$this->factory->post->create(array('post_title' => 'bagel3', 'post_type' => 'pastry'));
			$output = StreamManagerAjaxHelper::search_posts('bagel', $pid);
			$this->assertEquals(1, count($output));
		}

		function testSearchPostsNoMatches() {
			$pid = $this->buildStream();
			$bagel_post = $this->factory->post->create(array('post_title' => 'Bagels'));
			$output = StreamManagerAjaxHelper::search_posts('muffin', $pid);
			$this->assertEquals(0, count($output));
		}

	}