![](https://github.com/Upstatement/stream-manager/blob/master/assets/stream_manager-readme_banner.png)

[![Build Status](https://magnum.travis-ci.com/Upstatement/stream-manager.svg?token=d8Cx5Kv4z1vKq3YdKbM2)](https://magnum.travis-ci.com/Upstatement/stream-manager)
[![Coverage Status](https://coveralls.io/repos/Upstatement/stream-manager/badge.svg?branch=master&service=github&t=0LpO9W)](https://coveralls.io/github/Upstatement/stream-manager?branch=master)

Curate streams of WordPress posts.

## Setup

Install and activate [Timber](https://github.com/jarednova/timber), then install and activate this plugin. Create your first stream in the WordPress admin, and then use this in your template file, replacing the ID with the stream ID:

```php
$context['stream'] = new TimberStream(5);
```

And add this to your twig file:

```twig
{% for post in stream.get_posts %}

    {{ post.title }}

{% endfor %}
```

## User Guide

[Walkthrough Screencast](https://vimeo.com/160133857/025e8af0ae)

### Adding posts to a stream

To add a post to the stream, start typing the title of the post in the 'Add Post' box.  When the post you want to add appears, click it.  The post should automatically be added to the top of the stream.

![](https://github.com/Upstatement/stream-manager/blob/master/assets/screenshot-add.png)

### Removing posts from a stream

To remove a post from the stream, hover over the post and click the x in the upper right.  Note that the post won't be deleted entirely -- instead, it will be removed from its current position and appended to the bottom of the stream.

![](https://github.com/Upstatement/stream-manager/blob/master/assets/screenshot-remove.png)

### Pinning posts

Pinning a post will fix in in its current spot in the stream, even if new posts are added.  For example, if you were to pin a post in the top slot, the next new post to be published will go to the second slot and the original post will remain at the top.  Pin a post to its current spot by clicking on the thumbtack icon to the left of the post title.  If the thumbtack is red, the post is pinned.  Unpin a post by clicking the thumbtack a second time.

![](https://github.com/Upstatement/stream-manager/blob/master/assets/screenshot-pin.png)

### Reordering a stream

Posts in the stream can be reordered via drag and drop.  Make sure to click 'Update Post' after making changes to the stream.

### Using zones

Zones are a useful tool for visualizing where posts are going to display on the page.  For example, if the first post in the stream appears in a special featured slot, you might demarcate that using a zone title 'Featured Post.'  To add a zone, type the name of the zone in the 'Zones' box on the right and click 'Add Zone.'  The zone will be added to the the top of the stream, after which it can be dragged and dropped to the desired location.

![](https://github.com/Upstatement/stream-manager/blob/master/assets/screenshot-zones.png)

## Filter Hooks

Stream Manager includes several filter hooks that can be used to modify the options array attached to a stream. A common use case is to modify the query that populates the stream.

### Default Options

```php
$default = array(
    'query' => array(
      'post_type'           => 'post',
      'post_status'         => 'publish',
      'has_password'        => false,
      'ignore_sticky_posts' => true,
      'posts_per_page'      => 100,
      'orderby'             => 'post__in'
    ),

    'stream'  => array(),
    'layouts' => array(
      'active' => 'default',
      'layouts' => array(
        'default' => array(
          'name' => 'Default',
          'zones' => array()
        )
      )
    )
  );
``` 
* * *

### stream-manager/options/id={stream-id}

Restrict stream #3 to posts of the 'event' post type.

```php
add_filter('stream-manager/options/id=3', function($defaults) {
  $defaults['query'] = array_merge($defaults['query'], array('post_type' => array('event')));
  return $defaults;
});
```

* * *

### stream-manager/options/{stream-slug}

Restrict the 'homepage' stream to posts in the 'local-news' category.

```php
add_filter('stream-manager/options/homepage', function($defaults) {
  $defaults['query'] = array_merge($defaults['query'], array('category_name' => 'local-news'));
  return $defaults;
});
```

* * *

### stream-manager/taxonomy/{stream-slug}

Restrict the 'classifieds' stream to the posts with the tags with term ids of 12 and 13

```php
add_filter('stream-manager/taxonomy/classifieds', function($defaults) {
	$defaults['relation'] = "OR";
	$defaults['post_tag'] = array( 12,13 );
	return $defaults;
});
```

