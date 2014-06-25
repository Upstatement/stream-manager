# Feed Manager Functional Specification

- v0.1.0 - 2014-06-23 - initial outline and draft - cv
- v0.1.1 - 2014-06-25 - cleaning up specification, starting glossary - cv

# Overview


## Methodology

### Use Case Examples

#### Daily Orange



#### Upstatement



# Glossary

- **Functional Specification**
- **Technical Specification**
- **Feed**
- **Stickied Post** - a post that will permanently be displayed in a particular position in a feed until a user unstickies it. This is a working title that will likely be changed to avoid confusion with WordPress' own built-in stickied posts.
- **Timber**
- **WP**


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

Base this on Advanced Custom Fields' Location rules, with additional filters for post metadata like date ranges.

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

#### 4.4.1 Publish Changes

#### 4.4.2 Discard Changes

When the user leaves the page without publishing changes, confirm and then discard the changes.

#### 4.4.3 Revert to Older Configuration

Uses WordPress' existing post revision system.

#### 4.4.4 Theme Integration Helper

Provides code snippets for easily integrating the feed into a theme.

### Todo:

- Add stickied post from post edit page
- Hide a post from a feed altogether
- Save draft of feed?
- Preview feed?



## 4 Offboarding

Upon deactivating or uninstalling the plugin, the posts should remain in the database?


# Very early implementation notes

These are NOT final, just based on early discussions.

- Use existing WP post functionality for building out admin interface (similar to ACF). No Feed Manager interface; just a heavily customized wordpress post type.
- Build on top of Timber. Assume that this will be primarily used internally at Upstatement.
