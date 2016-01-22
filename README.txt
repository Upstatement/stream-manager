=== Stream Manager ===
Contributors: chrisvoll, lggorman, jarednova
Tags: posts
Requires at least: 3.8
Tested up to: 3.9.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily curate streams of recent posts.  Pin, remove, or add posts to a stream via a drag and drop interface.

== Description ==

Traditionally, feeds of featured posts on the homepage or elsewhere are populated automatically with new content in reverse chronological order, or else selected manually.  Stream Manager combines the best of both worlds by creating streams that automatically pull in new content, but can be easily modified from the WordPres admin.  Stream Manager is designed to work with Twig Templating plugin Timber.

= Links =
* [Github repo](http://github.com/Upstatement/stream-manager) (includes user guide)
* [Developer docs](https://upstatement.github.io/stream-manager/)
* [Timber](https://wordpress.org/plugins/timber-library/) 

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

