#!/bin/sh
	php app/console cache:clear
        chmod -R 777 ./app/cache
        chmod -R 777 ./app/logs

