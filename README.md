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

