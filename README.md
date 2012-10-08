DotMesh
=======
Open source macroblogging application ready to be deployed on any PHP/MySQL server.

See it in action
================
Go to http://dotmesh.org and sign up!

Features
========

* Use as local Twitter clone
* Subscribe to local and remote Users and Tags
* Pin new posts as admin, and unpin later
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
* Run multiple nodes from the same code/db instance
* Supports full i18n
* Canonical URLs allow SEO for posts (add your own text in URL)

Security measures
=================

* User passwords are hashed using bcrypt with difficulty 10
* Remote users validated using SHA512 double hash:
    SHA512( [agent ip] * SHA512( [local node secret key] * [remote node secret key] * [user secret key] ))
* Remote user signatures are re-validated against claiming node when needed
* Server to server communication is DNS validated
* Use SSL when available
* If a node database is compromised, change local secret key and send request to remote nodes to invalidate all user signatures (roadmap)

Immediate Roadmap
=================

* Admin interface (manage nodes and users)
* Block nodes and users on node level (admin)
* Block nodes and users on a user level

Wishlist
========

* Post attachments
* Per user page design
* Edit posts
* Inline reply
* Post from header popup
* Implement other OAuth/OpenID adapters (Facebook, Google+, LinkedIn, HotMail, Yahoo)
* Implement User page as OpenID identity
* Guest user API
* Client side AES256 encryption
* User/tag Karma
* The sky is no limit

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

Installation Instructions
=========================

* Clone git repository (https://github.com/unirgy/dotmesh)
* OR Download as zip (https://github.com/unirgy/dotmesh/zipball/master)
* Copy dotmesh/config.php.dist to dotmesh/config.php
* Edit dotmesh/config.php and follow instructions to configure the instance
* Navigate to your node web address, setup page should be shown
* Fill out setup page (node and admin user info)
* Enjoy your own DotMesh node!

How you can help?
=================

If you like what you see, do what you can:

* Help to test the software, by using it and reporting issues or writing PHPUnit tests
* Help to secure the software, by examining the code and do blackbox audits
* Help to develop the software, by forking it and submitting pull requests
* Help to increase awareness, by talking with your friends and co-workers
* Help to make it pretty, by creating custom themes
* Help to translate it to other languages
