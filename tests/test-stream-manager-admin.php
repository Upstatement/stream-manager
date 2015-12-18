<?php

	class TestStreamManagerAdmin extends StreamManager_UnitTestCase {
		
		function testAdminGetInstance() {
			$admin = StreamManagerAdmin::get_instance();
			$this->assertObjectHasAttribute('plugin', $admin);
			$this->assertObjectHasAttribute('default_query', $admin);
		}

		function testSaveStream() {
			$admin = StreamManagerAdmin::get_instance();
			$stream = $this->buildStream();
			$postids = $this->buildPosts(5);		
			foreach($postids as $id) {
				$_POST['sm_sort'][] = $id;
			}
			$admin->save_stream($stream->ID, false);
			$stream = new TimberStream($stream->ID);
			$this->assertEquals(5, count($stream->options['stream']));
		}

	}
