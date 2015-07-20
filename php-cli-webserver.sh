#!/bin/bash

PID=$(pidof php5)
PORT=$2
ISROOT=$(whoami);
WEBROOT=/home/nulled/anypanel


if ! [ $ISROOT = 'root' ]; then
  echo "error: must be root";
  exit 1;
fi

case "$1" in
  start)
    re='^[0-9]+$'
    if ! [[ $PORT =~ $re ]]; then
      echo "error: Not a number defaulting to port 8000" >&2;
      PORT=8000;
    fi
    
    if [ -z $PID ]; then
      echo "php-cli-webserver Starting [OK]"
      /usr/bin/nohup /usr/bin/php5 -S localhost:$PORT -t $WEBROOT & > /dev/null
    else
      echo "php-cli-webserver $PID Started [OK]"
    fi
    ;;
  stop)
    kill -9 $PID &> /dev/null
    [ -z "$?" ] && echo "php-cli-webserver $PID Stopped [OK]"
    ;;
  status)
    if [ -z $PID ]; then
      echo "php-cli-webserver not Running"
    else
      echo "php-cli-webserver $PID Running [OK]"
    fi
    ;;
  *)
    echo "Usage: php-cli-webserver.sh [start|stop|restart|status]"
    exit 1
esac
