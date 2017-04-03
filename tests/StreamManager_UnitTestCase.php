<?php
	
	class StreamManager_UnitTestCase extends WP_UnitTestCase {

		public function buildPosts($count = 10) {
			$post_ids = array();
			for ($i = 0; $i<$count; $i++) {
				$date = date("Y-m-d H:i:s", strtotime('-'.$i.' days'));
				$post_ids[] = $this->factory->post->create(array('post_date' => $date));
			}
			return $post_ids;
		}

		function buildStream( $name = 'Sample Stream', $options = array() ) {
			$pid = $this->factory->post->create(array('post_type' => 'sm_stream', 'post_content' => '', 'post_title' => $name));
			add_filter('stream-manager/options/id='.$pid, function($defaults, $stream) use ($options) {
				$defaults['query'] = array_merge($defaults['query'], $options);
				return $defaults;
			}, 10, 2);
			$stream = new TimberStream($pid);
			return $stream;
		}

	}