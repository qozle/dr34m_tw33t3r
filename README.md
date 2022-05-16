# dr34m_tw33t3r

This is a rather sloppy and (most likely) overly-complex project that does the following:

-  builds a random "sentence" from a parts-of-speech template and parts-of-speech organized word library (see /random-words)
-  does a google search for the generated "sentence"
-  picks a random result from the top 10 results
-  crawls the site selected and picks a random page
-  scrapes the page for all its text
-  breaks the text up into sentences, and then picks two random ones
-  feeds the random sentences into OpenAI's text generator
-  takes the result, removes the initial sentences, and takes as many sentences as will fit into a tweet
-  sends a copy of the tweet to my email for review
-  if approved, tweets the generated text through the twitter api


This script will eventually be refactored.  It started off as a fun experiment, quickly snowballed into a monster, so once I got it working I pretty much moved on to something else.  