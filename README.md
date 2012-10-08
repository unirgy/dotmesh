DotMesh
=======
Open source macroblogging application ready to be deployed on any PHP/MySQL server.

Features
========

* Use as local Twitter clone
* Subscribe to local and remote Users and Tags
* Star (Favorite) posts
* Flag (Report as offensive) posts
* Vote posts up and down
* Echo (Retweet) posts
* Send Private posts (visible only to mentioned users)
* Sort timelines by: Recent, Hot, Best, Worst, Controversial
* Search for posts, users and tags
* RSS Feeds for all timelines
* Inline YouTube videos and images
* Fully extendable via plugins
* Twitter integration (sign up, login, cross-post)
* Markdown post formatting
* Custom theming (plugin skeleton included)
* Servers communicate using SSL if supported by either

Immediate Roadmap
=================

* Admin interface (manage nodes and users)
* Block nodes and users on node level (admin)
* Block nodes and users on a user level

Wishlist
========

* Per user page design
* Edit posts
* Inline reply
* Post from header popup
* Implement other OAuth/OpenID adapters (Facebook, Google+, LinkedIn, HotMail, Yahoo)
* Implement User page as OpenID identity
* Guest user API
* Client side AES256 encryption
* User/tag Karma

Potential Uses
==============

* Personal use (only 1 user, private notes, communicate with other nodes)
* Share with friends
* Setup a node for company, organization or school
* Use for crowd voting (e.g. features wishlist)

Benefits
========

* If using as purely local node, all your data is on your server only.
* If communicating with other servers, the data is on yours and the other servers only, no middle man.
* Full control over your own data
* No inline advertisements (unless the node admin decides to inject)
* Multiple nodes increase difficulty for blocking on infrastructure level


Trade offs vs Cloud solutions (e.g. Twitter)
============================================

* Maintain your own real/virtual server, backups, host security, upgrades, etc.
