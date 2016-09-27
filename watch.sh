#!/usr/bin/env bash

function upd {
    date
    cp -R upload/* ../opencart20/
    cp -R upload/* ../opencart21/
    cp -R upload/* ../opencart22/
    echo "done"
}

export -f upd

fswatch -o upload/ | xargs -n1 -I{} bash -c "upd"
