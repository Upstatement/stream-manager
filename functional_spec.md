# Feed Manager Functional Specification

- v0.1.0 - 2014-06-23 - initial outline and draft - cv
- v0.1.1 - 2014-06-25 - cleaning up specification, starting glossary and use case scenarios - cv

# Overview

This document outlines the front-facing functionality for the upcoming Feed Manager (working title; also tentatively referred to as Stream Manager) WordPress plugin rewrite.

## Methodology

This specification is intended to reflect the existing proprietary Feed Manager plugin built by Upstatement for use in client projects. One of the primary goals of this rewrite is to continue to target editorial end users, but remove complexity and reconsider the assumptions that caused the older plugin to miss its mark.

The scope of this document is focused on the end user and the actions that he or she will take while using this plugin. It does not contain any implementation or technical notes (e.g., interfacing with themes, etc.).

### Use Case Scenarios

These examples are intended to provide insight that will help direct the functionality of the plugin. While the Feed Manager cannot (and should not) be everything to everyone, it is important to consider how people will be using it in real-world scenarios.

#### The Daily Orange (Syracuse University's student-run newspaper)

On four nights a week throughout the school year, the Daily Orange publishes thirty to forty stories. At the end of each night, Lara, the editor in chief, organizes these stories into several buckets:

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

#### Upstatement



# Glossary

- A **functional specification** (this document) describes the features and functionality of a product.
- A **technical specification** details the technical implementation of the product, including data structures, programming languages, dependencies, APIs, and more.
- **Feeds** are collections of posts that are automatically populated using user-defined criteria, and are then displayed using a template. For example, a news site may have a feed containing the latest posts.
- A **stickied post** (working title) is a post that will permanently be displayed in a particular position in a feed.



# URL Structure

The plugin will use WordPress' existing posts functionality, so no new URLs will be required.


# Specification

## 1 Onboarding

Installation of the plugin, initial setup

### 1.1 Plugin Activation

A user installs and activates the plugin. The site responds by displaying a notification (only once) with a link to create a new feed.

#### 1.1.1


## 2 Feed Creation

### 2.1 Create New Feed



## 3 Browse Feeds

### 3.1 Feeds List

Provides a high-level overview of feeds.

#### 3.1.1 Feed Overview

Name, top item (maybe), others TBD

#### 3.1.2 Edit Feed

See #4.

#### 3.1.3 Delete Feed

Sends feed to trash, making it unavailable for use in the front end


## 4 Edit Feed

### 4.1 Posts Source

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

### 4.2 Add Stickied Post

#### 4.2.1 From search

A user types the name of an article into a search box, and the site responds with a dropdown of articles whose titles match that. Selecting a post from this list will prepend it to the top of the feed.

#### 4.2.2 From list of recent posts

From the list of articles already populating a feed, select one to sticky. This can be done in one of two ways:

1. The user drags the post to a new position
2. The user clicks a "Sticky" button next to the post, which will permanently locate it in that slot

### 4.3 Unsticky Post(s)

#### 4.3.1 Unsticky Single Post

A user clicks the "Unsticky" button on a stickied post, and it will move to its normal location in the feed.

#### 4.3.2 Unsticky All Posts

A user clicks an "Unsticky All" button, and the site responds by resetting the feed to its normal state without any stickied posts.

### 4.4 High-level Feed Actions

These will all use WordPress' existing post management actions.

#### 4.4.1 Publish Changes

#### 4.4.2 Discard Changes

#### 4.4.3 Revert to Older Configuration

#### 4.5 Theme Integration Helper

Provides code snippets for easily integrating the feed into a theme.



## 4 Offboarding

Upon deactivating or uninstalling the plugin, the posts should remain in the database?


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