Stream Manager
==============

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



### Roadmap
- [x] Define project requirements _Jared_
- [x] Write a technical spec _Chris_
- [ ] Create a WordPress plugin _Chris_
- [ ] Release WordPress plugin
- [ ] Integrate on a site

### Goals
- [ ] Gives editors an easy and intutive way to manage streams of content on their site
- [ ] Has ways Upstatement or other developers can extend/customize for particular implemenetation or site
- [ ] Doesn't require an editor to set after every update (ie. auto-insertion of published posts)

### Requirements
- [ ] Modeled after existing [feed manager](https://github.com/Upstatement/chainsaw-feed) for general UI and UX
- [ ] Matches general WordPress UI
- [ ] Contains Upstatement mention/branding
- [ ] Can handle multiple streams
- [ ] Built upon normal WordPress post_type architecture
- [ ] JavaScript / AJAX provides _enhancements_ as opposed to core UX

