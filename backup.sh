#!/bin/bash

NOW=$(date +"%m-%d-%Y")
FILENAME="anypanel_$NOW.tar.bz2"

cd /home/nulled
tar cvjf $FILENAME anypanel
mv $FILENAME /home/nulled/Dropbox/.
