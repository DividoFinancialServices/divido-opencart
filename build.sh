#!/usr/bin/env bash

if [ ! -d release ]; then
    mkdir release
fi

version=$(grep '<version>' install.xml | sed -e 's/<[^>]*>//g' | awk '{$1=$1};1');

perl -p -i -e 's/(<code>)(\d+)(<\/code>)/$1.($2+1).$3/e' install.xml 

filename=release/divido-v$version-opencart-2.x.ocmod.zip
rm $filename 
zip -r $filename install.* upload
