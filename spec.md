# Feed Manager Functional & Technical Specification

- v0.1.0 - 2014-06-23 - initial outline and draft - cv
- v0.1.1 - 2014-06-25 - cleaning up specification, starting glossary and use case scenarios - cv
- v0.2 - 2014-07-01 - more mature technical spec, also committing updates to the glossary and functional spec - cv

## 1. Overview

#### 1.1 About this Document

This document outlines the front-facing functionality for the upcoming Feed Manager (working title; also tentatively referred to as Stream Manager) WordPress plugin rewrite, in addition to a broad overview of the technical implementation.

#### 1.2 Methodology

This specification is intended to reflect the existing proprietary Feed Manager plugin built by Upstatement for use in client projects. One of the primary goals of this rewrite is to continue to target editorial end users, but remove complexity and reconsider the assumptions that caused the older plugin to miss its mark.

#### 1.3 Example Use Cases

These examples are intended to provide insight that will help direct the functionality of the plugin. While the Feed Manager cannot (and should not) be everything to everyone, it is important to consider how people will be using it in real-world scenarios.

**The Daily Orange (Syracuse University's student-run newspaper)**

For four nights a week throughout the school year, the Daily Orange publishes thirty to forty stories. At the end of each night, Lara, the editor in chief, organizes these stories into several buckets:

- 1 Top Story
- 2 Secondary Top Stories
- 6 Featured Stories
- Stories for the daily email newsletter

Due to the limitations of the existing system, the curation options available to Lara do not meet the needs of the newspaper for several reasons:

1. The Zoninator curation plugin is too slow to handle a large number of articles. It also does not handle many feeds well, leaving many sections of the site without any curation whatsoever.
2. Feeds need to be updated manually whenever the site is updated
3. Curated posts cannot be mixed with latest posts
4. Custom layouts and other additional layers of functionality must be handled separately
5. Any other feeds must be hardcoded into the theme, like sorting recent articles by number of comments.

A plugin that overcomes these obstacles would need to automate the process of generating feeds, while also making it incredibly simple for editors to make adjustments.

**Upstatement**



## 2. Glossary

#### 2.1 Broad Overview

- A **functional specification** (this document) describes the features and functionality of a product.

- A **technical specification** details the technical implementation of the product, including data structures, programming languages, dependencies, APIs, and more.

#### 2.2 WordPress

- **Advanced Custom Fields** (ACF) is a WordPress plugin for adding custom metadata to a post. 

- **Feed Manager** is the name of an older plugin developed internally by Upstatement, which this new plugin is replacing.

- **Post type** refers to the way in which WordPress differentiates between different kinds of content. Posts, pages, revisions, attachments, and (with this plugin) feeds are all different types of "posts" in the WordPress database.

- **Timber** is a plugin for WordPress that allows us to use the Twig Template Language to assemble HTML.

- **Twig** is a template language that allows us to write HTML and integrate variables from WordPress and PHP.

- **WordPress** is the content management system for which this plugin is being developed.

- **Zoninator** is a now-unsupported WordPress plugin used for curating content, which Upstatement has used in the past before it developed the original Feed Manager plugin. Some sites, like the Daily Orange, still use this.

#### 2.3 Feed Manager

- **Feeds** are collections of posts that are automatically populated using user-defined criteria, and are then displayed using a template. For example, a news site may have a feed containing the latest posts.

- A **stickied post** (working title) is a post that will permanently be displayed in a particular position in a feed.


## 3. URL Structure

The plugin will use WordPress' existing post functionality, so the new URLs will follow that logic:

- Feeds list: `/wp-admin/edit.php?post_type=POST_TYPE`
- Edit feed: `/wp-admin/post.php?post=POST_ID&action=edit`
- Delete feed: `/wp-admin/post.php?post=POST_ID&action=delete&_wpnonce=xxx`


## 4. Functional Specification

### 4.1 Onboarding

Installation of the plugin, initial setup

#### 4.1.1 Plugin Activation

A user installs and activates the plugin. The site responds by displaying a notification (only once) with a link to create a new feed.


### 4.2 Feed Creation

#### 4.2.1 Create New Feed

User clicks the New Feed button on the feeds listing page, and the site responds with a fresh new feed edit page.


### 4.3 Browse Feeds

#### 4.3.1 Feeds List

Provides a high-level overview of feeds.

##### 4.3.1.1 Feed Overview

Name, top item (maybe), others TBD

##### 4.3.1.2 Edit Feed

See #4.

##### 4.3.1.3 Delete Feed

Sends feed to trash, making it unavailable for use in the front end


### 4.4 Edit Feed

#### 4.4.1 Posts Source

Determine where posts come from (e.g., all latest posts, posts from one category, based on a custom field). Stickied posts can come from anywhere regardless of this setting.

Base this on Advanced Custom Fields' Location rules. The following filters will be required:

- post_type
- post_author
- post_date
- taxonomies
    - category
    - tag
    - custom taxonomies
- post_format
- post_status (?)
- custom fields (implementation TBD)

When a user changes these settings, the site will respond by repopulating the list of articles.

#### 4.4.2 Add Stickied Post

##### 4.4.2.1 From search

A user types the name of an article into a search box, and the site responds with a dropdown of articles whose titles match that. Selecting a post from this list will prepend it to the top of the feed.

##### 4.4.2.2 From list of recent posts

From the list of articles already populating a feed, select one to sticky. This can be done in one of two ways:

1. The user drags the post to a new position
2. The user clicks a "Sticky" button next to the post, which will permanently locate it in that slot

#### 4.4.3 Unsticky Post(s)

##### 4.4.3.1 Unsticky Single Post

A user clicks the "Unsticky" button on a stickied post, and it will move to its normal location in the feed.

##### 4.4.3.2 Unsticky All Posts

A user clicks an "Unsticky All" button, and the site responds by resetting the feed to its normal state without any stickied posts.

#### 4.4.4 High-level Feed Actions

These will all use WordPress' existing post management actions.

##### 4.4.4.1 Publish Changes

##### 4.4.4.2 Discard Changes

##### 4.4.4.3 Revert to Older Configuration

##### 4.4.5 Theme Integration Helper

Provides code snippets for easily integrating the feed into a theme.

### 4.5 Offboarding

Upon deactivating or uninstalling the plugin, the posts should remain in the database to avoid any data loss. Manually deleting all of the feeds would essentially erase all of the data that the feed manager is storing in the database.




## 5. Technical Specification

### 5.1 Content Types

**Feed** - WordPress post type. See fields in 5.2

### 5.2 Data Structure

Feeds will be implemented as a custom post type, and will require the following metadata:

> feed_posts_list

* Description: Map of stickied post IDs and their position in the feed
* Notes: Position starts at 0. This will be a serialized array, not json.
* Example:

```json
{
    sticky: [
        {
            id: 10001,
            pos: 0,
            // either put this here, or try to use the post's metadata
            custom: {
                key: value
            }
        },
        {
            id: 10002,
            pos: 2
        },
        [...]
    ],
    hide: [
        11004,
        [...]
    ],
    // The feed includes all of the queried posts, regardless
    // of their sticky or hidden status. Basically it's a cache
    // that will be refreshed as needed to avoiding querying the
    // database too often.
    feed: [
        10004,
        10003,
        10002,
        10001,
        10000,
        [...]
    ]
}
```

This will output a feed with in the following order:

- 0: 10001 - stickied
- 1: 10003 - recent (10004 is hidden)
- 2: 10002 - stickied
- 3: 10000 - recent

**QUESTIONS:**
- How will the feed refresh be triggered? Every time a post is published/updated? Every time someone posts a comment? Will it depend on the filters?
- How to best handle custom fields defined by themes?


> feed_options

* Description: Map of feed configuration, including a [WP_Query-able array](http://codex.wordpress.org/Class_Reference/WP_Query#Parameters).
* Notes: Having all of these options in the UI may be out of scope for the initial launch. As a MVP, it must support recent stories that can be filtered by a category or a tag, and the data will still be stored in a way that can be queried by `WP_Query`.
* Example:

```json
{
    query: {
        'post_type': 'post',
        'tax_query': {
            'taxonomy': 'tag',
            'field': 'slug',
            'terms': 'featured'
        },
        'posts_per_page': 5, // -1 for unlimited
        'cat': 3,
        'orderby': 'comment_count date',
        'order': 'DESC',
        'date_query': {
            'column': 'post_date_gmt',
            'after': '7 days ago'
        },
        // Advanced Custom Fields
        'meta_query': {
            'relation': 'AND',
            {
                'key': 'field_name',
                'value': 'field_value',
                'compare': '='
            },
            [...]
        },
        [...]
    }
}
```

### 5.3 Browser Support




---

# Implementation Notes

These are NOT final, just based on early discussions, and will eventually be moved to the technical specification.

- Use existing WP post functionality for building out admin interface (similar to ACF). No Feed Manager interface; just a heavily customized wordpress post type.
- Build on top of Timber. Assume that this will be primarily used internally at Upstatement.


## Other things to add here and to the technical spec

On the roadmap for v1:

- Add stickied post from post edit page
- **Hide a post** from a feed altogether
- **Zones.** Apply labels to specific slots.
- For the technical specification:
    - Advanced Custom Fields support. This _should_ be available right out of the box without any kind of coding on our end.
    - Theme hooks. Allow themes to add custom functionality to feeds, without being dependent on ACF.

Under consideration:

- **Feed preview.** From a UX perspective, it makes sense to have this, but technically is difficult to implement since the feed manager doesn't know where the feeds are implemented on the site.
- **Export** feed configurations
- **RSS** feeds?




So broad feature outline:

- Feeds
    - Posts come from customized source, which can be anything that can be plugged into a WP_Query arg.
    - Feeds will be updated automatically when anything happens that would impact the posts list. This includes posts being updated, comments being posted on relevant articles, etc. This is to improve performance so they aren't being queried on every page load.
    - Feeds can be manually curated. Posts can be stickied to any position, or can be hidden from the feed altogether.
    - Themes can hook into the feed UI to add...
        - Labels.
        - Custom fields. Maybe just allow themes to surface the posts' custom fields?

- Theme integration
    - Feeds can be implemented anywhere, but do not replace the normal wordpress posts loop. They have to be manually inserted through the view controller (e.g., get_stream(1234) would return a collection of TimberPost objects)