#!/bin/bash
screen -S panel -p 0 -X stuff "$1`echo -ne '\015'`"
