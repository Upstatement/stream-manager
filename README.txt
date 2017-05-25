=== Stream Manager ===
Contributors: chrisvoll, lggorman, jarednova
Tags: posts
Requires at least: 3.8
Tested up to: 4.7.3
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily curate streams of recent posts.  Pin, remove, or add posts to a stream via a drag and drop interface.

== Description ==

We created Stream Manager with news editors in mind.  Admins wanted the latest headlines to show up on the front page automatically, but didn’t want to give up the flexibility of pinning a major story in a featured spot or pushing a smaller item down below the fold.  

Stream Manager provides a simple interface for curating feeds of new posts from the WordPress Admin.  New posts show up automatically at the top of a stream, but content can easily be added, removed, or repositioned on the page via the stream editor.  Admins also have the option of pinning a post, which will lock it in its current position regardless of new content.

Stream Manager is designed to work with Twig templating plugin [Timber](https://wordpress.org/plugins/timber-library/), as detailed in the installation instructions. Check out the [Timber project page](http://upstatement.com/timber/) for more info.

= Links =
* [Github repo](http://github.com/Upstatement/stream-manager) (includes user guide)
* [Walkthough Screencast](https://vimeo.com/160133857/025e8af0ae)
* [Developer docs](https://upstatement.github.io/stream-manager/)
* [Timber docs](http://jarednova.github.io/timber/) 

== Installation ==

1. Install and activate Timber, then install and activate this plugin.
2. Create a new stream from the WordPress admin.
3. Add the following to your template file, replacing 'new-stream' with the slug of your stream.
`
$context['stream'] = new TimberStream('new-stream');
`
4. Finally, add this to your twig file.
`
{% for post in stream.get_posts %}

    {{ post.title }}

{% endfor %}
`

== Frequently Asked Questions ==

= Can streams be filtered by post type or category? =

Yes! Streams can be filtered by post type, taxonomy, or just about anything else that can be passed into a wp_query array.  Check out the [github readme](http://github.com/Upstatement/stream-manager) for details on filter hooks.


== Screenshots ==

1. Adding a new stream.
2. Pinning a stream to the top of 

