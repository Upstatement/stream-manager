<?php

	class TestStreamManagerIntegration extends WP_UnitTestCase {

		function testSample() {
			$this->assertTrue(true);
		}

		function buildPosts($count = 10) {
			$post_ids = array();
			for ($i = 0; $i<$count; $i++) {
				$date = date("Y-m-d H:i:s", strtotime('-'.$i.' days'));
				$post_ids[] = $this->factory->post->create(array('post_date' => $date));
			}
			return $post_ids;
		}

		function buildStream() {
			$pid = $this->factory->post->create(array('post_type' => 'sm_stream', 'post_content' => ''));
			$stream = new TimberStream($pid);
			return $stream;
		}

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

	}
