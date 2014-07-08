Feed Manager
============

## Setup

Install and activate Timber, then install and activate this plugin. Create your first feed in the admin, and then use this in your template file, replacing the ID with the feed ID:

```php
<?php
    # for index.php:

    $context = Timber::get_context();
    $context['feed'] = new TimberFeed(5);

    $templates = array('index.twig');
    if (is_home()){
        array_unshift($templates, 'home.twig');
    }
    Timber::render($templates, $context);
?>
```

And add this to your twig file:

```twig

{% for post in feed.get_posts %}

    <h2>{{ post.title }}</h2>

{% endfor %}

```






A better name, TBD

- [ ] Define project requirements _Jared_
- [ ] Write a technical spec _Chris_
- [ ] Create a WordPress plugin _Chris_
- [ ] Release WordPress plugin
- [ ] Integrate on a site (dailyorange.com)
- [ ] Repeat.

### Goals
- [ ] Gives editors an easy and intutive way to manage feeds of content on their site
- [ ] Has ways Upstatement or other developers can extend/customize for particular implemenetation or site
- [ ] Doesn't require an editor to set after every update (ie. auto-insertion of published posts)

### Requirements
- [ ] Modeled after existing [feed manager](https://github.com/Upstatement/chainsaw-feed) for general UI and UX
- [ ] Matches general WordPress UI
- [ ] Contains Upstatement mention/branding
- [ ] Can handle multipile "streams"
- [ ] Built upon normal WordPress post_type architecture
- [ ] JavaScript / Ajax provides _enhancements_ as opposed to core UX
_Jared and Chris to add other things here. Will meet to plan tech spec on Tuesday 6/24_
