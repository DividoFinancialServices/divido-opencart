#!/usr/bin/env bash
fswatch -o upload/ | xargs -n1 -I{} cp -R upload/* ../opencart15/
