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

	}
