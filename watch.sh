#!/usr/bin/env bash

function upd {
    date
    cp -R upload/* htdocs/opencart/opencart20/
    cp -R upload/* htdocs/opencart/opencart21/
    cp -R upload/* htdocs/opencart/opencart22/
    echo "done"
}

export -f upd

fswatch -o upload/ | xargs -n1 -I{} bash -c "upd"
