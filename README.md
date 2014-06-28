phpass by elcodedocle
=====================
#####*A phpass portable PHP password hashing framework upgrade for these modern times we're living in*

 Copyright (C) 2013 Gael Abadin<br/>
 License: [MIT Expat][1]<br />
 [![Code Climate](https://codeclimate.com/github/elcodedocle/phpass.png)](https://codeclimate.com/github/elcodedocle/phpass) 

### How to use

The same way as regular phpass; check out the phpdoc included on docs folder for the extras.


### Motivation

I wanted to implement proper hashing on my web app. password\_hash seemed the way to go, but it's only supported by the most recent versions of PHP (at the time of writing this, PHP 5.5.0 was realeased just a few months ago...).

So I did some research and the most reasonably fast and tested library was phpass, but it lacked support for, guess what, password\_hash. Since password\_hash should be the default option (at last, a sane way to use crypt!) and none of the other phpass hacks I checked (Drupal, WordPress) had it I decided to add it myself.

In the process, adding also support for sha512 and sha256 on portable methods seemed easy enough, so I beefed that up too.

### Acks

Solar Designer, author of the original library at http://www.openwall.com/phpass/


Enjoy!

(

And, if you're happy with this by-product, donate! 

bitcoin: 1P4AFJMt5VbWq6o49BEwxk4kAkkHFm9Jv7 

dogecoin: DEZrHzf6ajynL3XfLHDzjT9WpoR4eszxNA 

paypal: http://goo.gl/5xzM7D

)

[1]: https://raw.githubusercontent.com/elcodedocle/phpass/master/LICENSE
