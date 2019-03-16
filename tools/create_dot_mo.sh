#!/bin/bash

CUR_PATH="`dirname \"$0\"`"

cd "$CUR_PATH/../locales"

for a in $(ls *.po); do
	msgfmt $a -o "${a%.*}.mo"
done
