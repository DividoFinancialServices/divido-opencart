#!/usr/bin/env bash
#fswatch -o upload/ | xargs -n1 -I{} cp -R upload/* ../opencart20/
fswatch -o . | xargs -n1 -I{} zip -r ../opencart20-module.ocmod.zip *

