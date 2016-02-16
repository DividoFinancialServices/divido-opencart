#!/usr/bin/env bash

if [ ! -d release ]; then
    mkdir release
fi

perl -p -i -e 's/(<code>)(\d+)(<\/code>)/$1.($2+1).$3/e' install.xml 
rm release/divido-opencart-2.1-integration.ocmod.zip
zip -r release/divido-opencart-2.1-integration.ocmod.zip install.* upload -x "upload/system/*"
