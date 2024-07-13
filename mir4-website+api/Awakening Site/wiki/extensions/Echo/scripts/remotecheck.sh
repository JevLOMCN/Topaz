#!/usr/bin/env bash
if [ ! -e "scripts/remotes/gerrit.py" ]
then
	mkdir -p scripts/remotes
	echo 'Installing GerritCommandLine tool'
	curl -o scripts/remotes/gerrit.py https://raw.githubusercontent.com/jdlrobson/GerritCommandLine/master/gerrit.py
	chmod +x scripts/remotes/gerrit.py
fi
if [ ! -e "scripts/remotes/message.py" ]
then
	mkdir -p scripts/remotes
	echo 'Installing Message tool'
	curl -o scripts/remotes/message.py https://raw.githubusercontent.com/jdlrobson/WikimediaMessageDevScript/master/message.py
	chmod +x scripts/remotes/message.py
fi
