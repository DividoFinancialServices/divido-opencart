#!/usr/bin/env bash
fswatch -o upload/ | xargs -n1 -I{} cp -R upload/* ../opencart22/
fswatch -o upload/ | xargs -n1 -I{} cp -R upload/* ../opencart20/
fswatch -o upload/ | xargs -n1 -I{} cp -R upload/* ../opencart21/

function pack_module {
    date
    perl -p -i -e 's/(<code>)(\d+)(<\/code>)/$1.($2+1).$3/e' install.xml 
    zip -r ../opencart20-module.ocmod.zip *

}

export -f pack_module

#fswatch -o install* src/ | xargs -n1 -I {} bash -c 'pack_module'

