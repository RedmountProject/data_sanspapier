#!/bin/bash

if [ $# -lt 1 ]
then
	echo "User List File Missing"	
	exit 1
fi

echo " --- This script must be launch in data_sanspapier root directory --- \n"

PASSWORD=""

my_mkpasswd () {
	if [ $# -lt 1 ]
	then
		echo "No Input Seed"
		return 1
	fi	
	
	IFS='@' read -a username <<< "$1"	
	length=${#username}
	
	PASSWORD=`mkpasswd $username`
	return 0
}

CREATE_USER="php app/console fos:user:create"
ACTIVATE_USER="php app/console fos:user:activate"
GET_USER_ID="php app/console sanspapier:get:id"
ADD_PACKS="php app/console sanspapier:injectFreePack"
SEND_MAIL="php app/console sanspapier:SendMail_SDL2013"
PWD=`pwd`
FILE="${PWD}/$1"

while read line;
do
	IFS=' ' read -a array <<< "$line"
	
	username=$array
	mail=$array
	my_mkpasswd  $username
	echo "Password Used : $PASSWORD"	
	$CREATE_USER "$username" "$mail" "$PASSWORD"

	new_account="$?"
	echo $new_account
	
	$ACTIVATE_USER "$username"

	
	
	id=`$GET_USER_ID "$mail"`
	pack_ids=${array[@]:1}
	echo "Adding $pack_ids to $username identified by $id"
	$ADD_PACKS "$id" "$pack_ids"
	$SEND_MAIL "$mail" "$PASSWORD" "$pack_ids" "$new_account"
	
done < $FILE
